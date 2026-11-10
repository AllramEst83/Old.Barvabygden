<#
.SYNOPSIS
    Puts the optimized images into the live pages: repoints references to superseded PNGs and wraps
    every converted <img> in a <picture> with a WebP source.

.DESCRIPTION
    Step 1 (scripts/rewrite-links.ps1): every `convert` row of scripts/image-manifest.csv whose fallback
    changed format (opaque PNG -> .jpg) is repointed in all references (src, href, data-full, CSS url(),
    background ...), then the superseded PNG is removed with git rm.

    Step 2: in index.html, pages/, fragments/ and barva-bladet/ each <img> whose target has a .webp
    sibling becomes
        <picture><source type="image/webp" srcset="x.webp"><img ... original attributes ...></picture>
    The inner <img> keeps every attribute. Images already inside a <picture>, GIFs, and images without
    a .webp sibling are left alone. The original path style (./, /, ../, %-encoding) is kept.
    Files are rewritten byte-safely (UTF-8 or Windows-1252, line endings untouched).
    Re-running changes nothing.

.PARAMETER WhatIf
    Report what would change; modify nothing.

.EXAMPLE
    ./scripts/apply-image-pictures.ps1 -WhatIf
    ./scripts/apply-image-pictures.ps1
#>
param([switch]$WhatIf)
$ErrorActionPreference = 'Stop'
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8
$root = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
Set-Location $root
$strict = New-Object System.Text.UTF8Encoding($false, $true)
$cp1252 = [System.Text.Encoding]::GetEncoding(1252)
$latin1 = [System.Text.Encoding]::GetEncoding(28591)

# ---- Step 1: superseded PNGs ----
$rows = @(Import-Csv (Join-Path $PSScriptRoot 'image-manifest.csv') -Encoding UTF8)
$plan = New-Object System.Collections.Generic.List[object]
foreach ($r in $rows | Where-Object { $_.action -eq 'convert' }) {
    if ($r.old_path -match '(?i)\.png$' -and $r.new_path -match '\.jpg$') {
        $dir = $r.old_path.Substring(0, $r.old_path.LastIndexOf('/'))
        $stem = [System.IO.Path]::GetFileNameWithoutExtension($r.new_path)
        $png = "$dir/$stem.png"
        $jpg = "$dir/$stem.jpg"
        if (-not (Test-Path -LiteralPath (Join-Path $root $jpg))) { throw "Fallback missing: $jpg" }
        $plan.Add([pscustomobject]@{ old_path = $png; new_path = $jpg; class = 'image-superseded' })
    }
}
$tmp = Join-Path ([System.IO.Path]::GetTempPath()) "image-pictures-$([guid]::NewGuid().ToString('N')).csv"
try {
    $plan | Export-Csv -Path $tmp -NoTypeInformation -Encoding UTF8
    $a = @{ Classes = @('image-none'); DeleteClasses = @('image-superseded'); ManifestPath = $tmp }
    if ($WhatIf) { $a.WhatIf = $true }
    & (Join-Path $PSScriptRoot 'rewrite-links.ps1') @a
} finally { Remove-Item -LiteralPath $tmp -ErrorAction SilentlyContinue }

# ---- Step 2: <picture> wrapping ----
$tracked = @(git -c core.quotepath=off ls-files | Where-Object { $_ })
$lookup = @{}
foreach ($t in $tracked) { $lookup[$t.ToLowerInvariant()] = $t }
if ($WhatIf) {
    # A dry run of step 1 removed nothing, so superseded PNGs still exist; wrap decisions use the post-step-1 state.
    foreach ($p in $plan) { $lookup.Remove($p.old_path.ToLowerInvariant()) }
}

