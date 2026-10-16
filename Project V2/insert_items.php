<?php

session_start();

if (!isset($_SESSION['staff_id'])) {
    header("Location: loginstaff.php");
    exit();
}


$servername = "localhost";
$username = "root";
$password = "";
$dbname = "projectdb";

$conn = mysqli_connect($servername, $username, $password, $dbname);

if (!$conn) {
    die("Error: " . mysqli_connect_error());
}


$message = '';
$message_type = '';


$upload_dir = "uploads/";
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}


$material_list = [];
$material_sql = "SELECT mid, mname FROM materials ORDER BY mname";
$material_result = mysqli_query($conn, $material_sql);
if ($material_result) {
    while ($row = mysqli_fetch_assoc($material_result)) {
        $material_list[] = $row;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fname = trim($_POST['itemName']);
    $fdesc = trim($_POST['description']);
    $fprice = trim($_POST['price']);
    $selected_materials = isset($_POST['material']) ? $_POST['material'] : [];
    $selected_quantities = isset($_POST['quantity']) ? $_POST['quantity'] : [];


    $valid_materials = [];
    foreach ($selected_materials as $index => $mname) {
        $mname = trim($mname);
        $qty = isset($selected_quantities[$index]) ? intval($selected_quantities[$index]) : 0;
        if ($mname !== '' && $qty > 0) {
            $valid_materials[] = ['mname' => $mname, 'qty' => $qty];
        }
    }


    $material_id_map = [];
    foreach ($material_list as $m) {
        $material_id_map[$m['mname']] = $m['mid'];
    }


    if (empty($valid_materials)) {
        $message = "❌ Please select at least one material with quantity > 0！";
        $message_type = "error";
    } else {

        mysqli_begin_transaction($conn);

        try {

            $fimage = '';
            if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $file_type = $_FILES['image']['type'];

                if (in_array($file_type, $allowed_types)) {
                    $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                    $new_filename = time() . '_' . uniqid() . '.' . $file_extension;
                    $upload_path = $upload_dir . $new_filename;

                    if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                        $fimage = $upload_path; // 只記錄路徑，但不存入資料庫
                    }
                }
            }


            $fname = mysqli_real_escape_string($conn, $fname);
            $fdesc = mysqli_real_escape_string($conn, $fdesc);
            $fprice = mysqli_real_escape_string($conn, $fprice);

            $sql = "INSERT INTO furnitures (fname, fdesc, fprice) 
                    VALUES ('$fname', '$fdesc', '$fprice')";

            if (!mysqli_query($conn, $sql)) {
                throw new Exception("Failed to insert furniture: " . mysqli_error($conn));
            }


            $fid = mysqli_insert_id($conn);


            $insert_count = 0;
            foreach ($valid_materials as $item) {
                $mname = $item['mname'];
                $qty = $item['qty'];

                if (isset($material_id_map[$mname])) {
                    $mid = $material_id_map[$mname];
                    $pmqty = intval($qty);

                    $relation_sql = "INSERT INTO furniturematerials (fid, mid, pmqty) 
                                     VALUES ('$fid', '$mid', '$pmqty')";

                    if (mysqli_query($conn, $relation_sql)) {
                        $insert_count++;
                    } else {
                        throw new Exception("Failed to insert material relation: " . mysqli_error($conn));
                    }
                }
            }

            if ($insert_count > 0) {
                mysqli_commit($conn);

                if ($fimage) {
                    $message = "✅ Product added successfully！Added $insert_count material(s). Image saved to: $fimage (not stored in DB)";
                } else {
                    $message = "✅ Product added successfully！Added $insert_count material(s).";
                }
                $message_type = "success";
            } else {
                throw new Exception("No valid materials were added.");
            }

        } catch (Exception $e) {
            mysqli_rollback($conn);
            $message = "❌ Failed！" . $e->getMessage();
            $message_type = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Insert Items - Premium Living</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f0f0f0;
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 250px;
            background: #141414;
            color: white;
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            overflow-y: auto;
            z-index: 100;
        }

        .sidebar-header {
            padding: 25px 20px;
            border-bottom: 1px solid #333;
            text-align: center;
        }

        .sidebar-logo {
            width: 80px;
            height: 80px;
            object-fit: contain;
            border-radius: 12px;
            background: white;
            padding: 6px;
            margin-bottom: 10px;
        }

        .sidebar-header h2 {
            font-size: 20px;
            font-weight: 600;
            color: #fff;
        }

        .sidebar-nav {
            flex: 1;
            padding: 20px 0;
        }

        .sidebar-nav ul {
            list-style: none;
        }

        .sidebar-nav ul li {
            margin: 4px 12px;
        }

        .sidebar-nav ul li a {
            display: block;
            padding: 12px 18px;
            color: #ccc;
            text-decoration: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 500;
            transition: 0.2s;
        }

        .sidebar-nav ul li a:hover {
            background: #2a2a2a;
            color: white;
        }

        .sidebar-nav ul li a.active {
            background: #2a2a2a;
            color: white;
            font-weight: 600;
        }

        .staff-info {
            padding: 12px 18px;
            color: #aaa;
            font-size: 14px;
            border-bottom: 1px solid #333;
            margin-bottom: 8px;
        }

        .sidebar-footer {
            padding: 20px;
            border-top: 1px solid #333;
        }

        .logout-btn {
            display: block;
            padding: 12px 18px;
            color: #ff6b6b;
            text-decoration: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 500;
            text-align: center;
        }

        .logout-btn:hover {
            background: #2a1a1a;
            color: #ff4444;
        }

        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 30px 35px;
            background: #f5f5f5;
            min-height: 100vh;
        }

        .page-header {
            background: white;
            padding: 25px 30px;
            border-radius: 20px;
            margin-bottom: 24px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
        }

        .page-header h2 {
            font-size: 24px;
            font-weight: 700;
            color: #141414;
        }

        .form-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            font-size: 14px;
            color: #2c2c2c;
            margin-bottom: 6px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 10px 14px;
            border: 1px solid #ddd;
            border-radius: 12px;
            font-size: 14px;
            outline: none;
            transition: 0.2s;
            font-family: Arial, sans-serif;
            background: #fafafa;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #141414;
            box-shadow: 0 0 0 3px rgba(20,20,20,0.06);
            background: white;
        }

        .form-group textarea {
            height: 80px;
            resize: vertical;
            width: 100%;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 10px;
        }

        .btn-submit {
            background: #141414;
            color: white;
            border: none;
            padding: 12px 36px;
            border-radius: 60px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: 0.15s;
            font-family: Arial, sans-serif;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-submit:hover {
            background: #333;
        }

        .btn-cancel {
            background: transparent;
            border: 1px solid #ccc;
            padding: 11px 28px;
            border-radius: 60px;
            font-weight: 500;
            font-size: 14px;
            cursor: pointer;
            color: #2f2f2f;
            transition: 0.15s;
            font-family: Arial, sans-serif;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-cancel:hover {
            background: #f0efea;
            border-color: #aaa;
        }

        .btn-add-material {
            background: #e8f0fe;
            color: #1a73e8;
            border: 1px dashed #1a73e8;
            padding: 8px 16px;
            border-radius: 12px;
            font-weight: 500;
            font-size: 13px;
            cursor: pointer;
            transition: 0.15s;
            font-family: Arial, sans-serif;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 8px;
        }

        .btn-add-material:hover {
            background: #d2e3fc;
        }

        .btn-remove-material {
            background: #fee8e8;
            color: #d32f2f;
            border: none;
            padding: 4px 10px;
            border-radius: 8px;
            font-size: 12px;
            cursor: pointer;
            font-family: Arial, sans-serif;
            margin-left: 8px;
            flex-shrink: 0;
        }

        .btn-remove-material:hover {
            background: #fccccc;
        }

        .material-select-group {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
            gap: 8px;
            flex-wrap: wrap;
        }

        .material-select-group select {
            flex: 2;
            min-width: 150px;
        }

        .quantity-control {
            display: flex;
            align-items: center;
            gap: 4px;
            background: #f5f5f5;
            border-radius: 8px;
            padding: 2px;
            border: 1px solid #ddd;
        }

        .quantity-control button {
            width: 28px;
            height: 28px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            background: transparent;
            color: #333;
            transition: 0.15s;
            font-family: Arial, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .quantity-control button:hover {
            background: #e0e0e0;
        }

        .quantity-control button:active {
            background: #ccc;
        }

        .quantity-control input {
            width: 40px;
            height: 28px;
            border: none;
            text-align: center;
            font-size: 14px;
            font-weight: 600;
            background: transparent;
            outline: none;
            font-family: Arial, sans-serif;
            padding: 0;
        }

        .quantity-control input::-webkit-inner-spin-button,
        .quantity-control input::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        .quantity-control input[type=number] {
            -moz-appearance: textfield;
        }

        .msg {
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .material-count-hint {
            font-size: 12px;
            color: #888;
            margin-top: 4px;
        }

        /* ===== 圖片上傳樣式 ===== */
        .image-upload-area {
            border: 2px dashed #ddd;
            border-radius: 16px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: 0.2s;
            background: #fafafa;
            position: relative;
        }

        .image-upload-area:hover {
            border-color: #141414;
            background: #f5f5f5;
        }

        .image-upload-area .upload-icon {
            font-size: 40px;
            display: block;
            margin-bottom: 8px;
        }

        .image-upload-area .upload-text {
            font-size: 14px;
            color: #888;
        }

        .image-upload-area .upload-text strong {
            color: #141414;
        }

        .image-upload-area input[type="file"] {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }

        .image-preview {
            display: none;
            margin-top: 12px;
            padding: 10px;
            background: #f5f5f5;
            border-radius: 12px;
            align-items: center;
            gap: 16px;
        }


        .image-preview img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #ddd;
        }

        .image-preview .file-info {
            flex: 1;
            text-align: left;
        }

        .image-preview .file-info .file-name {
            font-weight: 600;
            font-size: 14px;
            color: #141414;
        }

        .image-preview .file-info .file-size {
            font-size: 12px;
            color: #888;
        }

        .image-preview .btn-remove-image {
            background: #fee8e8;
            color: #d32f2f;
            border: none;
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 12px;
            cursor: pointer;
            font-family: Arial, sans-serif;
            transition: 0.15s;
        }

        .image-preview .btn-remove-image:hover {
            background: #fccccc;
        }


        @media (max-width: 768px) {
            .sidebar { width: 200px; }
            .main-content { margin-left: 200px; padding: 20px; }
            .form-row { grid-template-columns: 1fr; gap: 0; }
            .material-select-group {
                flex-direction: column;
                align-items: stretch;
            }
            .material-select-group select {
                flex: 1;
            }
            .quantity-control {
                align-self: flex-start;
            }
            .image-preview {
                flex-direction: column;
                text-align: center;
            }
            .image-preview .file-info {
                text-align: center;
            }
        }

        @media (max-width: 480px) {
            .sidebar { width: 100%; height: auto; position: relative; }
            .main-content { margin-left: 0; padding: 16px; }
            .dashboard-container { flex-direction: column; }
            .form-actions { flex-direction: column; }
            .form-actions button { width: 100%; justify-content: center; }
        }
    </style>
</head>
<body>

<div class="dashboard-container">

    <aside class="sidebar">
        <div class="sidebar-header">
            <img src="logo.png" alt="Logo" class="sidebar-logo">
            <h2>Staff Portal</h2>
        </div>

        <div class="staff-info">
            👤 Welcome, <?php echo htmlspecialchars(isset($_SESSION['staff_name']) ? $_SESSION['staff_name'] : 'Staff'); ?>
        </div>

        <nav class="sidebar-nav">
            <ul>
                <li><a href="staff_dashboard.php">🏠 Dashboard</a></li>
                <li><a href="insert_items.php" class="active">➕ Insert Item</a></li>
                <li><a href="material_list.php">🧱 Material</a></li>
                <li><a href="update_order.php">📝 Update Orders</a></li>
                <li><a href="report_dashboard.php">📊 Generate Report</a></li>
                <li><a href="delete_item.php">🗑️ Delete</a></li>
            </ul>
        </nav>

        <div class="sidebar-footer">
            <a href="logout.php" class="logout-btn">🚪 Logout</a>
        </div>
    </aside>

    <main class="main-content">

        <div class="page-header">
            <h2>➕ Insert New Furniture Item</h2>
        </div>

        <div class="form-card">

            <?php if ($message) { ?>
                <div class="msg <?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php } ?>

            <form method="POST" action="" enctype="multipart/form-data" id="itemForm">

                <div class="form-group">
                    <label for="itemName">Furniture Name</label>
                    <input type="text" id="itemName" name="itemName" placeholder="e.g. Modern Oak Dining Table" style="width:100%;" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="price">Price (HKD)</label>
                        <input type="number" id="price" name="price" placeholder="e.g. 5999" step="0.01" style="width:100%;" required>
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" placeholder="Enter item description..."></textarea>
                    </div>
                </div>


                <div class="form-group">
                    <label for="image">📷 Product Image</label>
                    <div class="image-upload-area" id="imageUploadArea">
                        <span class="upload-icon">🖼️</span>
                        <span class="upload-text">Drag & drop or <strong>click to browse</strong></span>
                        <span class="upload-text" style="display:block; font-size:12px; color:#aaa; margin-top:4px;">
                            Supported: JPEG, PNG, GIF, WEBP (Max 5MB)
                        </span>
                        <input type="file" id="image" name="image" accept="image/*" onchange="previewImage(this)">
                    </div>
                    <div class="image-preview" id="imagePreview">
                        <img id="previewImg" src="#" alt="Preview">
                        <div class="file-info">
                            <div class="file-name" id="fileName">image.jpg</div>
                            <div class="file-size" id="fileSize">0 KB</div>
                        </div>
                        <button type="button" class="btn-remove-image" onclick="removeImage()">✕ Remove</button>
                    </div>

                </div>

                <div class="form-group">
                    <label>Materials (select at least one)</label>
                    <div id="materialContainer">
                        <div class="material-select-group" data-index="0">
                            <select name="material[]" class="material-select" required>
                                <option value="">Select Material</option>
                                <?php foreach ($material_list as $material): ?>
                                    <option value="<?php echo htmlspecialchars($material['mname']); ?>">
                                        <?php echo htmlspecialchars($material['mname']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="quantity-control">
                                <button type="button" onclick="changeQuantity(this, -1)">−</button>
                                <input type="number" name="quantity[]" value="1" min="1" max="999" class="qty-input">
                                <button type="button" onclick="changeQuantity(this, 1)">+</button>
                            </div>
                            <button type="button" class="btn-remove-material" onclick="removeMaterial(this)" style="display:none;">✕</button>
                        </div>
                    </div>
                    <button type="button" class="btn-add-material" onclick="addMaterial()">➕ Add Material</button>
                    <div class="material-count-hint">💡 You can add multiple materials with different quantities.</div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-submit">➕ Add Item</button>
                    <button type="reset" class="btn-cancel">↩️ Reset</button>
                </div>

            </form>
        </div>

    </main>
</div>

<script>

    const materialOptions = [
        <?php
        echo "{value: '', label: 'Select Material'}";
        foreach ($material_list as $m) {
            echo ", {value: '" . $m['mname'] . "', label: '" . $m['mname'] . "'}";
        }
        ?>
    ];


    function getMaterialOptionsHTML(selectedValue = '') {
        return materialOptions.map(opt =>
            `<option value="${opt.value}" ${opt.value === selectedValue ? 'selected' : ''}>${opt.label}</option>`
        ).join('');
    }


    function changeQuantity(btn, delta) {
        const group = btn.closest('.material-select-group');
        const input = group.querySelector('.qty-input');
        let val = parseInt(input.value) || 1;
        val = Math.max(1, Math.min(999, val + delta));
        input.value = val;
    }


    function addMaterial() {
        const container = document.getElementById('materialContainer');
        const selects = container.querySelectorAll('.material-select-group');


        selects.forEach(group => {
            const removeBtn = group.querySelector('.btn-remove-material');
            if (removeBtn) removeBtn.style.display = 'inline-block';
        });


        const newGroup = document.createElement('div');
        newGroup.className = 'material-select-group';
        newGroup.innerHTML = `
            <select name="material[]" class="material-select">
                ${getMaterialOptionsHTML()}
            </select>
            <div class="quantity-control">
                <button type="button" onclick="changeQuantity(this, -1)">−</button>
                <input type="number" name="quantity[]" value="1" min="1" max="999" class="qty-input">
                <button type="button" onclick="changeQuantity(this, 1)">+</button>
            </div>
            <button type="button" class="btn-remove-material" onclick="removeMaterial(this)">✕</button>
        `;
        container.appendChild(newGroup);


        const newSelect = newGroup.querySelector('.material-select');
        newSelect.focus();
    }


    function removeMaterial(btn) {
        const group = btn.parentElement;
        const container = document.getElementById('materialContainer');


        if (container.querySelectorAll('.material-select-group').length <= 1) {
            const select = group.querySelector('.material-select');
            const qtyInput = group.querySelector('.qty-input');
            select.value = '';
            qtyInput.value = 1;
            btn.style.display = 'none';
            return;
        }

        group.remove();


        const remaining = container.querySelectorAll('.material-select-group');
        if (remaining.length === 1) {
            const lastRemoveBtn = remaining[0].querySelector('.btn-remove-material');
            if (lastRemoveBtn) lastRemoveBtn.style.display = 'none';
        }
    }

    // ===== 圖片預覽功能 =====
    function previewImage(input) {
        const preview = document.getElementById('imagePreview');
        const previewImg = document.getElementById('previewImg');
        const fileName = document.getElementById('fileName');
        const fileSize = document.getElementById('fileSize');

        if (input.files && input.files[0]) {
            const file = input.files[0];
            const reader = new FileReader();

            reader.onload = function(e) {
                previewImg.src = e.target.result;
                preview.classList.add('show');
                fileName.textContent = file.name;
                fileSize.textContent = (file.size / 1024).toFixed(1) + ' KB';
            };

            reader.readAsDataURL(file);
        }
    }

    function removeImage() {
        const fileInput = document.getElementById('image');
        const preview = document.getElementById('imagePreview');

        fileInput.value = '';
        preview.classList.remove('show');
        document.getElementById('previewImg').src = '#';
    }


    document.addEventListener('DOMContentLoaded', function() {
        const container = document.getElementById('materialContainer');
        const groups = container.querySelectorAll('.material-select-group');
        if (groups.length === 1) {
            const removeBtn = groups[0].querySelector('.btn-remove-material');
            if (removeBtn) removeBtn.style.display = 'none';
        }
    });
</script>

</body>
</html>