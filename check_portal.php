<?php
echo "<h1>Portal Link Test</h1>";
echo "<ul>";
echo "<li><a href='portal-customer/index.php'>Customer Portal Root (index.php)</a></li>";
echo "<li><a href='portal-customer/dashboard.php'>Customer Dashboard</a></li>";
echo "<li><a href='portal-customer/path_test.php'>Diagnostic Script</a></li>";
echo "<li><a href='portal-agent/index.php'>Agent Portal</a></li>";
echo "<li><a href='portal-admin/index.php'>Admin Portal</a></li>";
echo "</ul>";

echo "<hr>";
echo "<h3>Debug Info:</h3>";
echo "Current File: " . __FILE__ . "<br>";
echo "PHP Version: " . phpversion() . "<br>";
?>
