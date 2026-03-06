<?php
echo "<h1>Path Diagnostic</h1>";
echo "Current File: " . __FILE__ . "<br>";
echo "Request URI: " . $_SERVER['REQUEST_URI'] . "<br>";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "Script Name: " . $_SERVER['SCRIPT_NAME'] . "<br>";

$includes_path = __DIR__ . '/../includes/db.php';
echo "Checking includes/db.php at: $includes_path <br>";
if (file_exists($includes_path)) {
    echo "✅ includes/db.php found!<br>";
} else {
    echo "❌ includes/db.php NOT found!<br>";
}

echo "<hr>";
echo "<a href='../index.php'>Back to Home</a>";
?>
