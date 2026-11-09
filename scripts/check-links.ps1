<#
.SYNOPSIS
    Reports broken local references (href, src, CSS url(), fetch()) in the deployed site files.

.DESCRIPTION
    Scans .html/.htm/.css/.js files under the repo root, skipping repo-only and untouchable folders
    (web, .ilosoft, .git, _bmad*, .agents, .claude, .vscode, docs, scripts, attic, migration-sources).
    Each local reference is URL-decoded, stripped of ?query/#fragment and resolved against the file's
    folder (or the repo root for a leading "/", and for fetch() in .js files). References that do not
    exist on disk are "broken".

    Without switches the result is compared to scripts/link-baseline.txt: exit code 0 when no broken
    reference is new, 1 otherwise. Baseline lines are "source -> target".

.PARAMETER UpdateBaseline
    Write the current broken-reference list to scripts/link-baseline.txt instead of comparing.

.EXAMPLE
    ./scripts/check-links.ps1
    ./scripts/check-links.ps1 -UpdateBaseline
#>
param([switch]$UpdateBaseline)

$ErrorActionPreference = 'Stop'
$root = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$baselineFile = Join-Path $PSScriptRoot 'link-baseline.txt'
$skip = '^(web|\.ilosoft|\.git|_bmad[^/]*|\.agents|\.claude|\.vscode|docs|scripts|attic|migration-sources)$'
$strict = New-Object System.Text.UTF8Encoding($false, $true)
$cp1252 = [System.Text.Encoding]::GetEncoding(1252)

function Read-Text([string]$path) {
    $bytes = [System.IO.File]::ReadAllBytes($path)
    try { return $strict.GetString($bytes) } catch { return $cp1252.GetString($bytes) }
}

function Get-Files([string]$dir) {
    foreach ($item in Get-ChildItem -LiteralPath $dir -Force) {
        $rel = $item.FullName.Substring($root.Length).TrimStart('\', '/')
        $top = ($rel -split '[\\/]')[0]
        if ($item.PSIsContainer) {
            if ($dir -eq $root -and $top -match $skip) { continue }
            Get-Files $item.FullName
        } elseif ($item.Extension -match '^\.(html?|css|js)$') {
            if ($dir -eq $root -and $top -match $skip) { continue }
            $item
        }
    }
}

$patterns = @(
    '(?is)\b(?:href|src|background|poster|data-src)\s*=\s*"([^"]*)"',
    "(?is)\b(?:href|src|background|poster|data-src)\s*=\s*'([^']*)'",
    '(?is)\b(?:href|src|background|poster|data-src)\s*=\s*([^\s"''>]+)',
    '(?is)url\(\s*["'']?([^)"'']+?)["'']?\s*\)',
    '(?is)fetch\(\s*["'']([^"'']+)["'']'
)

$broken = New-Object System.Collections.Generic.HashSet[string]
foreach ($file in Get-Files $root) {
    $rel = $file.FullName.Substring($root.Length).TrimStart('\', '/').Replace('\', '/')
    $isJs = $file.Extension -eq '.js'
    $text = Read-Text $file.FullName
    $refs = New-Object System.Collections.Generic.HashSet[string]
    foreach ($p in $patterns) {
        if ($isJs -and $p -notmatch 'fetch') { continue }
        foreach ($m in [regex]::Matches($text, $p)) { [void]$refs.Add($m.Groups[1].Value.Trim()) }
    }
    foreach ($ref in $refs) {
        if ($ref -eq '' -or $ref -match '^(#|[a-zA-Z][a-zA-Z0-9+.-]*:|//)' -or $ref -match '[{}<>$]|\+') { continue }
        $clean = ($ref -split '[?#]')[0]
        if ($clean -eq '') { continue }
        $clean = [System.Uri]::UnescapeDataString($clean)
        if ($clean.StartsWith('/')) { $base = $root; $clean = $clean.TrimStart('/') }
        elseif ($isJs) { $base = $root }
        else { $base = $file.DirectoryName }
        try { $target = Join-Path $base $clean } catch { continue }
        if (-not (Test-Path -LiteralPath $target)) { [void]$broken.Add("$rel -> $ref") }
    }
}

$current = @($broken | Sort-Object { $_.ToLowerInvariant() }, { $_ })

if ($UpdateBaseline) {
    [System.IO.File]::WriteAllLines($baselineFile, [string[]]$current, (New-Object System.Text.UTF8Encoding($false)))
    Write-Host "Baseline written: $($current.Count) broken references -> $baselineFile" -ForegroundColor Green
    exit 0
}

$known = @()
if (Test-Path -LiteralPath $baselineFile) { $known = [System.IO.File]::ReadAllLines($baselineFile) }
$new = @($current | Where-Object { $known -notcontains $_ })
$fixed = @($known | Where-Object { $current -notcontains $_ })

Write-Host "Broken references: $($current.Count) (baseline $($known.Count))"
if ($fixed.Count) { Write-Host "Fixed vs baseline: $($fixed.Count)" -ForegroundColor Green }
if ($new.Count) {
    Write-Host "NEW broken references: $($new.Count)" -ForegroundColor Red
    $new | ForEach-Object { Write-Host "  $_" }
    exit 1
}
Write-Host 'No new broken references.' -ForegroundColor Green
exit 0
