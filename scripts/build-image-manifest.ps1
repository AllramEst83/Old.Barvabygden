<#
.SYNOPSIS
    Builds scripts/image-manifest.csv: the plan for every live image (rename, merge, convert, resize,
    quarantine, keep) per _bmad-output/specs/spec-image-cleanup/image-rules.md.

.DESCRIPTION
    Live scope: tracked images under assets/images/ and barva-bladet/ (jpg, jpeg, png, gif, webp, ico).
    Nothing is moved, edited, converted or deleted; only scripts/image-manifest.csv is written.
    Output is sorted and deterministic; re-running changes nothing.

    Columns: old_path,new_path,action,old_bytes,new_bytes
    Action precedence: quarantine > merge > convert > resize > rename > keep.
      quarantine  no live source names the image (strong scan); new_path is attic/unreferenced-images/<old_path>
      merge       byte-identical (SHA-256) to another file inside assets/images/; new_path is the kept file's new_path
      convert     jpg/jpeg/png: new_path is the fallback (opaque PNG -> .jpg, transparent PNG -> .png);
                  the .webp sibling is implied. Also covers any rename or resize of that file.
      resize      webp over 1920 px or 500 KB (GIF and .ico are never resized)
      rename      name normalized, nothing else
      keep        already conforming
    new_bytes is the old size for rename/keep/quarantine, 0 for merge, empty for convert/resize
    (known only after scripts/optimize-images.ps1 runs).

    Unreferenced scan: the lowercased file name must be absent, in plain, %-encoded, NFC/NFD and
    mojibake forms, from index.html, pages/, fragments/, barva-bladet/ pages, assets/css and assets/js
    (raw and %-decoded text). An image is live when a path-resolved reference hits it, when
    "parent-folder/name" appears in any source, or when its bare name appears in script text
    (js files, inline <script>), where paths may be built at runtime, or when a reference whose path
    resolves to nothing (an already-broken link) names it.

    Exit code 1 when two non-merge rows share a new_path. Unreadable images are kept with a warning.

.EXAMPLE
    ./scripts/build-image-manifest.ps1
#>
$ErrorActionPreference = 'Stop'
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8
Add-Type -AssemblyName System.Drawing
$root = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
Set-Location $root
$outFile = Join-Path $PSScriptRoot 'image-manifest.csv'
$strict = New-Object System.Text.UTF8Encoding($false, $true)
$utf8 = New-Object System.Text.UTF8Encoding($false)
$cp1252 = [System.Text.Encoding]::GetEncoding(1252)
$maxEdge = 1920
$maxBytes = 500KB
$imgRe = '\.(jpe?g|png|gif|webp|ico)$'

$tracked = @(git -c core.quotepath=off ls-files | Where-Object { $_ })
$lookup = @{}
foreach ($t in $tracked) { $lookup[$t.ToLowerInvariant()] = $t }
$images = @($tracked | Where-Object { $_ -match '^(assets/images|barva-bladet)/' -and $_ -match "(?i)$imgRe" } | Sort-Object -CaseSensitive)

function Read-Text([string]$path) {
    $bytes = [System.IO.File]::ReadAllBytes((Join-Path $root $path))
    try { return $strict.GetString($bytes) } catch { return $cp1252.GetString($bytes) }
}

# ---- Text sources ----
$sources = @($tracked | Where-Object {
        $_ -eq 'index.html' -or
        $_ -match '^(pages|fragments)/.*\.(html?|js|css)$' -or
        $_ -match '^barva-bladet/.*\.(html?|js|css)$' -or
        $_ -match '^assets/(css|js)/.*\.(css|js)$' })
$sb = New-Object System.Text.StringBuilder
$decodedRe = [regex]'%[0-9A-Fa-f]{2}'
foreach ($s in $sources) {
    $text = Read-Text $s
    [void]$sb.Append($text.ToLowerInvariant()).Append("`n")
    if ($decodedRe.IsMatch($text)) {
        try { $dec = [System.Uri]::UnescapeDataString($text) } catch { $dec = $text }
        [void]$sb.Append($dec.ToLowerInvariant()).Append("`n")
    }
}
$hay = $sb.ToString()
# Script text (js files and inline <script> blocks): there a bare name counts, paths may be built at runtime.
$sbs = New-Object System.Text.StringBuilder
foreach ($s in $sources) {
    if ($s -match '\.js$') { [void]$sbs.Append((Read-Text $s).ToLowerInvariant()).Append("`n") }
    elseif ($s -match '\.html?$') { foreach ($m in [regex]::Matches((Read-Text $s), '(?is)<script[^>]*>(.*?)</script>')) { [void]$sbs.Append($m.Groups[1].Value.ToLowerInvariant()).Append("`n") } }
}
$hayScript = $sbs.ToString()

