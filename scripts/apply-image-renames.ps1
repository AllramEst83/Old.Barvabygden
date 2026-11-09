<#
.SYNOPSIS
    Applies the rename and merge rows of scripts/image-manifest.csv: rewrites every reference, then
    git mv (rename) or git rm (merge) via scripts/rewrite-links.ps1.

.DESCRIPTION
    - rename/convert/resize/keep rows: the image gets its normalized name but keeps its current format.
      The target is the folder of old_path plus the stem of new_path plus the lowercased original
      extension (.jpeg -> .jpg). A later step converts format and resizes (convert rows' new_path is the
      fallback name, which may change the extension).
    - merge rows: references are repointed to the kept file's renamed target, then the duplicate is removed.
    - quarantine rows are skipped (they were moved already).
    - Re-running changes nothing: rows whose old path no longer exists are ignored.

.PARAMETER WhatIf
    Report what would change; modify nothing.

.EXAMPLE
    ./scripts/apply-image-renames.ps1 -WhatIf
    ./scripts/apply-image-renames.ps1
#>
param([switch]$WhatIf)
$ErrorActionPreference = 'Stop'
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8
$root = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
Set-Location $root

$rows = @(Import-Csv (Join-Path $PSScriptRoot 'image-manifest.csv') -Encoding UTF8)

function Get-ExtLower([string]$p) {
    $e = [System.IO.Path]::GetExtension($p).ToLowerInvariant()
    if ($e -eq '.jpeg') { '.jpg' } else { $e }
}
function Get-RenameTarget($r) {
    $dir = $r.old_path.Substring(0, $r.old_path.LastIndexOf('/'))
    $stem = [System.IO.Path]::GetFileNameWithoutExtension($r.new_path)
    return "$dir/$stem$(Get-ExtLower $r.old_path)"
}

# Final renamed path of every kept file, keyed by the manifest new_path (merge rows point at it).
$targetByNew = @{}
foreach ($r in $rows) { if ($r.action -notin 'merge', 'quarantine') { $targetByNew[$r.new_path] = Get-RenameTarget $r } }

$plan = New-Object System.Collections.Generic.List[object]
foreach ($r in $rows) {
    switch ($r.action) {
        'quarantine' { continue }
        'merge' {
            if (-not $targetByNew.ContainsKey($r.new_path)) { throw "Merge target missing for $($r.old_path) -> $($r.new_path)" }
            $plan.Add([pscustomobject]@{ old_path = $r.old_path; new_path = $targetByNew[$r.new_path]; class = 'image-merge' })
        }
        default {
            $t = Get-RenameTarget $r
            if ($t -cne $r.old_path) { $plan.Add([pscustomobject]@{ old_path = $r.old_path; new_path = $t; class = 'image-rename' }) }
        }
    }
}

$tmp = Join-Path ([System.IO.Path]::GetTempPath()) "image-plan-$([guid]::NewGuid().ToString('N')).csv"
try {
    $plan | Export-Csv -Path $tmp -NoTypeInformation -Encoding UTF8
    $args2 = @{ Classes = @('image-rename'); DeleteClasses = @('image-merge'); ManifestPath = $tmp }
    if ($WhatIf) { $args2.WhatIf = $true }
    & (Join-Path $PSScriptRoot 'rewrite-links.ps1') @args2
} finally {
    Remove-Item -LiteralPath $tmp -ErrorAction SilentlyContinue
}
