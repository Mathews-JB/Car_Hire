<?php
include_once '../includes/db.php';
include_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
}

$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

$sql = "SELECT * FROM vehicles";
$params = [];

if ($status_filter !== 'all') {
    $sql .= " WHERE status = ?";
    $params[] = $status_filter;
}

$sql .= " ORDER BY make ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$vehicles = $stmt->fetchAll();

// Fetch all gallery images for these vehicles
$galleries = [];
if (!empty($vehicles)) {
    $v_ids = array_column($vehicles, 'id');
    $placeholders = implode(',', array_fill(0, count($v_ids), '?'));
    $img_stmt = $pdo->prepare("SELECT * FROM vehicle_images WHERE vehicle_id IN ($placeholders)");
    $img_stmt->execute($v_ids);
    $all_images = $img_stmt->fetchAll();
    
    foreach ($all_images as $img) {
        $galleries[$img['vehicle_id']][] = $img;
    }
}

// Handle Add Vehicle
if (isset($_POST['add_vehicle'])) {
    $make = $_POST['make'];
    $model = $_POST['model'];
    $year = $_POST['year'];
    $plate = $_POST['plate_number'];
    $price = $_POST['price_per_day'];
    $capacity = $_POST['capacity'];
    $vin = $_POST['vin'];
    $fuel = $_POST['fuel_type'];
    $trans = $_POST['transmission'];
    $mileage = $_POST['current_mileage'];
    $ins_exp = $_POST['insurance_expiry'];
    $tax_exp = $_POST['road_tax_expiry'];
    $fit_exp = $_POST['fitness_expiry'];
    $service_km = $_POST['service_due_km'];
    $features = $_POST['features'] ?? '';
    
    // Safe defaults — must be set before upload logic
    $image_url = 'public/images/cars/default.jpg';
    $interior_image_url = null;
    
    // Handle Main Thumbnail Upload (exterior_image = single primary photo)
    if (isset($_FILES['exterior_image']) && $_FILES['exterior_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../public/images/cars/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        $file_ext = strtolower(pathinfo($_FILES['exterior_image']['name'], PATHINFO_EXTENSION));
        $file_name = 'car_' . time() . '_' . uniqid() . '.' . $file_ext;
        $target_path = $upload_dir . $file_name;
        if (move_uploaded_file($_FILES['exterior_image']['tmp_name'], $target_path)) {
            $image_url = 'public/images/cars/' . $file_name;
        }
    }
    
    // Handle Main Interior Image Upload
    if (isset($_FILES['interior_image']) && $_FILES['interior_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir_int = '../public/images/cars/interior/';
        if (!is_dir($upload_dir_int)) { mkdir($upload_dir_int, 0755, true); }
        $file_ext = strtolower(pathinfo($_FILES['interior_image']['name'], PATHINFO_EXTENSION));
        $file_name = 'interior_' . time() . '_' . uniqid() . '.' . $file_ext;
        if (move_uploaded_file($_FILES['interior_image']['tmp_name'], $upload_dir_int . $file_name)) {
            $interior_image_url = 'public/images/cars/interior/' . $file_name;
        }
    }
    
    // Handle Video Upload
    $video_url = null;
    if (isset($_FILES['video']) && $_FILES['video']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../public/videos/cars/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        $file_ext = pathinfo($_FILES['video']['name'], PATHINFO_EXTENSION);
        $file_name = 'video_' . time() . '_' . uniqid() . '.' . $file_ext;
        $target_path = $upload_dir . $file_name;
        if (move_uploaded_file($_FILES['video']['tmp_name'], $target_path)) {
            $video_url = 'public/videos/cars/' . $file_name;
        }
    }
    
    $stmt = $pdo->prepare("INSERT INTO vehicles (make, model, year, plate_number, price_per_day, capacity, status, image_url, vin, fuel_type, transmission, current_mileage, insurance_expiry, road_tax_expiry, fitness_expiry, service_due_km, features, interior_image_url, video_url) VALUES (?, ?, ?, ?, ?, ?, 'available', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$make, $model, $year, $plate, $price, $capacity, $image_url, $vin, $fuel, $trans, $mileage, $ins_exp, $tax_exp, $fit_exp, $service_km, $features, $interior_image_url, $video_url]);
    $vehicle_id = $pdo->lastInsertId();

    // Handle Multiple Exterior Images
    if (isset($_FILES['exterior_images'])) {
        foreach ($_FILES['exterior_images']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['exterior_images']['error'][$key] === UPLOAD_ERR_OK) {
                $file_ext = pathinfo($_FILES['exterior_images']['name'][$key], PATHINFO_EXTENSION);
                $file_name = 'car_' . time() . '_' . uniqid() . '.' . $file_ext;
                $target_path = '../public/images/cars/' . $file_name;
                if (move_uploaded_file($tmp_name, $target_path)) {
                    $img_url = 'public/images/cars/' . $file_name;
                    $is_primary = ($key === 0 && empty($image_url)) ? 1 : 0;
                    $stmt = $pdo->prepare("INSERT INTO vehicle_images (vehicle_id, image_url, is_primary, view_type) VALUES (?, ?, ?, 'exterior')");
                    $stmt->execute([$vehicle_id, $img_url, $is_primary]);
                }
            }
        }
    }

    // Handle Multiple Interior Images
    if (isset($_FILES['interior_images'])) {
        foreach ($_FILES['interior_images']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['interior_images']['error'][$key] === UPLOAD_ERR_OK) {
                $file_ext = pathinfo($_FILES['interior_images']['name'][$key], PATHINFO_EXTENSION);
                $file_name = 'interior_' . time() . '_' . uniqid() . '.' . $file_ext;
                $target_path = '../public/images/cars/interior/' . $file_name;
                if (move_uploaded_file($tmp_name, $target_path)) {
                    $img_url = 'public/images/cars/interior/' . $file_name;
                    $stmt = $pdo->prepare("INSERT INTO vehicle_images (vehicle_id, image_url, is_primary, view_type) VALUES (?, ?, 0, 'interior')");
                    $stmt->execute([$vehicle_id, $img_url]);
                }
            }
        }
    }
    
    header("Location: fleet.php?msg=added");
    exit;
}
// Handle Edit Vehicle
if (isset($_POST['edit_vehicle'])) {
    $id = $_POST['vehicle_id'];
    $make = $_POST['make'];
    $model = $_POST['model'];
    $year = $_POST['year'];
    $plate = $_POST['plate_number'];
    $price = $_POST['price_per_day'];
    $capacity = $_POST['capacity'];
    $status = $_POST['status'];
    $vin = $_POST['vin'];
    $fuel = $_POST['fuel_type'];
    $trans = $_POST['transmission'];
    $mileage = $_POST['current_mileage'];
    $ins_exp = $_POST['insurance_expiry'];
    $tax_exp = $_POST['road_tax_expiry'];
    $fit_exp = $_POST['fitness_expiry'];
    $service_km = $_POST['service_due_km'];
    $features = $_POST['features'] ?? '';
    
    // Handle Exterior Image Upload
    $exterior_image_url = $_POST['existing_exterior_image'] ?? 'public/images/cars/default.jpg';
    if (isset($_FILES['exterior_image']) && $_FILES['exterior_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../public/images/cars/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        $file_ext = strtolower(pathinfo($_FILES['exterior_image']['name'], PATHINFO_EXTENSION));
        $file_name = 'car_' . time() . '_' . uniqid() . '.' . $file_ext;
        if (move_uploaded_file($_FILES['exterior_image']['tmp_name'], $upload_dir . $file_name)) {
            $exterior_image_url = 'public/images/cars/' . $file_name;
        }
    }

    // Handle Interior Image Upload
    $interior_image_url = $_POST['existing_interior_image'] ?? null;
    if (isset($_FILES['interior_image']) && $_FILES['interior_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir_int = '../public/images/cars/interior/';
        if (!is_dir($upload_dir_int)) mkdir($upload_dir_int, 0755, true);
        $file_ext = strtolower(pathinfo($_FILES['interior_image']['name'], PATHINFO_EXTENSION));
        $file_name = 'interior_' . time() . '_' . uniqid() . '.' . $file_ext;
        if (move_uploaded_file($_FILES['interior_image']['tmp_name'], $upload_dir_int . $file_name)) {
            $interior_image_url = 'public/images/cars/interior/' . $file_name;
        }
    }

    // Handle Video Upload (if new file provided)
    $video_url = $_POST['existing_video_url'] ?? null;
    if (isset($_FILES['video']) && $_FILES['video']['error'] === UPLOAD_ERR_OK) {
        $upload_dir_vid = '../public/videos/cars/';
        if (!is_dir($upload_dir_vid)) mkdir($upload_dir_vid, 0755, true);
        $file_ext = strtolower(pathinfo($_FILES['video']['name'], PATHINFO_EXTENSION));
        $file_name = 'video_' . time() . '_' . uniqid() . '.' . $file_ext;
        if (move_uploaded_file($_FILES['video']['tmp_name'], $upload_dir_vid . $file_name)) {
            $video_url = 'public/videos/cars/' . $file_name;
        }
    }
    
    $stmt = $pdo->prepare("UPDATE vehicles SET make = ?, model = ?, year = ?, plate_number = ?, price_per_day = ?, capacity = ?, status = ?, vin = ?, fuel_type = ?, transmission = ?, current_mileage = ?, insurance_expiry = ?, road_tax_expiry = ?, fitness_expiry = ?, service_due_km = ?, features = ?, interior_image_url = ?, image_url = ?, video_url = ? WHERE id = ?");
    $stmt->execute([$make, $model, $year, $plate, $price, $capacity, $status, $vin, $fuel, $trans, $mileage, $ins_exp, $tax_exp, $fit_exp, $service_km, $features, $interior_image_url, $exterior_image_url, $video_url, $id]);

    // Handle Multiple Exterior Images (New)
    if (isset($_FILES['exterior_images'])) {
        foreach ($_FILES['exterior_images']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['exterior_images']['error'][$key] === UPLOAD_ERR_OK) {
                $file_ext = pathinfo($_FILES['exterior_images']['name'][$key], PATHINFO_EXTENSION);
                $file_name = 'car_' . time() . '_' . uniqid() . '.' . $file_ext;
                $target_path = '../public/images/cars/' . $file_name;
                if (move_uploaded_file($tmp_name, $target_path)) {
                    $img_url = 'public/images/cars/' . $file_name;
                    $stmt = $pdo->prepare("INSERT INTO vehicle_images (vehicle_id, image_url, is_primary, view_type) VALUES (?, ?, 0, 'exterior')");
                    $stmt->execute([$id, $img_url]);
                }
            }
        }
    }

    // Handle Multiple Interior Images (New)
    if (isset($_FILES['interior_images'])) {
        foreach ($_FILES['interior_images']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['interior_images']['error'][$key] === UPLOAD_ERR_OK) {
                $file_ext = pathinfo($_FILES['interior_images']['name'][$key], PATHINFO_EXTENSION);
                $file_name = 'interior_' . time() . '_' . uniqid() . '.' . $file_ext;
                $target_path = '../public/images/cars/interior/' . $file_name;
                if (move_uploaded_file($tmp_name, $target_path)) {
                    $img_url = 'public/images/cars/interior/' . $file_name;
                    $stmt = $pdo->prepare("INSERT INTO vehicle_images (vehicle_id, image_url, is_primary, view_type) VALUES (?, ?, 0, 'interior')");
                    $stmt->execute([$id, $img_url]);
                }
            }
        }
    }
    
    header("Location: fleet.php?msg=updated");
    exit;
}
// Handle Delete Vehicle
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM vehicles WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    header("Location: fleet.php?msg=deleted");
    exit;
}

