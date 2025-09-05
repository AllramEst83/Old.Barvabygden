# Deployment Helper Script for Cache Busting
# This script updates version numbers in HTML files when deploying
# Run from Root of project directory: PS C:\Users\kaywi\dev\Old.Barvabygden> ./assets/powershell/update-version.ps1

param(
    [string]$Version = (Get-Date -Format "yyyy.MM.dd.HHmm"),
    [string]$HtmlFile = "index.html"
)

Write-Host "Updating asset versions to: $Version" -ForegroundColor Green

# Update version in HTML file
if (Test-Path $HtmlFile) {
    $content = Get-Content $HtmlFile -Raw
    
    # Update CSS files
    $content = $content -replace '(\.css)\?v=[^"'']*', "`$1?v=$Version"
    
    # Update JS files
    $content = $content -replace '(\.js)\?v=[^"'']*', "`$1?v=$Version"
    
    # If no version parameters exist, add them
    if ($content -notmatch '\?v=') {
        $content = $content -replace '(assets/[^"'']*\.css)', "`$1?v=$Version"
        $content = $content -replace '(assets/[^"'']*\.js)', "`$1?v=$Version"
    }
    
    Set-Content $HtmlFile -Value $content -Encoding UTF8
    Write-Host "Updated $HtmlFile with version $Version" -ForegroundColor Green
} else {
    Write-Host "Error: $HtmlFile not found!" -ForegroundColor Red
}

# Update version in version-manager.js if it exists
$versionManagerPath = "assets/js/version-manager.js"
if (Test-Path $versionManagerPath) {
    $vmContent = Get-Content $versionManagerPath -Raw
    $vmContent = $vmContent -replace "const ASSET_VERSION = '[^']*'", "const ASSET_VERSION = '$Version'"
    Set-Content $versionManagerPath -Value $vmContent -Encoding UTF8
    Write-Host "Updated $versionManagerPath with version $Version" -ForegroundColor Green
}

Write-Host "Deployment update complete!" -ForegroundColor Green
Write-Host "Don't forget to upload the updated files to your server." -ForegroundColor Yellow
