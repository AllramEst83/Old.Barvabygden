<#
.SYNOPSIS
    Builds scripts/path-manifest.csv: the old-path -> new-path map for the folder restructure.

.DESCRIPTION
    Classifies every tracked file that will move, per migration-rules.md, and writes
    scripts/path-manifest.csv with the columns old_path,new_path,class. Nothing is moved,
    edited or deleted. Output is sorted and deterministic.

    Untouched: web/, .ilosoft/, _bmad*, .agents/, .claude/, .vscode/, docs/, scripts/,
    assets/css, assets/js, assets/images (existing files stay put) and the root allowlist.

    Exit code 1 when two sources map to the same new_path. Unmapped live pages and
    unclassified files are printed as warnings (exit 0).

.EXAMPLE
    ./scripts/build-manifest.ps1
#>
$ErrorActionPreference = 'Stop'
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8
$root = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
Set-Location $root
$outFile = Join-Path $PSScriptRoot 'path-manifest.csv'
$strict = New-Object System.Text.UTF8Encoding($false, $true)
$cp1252 = [System.Text.Encoding]::GetEncoding(1252)

# ---- Page-topic map (migration-rules.md): filename pattern -> topic folder under assets/images/ ----
$topicMap = @(
    @('^kyrkan?\.htm$', 'kyrkan'), @('^skol(an|m)\.htm$', 'skolan'), @('^utst\.htm$', 'museum'),
    @('^prost-sk\.htm$', 'prostokna'), @('^hagaborg\.htm$', 'hagaborg'), @('^spackelf\.htm$', 'spackelfabriken'),
    @('^bron\.htm$', 'bron'), @('^gruv\.htm$', 'gruvan'), @('^bankl\.htm$', 'banklekmen'),
    @('^(folkd|dans.*|danser.*)\.htm$', 'folkdans'), @('^(bfa|bf)\.htm$', 'barva-framtid'),
    @('^lucia\.htm$', 'lucia'), @('^luren\.htm$', 'luren'), @('^tromb\.htm$', 'tromben'),
    @('^gardsg\.htm$', 'gardsgrupper'), @('^(macken|motor)\.htm$', 'macken'), @('^bib\.htm$', 'biblioteket'),
    @('^barvaif_2011\.htm$', 'barva-if'), @('^vatten\.htm$', 'vatten'), @('^(bdag-08|fnv-08)\.htm$', 'events'),
    @('^(barva|bh|bladen|enket|index|barvaf|stiftelsen-barva-bygdegard|v.lkommen till barva och barvabygden)\.html?$', 'shared')
)
$stale = @('old_index.htm', 'öld_index.html', 'barvaf (2).htm', 'bfa_temp.htm')
$staleLeftover = '^bb-(31|32|33|48|49x)\.html?$'
$allowlist = @('index.html', '.htaccess', '.gitignore', 'README.md', 'CLAUDE.md', 'GEMINI.md')
$untouched = '^(web|\.ilosoft|_bmad[^/]*|\.agents|\.claude|\.vscode|docs|scripts)/'
$media = '\.(jpe?g|gif|png|bmp|webp|tif)$'

# ---- Tracked files ----
$tracked = @(git -c core.quotepath=off ls-files | Where-Object { $_ })
$lookup = @{}
foreach ($t in $tracked) { $lookup[$t.ToLowerInvariant()] = $t }

function Read-Text([string]$path) {
    $bytes = [System.IO.File]::ReadAllBytes((Join-Path $root $path))
    try { return $strict.GetString($bytes) } catch { return $cp1252.GetString($bytes) }
}

$patterns = @(
    '(?is)\b(?:href|src|background|poster|data-src)\s*=\s*"([^"]*)"',
    "(?is)\b(?:href|src|background|poster|data-src)\s*=\s*'([^']*)'",
    '(?is)\b(?:href|src|background|poster|data-src)\s*=\s*([^\s"''>]+)',
    '(?is)url\(\s*["'']?([^)"'']+?)["'']?\s*\)',
    '(?is)fetch\(\s*["'']([^"'']+)["'']'
)

