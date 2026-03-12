# Update ALL version strings for theme files to v=4.0
$root = "c:\xampp\htdocs\Car_Higher"
$allPhp = Get-ChildItem -Path $root -Recurse -Include "*.php" -File
$count = 0

foreach ($f in $allPhp) {
    $content = [System.IO.File]::ReadAllText($f.FullName)
    $newContent = $content -replace 'theme\.css\?v=[\d\.]+', 'theme.css?v=4.0'
    $newContent = $newContent -replace 'theme-switcher\.js\?v=[\d\.]+', 'theme-switcher.js?v=4.0'
    if ($newContent -ne $content) {
        [System.IO.File]::WriteAllText($f.FullName, $newContent)
        $count++
    }
}
Write-Host "Updated version strings in $count files"
