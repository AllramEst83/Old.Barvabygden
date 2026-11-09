<#
.SYNOPSIS
    Writes scripts/server-delete-list.txt: the old server copies to delete after cutover (CAP-9).

.DESCRIPTION
    Derived from scripts/path-manifest.csv. Lists the old folders (assets/pages, assets/barva-news,
    assets/powershell, assets/docs) and every moved page or PDF that sat at the server root. Old root
    media files are never listed: they stay on the server so external image links keep working.
    One path per line, relative to the web root, folders with a trailing slash.

.EXAMPLE
    ./scripts/build-server-delete-list.ps1
#>
$ErrorActionPreference = 'Stop'
$utf8 = New-Object System.Text.UTF8Encoding($false)
$rows = Import-Csv (Join-Path $PSScriptRoot 'path-manifest.csv') -Encoding UTF8
$folders = 'assets/pages/', 'assets/barva-news/', 'assets/powershell/', 'assets/docs/'
$files = $rows | Where-Object { $_.old_path -notmatch '/' -and $_.old_path -match '\.(html?|pdf)$' } |
    ForEach-Object { $_.old_path } | Sort-Object { $_.ToLowerInvariant() }, { $_ } -Unique
$lines = @($folders) + @($files)
[System.IO.File]::WriteAllText((Join-Path $PSScriptRoot 'server-delete-list.txt'), (($lines -join "`n") + "`n"), $utf8)
Write-Host "Wrote $($lines.Count) entries ($($folders.Count) folders, $(@($files).Count) root files) to scripts/server-delete-list.txt" -ForegroundColor Green