function Get-Dir([string]$p) { if ($p.Contains('/')) { $p.Substring(0, $p.LastIndexOf('/')) } else { '' } }
function Read-File([string]$rel) {
    $bytes = [System.IO.File]::ReadAllBytes((Join-Path $root $rel))
    foreach ($enc in @($strict, $cp1252, $latin1)) {
        try {
            $s = $enc.GetString($bytes)
            $back = $enc.GetBytes($s)
            if ($back.Length -eq $bytes.Length -and -not (Compare-Object $back $bytes -SyncWindow 0)) { return @{ text = $s; enc = $enc } }
        } catch { }
    }
    throw "Cannot round-trip $rel"
}
function Resolve-Segments([string]$baseDir, [string]$ref) {
    $parts = New-Object System.Collections.Generic.List[string]
    if (-not $ref.StartsWith('/') -and $baseDir -ne '') { foreach ($d in $baseDir.Split('/')) { $parts.Add($d) } }
    foreach ($seg in $ref.TrimStart('/').Replace('\', '/').Split('/')) {
        if ($seg -eq '' -or $seg -eq '.') { continue }
        if ($seg -eq '..') { if ($parts.Count) { $parts.RemoveAt($parts.Count - 1) } continue }
        $parts.Add($seg)
    }
    return ($parts -join '/')
}
function Get-Relative([string]$fromDir, [string]$to) {
    $a = @(if ($fromDir -ne '') { $fromDir.Split('/') })
    $b = $to.Split('/')
    $i = 0
    while ($i -lt $a.Count -and $i -lt ($b.Count - 1) -and $a[$i].ToLowerInvariant() -eq $b[$i].ToLowerInvariant()) { $i++ }
    $out = @()
    for ($k = $i; $k -lt $a.Count; $k++) { $out += '..' }
    for ($k = $i; $k -lt $b.Count; $k++) { $out += $b[$k] }
    return ($out -join '/')
}

$imgOrPicture = [regex]'(?is)<picture\b.*?</picture>|<img\b[^>]*>'
$srcRe = [regex]'(?is)\bsrc\s*=\s*(?:"([^"]*)"|''([^'']*)''|([^\s"''>]+))'
$sources = $tracked | Where-Object { $_ -match '\.html?$' -and ($_ -eq 'index.html' -or $_ -match '^(pages|fragments|barva-bladet)/') }
$wrapped = 0; $files = 0; $unresolved = 0
foreach ($src in $sources) {
    $isFragment = $src -match '^fragments/'
    $baseDir = if ($isFragment) { '' } else { Get-Dir $src }   # fragments are fetched into index.html: root-relative
    $file = Read-File $src
    $script:fileCount = 0
    $new = $imgOrPicture.Replace($file.text, {
            param($m)
            $tag = $m.Value
            if ($tag -match '^(?i)<picture') { return $tag }
            $sm = $srcRe.Match($tag)
            if (-not $sm.Success) { return $tag }
            $ref = if ($sm.Groups[1].Success) { $sm.Groups[1].Value } elseif ($sm.Groups[2].Success) { $sm.Groups[2].Value } else { $sm.Groups[3].Value }
            $trim = $ref.Trim()
            if ($trim -eq '' -or $trim -match '^(#|[a-zA-Z][a-zA-Z0-9+.-]*:|//)' -or $trim -match '[{}<>$\[\]]|\+') { return $tag }
            $pathPart = ($trim -split '[?#]')[0]
            if ($pathPart -notmatch '(?i)\.(jpe?g|png)$') { return $tag }
            try { $decoded = [System.Uri]::UnescapeDataString($pathPart) } catch { return $tag }
            $key = (Resolve-Segments $baseDir $decoded).ToLowerInvariant()
            if (-not $lookup.ContainsKey($key)) { $script:unresolved++; return $tag }
            $target = $lookup[$key]
            $webpKey = ($target.Substring(0, $target.LastIndexOf('.')) + '.webp').ToLowerInvariant()
            if (-not $lookup.ContainsKey($webpKey)) { return $tag }
            $webp = $lookup[$webpKey]
            if ($decoded.StartsWith('/')) { $rel = '/' + $webp }
            else {
                $rel = Get-Relative $baseDir $webp
                if ($decoded.StartsWith('./') -and -not $rel.StartsWith('../')) { $rel = './' + $rel }
            }
            if ($pathPart.Contains('%')) { $rel = ($rel.Split('/') | ForEach-Object { if ($_ -eq '..' -or $_ -eq '.') { $_ } else { [System.Uri]::EscapeDataString($_) } }) -join '/' }
            $script:fileCount++
            return '<picture><source type="image/webp" srcset="' + $rel + '">' + $tag + '</picture>'
        })
    if ($script:fileCount -gt 0) {
        $files++; $wrapped += $script:fileCount
        if (-not $WhatIf) { [System.IO.File]::WriteAllBytes((Join-Path $root $src), $file.enc.GetBytes($new)) }
    }
}
$verb = if ($WhatIf) { 'Would wrap' } else { 'Wrapped' }
Write-Host "$verb $wrapped images in $files files ($unresolved img targets did not resolve and were left alone)." -ForegroundColor Green
