<#
.SYNOPSIS
    Manifest-driven reference rewrite followed by git mv, for one or more manifest classes.

.DESCRIPTION
    Reads scripts/path-manifest.csv. Rows whose class is in -Classes are "moving". Before anything
    moves, every reference (href/src/background/poster/data-src, CSS url(), fetch()) that comes from a
    moving file or points at a moving file is rewritten so it resolves to the same target after the
    move, relative to the source file's new location. Then the moving files are moved with git mv.

    - References are resolved against tracked files, case-insensitively and URL-decoded; those that
      resolve to nothing (e.g. the known *-filer/ folders) are left alone.
    - The original style is kept: "./" prefix, root-absolute "/...", ?query/#fragment, %-encoding.
    - Files are rewritten byte-safely (UTF-8 or Windows-1252 as detected, line endings untouched).
    - Skipped as sources: manifest classes pending-source and stale, and web/, .ilosoft/, _bmad*,
      .agents/, .claude/, .vscode/, docs/, scripts/.
    - Fragments (fragments/*.html) and .js fetch() targets resolve against the repo root.
    - When a file has both a "referenced" and a "split" row, the non-split row decides the move.
    - Re-running after a move changes nothing.

.PARAMETER Classes
    Manifest classes to move, e.g. live-page,referenced.

.PARAMETER WhatIf
    Report what would change; modify nothing.

.EXAMPLE
    ./scripts/rewrite-links.ps1 -Classes live-page,referenced -WhatIf
    ./scripts/rewrite-links.ps1 -Classes live-page,referenced
#>
param(
    [Parameter(Mandatory)][string[]]$Classes,
    [string]$ManifestPath = (Join-Path $PSScriptRoot 'path-manifest.csv'),
    [string[]]$DeleteClasses = @(),   # rewritten like moves, then removed with git rm instead of git mv (merged duplicates)
    [switch]$WhatIf
)
$ErrorActionPreference = 'Stop'
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8
$root = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
Set-Location $root
$strict = New-Object System.Text.UTF8Encoding($false, $true)
$cp1252 = [System.Text.Encoding]::GetEncoding(1252)
$latin1 = [System.Text.Encoding]::GetEncoding(28591)

$manifest = @(Import-Csv $ManifestPath -Encoding UTF8)
$tracked = @(git -c core.quotepath=off ls-files | Where-Object { $_ })
$lookup = @{}
foreach ($t in $tracked) { $lookup[$t.ToLowerInvariant()] = $t }

# old -> new for moving rows that still exist at their old path
$moveMap = @{}
foreach ($r in ($manifest | Sort-Object { if ($_.class -eq 'split') { 1 } else { 0 } })) {
    if (($Classes -contains $r.class -or $DeleteClasses -contains $r.class) -and $lookup.ContainsKey($r.old_path.ToLowerInvariant()) -and $lookup[$r.old_path.ToLowerInvariant()] -ceq $r.old_path -and -not $moveMap.ContainsKey($r.old_path)) {
        $moveMap[$r.old_path] = $r.new_path
    }
}
# Rows of the requested classes whose tracked path differs only in case are skipped; say so instead of passing silently.
foreach ($r in $manifest) {
    if (($Classes -contains $r.class -or $DeleteClasses -contains $r.class) -and $lookup.ContainsKey($r.old_path.ToLowerInvariant()) -and $lookup[$r.old_path.ToLowerInvariant()] -cne $r.old_path -and $lookup[$r.old_path.ToLowerInvariant()] -cne $r.new_path) {
        Write-Warning "Skipped (tracked path differs in case): $($r.old_path)"
    }
}
$deleteSet = @{}
foreach ($r in $manifest) { if ($DeleteClasses -contains $r.class) { $deleteSet[$r.old_path] = $true } }
$noSource = @{}
foreach ($r in $manifest) { if ($r.class -in 'pending-source', 'stale') { $noSource[$r.old_path] = $true } }
$skipDir = '^(web|\.ilosoft|_bmad[^/]*|\.agents|\.claude|\.vscode|docs|scripts)/'

$patterns = @(
    '(?is)(\b(?:href|src|background|poster|data-src|data-full)\s*=\s*")([^"]*)(")',
    "(?is)(\b(?:href|src|background|poster|data-src|data-full)\s*=\s*')([^']*)(')",
    '(?is)(\b(?:href|src|background|poster|data-src|data-full)\s*=\s*)([^\s"''>]+)()',
    '(?is)(url\(\s*["'']?)([^)"'']+?)(["'']?\s*\))',
    '(?is)(fetch\(\s*["''])([^"'']+)(["''])'
)

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

$changedFiles = 0; $changedRefs = 0
$sources = $tracked | Where-Object {
    $_ -match '\.(html?|css|js)$' -and $_ -notmatch $skipDir -and -not $noSource.ContainsKey($_) -and
    -not ($_ -match '^(attic|migration-sources)/')
}
foreach ($src in $sources) {
    $isJs = $src -match '\.js$'
    $isFragment = $src -match '^(fragments|assets/pages)/' -and $src -notmatch '^assets/pages/.+\.htm$'
    $newSrc = if ($moveMap.ContainsKey($src)) { $moveMap[$src] } else { $src }
    # Fragments and fetch() targets resolve against the page that loads them (repo root), and stay there.
    $oldBase = if ($isJs -or $isFragment) { '' } else { Get-Dir $src }
    $newBase = if ($isJs -or $isFragment) { '' } else { Get-Dir $newSrc }

    $file = Read-File $src
    $text = $file.text
    $count = 0
    foreach ($p in $patterns) {
        if ($isJs -and $p -notmatch 'fetch') { continue }
        $text = [regex]::Replace($text, $p, {
            param($m)
            $ref = $m.Groups[2].Value
            $trim = $ref.Trim()
            if ($trim -eq '' -or $trim -match '^(#|[a-zA-Z][a-zA-Z0-9+.-]*:|//)' -or $trim -match '[{}<>$]|\+') { return $m.Value }
            $suffixAt = $trim.IndexOfAny([char[]]'?#')
            $pathPart = if ($suffixAt -ge 0) { $trim.Substring(0, $suffixAt) } else { $trim }
            $suffix = if ($suffixAt -ge 0) { $trim.Substring($suffixAt) } else { '' }
            if ($pathPart -eq '') { return $m.Value }
            try { $decoded = [System.Uri]::UnescapeDataString($pathPart) } catch { return $m.Value }
            $key = (Resolve-Segments $oldBase $decoded).ToLowerInvariant()
            if (-not $lookup.ContainsKey($key)) { return $m.Value }
            $target = $lookup[$key]
            $newTarget = if ($moveMap.ContainsKey($target)) { $moveMap[$target] } else { $target }
            if ($newSrc -ceq $src -and $newTarget -ceq $target) { return $m.Value }   # case-sensitive: case-only renames must be rewritten
            if ($decoded.StartsWith('/')) { $new = '/' + $newTarget }
            else {
                $new = Get-Relative $newBase $newTarget
                if ($decoded.StartsWith('./') -and -not $new.StartsWith('../')) { $new = './' + $new }
            }
            if ($pathPart.Contains('%')) { $new = ($new.Split('/') | ForEach-Object { if ($_ -eq '..' -or $_ -eq '.') { $_ } else { [System.Uri]::EscapeDataString($_) } }) -join '/' }
            if ($new -ceq $decoded) { return $m.Value }
            $script:count++
            return $m.Groups[1].Value + $new + $suffix + $m.Groups[3].Value
        })
    }
    if ($count -gt 0) {
        $changedFiles++; $changedRefs += $count
        if (-not $WhatIf) { [System.IO.File]::WriteAllBytes((Join-Path $root $src), $file.enc.GetBytes($text)) }
    }
}

$moved = 0
foreach ($old in ($moveMap.Keys | Sort-Object)) {
    $new = $moveMap[$old]
    if ($WhatIf) { $moved++; continue }
    if ($deleteSet.ContainsKey($old)) {
        git rm -q -- $old
        if ($LASTEXITCODE -ne 0) { throw "git rm failed: $old" }
        $moved++
        continue
    }
    $dir = Get-Dir $new
    if ($dir -ne '') { New-Item -ItemType Directory -Force -Path (Join-Path $root $dir) | Out-Null }
    git mv -- $old $new
    if ($LASTEXITCODE -ne 0) { throw "git mv failed: $old -> $new" }
    $moved++
}
$verb = if ($WhatIf) { 'Would rewrite' } else { 'Rewrote' }
Write-Host "$verb $changedRefs references in $changedFiles files; $(if ($WhatIf) { 'would move' } else { 'moved' }) $moved files." -ForegroundColor Green