// Handle Delete Specific Image
if (isset($_GET['delete_image'])) {
    $img_id = $_GET['delete_image'];
    $v_id = $_GET['v_id'];
    
    // Get image path to delete file
    $stmt = $pdo->prepare("SELECT image_url FROM vehicle_images WHERE id = ?");
    $stmt->execute([$img_id]);
    $img = $stmt->fetch();
    
    if ($img) {
        $file_path = '../' . $img['image_url'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        $stmt = $pdo->prepare("DELETE FROM vehicle_images WHERE id = ?");
        $stmt->execute([$img_id]);
    }
    
    header("Location: fleet.php?msg=img_deleted&v_id=" . $v_id);
    exit;
}

// Handle Add Brand (Integrated from brands.php)
if (isset($_POST['add_brand'])) {
    $name = trim($_POST['name']);
    if (!empty($name)) {
        $stmt = $pdo->prepare("INSERT INTO brands (name) VALUES (?)");
        $stmt->execute([$name]);
        header("Location: fleet.php?msg=brand_added");
        exit;
    }
}

// Handle Brand Deletion
if (isset($_GET['delete_brand'])) {
    $id = (int)$_GET['delete_brand'];
    try {
        $stmt = $pdo->prepare("DELETE FROM brands WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: fleet.php?msg=brand_deleted");
    } catch (PDOException $e) {
        header("Location: fleet.php?msg=brand_error");
    }
    exit;
}

// Fetch brands for dropdowns
$brands_list = $pdo->query("SELECT * FROM brands ORDER BY name ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fleet Control | Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../public/css/style.css">
    <!-- Theme System -->
    <link rel="stylesheet" href="../public/css/theme.css?v=4.0">
    <script src="../public/js/theme-switcher.js?v=4.0"></script>
</head>
<body>
    <?php include_once '../includes/mobile_header.php'; ?>


    <div class="admin-layout">
        <?php include_once '../includes/admin_sidebar.php'; ?>

        <main class="main-content">
            <?php include_once '../includes/toast_notifications.php'; ?>
            
            <?php if(isset($_GET['msg'])): ?>
                <script>
                    document.addEventListener('DOMContentLoaded', () => {
                        const msg = <?php echo json_encode($_GET['msg']); ?>;
                        if (msg === 'added') showToast('Vehicle added to fleet successfully!', 'success');
                        if (msg === 'updated') showToast('Vehicle details updated successfully.', 'success');
                        if (msg === 'deleted') showToast('Vehicle removed from fleet.', 'info');
                        if (msg === 'img_deleted') showToast('Gallery image deleted.', 'info');
                        if (msg === 'brand_added') showToast('New brand manufacturer registered.', 'success');
                        if (msg === 'brand_deleted') showToast('Brand manufacturer removed.', 'info');
                        if (msg === 'brand_error') showToast('Cannot delete brand: It is active in your fleet.', 'error');
                    });
                </script>
            <?php endif; ?>

            <div class="dashboard-header">
                <div>
                    <h1>Fleet Control</h1>
                    <p class="text-secondary">Manage vehicle inventory and status.</p>
                </div>
                <div class="header-actions">
                    <?php include_once '../includes/theme_switcher.php'; ?>
                    <a href="reports.php?type=fleet" class="btn btn-outline" style="border-color: rgba(255,255,255,0.1);"><i class="fas fa-file-export"></i> CSV</a>
                    <button class="btn btn-outline" onclick="openModal('brandModal')"><i class="fas fa-tags"></i> Brands</button>
                    <button class="btn btn-primary" onclick="openModal('vehicleModal')"><i class="fas fa-plus"></i> Add Vehicle</button>
                </div>
            </div>

            <div class="filters" style="margin-bottom: 25px; overflow-x: auto; white-space: nowrap; display: flex; gap: 10px; padding-bottom: 5px;">
                <a href="fleet.php?status=all" class="filter-btn <?php echo $status_filter === 'all' ? 'active' : ''; ?>">All</a>
                <a href="fleet.php?status=available" class="filter-btn <?php echo $status_filter === 'available' ? 'active' : ''; ?>">Available</a>
                <a href="fleet.php?status=hired" class="filter-btn <?php echo $status_filter === 'hired' ? 'active' : ''; ?>">Hired</a>
                <a href="fleet.php?status=booked" class="filter-btn <?php echo $status_filter === 'booked' ? 'active' : ''; ?>">Booked</a>
                <a href="fleet.php?status=maintenance" class="filter-btn <?php echo $status_filter === 'maintenance' ? 'active' : ''; ?>">Repair</a>
            </div>

            <div class="data-card">
                <table class="data-table admin-table">
                    <thead>
                        <tr>
                            <th>Vehicle</th>
                            <th>Price/Day</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($vehicles as $v): ?>
                        <tr>
                            <td data-label="Vehicle">
                                <div class="vehicle-mini">
                                    <img src="<?php echo !empty($v['image_url']) ? '../' . $v['image_url'] : 'https://via.placeholder.com/150?text=Car'; ?>" alt="Car" style="width: 50px; height: 35px;">
                                    <div>
                                        <strong><?php echo $v['make'] . ' ' . $v['model']; ?></strong>
                                        <small style="opacity: 0.5;"><?php echo $v['plate_number']; ?></small>
                                    </div>
                                </div>
                            </td>
                            <td data-label="Price" class="font-bold">ZMW <?php echo number_format($v['price_per_day'], 0); ?></td>
                            <td data-label="Status">
                                <span class="status-pill status-<?php echo $v['status']; ?>">
                                    <?php echo strtoupper($v['status']); ?>
                                </span>
                            </td>
                            <td data-label="Action">
                                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                    <button class="btn btn-outline" style="padding: 6px 12px; font-size: 0.8rem;" onclick='editVehicle(<?php echo htmlspecialchars(json_encode($v)); ?>, <?php echo htmlspecialchars(json_encode($galleries[$v['id']] ?? [])); ?>)'>
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <a href="maintenance.php?vehicle_id=<?php echo $v['id']; ?>&action=log" class="btn btn-outline" style="padding: 6px 12px; font-size: 0.8rem; border-color: var(--accent-color); color: var(--accent-color);">
                                        <i class="fas fa-tools"></i> Log Service
                                    </a>
                                    <a href="fleet.php?delete=<?php echo $v['id']; ?>" class="btn btn-outline text-danger" style="padding: 6px 10px;" onclick="return confirm('Delete this vehicle?')">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Add Vehicle Modal -->
    <div id="vehicleModal" class="modal-overlay">
        <div class="modal-content" style="max-width: 700px;">
            <span class="close-modal" onclick="closeModal('vehicleModal')">&times;</span>
            <div class="modal-header">
                <h2>Add New Vehicle</h2>
            </div>
            <form action="" method="POST" enctype="multipart/form-data">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 12px;">
                    <div class="form-group">
                        <label>Make (Brand)</label>
                        <select name="make" required class="form-control">
                            <option value="">Select Brand</option>
                            <?php foreach($brands_list as $bl): ?>
                                <option value="<?php echo htmlspecialchars($bl['name']); ?>"><?php echo htmlspecialchars($bl['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Model</label>
                        <input type="text" name="model" required class="form-control" placeholder="e.g. Hilux">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 2fr 1fr; gap: 15px; margin-bottom: 12px;">
                    <div class="form-group">
                        <label>Year</label>
                        <input type="number" name="year" required class="form-control" placeholder="2023">
                    </div>
                    <div class="form-group">
                        <label>Plate Number</label>
                        <input type="text" name="plate_number" required class="form-control" placeholder="ABC 1234">
                    </div>
                    <div class="form-group">
                        <label>Capacity</label>
                        <input type="number" name="capacity" required class="form-control" placeholder="5">
                    </div>
                </div>

                <div class="form-group mb-2">
                    <label>VIN / Chassis Number</label>
                    <input type="text" name="vin" required class="form-control" placeholder="VIN123456789">
                </div>

                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 12px;">
                    <div class="form-group">
                        <label>Price/Day (ZMW)</label>
                        <input type="number" name="price_per_day" required class="form-control" placeholder="1200">
                    </div>
                    <div class="form-group">
                        <label>Fuel Type</label>
                        <select name="fuel_type" class="form-control">
                            <option value="Diesel">Diesel</option>
                            <option value="Petrol">Petrol</option>
                            <option value="Hybrid">Hybrid</option>
                            <option value="Electric">Electric</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Transmission</label>
                        <select name="transmission" class="form-control">
                            <option value="Automatic">Automatic</option>
                            <option value="Manual">Manual</option>
                        </select>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 12px;">
                    <div class="form-group">
                        <label>Current Mileage (KM)</label>
                        <input type="number" name="current_mileage" required class="form-control" placeholder="15000">
                    </div>
                    <div class="form-group">
                        <label>Service Due at (KM)</label>
                        <input type="number" name="service_due_km" value="5000" required class="form-control">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 12px;">
                    <div class="form-group">
                        <label>Insurance Exp.</label>
                        <input type="date" name="insurance_expiry" required class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Road Tax Exp.</label>
                        <input type="date" name="road_tax_expiry" required class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Fitness Exp.</label>
                        <input type="date" name="fitness_expiry" required class="form-control">
                    </div>
                </div>

                <!-- PRIMARY THUMBNAIL (Required for the fleet card image) -->
                <div class="form-group mb-3" style="background: rgba(59,130,246,0.08); border: 1px dashed rgba(59,130,246,0.4); border-radius: 12px; padding: 16px;">
                    <label style="display:flex; align-items:center; gap:8px; color:#60a5fa; font-weight:700; margin-bottom:10px;">
                        <i class="fas fa-image"></i> Main Thumbnail Photo <span style="color:#ef4444;">*</span>
                        <small style="color:rgba(255,255,255,0.4); font-weight:400; font-size:0.7rem;">— This is what customers see on the fleet card</small>
                    </label>
                    <div style="display:flex; align-items:center; gap:16px;">
                        <div style="flex:1;">
                            <input type="file" name="exterior_image" id="thumb_upload" accept="image/*" class="form-control" onchange="previewThumb(this)" required>
                            <small style="color:rgba(255,255,255,0.4); font-size:0.72rem;">JPG, PNG or WEBP. Recommended: 800×500px</small>
                        </div>
                        <div id="thumb_preview_wrap" style="display:none; flex-shrink:0;">
                            <img id="thumb_preview" src="" style="width:100px; height:68px; object-fit:cover; border-radius:8px; border:2px solid rgba(59,130,246,0.5);" alt="Preview">
                        </div>
                    </div>
                </div>

                <!-- PRIMARY INTERIOR PHOTO (Add) -->
                <div class="form-group mb-3" style="background: rgba(16,185,129,0.06); border: 1px dashed rgba(16,185,129,0.3); border-radius: 12px; padding: 16px;">
                    <label style="display:flex; align-items:center; gap:8px; color:#10b981; font-weight:700; margin-bottom:10px;">
                        <i class="fas fa-couch"></i> Main Interior Photo <span style="color:#ef4444;">*</span>
                    </label>
                    <div style="display:flex; align-items:center; gap:16px;">
                        <div style="flex:1;">
                            <input type="file" name="interior_image" accept="image/*" class="form-control" onchange="previewInterior(this)" required>
                        </div>
                        <div id="interior_preview_wrap" style="display:none; flex-shrink:0;">
                            <img id="interior_preview" src="" style="width:100px; height:68px; object-fit:cover; border-radius:8px; border:2px solid rgba(16,185,129,0.5);" alt="Preview">
                        </div>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 12px;">
                    <div class="form-group">
                        <label>Extra Gallery Photos</label>
                        <input type="file" name="exterior_images[]" accept="image/*" multiple class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Car Video (MP4)</label>
                        <input type="file" name="video" accept="video/mp4,video/*" class="form-control">
                    </div>
                </div>
                
                <div class="form-group mb-3">
                    <label>Features (Optional)</label>
                    <textarea name="features" rows="2" class="form-control" placeholder="e.g., Leather Seats, AC, Bluetooth, Sunroof, GPS"></textarea>
                    <small style="color: rgba(255,255,255,0.5); font-size: 0.75rem;">Comma-separated list of vehicle features.</small>
                </div>

                <button type="submit" name="add_vehicle" class="btn btn-primary w-100 py-3">Add Vehicle to Fleet</button>
            </form>
        </div>
    </div>    <!-- Brand Management Modal -->
    <div id="brandModal" class="modal-overlay">
        <div class="modal-content" style="max-width: 500px;">
            <span class="close-modal" onclick="closeModal('brandModal')">&times;</span>
            <div class="modal-header">
                <h2>Manage Brands</h2>
            </div>
            <div style="margin-bottom: 20px;">
                <form action="" method="POST" style="display: flex; gap: 10px;">
                    <input type="text" name="name" required class="form-control" placeholder="New Brand Name (e.g. BMW)">
                    <button type="submit" name="add_brand" class="btn btn-primary">Add</button>
                </form>
            </div>
            <div style="max-height: 300px; overflow-y: auto; background: rgba(0,0,0,0.2); border-radius: 10px; padding: 10px;">
                <table class="data-table" style="font-size: 0.9rem;">
                    <thead>
                        <tr>
                            <th>Brand Name</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($brands_list as $b): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($b['name']); ?></td>
                            <td>
                                <a href="fleet.php?delete_brand=<?php echo $b['id']; ?>" class="text-danger" onclick="return confirm('Delete this brand?')"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($brands_list)): ?>
                        <tr><td colspan="2" style="text-align:center; opacity:0.5;">No brands found</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Edit Vehicle Modal -->
    <div id="editVehicleModal" class="modal-overlay">
        <div class="modal-content" style="max-width: 700px;">
            <span class="close-modal" onclick="closeModal('editVehicleModal')">&times;</span>
            <div class="modal-header">
                <h2>Edit Vehicle Details</h2>
            </div>
            <form action="" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="vehicle_id" id="edit_vehicle_id">
                <input type="hidden" name="existing_exterior_image" id="edit_existing_exterior">
                <input type="hidden" name="existing_interior_image" id="edit_existing_interior">
                <input type="hidden" name="existing_video_url" id="edit_existing_video">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 12px;">
                    <div class="form-group">
                        <label>Make (Brand)</label>
                        <select name="make" id="edit_make" required class="form-control">
                            <?php foreach($brands_list as $bl): ?>
                                <option value="<?php echo htmlspecialchars($bl['name']); ?>"><?php echo htmlspecialchars($bl['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Model</label>
                        <input type="text" name="model" id="edit_model" required class="form-control">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 2fr 1fr; gap: 15px; margin-bottom: 12px;">
                    <div class="form-group">
                        <label>Year</label>
                        <input type="number" name="year" id="edit_year" required class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Plate Number</label>
                        <input type="text" name="plate_number" id="edit_plate" required class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Capacity</label>
                        <input type="number" name="capacity" id="edit_capacity" required class="form-control">
                    </div>
                </div>

                <div class="form-group mb-2">
                    <label>VIN / Chassis Number</label>
                    <input type="text" name="vin" id="edit_vin" required class="form-control">
                </div>

                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 12px;">
                    <div class="form-group">
                        <label>Price/Day (ZMW)</label>
                        <input type="number" name="price_per_day" id="edit_price" required class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Fuel Type</label>
                        <select name="fuel_type" id="edit_fuel" class="form-control">
                            <option value="Diesel">Diesel</option>
                            <option value="Petrol">Petrol</option>
                            <option value="Hybrid">Hybrid</option>
                            <option value="Electric">Electric</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Transmission</label>
                        <select name="transmission" id="edit_trans" class="form-control">
                            <option value="Automatic">Automatic</option>
                            <option value="Manual">Manual</option>
                        </select>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 12px;">
                    <div class="form-group">
                        <label>Current Mileage (KM)</label>
                        <input type="number" name="current_mileage" id="edit_mileage" required class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Service Due at (KM)</label>
                        <input type="number" name="service_due_km" id="edit_service_km" required class="form-control">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 12px;">
                    <div class="form-group">
                        <label>Insurance Exp.</label>
                        <input type="date" name="insurance_expiry" id="edit_insurance_expiry" required class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Road Tax Exp.</label>
                        <input type="date" name="road_tax_expiry" id="edit_road_tax_expiry" required class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Fitness Exp.</label>
                        <input type="date" name="fitness_expiry" id="edit_fitness_expiry" required class="form-control">
                    </div>
                </div>

                <div class="form-group mb-3">
                    <label>Status</label>
                    <select name="status" id="edit_status" required class="form-control">
                        <option value="available">Available</option>
                        <option value="hired">Hired</option>
                        <option value="booked">Booked</option>
                        <option value="maintenance">Maintenance</option>
                    </select>
                </div>

                <!-- PRIMARY THUMBNAIL EDIT -->
                <div class="form-group mb-3" style="background: rgba(59,130,246,0.08); border: 1px dashed rgba(59,130,246,0.4); border-radius: 12px; padding: 16px;">
                    <label style="display:flex; align-items:center; gap:8px; color:#60a5fa; font-weight:700; margin-bottom:10px;">
                        <i class="fas fa-image"></i> Change Main Thumbnail Photo
                        <small style="color:rgba(255,255,255,0.4); font-weight:400; font-size:0.7rem;">— (Leave empty to keep current)</small>
                    </label>
                    <div style="display:flex; align-items:center; gap:16px;">
                        <div style="flex:1;">
                            <input type="file" name="exterior_image" class="form-control" onchange="previewEditThumb(this)">
                            <small style="color:rgba(255,255,255,0.4); font-size:0.72rem;">Updates the primary photo shown on fleet cards.</small>
                        </div>
                        <div id="edit_thumb_preview_wrap" style="flex-shrink:0;">
                            <img id="edit_thumb_preview" src="" style="width:100px; height:68px; object-fit:cover; border-radius:8px; border:2px solid rgba(59,130,246,0.5);" alt="Current Main Photo">
                        </div>
                    </div>
                </div>

                <!-- PRIMARY INTERIOR PHOTO EDIT -->
                <div class="form-group mb-3" style="background: rgba(16,185,129,0.06); border: 1px dashed rgba(16,185,129,0.3); border-radius: 12px; padding: 16px;">
                    <label style="display:flex; align-items:center; gap:8px; color:#10b981; font-weight:700; margin-bottom:10px;">
                        <i class="fas fa-couch"></i> Change Main Interior Photo
                    </label>
                    <div style="display:flex; align-items:center; gap:16px;">
                        <div style="flex:1;">
                            <input type="file" name="interior_image" class="form-control" onchange="previewEditInterior(this)">
                            <small style="color:rgba(255,255,255,0.4); font-size:0.72rem;">Updates the primary dashboard/seat photo.</small>
                        </div>
                        <div id="edit_interior_preview_wrap" style="flex-shrink:0;">
                            <img id="edit_interior_preview" src="" style="width:100px; height:68px; object-fit:cover; border-radius:8px; border:2px solid rgba(16,185,129,0.5);" alt="Current Interior">
                        </div>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; margin-bottom: 12px;">
                    <div class="form-group">
                        <label>Add Extra Gallery Photos</label>
                        <input type="file" name="exterior_images[]" accept="image/*" multiple class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Update Car Video</label>
                        <input type="file" name="video" accept="video/mp4,video/*" class="form-control">
                        <div id="current_video_preview" style="margin-top: 10px;"></div>
                    </div>
                </div>

                <div class="form-group mb-3">
                    <label>Features (Optional)</label>
                    <textarea name="features" id="edit_features" rows="2" class="form-control" placeholder="e.g., Leather Seats, AC, Bluetooth, Sunroof, GPS"></textarea>
                </div>

                <div id="gallery_management" style="margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 15px;">
                    <h3 style="font-size: 1rem; margin-bottom: 10px;">Existing Gallery</h3>
                    <div id="gallery_grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 10px;">
                        <!-- Images populated by JS -->
                    </div>
                </div>

                <button type="submit" name="edit_vehicle" class="btn btn-primary w-100 py-3" style="margin-top:20px;">Save Changes</button>
            </form>
        </div>
    </div>

    <style>
        .gallery-item {
            position: relative;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.1);
        }
        .gallery-item img {
            width: 100%;
            height: 80px;
            object-fit: cover;
        }
        .delete-img-btn {
            position: absolute;
            top: 2px;
            right: 2px;
            background: rgba(220, 38, 38, 0.8);
            color: white;
            border: none;
            border-radius: 4px;
            width: 20px;
            height: 20px;
            font-size: 10px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .delete-img-btn:hover {
            background: #dc2626;
        }
        .view-type-tag {
            position: absolute;
            bottom: 2px;
            left: 2px;
            background: rgba(0,0,0,0.6);
            color: white;
            font-size: 8px;
            padding: 2px 4px;
            border-radius: 2px;
        }
    </style>

    <script>
        function openModal(id) {
            document.getElementById(id).style.display = 'flex';
        }
        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
        }

        // Live thumbnail preview in Add Vehicle form
        function previewThumb(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('thumb_preview').src = e.target.result;
                    document.getElementById('thumb_preview_wrap').style.display = 'block';
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Live thumbnail preview in Edit Vehicle form
        function previewEditThumb(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('edit_thumb_preview').src = e.target.result;
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Live interior preview in Add Vehicle form
        function previewInterior(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('interior_preview').src = e.target.result;
                    document.getElementById('interior_preview_wrap').style.display = 'block';
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Live interior preview in Edit Vehicle form
        function previewEditInterior(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('edit_interior_preview').src = e.target.result;
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        function editVehicle(vehicle, gallery) {
            openModal('editVehicleModal');
            document.getElementById('edit_vehicle_id').value = vehicle.id;
            document.getElementById('edit_make').value = vehicle.make;
            document.getElementById('edit_model').value = vehicle.model;
            document.getElementById('edit_year').value = vehicle.year;
            document.getElementById('edit_plate').value = vehicle.plate_number;
            document.getElementById('edit_price').value = vehicle.price_per_day;
            document.getElementById('edit_capacity').value = vehicle.capacity;
            document.getElementById('edit_status').value = vehicle.status;
            
            // New Compliance Fields
            document.getElementById('edit_vin').value = vehicle.vin || '';
            document.getElementById('edit_fuel').value = vehicle.fuel_type || 'Diesel';
            document.getElementById('edit_trans').value = vehicle.transmission || 'Automatic';
            document.getElementById('edit_mileage').value = vehicle.current_mileage || 0;
            document.getElementById('edit_service_km').value = vehicle.service_due_km || 5000;
            document.getElementById('edit_insurance_expiry').value = vehicle.insurance_expiry || '';
            document.getElementById('edit_road_tax_expiry').value = vehicle.road_tax_expiry || '';
            document.getElementById('edit_fitness_expiry').value = vehicle.fitness_expiry || '';
            
            // Interior & Exterior Specs Fields
            document.getElementById('edit_features').value = vehicle.features || '';
            document.getElementById('edit_existing_interior').value = vehicle.interior_image_url || '';
            document.getElementById('edit_existing_exterior').value = vehicle.image_url || 'public/images/cars/default.jpg';
            
            // Preview Primary Interior
            const edit_int = document.getElementById('edit_interior_preview');
            const intPath = vehicle.interior_image_url ? '../' + vehicle.interior_image_url : 'https://via.placeholder.com/150?text=No+Interior';
            edit_int.src = intPath;
            
            // Populate Gallery Grid
            const galleryGrid = document.getElementById('gallery_grid');
            galleryGrid.innerHTML = '';
            
            if (gallery && gallery.length > 0) {
                gallery.forEach(img => {
                    const div = document.createElement('div');
                    div.className = 'gallery-item';
                    div.innerHTML = `
                        <img src="../${img.image_url}" alt="Gallery">
                        <span class="view-type-tag">${img.view_type}</span>
                        <button type="button" class="delete-img-btn" onclick="deleteImage(${img.id}, ${vehicle.id})">
                            <i class="fas fa-times"></i>
                        </button>
                    `;
                    galleryGrid.appendChild(div);
                });
            } else {
                galleryGrid.innerHTML = '<p style="grid-column: 1/-1; opacity: 0.5; font-size: 0.8rem;">No gallery images yet.</p>';
            }

            // Preview Primary Photo
            const edit_thumb = document.getElementById('edit_thumb_preview');
            const mainPath = vehicle.image_url ? '../' + vehicle.image_url : 'https://via.placeholder.com/150?text=No+Main+Image';
            edit_thumb.src = mainPath;
            
            // Hidden fields for persistence
            document.getElementById('edit_existing_exterior').value = vehicle.image_url || '';
            document.getElementById('edit_existing_video').value = vehicle.video_url || '';

            // Show current video preview
            const videoPreviewDiv = document.getElementById('current_video_preview');
            if (vehicle.video_url) {
                videoPreviewDiv.innerHTML = `
                    <div style="position: relative; width: 100px;">
                        <video src="../${vehicle.video_url}" style="width: 100%; border-radius: 8px; margin-top: 5px;" muted></video>
                    </div>
                `;
            } else {
                videoPreviewDiv.innerHTML = '<small style="color: rgba(255,255,255,0.4);">No video uploaded yet.</small>';
            }
            
            openModal('editVehicleModal');
        }

        function deleteImage(imgId, vehicleId) {
            if (confirm('Are you sure you want to remove this image from the gallery?')) {
                window.location.href = `fleet.php?delete_image=${imgId}&v_id=${vehicleId}`;
            }
        }

        // Auto-trigger if action=add is in URL or open modal for specific vehicle back from deletion
        window.onload = function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('action') === 'add') {
                openModal('vehicleModal');
            }
            
            // If we just deleted an image, we might want to stay in the edit modal
            const v_id = urlParams.get('v_id');
            if (v_id) {
                // This is a bit tricky since we need the JSON data. 
                // In a real app, we'd use AJAX to open it. 
                // For now, let's just show a success message.
            }
        }
        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal-overlay')) {
                event.target.style.display = 'none';
            }
        }
    </script>
    <?php include_once '../includes/mobile_nav.php'; ?>
</body>
</html>
