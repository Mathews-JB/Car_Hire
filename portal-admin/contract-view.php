<?php
// Legacy redirect for old links
$id = $_GET['id'] ?? '';
$params = !empty($id) ? "?id=" . urlencode($id) : "";
header("Location: contract-viewer.php" . $params);
exit;
?>