# Returns tracked paths that a file references (resolved case-insensitively, URL-decoded).
function Get-Refs([string]$rel) {
    $isJs = $rel -match '\.js$'
    $text = Read-Text $rel
    $dir = if ($rel.Contains('/')) { $rel.Substring(0, $rel.LastIndexOf('/')) } else { '' }
    $found = New-Object System.Collections.Generic.HashSet[string]
    foreach ($p in $patterns) {
        if ($isJs -and $p -notmatch 'fetch') { continue }
        foreach ($m in [regex]::Matches($text, $p)) {
            $ref = $m.Groups[1].Value.Trim()
            if ($ref -eq '' -or $ref -match '^(#|[a-zA-Z][a-zA-Z0-9+.-]*:|//)' -or $ref -match '[{}<>$]|\+') { continue }
            $clean = ($ref -split '[?#]')[0]
            if ($clean -eq '') { continue }
            try { $clean = [System.Uri]::UnescapeDataString($clean) } catch { continue }
            $parts = New-Object System.Collections.Generic.List[string]
            if ($clean.StartsWith('/')) { $clean = $clean.TrimStart('/') }
            elseif ($isJs) { }
            elseif ($dir -ne '') { foreach ($d in $dir.Split('/')) { $parts.Add($d) } }
            foreach ($seg in $clean.Replace('\', '/').Split('/')) {
                if ($seg -eq '' -or $seg -eq '.') { continue }
                if ($seg -eq '..') { if ($parts.Count) { $parts.RemoveAt($parts.Count - 1) } continue }
                $parts.Add($seg)
            }
            $key = ($parts -join '/').ToLowerInvariant()
            if ($lookup.ContainsKey($key)) { [void]$found.Add($lookup[$key]) }
        }
    }
    return $found
}

function Get-Topic([string]$page) {
    $name = ($page -replace '^.*/', '').ToLowerInvariant()
    foreach ($e in $topicMap) { if ($name -match $e[0]) { return $e[1] } }
    return $null
}

# ---- Classify root pages ----
$rootPages = @($tracked | Where-Object { $_ -notmatch '/' -and $_ -match '\.html?$' })
$pending = @($rootPages | Where-Object { $_ -match '^bb-(\d+)\.html?$' -and [int]$Matches[1] -ge 37 -and [int]$Matches[1] -le 46 })
$staleSet = @($rootPages | Where-Object { $stale -contains $_ -or $_ -match $staleLeftover })

# Live pages: breadth-first from index.html, fragments, assets/pages and the newsletter archive.
$seeds = @($tracked | Where-Object { $_ -eq 'index.html' -or $_ -match '^assets/(pages|barva-news)/.*\.html?$' })
$live = New-Object System.Collections.Generic.HashSet[string]
$sources = New-Object System.Collections.Generic.List[string]
$queue = New-Object System.Collections.Generic.Queue[string]
foreach ($s in $seeds) { $sources.Add($s); $queue.Enqueue($s) }
$refsOf = @{}
while ($queue.Count) {
    $cur = $queue.Dequeue()
    $refsOf[$cur] = Get-Refs $cur
    foreach ($r in $refsOf[$cur]) {
        if ($r -notmatch '/' -and $r -match '\.html?$' -and $pending -notcontains $r -and $staleSet -notcontains $r -and $live.Add($r)) {
            $sources.Add($r); $queue.Enqueue($r)
        }
    }
}
# Per target-layout.md, every root page that is not stale or pending is a live page ("other live root .htm"),
# even when only old pages link to it. Pages never linked from a live page are only reported.
$linked = New-Object System.Collections.Generic.HashSet[string]($live)
foreach ($p in $rootPages) {
    if ($stale -contains $p -or $staleSet -contains $p -or $pending -contains $p) { continue }
    if ($live.Add($p)) { $sources.Add($p); $refsOf[$p] = Get-Refs $p }
}
# CSS and JS files also reference media (always shared).
foreach ($a in @($tracked | Where-Object { $_ -match '^assets/(css|js)/.*\.(css|js)$' })) {
    $sources.Add($a); $refsOf[$a] = Get-Refs $a
}

# media (root + bilder) -> set of topics from live sources, and pending usage
$candidateMedia = @($tracked | Where-Object { ($_ -notmatch '/' -or $_ -match '^bilder/') -and $_ -match $media })
$topicsOf = @{}
$unmapped = New-Object System.Collections.Generic.HashSet[string]
foreach ($src in $sources) {
    if ($src -eq 'index.html') { $topic = 'shared' }
    elseif ($src -match '/') { $topic = 'shared' }   # fragments, archive issues, css, js
    else { $topic = Get-Topic $src }
    if (-not $topic) { [void]$unmapped.Add($src); $topic = 'shared' }
    foreach ($r in $refsOf[$src]) {
        if ($r -notmatch $media) { continue }
        if (-not $topicsOf.ContainsKey($r)) { $topicsOf[$r] = New-Object System.Collections.Generic.HashSet[string] }
        [void]$topicsOf[$r].Add($topic)
    }
}
$pendingMedia = New-Object System.Collections.Generic.HashSet[string]
$pendingPdf = New-Object System.Collections.Generic.HashSet[string]
foreach ($p in $pending) { foreach ($r in (Get-Refs $p)) { if ($r -match $media) { [void]$pendingMedia.Add($r) } } }

# live-page PDFs and other referenced files
$pdfRefs = New-Object System.Collections.Generic.HashSet[string]
foreach ($src in $sources) { foreach ($r in $refsOf[$src]) { if ($r -match '\.pdf$' -and $r -notmatch '/') { [void]$pdfRefs.Add($r) } } }

# ---- Build rows ----
$rows = New-Object System.Collections.Generic.List[object]
function Add-Row([string]$old, [string]$new, [string]$class) { $rows.Add([pscustomobject]@{ old_path = $old; new_path = $new; class = $class }) }
$handled = New-Object System.Collections.Generic.HashSet[string]

foreach ($f in $tracked) {
    if ($f -match $untouched -or $allowlist -contains $f) { [void]$handled.Add($f); continue }
    $name = $f -replace '^.*/', ''
    if ($f -match '^assets/(css|js|images)/' -and $f -notmatch '^assets/images/coffee-beans/') { [void]$handled.Add($f); continue }
    [void]$handled.Add($f)
    if ($f -match '^assets/pages/(events|newsletter)\.html$') { Add-Row $f "fragments/$name" 'fragment'; continue }
    if ($f -eq 'assets/pages/stiftelsen-barva-bygdegard.htm') { Add-Row $f "pages/$name" 'live-page'; continue }
    if ($f -match '^assets/barva-news/') { Add-Row $f ('barva-bladet/' + $f.Substring('assets/barva-news/'.Length)) 'archive'; continue }
    if ($f -match '^assets/unused-images/') { Add-Row $f "attic/unused-images/$($f.Substring('assets/unused-images/'.Length))" 'junk'; continue }
    if ($f -match '^onewebmedia/') { Add-Row $f "attic/$f" 'junk'; continue }
    if ($f -match '^upload\.tmp/') { Add-Row $f "attic/$f" 'junk'; continue }
    if ($f -match '^images/coffee-beans/' -or $f -match '^assets/images/coffee-beans/') { Add-Row $f ('attic/coffee-beans/' + ($f -replace '^.*coffee-beans/', '')) 'junk'; continue }
    if ($f -match '^bilder/') {
        if ($f -match $media -and $topicsOf.ContainsKey($f)) {
            $t = @($topicsOf[$f] | Sort-Object)
            $topic = if ($t.Count -eq 1) { $t[0] } else { 'shared' }
            Add-Row $f "assets/images/$topic/$name" 'referenced'
            if ($pendingMedia.Contains($f)) { Add-Row $f "migration-sources/barva-bladet/$name" 'split' }
        } elseif ($pendingMedia.Contains($f)) { Add-Row $f "migration-sources/barva-bladet/$name" 'pending-source' }
        else { Add-Row $f "attic/bilder-unreferenced/$($f.Substring('bilder/'.Length))" 'orphan' }
        continue
    }
    if ($f -match '/') { Write-Warning "Unclassified: $f"; [void]$handled.Remove($f); continue }

    # ---- root files ----
    if ($f -match '\.html?$') {
        if ($stale -contains $f -or $f -match $staleLeftover) { Add-Row $f "attic/pages/$name" 'stale' }
        elseif ($pending -contains $f) { Add-Row $f "migration-sources/barva-bladet/$name" 'pending-source' }
        elseif ($live.Contains($f)) { Add-Row $f "pages/$name" 'live-page' }
        else { Add-Row $f "attic/pages/$name" 'stale' }
        continue
    }
    if ($f -match '\.pdf$') {
        if ($f -match '^(?:bb|barva-bladet|nr)[-\s]*(\d+)' -and [int]$Matches[1] -ge 37 -and [int]$Matches[1] -le 46) { Add-Row $f "migration-sources/barva-bladet/$name" 'pending-source' }
        elseif ($f -match '^(?:bb|barva-bladet|nr)[-\s]*\d+') { Add-Row $f "attic/pdfs/$name" 'duplicate' }
        elseif ($pdfRefs.Contains($f)) { Add-Row $f "assets/files/$name" 'referenced' }
        else { Add-Row $f "attic/pdfs/$name" 'orphan' }
        continue
    }
    if ($f -match $media) {
        $live2 = $topicsOf.ContainsKey($f)
        $pend = $pendingMedia.Contains($f)
        if ($live2) {
            $t = @($topicsOf[$f] | Sort-Object)
            $topic = if ($t.Count -eq 1) { $t[0] } else { 'shared' }
            Add-Row $f "assets/images/$topic/$name" 'referenced'
            if ($pend) { Add-Row $f "migration-sources/barva-bladet/$name" 'split' }
        } elseif ($pend) { Add-Row $f "migration-sources/barva-bladet/$name" 'pending-source' }
        else { Add-Row $f "attic/media/$name" 'orphan' }
        continue
    }
    Write-Warning "Unclassified root file: $f"
    [void]$handled.Remove($f)
}

# Moves completed in story 1: recorded for redirects and the server delete list; files no longer exist locally.
Add-Row 'assets/powershell/update-version.ps1' 'scripts/update-version.ps1' 'tooling'
Add-Row 'assets/powershell/extract-broken-words.ps1' 'scripts/extract-broken-words.ps1' 'tooling'
Add-Row 'assets/docs/PROJECT_DOCUMENTATION.md' 'docs/project-documentation.md' 'docs'
Add-Row 'assets/goals.md' 'docs/modernization-plan.md' 'docs'
Add-Row 'assets/todo.md' 'docs/todo.md' 'docs'

# ---- Collisions ----
# A bilder/ file whose name equals a root file's goes into a bilder/ subfolder of the same topic.
$byNew = $rows | Group-Object { $_.new_path.ToLowerInvariant() } | Where-Object { $_.Count -gt 1 }
foreach ($g in $byNew) {
    foreach ($r in $g.Group) {
        if ($r.old_path -match '^bilder/' -and $r.new_path -match '^assets/images/') {
            $r.new_path = ($r.new_path -replace '/[^/]+$', '') + '/bilder/' + ($r.new_path -replace '^.*/', '')
            Write-Host "Collision resolved: $($r.old_path) -> $($r.new_path)" -ForegroundColor Yellow
        }
    }
}
$collide = @($rows | Group-Object { $_.new_path.ToLowerInvariant() } | Where-Object { $_.Count -gt 1 })
if ($collide.Count) {
    Write-Host "Destination collisions: $($collide.Count)" -ForegroundColor Red
    foreach ($g in $collide) { foreach ($r in $g.Group) { Write-Host "  $($r.old_path) -> $($r.new_path) [$($r.class)]" } }
    exit 1
}

# ---- Write (sorted, UTF-8 without BOM, LF) ----
function Esc([string]$s) { if ($s -match '[",\r\n]') { '"' + $s.Replace('"', '""') + '"' } else { $s } }
$sorted = $rows | Sort-Object { $_.old_path.ToLowerInvariant() }, { $_.old_path }, { $_.new_path }
$lines = @('old_path,new_path,class') + @($sorted | ForEach-Object { "$(Esc $_.old_path),$(Esc $_.new_path),$($_.class)" })
[System.IO.File]::WriteAllText($outFile, (($lines -join "`n") + "`n"), (New-Object System.Text.UTF8Encoding($false)))

Write-Host "Manifest written: $($rows.Count) rows -> scripts/path-manifest.csv" -ForegroundColor Green
$rows | Group-Object class | Sort-Object Name | ForEach-Object { Write-Host ("  {0,-15} {1}" -f $_.Name, $_.Count) }
$unlinked = @($rootPages | Where-Object { $live.Contains($_) -and -not $linked.Contains($_) -and $_ -ne 'index.html' } | Sort-Object)
if ($unlinked.Count) {
    Write-Host "Live root pages not linked from index.html or any other live page ($($unlinked.Count)): $($unlinked -join ', ')" -ForegroundColor Yellow
}
if ($unmapped.Count) {
    Write-Host 'Live pages missing from the topic map (images default to shared/):' -ForegroundColor Yellow
    $unmapped | Sort-Object | ForEach-Object { Write-Host "  $_" }
}
exit 0
