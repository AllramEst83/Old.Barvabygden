<#
.SYNOPSIS
    Resizes and converts the live images listed as `convert` in scripts/image-manifest.csv: writes a
    resized fallback and a .webp sibling for each. HTML and CSS are not touched.

.DESCRIPTION
    Per image-rules.md: long edge at most 1920 px (never upscaled), EXIF stripped (orientation applied
    first), WebP quality 80, lossless WebP for transparent PNG. The encoder is sharp, run through
    `npx -y sharp-cli@<pinned>`; nothing is added to the repo's package files.

    - Input is the file at its current (story-3) name: folder of old_path + stem of new_path + the
      lowercased original extension.
    - Fallback: the manifest new_path. JPG stays JPG (replaced in place when the result is smaller),
      transparent PNG stays PNG, opaque PNG becomes a new .jpg beside the old .png (pages keep
      pointing at the PNG until the picture-markup story removes it).
    - The .webp is written last, so its presence marks an image as done. Re-running skips done images
      and changes nothing.
    - A result over 500 KB is retried at quality 70; if it is still over, it is kept and listed.
    - The manifest's new_bytes column is filled with the fallback size.

.PARAMETER WhatIf
    List what would be processed; write nothing.

.EXAMPLE
    ./scripts/optimize-images.ps1
#>
param([switch]$WhatIf)
$ErrorActionPreference = 'Stop'
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8
$root = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
Set-Location $root
$sharpPkg = 'sharp-cli@6.1.0'
$maxEdge = 1920
$quality = 80
$retryQuality = 70
$maxBytes = 500KB
$csv = Join-Path $PSScriptRoot 'image-manifest.csv'
$rows = @(Import-Csv $csv -Encoding UTF8)

function Get-Stem([string]$p) { [System.IO.Path]::GetFileNameWithoutExtension($p) }
function Get-Ext([string]$p) { $e = [System.IO.Path]::GetExtension($p).ToLowerInvariant(); if ($e -eq '.jpeg') { '.jpg' } else { $e } }
function Get-DirOf([string]$p) { $p.Substring(0, $p.LastIndexOf('/')) }

# ---- Plan ----
$jobs = New-Object System.Collections.Generic.List[object]
foreach ($r in $rows | Where-Object { $_.action -eq 'convert' }) {
    $dir = Get-DirOf $r.old_path
    $stem = Get-Stem $r.new_path
    $input = "$dir/$stem$(Get-Ext $r.old_path)"
    $fallback = $r.new_path
    $webp = "$dir/$stem.webp"
    if (-not (Test-Path -LiteralPath (Join-Path $root $input))) { Write-Warning "Input missing, skipped: $input"; continue }
    if (Test-Path -LiteralPath (Join-Path $root $webp)) { continue }   # done
    $jobs.Add([pscustomobject]@{
            Row = $r; Dir = $dir; Stem = $stem; Input = $input; Fallback = $fallback; Webp = $webp
            Transparent = ($fallback -like '*.png'); OldBytes = (Get-Item -LiteralPath (Join-Path $root $input)).Length
        })
}
Write-Host "To process: $($jobs.Count) of $(@($rows | Where-Object { $_.action -eq 'convert' }).Count) convert images"
if ($WhatIf -or $jobs.Count -eq 0) { return }

$work = Join-Path ([System.IO.Path]::GetTempPath()) "optimize-images-$([guid]::NewGuid().ToString('N'))"
New-Item -ItemType Directory -Path $work | Out-Null

# Runs sharp-cli on files of one folder; returns a map stem -> output file path.
function Invoke-Sharp([string[]]$inputs, [string]$format, [int]$q, [bool]$lossless) {
    $out = Join-Path $work ([guid]::NewGuid().ToString('N'))
    New-Item -ItemType Directory -Path $out | Out-Null
    $argList = @('-y', $sharpPkg, '-i') + $inputs + @('-o', $out, '-f', $format, '-q', "$q", '--autoOrient')
    if ($lossless) { $argList += '--lossless' }
    if ($format -eq 'jpeg') { $argList += '--progressive' }
    if ($format -eq 'png') { $argList += @('-c', '9') }
    $argList += @('resize', "$maxEdge", "$maxEdge", '--fit', 'inside', '--withoutEnlargement')
    & npx @argList | Out-Null
    if ($LASTEXITCODE -ne 0) { throw "sharp-cli failed ($format) for: $($inputs -join ', ')" }
    $map = @{}
    foreach ($f in Get-ChildItem -LiteralPath $out -File) { $map[$f.BaseName] = $f.FullName }
    return $map
}

$overCap = New-Object System.Collections.Generic.List[string]
$fallbackBytes = @{}
$webpTotal = 0; $fbTotal = 0; $oldTotal = 0

