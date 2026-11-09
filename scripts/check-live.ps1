<#
.SYNOPSIS
    Post-cutover verification against the live site (CAP-8, CAP-9).

.DESCRIPTION
    Reads the generated redirect block from .htaccess and checks, for each line, that the old URL
    answers 301 with a Location that matches the new URL, and that the new URL answers 200. Also
    checks that repo-only URLs answer 404 and that the home page answers 200.
    Exit code 0 when everything passes, 1 otherwise. Makes real HTTP requests: run it after upload.

.PARAMETER BaseUrl
    Site to check. Default https://barvabygden.se

.PARAMETER DelayMs
    Pause between requests, in milliseconds (default 700). The host rate-limits bursts with
    429/503; the script also retries those with a growing wait.

.PARAMETER ListOnly
    Parse .htaccess and print the checks without making requests.

.EXAMPLE
    ./scripts/check-live.ps1
    ./scripts/check-live.ps1 -BaseUrl https://barvabygden.se -ListOnly
#>
param(
    [string]$BaseUrl = 'https://barvabygden.se',
    [switch]$ListOnly,
    [int]$DelayMs = 700
)
$ErrorActionPreference = 'Stop'
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8
$BaseUrl = $BaseUrl.TrimEnd('/')
$root = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$utf8 = New-Object System.Text.UTF8Encoding($false)
[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12

function Esc-Url([string]$path) { '/' + (($path.TrimStart('/').Split('/') | ForEach-Object { [System.Uri]::EscapeDataString($_) }) -join '/') }

function Get-Status([string]$url) {
    for ($try = 0; $try -lt 6; $try++) {
        Start-Sleep -Milliseconds ($DelayMs + $try * 3000)
        $r = Get-StatusOnce $url
        if ($r.code -ne 429 -and $r.code -ne 503) { return $r }
    }
    return $r
}

function Get-StatusOnce([string]$url) {
    $req = [System.Net.HttpWebRequest]::Create($url)
    $req.AllowAutoRedirect = $false
    $req.Method = 'GET'
    $req.Timeout = 30000
    $req.UserAgent = 'barvabygden-check-live'
    try { $resp = $req.GetResponse() } catch [System.Net.WebException] {
        if ($null -eq $_.Exception.Response) { return @{ code = 0; location = '' } }
        $resp = $_.Exception.Response
    }
    $result = @{ code = [int]$resp.StatusCode; location = $resp.Headers['Location'] }
    $resp.Close()
    return $result
}

# Old -> new pairs from the generated block
$pairs = New-Object System.Collections.Generic.List[object]
foreach ($line in [System.IO.File]::ReadAllLines((Join-Path $root '.htaccess'), $utf8)) {
    if ($line -match '^Redirect 301 "((?:[^"\\]|\\.)*)" "((?:[^"\\]|\\.)*)"') {
        $pairs.Add([pscustomobject]@{ old = ($Matches[1] -replace '\\(.)', '$1'); new = ($Matches[2] -replace '\\(.)', '$1') })
    }
}
if (-not $pairs.Count) { Write-Host 'No redirect lines found in .htaccess; run scripts/build-redirects.ps1.' -ForegroundColor Red; exit 1 }
$blocked = @('/docs/', '/scripts/', '/attic/', '/migration-sources/', '/README.md', '/CLAUDE.md', '/.agents/', '/.claude/', '/.git/', '/scripts/path-manifest.csv')

if ($ListOnly) {
    Write-Host "$($pairs.Count) redirects to check, $($blocked.Count) blocked URLs, plus the home page."
    $pairs | Select-Object -First 5 | ForEach-Object { Write-Host "  $($_.old) -> $($_.new)" }
    exit 0
}

$fail = New-Object System.Collections.Generic.List[string]
function Fail([string]$msg) { $script:fail.Add($msg); Write-Host "FAIL $msg" -ForegroundColor Red }

Write-Host "Checking ${BaseUrl}: $($pairs.Count) redirects, $($blocked.Count) blocked URLs. Takes a few minutes (pause $DelayMs ms between requests)..."
$homeResp = Get-Status "$BaseUrl/"
if ($homeResp.code -ne 200) { Fail "/ expected 200, got $($homeResp.code)" }

$done = 0
foreach ($p in $pairs) {
    $done++
    if ($done % 10 -eq 0) { Write-Host "  $done / $($pairs.Count) redirects checked, $($fail.Count) failures so far" }
    $r = Get-Status ($BaseUrl + (Esc-Url $p.old))
    $expected = if ($p.new -eq '/') { '/' } else { $p.new }
    $loc = [string]$r.location
    $locPath = if ($loc -match '^https?://[^/]+(/.*)?$') { if ($Matches[1]) { $Matches[1] } else { '/' } } else { $loc }
    if ($r.code -ne 301) { Fail "$($p.old): expected 301, got $($r.code)"; continue }
    if ($locPath -ne $expected) { Fail "$($p.old): Location $locPath, expected $expected"; continue }
    $n = Get-Status ($BaseUrl + $expected)
    if ($n.code -ne 200) { Fail "$($p.old): new URL $expected expected 200, got $($n.code)" }
}

foreach ($b in $blocked) {
    $r = Get-Status ($BaseUrl + $b)
    if ($r.code -ne 404) { Fail "$b expected 404, got $($r.code)" }
}

Write-Host "Checked $($pairs.Count) redirects, $($blocked.Count) blocked URLs: $($fail.Count) failures."
if ($fail.Count) { exit 1 }
Write-Host 'All live checks passed.' -ForegroundColor Green
exit 0
