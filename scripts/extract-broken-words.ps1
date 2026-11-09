<#
.SYNOPSIS
    Extracts all unique word tokens containing U+FFFD from an HTML file.
    Outputs a clean list that an agent/LLM can reason about without reading the full file.

.PARAMETER Path
    Path to the HTML file to analyse.

.PARAMETER WithContext
    If set, prints one line of surrounding text context per unique word (helps
    disambiguate words like "?r" which can be år/är/Är/År).

.EXAMPLE
    .\extract-broken-words.ps1 -Path "assets\barva-news\2008\barva-news-2008-issue-31.htm"
    .\extract-broken-words.ps1 -Path "assets\barva-news\2008\barva-news-2008-issue-31.htm" -WithContext
#>
param(
    [Parameter(Mandatory)][string]$Path,
    [switch]$WithContext
)

$r    = [char]0xFFFD
$text = [System.IO.File]::ReadAllText($Path, [System.Text.Encoding]::UTF8)

# Strip HTML tags and common entities to get plain text
$plain = $text -replace '<[^>]+>',          ' '
$plain = $plain -replace '&nbsp;',          ' '
$plain = $plain -replace '&amp;',           '&'
$plain = $plain -replace '&lt;',            '<'
$plain = $plain -replace '&gt;',            '>'
$plain = $plain -replace '&quot;',          '"'
$plain = $plain -replace '&#\d+;',          ' '
$plain = $plain -replace '&[a-z]{2,6};',   ' '

# Split into tokens on whitespace and punctuation boundaries
# Keep letters + U+FFFD together as one token
$tokens = [regex]::Matches($plain, "[\p{L}$r][\p{L}$r]*") |
    ForEach-Object { $_.Value }

# Unique broken tokens (contain at least one U+FFFD)
$brokenUnique = $tokens |
    Where-Object { $_.Contains($r) } |
    Sort-Object -Unique

$total = ($tokens | Where-Object { $_.Contains($r) }).Count

Write-Host ""
Write-Host "File   : $Path"
Write-Host "Total U+FFFD occurrences : $total"
Write-Host "Unique broken word forms : $($brokenUnique.Count)"
Write-Host ""
Write-Host "--- BROKEN WORDS (copy this list to the fix agent) ---"

if ($WithContext) {
    foreach ($word in $brokenUnique) {
        # Find first occurrence in plain text and grab surrounding context
        $idx = $plain.IndexOf($word)
        if ($idx -ge 0) {
            $start = [Math]::Max(0, $idx - 40)
            $end   = [Math]::Min($plain.Length, $idx + $word.Length + 40)
            $ctx   = $plain.Substring($start, $end - $start) -replace '\s+', ' '
            Write-Host "  $word"
            Write-Host "    context: ...${ctx}..."
        } else {
            Write-Host "  $word"
        }
    }
} else {
    $brokenUnique | ForEach-Object { Write-Host "  $_" }
}

Write-Host ""
Write-Host "--- END OF LIST ---"
Write-Host ""
Write-Host "Paste the list above into the fix agent and ask it to:"
Write-Host "  1. Decide the correct Swedish word for each broken form."
Write-Host "  2. Generate a PowerShell fix script using [char]0xFFFD replacements."
Write-Host "  3. Run the fix script against: $Path"
