<?php
include 'includes/db.php';

$updates = [
    1 => 'public/images/cars/prado.jpg',
    2 => 'public/images/cars/hilux.png',
    3 => 'public/images/cars/ranger.png',
    4 => 'https://images.unsplash.com/photo-1541899481282-d53bffe3c35d?auto=format&fit=crop&q=80&w=1000',
    5 => 'https://images.unsplash.com/photo-1629897048514-3dd7414fe72a?auto=format&fit=crop&q=80&w=1000',
    6 => 'https://images.unsplash.com/photo-1520031441872-265e4ff70366?auto=format&fit=crop&q=80&w=1000',
    7 => 'https://images.unsplash.com/photo-1523983388277-336a66bf9bcc?auto=format&fit=crop&q=80&w=1000',
    8 => 'https://images.unsplash.com/photo-1583121274602-3e2820bc6988?auto=format&fit=crop&q=80&w=1000',
    9 => 'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?auto=format&fit=crop&q=80&w=1000',
    10 => 'https://images.unsplash.com/photo-1503376780353-7e6692767b70?auto=format&fit=crop&q=80&w=1000',
    11 => 'https://images.unsplash.com/photo-1621007947382-bb3c3994e3fb?auto=format&fit=crop&q=80&w=1000',
    12 => 'https://images.unsplash.com/photo-1541348263662-e0c8de4221fe?auto=format&fit=crop&q=80&w=1000',
    13 => 'https://images.unsplash.com/photo-1539414417088-348620803cbe?auto=format&fit=crop&q=80&w=1000',
    14 => 'https://images.unsplash.com/photo-1616422329260-23facca31293?auto=format&fit=crop&q=80&w=1000',
    15 => 'https://images.unsplash.com/photo-1616422285623-13ff0167c958?auto=format&fit=crop&q=80&w=1000',
    16 => 'https://images.unsplash.com/photo-1605559424843-9e4c228bf1c2?auto=format&fit=crop&q=80&w=1000',
    17 => 'https://images.unsplash.com/photo-1621935579201-987820fac849?auto=format&fit=crop&q=80&w=1000',
    18 => 'https://images.unsplash.com/photo-1494976388531-d1058494cdd8?auto=format&fit=crop&q=80&w=1000'
];

foreach ($updates as $id => $path) {
    $stmt = $pdo->prepare('UPDATE vehicles SET image_url = ? WHERE id = ?');
    $stmt->execute([$path, $id]);
}
echo "Forcibly updated all 18 vehicles with the correct image paths.\n";
?>