try {
    foreach ($group in ($jobs | Group-Object Dir | Sort-Object Name)) {
        $g = @($group.Group)
        Write-Host "$($group.Name): $($g.Count)"
        $abs = { param($j) Join-Path $root $j.Input }

        # 1. Fallbacks (written first). jpeg for .jpg targets, png for transparent PNGs.
        foreach ($set in @(@{ fmt = 'jpeg'; jobs = @($g | Where-Object { -not $_.Transparent }) }, @{ fmt = 'png'; jobs = @($g | Where-Object { $_.Transparent }) })) {
            if (-not $set.jobs.Count) { continue }
            $res = Invoke-Sharp @($set.jobs | ForEach-Object { & $abs $_ }) $set.fmt $quality $false
            foreach ($j in $set.jobs) {
                $key = [System.IO.Path]::GetFileNameWithoutExtension($j.Input)
                $outFile = $res[$key]
                if (-not $outFile) { throw "No output for $($j.Input)" }
                if ($set.fmt -eq 'jpeg' -and (Get-Item $outFile).Length -gt $maxBytes) {
                    $retry = Invoke-Sharp @(& $abs $j) 'jpeg' $retryQuality $false
                    $outFile = $retry[$key]
                }
                $newLen = (Get-Item $outFile).Length
                $target = Join-Path $root $j.Fallback
                $sameFile = ($j.Input -ceq $j.Fallback)
                # Replace an existing fallback only when the result is smaller; a new .jpg beside a .png is always written.
                if (-not $sameFile -or $newLen -lt $j.OldBytes) { Move-Item -LiteralPath $outFile -Destination $target -Force }
                else { $newLen = $j.OldBytes }
                $fallbackBytes[$j.Row.old_path] = $newLen
                if ($newLen -gt $maxBytes) { $overCap.Add("$($j.Fallback) $([Math]::Round($newLen / 1KB)) KB (fallback)") }
                $fbTotal += $newLen; $oldTotal += $j.OldBytes
            }
        }

        # 2. WebP siblings (written last: their presence marks the image as done).
        foreach ($set in @(@{ lossless = $false; jobs = @($g | Where-Object { -not $_.Transparent }) }, @{ lossless = $true; jobs = @($g | Where-Object { $_.Transparent }) })) {
            if (-not $set.jobs.Count) { continue }
            $res = Invoke-Sharp @($set.jobs | ForEach-Object { & $abs $_ }) 'webp' $quality $set.lossless
            foreach ($j in $set.jobs) {
                $key = [System.IO.Path]::GetFileNameWithoutExtension($j.Input)
                $outFile = $res[$key]
                if (-not $outFile) { throw "No webp output for $($j.Input)" }
                if (-not $set.lossless -and (Get-Item $outFile).Length -gt $maxBytes) { $outFile = (Invoke-Sharp @(& $abs $j) 'webp' $retryQuality $false)[$key] }
                $len = (Get-Item $outFile).Length
                Move-Item -LiteralPath $outFile -Destination (Join-Path $root $j.Webp) -Force
                $webpTotal += $len
                if ($len -gt $maxBytes) { $overCap.Add("$($j.Webp) $([Math]::Round($len / 1KB)) KB (webp)") }
            }
        }
    }
} finally {
    Remove-Item -LiteralPath $work -Recurse -Force -ErrorAction SilentlyContinue
}

# ---- Record fallback sizes in the manifest (same layout as build-image-manifest.ps1) ----
function Q([string]$s) { if ($s -match '[,"\r\n]') { '"' + $s.Replace('"', '""') + '"' } else { $s } }
$lines = New-Object System.Collections.Generic.List[string]
$lines.Add('old_path,new_path,action,old_bytes,new_bytes')
foreach ($r in $rows) {
    $nb = if ($fallbackBytes.ContainsKey($r.old_path)) { $fallbackBytes[$r.old_path] } else { $r.new_bytes }
    $lines.Add("$(Q $r.old_path),$(Q $r.new_path),$($r.action),$($r.old_bytes),$nb")
}
[System.IO.File]::WriteAllText($csv, (($lines -join "`n") + "`n"), (New-Object System.Text.UTF8Encoding($false)))

Write-Host ("Processed {0} images: originals {1} MB -> fallbacks {2} MB + webp {3} MB" -f $jobs.Count, [Math]::Round($oldTotal / 1MB, 1), [Math]::Round($fbTotal / 1MB, 1), [Math]::Round($webpTotal / 1MB, 1))
if ($overCap.Count) { Write-Warning "Over 500 KB after the quality retry:"; $overCap | ForEach-Object { Write-Host "  $_" } }