# Path-resolved references per image (for choosing which duplicate to keep).
$patterns = @(
    '(?is)\b(?:href|src|background|poster|data-src)\s*=\s*"([^"]*)"',
    "(?is)\b(?:href|src|background|poster|data-src)\s*=\s*'([^']*)'",
    '(?is)\b(?:href|src|background|poster|data-src)\s*=\s*([^\s"''>]+)',
    '(?is)url\(\s*["'']?([^)"'']+?)["'']?\s*\)',
    '(?is)srcset\s*=\s*"([^"]*)"'
)
$refCount = @{}
$dangling = New-Object System.Collections.Generic.HashSet[string]   # image names referenced by a path that resolves to nothing (already-broken links)
foreach ($s in $sources) {
    if ($s -match '\.js$') { continue }
    $text = Read-Text $s
    $dir = if ($s.Contains('/')) { $s.Substring(0, $s.LastIndexOf('/')) } else { '' }
    $seen = New-Object System.Collections.Generic.HashSet[string]
    foreach ($p in $patterns) {
        foreach ($m in [regex]::Matches($text, $p)) {
            $ref = $m.Groups[1].Value.Trim()
            if ($ref -eq '' -or $ref -match '^(#|[a-zA-Z][a-zA-Z0-9+.-]*:|//)' -or $ref -match '[{}<>$]|\+') { continue }
            $clean = ($ref -split '[?#]')[0]
            if ($clean -eq '') { continue }
            try { $clean = [System.Uri]::UnescapeDataString($clean) } catch { continue }
            $parts = New-Object System.Collections.Generic.List[string]
            if ($clean.StartsWith('/')) { $clean = $clean.TrimStart('/') }
            elseif ($s -match '^fragments/') { }   # fetched into index.html: root-relative
            elseif ($dir -ne '') { foreach ($d in $dir.Split('/')) { $parts.Add($d) } }
            foreach ($seg in $clean.Replace('\', '/').Split('/')) {
                if ($seg -eq '' -or $seg -eq '.') { continue }
                if ($seg -eq '..') { if ($parts.Count) { $parts.RemoveAt($parts.Count - 1) } continue }
                $parts.Add($seg)
            }
            $key = ($parts -join '/').ToLowerInvariant()
            if ($lookup.ContainsKey($key)) { [void]$seen.Add($lookup[$key]) }
            elseif ($clean -match "(?i)$imgRe") { [void]$dangling.Add((($clean.Replace('\', '/') -split '/')[-1]).ToLowerInvariant()) }
        }
    }
    foreach ($r in $seen) { $refCount[$r] = 1 + [int]$refCount[$r] }
}

# ---- Helpers ----
function Test-Unreferenced([string]$path) {
    if ($refCount.ContainsKey($path)) { return $false }
    $segs = $path.ToLowerInvariant().Split('/')
    $name = $segs[-1]
    if ($dangling.Contains($name)) { return $false }   # a broken link may be meant for this file; repointing is out of scope
    $tail = if ($segs.Count -ge 2) { $segs[-2] + '/' + $name } else { $name }
    $forms = New-Object System.Collections.Generic.HashSet[string]
    foreach ($n in @($name, $name.Normalize([Text.NormalizationForm]::FormC), $name.Normalize([Text.NormalizationForm]::FormD))) {
        if ($hayScript.Contains($n) -or $hayScript.Contains($cp1252.GetString($utf8.GetBytes($n)))) { return $false }
    }
    foreach ($n in @($tail, $tail.Normalize([Text.NormalizationForm]::FormC), $tail.Normalize([Text.NormalizationForm]::FormD))) {
        [void]$forms.Add($n)
        [void]$forms.Add(($n.Split('/') | ForEach-Object { [System.Uri]::EscapeDataString($_).ToLowerInvariant() }) -join '/')
        [void]$forms.Add($n.Replace(' ', '%20'))
        [void]$forms.Add($cp1252.GetString($utf8.GetBytes($n)))
    }
    foreach ($f in $forms) { if ($hay.Contains($f)) { return $false } }
    return $true
}

function Get-NormalName([string]$file) {
    $ext = [System.IO.Path]::GetExtension($file).ToLowerInvariant().TrimStart('.')
    if ($ext -eq 'jpeg') { $ext = 'jpg' }
    $base = [System.IO.Path]::GetFileNameWithoutExtension($file).ToLowerInvariant()
    $base = $base -replace '\.(jpe?g|png|gif)-for-web-normal$', ''
    $d = $base.Normalize([Text.NormalizationForm]::FormD)
    $sbn = New-Object System.Text.StringBuilder
    foreach ($ch in $d.ToCharArray()) {
        if ([System.Globalization.CharUnicodeInfo]::GetUnicodeCategory($ch) -ne 'NonSpacingMark') { [void]$sbn.Append($ch) }
    }
    $base = ($sbn.ToString() -replace '[^a-z0-9]+', '-').Trim('-')
    if ($base -eq '') { $base = 'image' }
    return @($base, $ext)
}

function Get-WebpSize([string]$path) {
    $b = [System.IO.File]::ReadAllBytes((Join-Path $root $path))
    if ($b.Length -lt 30) { return $null }
    $fmt = [System.Text.Encoding]::ASCII.GetString($b, 12, 4)
    if ($fmt -eq 'VP8X') { return @(([int]$b[24] + [int]$b[25] * 256 + [int]$b[26] * 65536 + 1), ([int]$b[27] + [int]$b[28] * 256 + [int]$b[29] * 65536 + 1)) }
    if ($fmt -eq 'VP8 ') { return @((([int]$b[26] + [int]$b[27] * 256) -band 0x3fff), (([int]$b[28] + [int]$b[29] * 256) -band 0x3fff)) }
    if ($fmt -eq 'VP8L') { $v = [uint32]($b[21] + $b[22] * 256 + $b[23] * 65536 + $b[24] * 16777216); return @((($v -band 0x3fff) + 1), ((($v -shr 14) -band 0x3fff) + 1)) }
    return $null
}

function Test-PngAlpha([System.Drawing.Bitmap]$bmp) {
    if (-not [System.Drawing.Image]::IsAlphaPixelFormat($bmp.PixelFormat)) { return $false }
    $rect = New-Object System.Drawing.Rectangle(0, 0, $bmp.Width, $bmp.Height)
    $data = $bmp.LockBits($rect, [System.Drawing.Imaging.ImageLockMode]::ReadOnly, [System.Drawing.Imaging.PixelFormat]::Format32bppArgb)
    try {
        $len = [Math]::Abs($data.Stride) * $bmp.Height
        $buf = New-Object byte[] $len
        [System.Runtime.InteropServices.Marshal]::Copy($data.Scan0, $buf, 0, $len)
        for ($i = 3; $i -lt $len; $i += 4) { if ($buf[$i] -ne 255) { return $true } }
        return $false
    } finally { $bmp.UnlockBits($data) }
}

# ---- Survey ----
$info = @{}
$sha = [System.Security.Cryptography.SHA256]::Create()
foreach ($f in $images) {
    $full = Join-Path $root $f
    $bytes = [System.IO.File]::ReadAllBytes($full)
    $e = [pscustomobject]@{
        Bytes = $bytes.Length
        Hash = [BitConverter]::ToString($sha.ComputeHash($bytes)).Replace('-', '')
        W = 0; H = 0; Alpha = $false; Readable = $true; Unref = $false
    }
    $ext = [System.IO.Path]::GetExtension($f).ToLowerInvariant()
    $e.Unref = Test-Unreferenced $f
    try {
        if ($ext -eq '.webp') {
            $sz = Get-WebpSize $f
            if ($sz) { $e.W = $sz[0]; $e.H = $sz[1] } else { $e.Readable = $false }
        } elseif ($ext -in '.jpg', '.jpeg', '.png', '.gif') {
            $ms = New-Object System.IO.MemoryStream(, $bytes)
            $bmp = New-Object System.Drawing.Bitmap($ms)
            try {
                $e.W = $bmp.Width; $e.H = $bmp.Height
                if ($ext -eq '.png' -and -not $e.Unref) { $e.Alpha = Test-PngAlpha $bmp }
            } finally { $bmp.Dispose(); $ms.Dispose() }
        }
    } catch { $e.Readable = $false; Write-Warning "Unreadable image, kept as is: $f" }
    $info[$f] = $e
}

# ---- Duplicates (inside assets/images only, live files only) ----
$mergeInto = @{}
$groups = $images | Where-Object { $_ -match '^assets/images/' -and -not $info[$_].Unref } | Group-Object { $info[$_].Hash } | Where-Object { $_.Count -gt 1 }
foreach ($g in $groups) {
    $members = @($g.Group | Sort-Object @{ e = { -[int]$refCount[$_] } }, @{ e = { if ($_ -match '/shared/') { 0 } else { 1 } } }, @{ e = { $_.Length } }, @{ e = { $_ } })
    $keep = $members[0]
    foreach ($m in $members[1..($members.Count - 1)]) { $mergeInto[$m] = $keep }
}

# ---- Desired paths ----
$desired = @{}
$action = @{}
foreach ($f in $images) {
    $e = $info[$f]
    $dir = if ($f.Contains('/')) { $f.Substring(0, $f.LastIndexOf('/')) } else { '' }
    $nb = Get-NormalName $f
    $ext = $nb[1]
    if ($e.Unref) { $action[$f] = 'quarantine'; continue }
    if ($mergeInto.ContainsKey($f)) { $action[$f] = 'merge'; continue }
    $convert = $e.Readable -and ($ext -in 'jpg', 'png')
    if ($convert -and $ext -eq 'png' -and -not $e.Alpha) { $ext = 'jpg' }
    $desired[$f] = "$dir/$($nb[0]).$ext"
    if ($convert) { $action[$f] = 'convert' }
    elseif ($ext -eq 'webp' -and $e.Readable -and ([Math]::Max($e.W, $e.H) -gt $maxEdge -or $e.Bytes -gt $maxBytes)) { $action[$f] = 'resize' }
    elseif ($desired[$f] -ne $f) { $action[$f] = 'rename' }
    else { $action[$f] = 'keep' }
}

# ---- Collisions: unchanged names keep theirs; the others get a numeric suffix ----
$used = New-Object System.Collections.Generic.HashSet[string]([StringComparer]::OrdinalIgnoreCase)
foreach ($f in $images) { if ($desired.ContainsKey($f) -and $desired[$f] -ceq $f) { [void]$used.Add($f) } }
foreach ($f in $images) {
    if (-not $desired.ContainsKey($f) -or $desired[$f] -ceq $f) { continue }
    $cand = $desired[$f]
    if ($used.Contains($cand) -or ($images | Where-Object { $_ -ne $f -and $desired.ContainsKey($_) -and $desired[$_] -ceq $cand -and $_ -lt $f })) {
        $stem = $cand.Substring(0, $cand.LastIndexOf('.')); $x = $cand.Substring($cand.LastIndexOf('.'))
        $n = 2
        while ($used.Contains("$stem-$n$x") -or $lookup.ContainsKey("$stem-$n$x".ToLowerInvariant())) { $n++ }
        $cand = "$stem-$n$x"
    }
    $desired[$f] = $cand
    [void]$used.Add($cand)
}

# ---- Rows ----
$rows = New-Object System.Collections.Generic.List[object]
foreach ($f in $images) {
    $e = $info[$f]
    $act = $action[$f]
    switch ($act) {
        'quarantine' { $new = "attic/unreferenced-images/$f"; $nb = $e.Bytes }
        'merge' { $new = $desired[$mergeInto[$f]]; $nb = 0 }
        { $_ -in 'convert', 'resize' } { $new = $desired[$f]; $nb = '' }
        default { $new = $desired[$f]; $nb = $e.Bytes }
    }
    $rows.Add([pscustomobject]@{ old_path = $f; new_path = $new; action = $act; old_bytes = $e.Bytes; new_bytes = $nb })
}

$dupes = @($rows | Where-Object { $_.action -ne 'merge' } | Group-Object { $_.new_path.ToLowerInvariant() } | Where-Object { $_.Count -gt 1 })
foreach ($d in $dupes) { Write-Error "new_path collision: $($d.Name) <- $(($d.Group.old_path) -join ', ')" -ErrorAction Continue }

$lines = New-Object System.Collections.Generic.List[string]
$lines.Add('old_path,new_path,action,old_bytes,new_bytes')
function Q([string]$s) { if ($s -match '[,"\r\n]') { '"' + $s.Replace('"', '""') + '"' } else { $s } }
foreach ($r in $rows) { $lines.Add("$(Q $r.old_path),$(Q $r.new_path),$($r.action),$($r.old_bytes),$($r.new_bytes)") }
[System.IO.File]::WriteAllText($outFile, (($lines -join "`n") + "`n"), $utf8)

# ---- Summary ----
Write-Host "Live images: $($rows.Count); manifest: scripts/image-manifest.csv"
$rows | Group-Object action | Sort-Object Name | ForEach-Object {
    $mb = [Math]::Round((($_.Group | Measure-Object old_bytes -Sum).Sum) / 1MB, 1)
    Write-Host ("  {0,-11} {1,4} files  {2,6} MB" -f $_.Name, $_.Count, $mb)
}
if ($dupes.Count) { exit 1 }
