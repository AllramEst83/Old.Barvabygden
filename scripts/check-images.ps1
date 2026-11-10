<#
.SYNOPSIS
    Checks the live images against the rules in CLAUDE.md (naming, size, WebP, picture markup, duplicates).

.DESCRIPTION
    Scans tracked images under assets/images/ and barva-bladet/ and the live pages, and fails (exit 1)
    on any of:
      - a file name that does not match ^[a-z0-9]+(-[a-z0-9]+)*\.(webp|jpg|png|gif|ico)$
      - a file over 500 KB, or an image with a long edge over 1920 px
      - a .jpg/.png without a .webp sibling
      - byte-identical files inside assets/images/
      - an <img> of a .jpg/.png that has a .webp sibling but is not inside a <picture>
    Run it after adding or migrating images (git add new files first; the script reads `git ls-files`).
    Unreadable images are reported as warnings only.

.PARAMETER Path
    Optional folder (repo-relative, e.g. barva-bladet/2016/images/issue-48) to limit the file checks.
    The duplicate and markup checks still cover everything.

.EXAMPLE
    ./scripts/check-images.ps1
    ./scripts/check-images.ps1 -Path barva-bladet/2016/images
#>
param([string]$Path = '')
$ErrorActionPreference = 'Stop'
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8
Add-Type -AssemblyName System.Drawing
$root = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
Set-Location $root
$strict = New-Object System.Text.UTF8Encoding($false, $true)
$cp1252 = [System.Text.Encoding]::GetEncoding(1252)
$maxBytes = 500KB
$maxEdge = 1920
$namePat = '^[a-z0-9]+(-[a-z0-9]+)*\.(webp|jpg|png|gif|ico)$'

$tracked = @(git -c core.quotepath=off ls-files | Where-Object { $_ })
$lookup = @{}
foreach ($t in $tracked) { $lookup[$t.ToLowerInvariant()] = $t }
$images = @($tracked | Where-Object { $_ -match '^(assets/images|barva-bladet)/' -and $_ -match '(?i)\.(jpe?g|png|gif|webp|ico)$' -and (Test-Path -LiteralPath (Join-Path $root $_)) })
$scoped = if ($Path) { $p = $Path.Trim('/\').Replace('\', '/') + '/'; @($images | Where-Object { $_.StartsWith($p, [StringComparison]::OrdinalIgnoreCase) }) } else { $images }

$fail = New-Object System.Collections.Generic.List[string]
$warn = New-Object System.Collections.Generic.List[string]

foreach ($f in $scoped) {
    $name = $f -replace '^.*/', ''
    if ($name -cnotmatch $namePat) { $fail.Add("name: $f") }
    $len = (Get-Item -LiteralPath (Join-Path $root $f)).Length
    if ($len -gt $maxBytes) { $fail.Add("size $([Math]::Round($len / 1KB)) KB: $f") }
    if ($f -match '(?i)\.(jpe?g|png)$' -and -not $lookup.ContainsKey(($f.Substring(0, $f.LastIndexOf('.')) + '.webp').ToLowerInvariant())) { $fail.Add("no .webp sibling: $f") }
    if ($f -match '(?i)\.(jpe?g|png|gif)$' -and $len -gt 0) {
        try {
            $img = [System.Drawing.Image]::FromFile((Join-Path $root $f))
            try { if ([Math]::Max($img.Width, $img.Height) -gt $maxEdge) { $fail.Add("long edge $([Math]::Max($img.Width, $img.Height)) px: $f") } } finally { $img.Dispose() }
        } catch { $warn.Add("unreadable image: $f") }
    } elseif ($len -eq 0) { $warn.Add("empty file: $f") }
}

# Byte-identical files inside assets/images
$sha = [System.Security.Cryptography.SHA256]::Create()
$byHash = @{}
foreach ($f in @($images | Where-Object { $_ -match '^assets/images/' })) {
    $b = [System.IO.File]::ReadAllBytes((Join-Path $root $f))
    if ($b.Length -eq 0) { continue }
    $h = [BitConverter]::ToString($sha.ComputeHash($b))
    if (-not $byHash.ContainsKey($h)) { $byHash[$h] = New-Object System.Collections.Generic.List[string] }
    $byHash[$h].Add($f)
}
foreach ($g in $byHash.Values) { if ($g.Count -gt 1) { $fail.Add("duplicate files: $($g -join ' = ')") } }

# <img> of a converted image outside <picture>
function Read-Text([string]$rel) {
    $bytes = [System.IO.File]::ReadAllBytes((Join-Path $root $rel))
    try { $strict.GetString($bytes) } catch { $cp1252.GetString($bytes) }
}
function Resolve-Ref([string]$baseDir, [string]$ref) {
    $parts = New-Object System.Collections.Generic.List[string]
    if (-not $ref.StartsWith('/') -and $baseDir -ne '') { foreach ($d in $baseDir.Split('/')) { $parts.Add($d) } }
    foreach ($seg in $ref.TrimStart('/').Replace('\', '/').Split('/')) {
        if ($seg -eq '' -or $seg -eq '.') { continue }
        if ($seg -eq '..') { if ($parts.Count) { $parts.RemoveAt($parts.Count - 1) } continue }
        $parts.Add($seg)
    }
    return ($parts -join '/')
}
$re = [regex]'(?is)<picture\b.*?</picture>|<img\b[^>]*>'
$srcRe = [regex]'(?is)\bsrc\s*=\s*(?:"([^"]*)"|''([^'']*)''|([^\s"''>]+))'
$pages = $tracked | Where-Object { $_ -match '\.html?$' -and ($_ -eq 'index.html' -or $_ -match '^(pages|fragments|barva-bladet)/') }
foreach ($page in $pages) {
    $baseDir = if ($page -match '^fragments/' -or -not $page.Contains('/')) { '' } else { $page.Substring(0, $page.LastIndexOf('/')) }
    foreach ($m in $re.Matches((Read-Text $page))) {
        if ($m.Value -match '^(?i)<picture') { continue }
        $sm = $srcRe.Match($m.Value)
        if (-not $sm.Success) { continue }
        $ref = (@($sm.Groups[1], $sm.Groups[2], $sm.Groups[3]) | Where-Object { $_.Success } | Select-Object -First 1).Value.Trim()
        if ($ref -eq '' -or $ref -match '^(#|[a-zA-Z][a-zA-Z0-9+.-]*:|//)' -or $ref -match '[{}<>$\[\]]|\+') { continue }
        $pathPart = ($ref -split '[?#]')[0]
        if ($pathPart -notmatch '(?i)\.(jpe?g|png)$') { continue }
        try { $dec = [System.Uri]::UnescapeDataString($pathPart) } catch { continue }
        $key = (Resolve-Ref $baseDir $dec).ToLowerInvariant()
        if (-not $lookup.ContainsKey($key)) { continue }
        if ($lookup.ContainsKey(($key.Substring(0, $key.LastIndexOf('.')) + '.webp'))) { $fail.Add("img not in <picture>: $page -> $pathPart") }
    }
}

$warn | ForEach-Object { Write-Warning $_ }
$fail | ForEach-Object { Write-Host "FAIL $_" -ForegroundColor Red }
Write-Host "Checked $($scoped.Count) images and $(@($pages).Count) pages: $($fail.Count) failures, $($warn.Count) warnings."
if ($fail.Count) { exit 1 }
Write-Host 'All image checks passed.' -ForegroundColor Green
