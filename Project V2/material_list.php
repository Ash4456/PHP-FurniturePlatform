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

$update_message = '';
$update_message_type = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_stock'])) {
    $mid = intval($_POST['mid']);
    $new_qty = intval($_POST['new_qty']);

    if ($new_qty < 0) {
        $update_message = " Quantity cannot be negative!";
        $update_message_type = "error";
    } else {
        $sql = "UPDATE materials SET mqty = $new_qty WHERE mid = $mid";
        if (mysqli_query($conn, $sql)) {
            $update_message = "✅ Stock updated successfully!";
            $update_message_type = "success";
        } else {
            $update_message = "❌ Failed to update stock: " . mysqli_error($conn);
            $update_message_type = "error";
        }
    }
}


$material_list = [];
$sql = "SELECT mid, mname, mqty, munit FROM materials ORDER BY mid";
$result = mysqli_query($conn, $sql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $material_list[] = $row;
    }
}


$total_materials = count($material_list);


$low_stock_count = 0;

$in_stock_count = 0;

$out_of_stock_count = 0;

foreach ($material_list as $m) {
    if ($m['mqty'] <= 0) {
        $out_of_stock_count++;
    } elseif ($m['mqty'] < 20) {
        $low_stock_count++;
    } else {
        $in_stock_count++;
    }
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Material List - Staff Dashboard</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

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

        .content-header {
            background: white;
            padding: 25px 30px;
            border-radius: 20px;
            margin-bottom: 24px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        .content-header h1 {
            font-size: 24px;
            font-weight: 700;
            color: #141414;
        }

        .header-actions {
            display: flex;
            gap: 12px;
        }

        .btn {
            padding: 10px 24px;
            border-radius: 60px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: none;
            font-family: Arial, sans-serif;
            transition: 0.15s;
        }

        .btn-primary {
            background: #141414;
            color: white;
        }

        .btn-primary:hover {
            background: #333;
        }

        .btn-sm {
            padding: 6px 14px;
            font-size: 12px;
            border-radius: 40px;
        }

        .btn-edit {
            background: #fff3e0;
            color: #e65100;
        }

        .btn-edit:hover {
            background: #ffe0b2;
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 20px 24px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .stat-card .stat-icon {
            font-size: 32px;
        }

        .stat-card .stat-info {
            display: flex;
            flex-direction: column;
        }

        .stat-card .stat-number {
            font-size: 28px;
            font-weight: 700;
            color: #141414;
        }

        .stat-card .stat-label {
            font-size: 14px;
            color: #888;
        }

        .stat-card.stat-warning .stat-number { color: #e65100; }
        .stat-card.stat-success .stat-number { color: #2e7d32; }
        .stat-card.stat-danger .stat-number { color: #c0392b; }

        /* ===== 過濾區域 ===== */
        .filter-section {
            background: white;
            border-radius: 16px;
            padding: 20px 24px;
            margin-bottom: 24px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            align-items: center;
        }

        .search-box {
            display: flex;
            flex: 1;
            min-width: 200px;
        }

        .search-box input {
            flex: 1;
            padding: 10px 16px;
            border: 1px solid #ddd;
            border-radius: 40px 0 0 40px;
            font-size: 14px;
            outline: none;
            font-family: Arial, sans-serif;
        }

        .search-box input:focus {
            border-color: #141414;
        }

        .btn-search {
            background: #141414;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 0 40px 40px 0;
            cursor: pointer;
            font-size: 14px;
            font-family: Arial, sans-serif;
        }

        .btn-search:hover {
            background: #333;
        }

        .filter-group {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .filter-select {
            padding: 10px 14px;
            border: 1px solid #ddd;
            border-radius: 40px;
            font-size: 14px;
            outline: none;
            background: #fafafa;
            font-family: Arial, sans-serif;
            cursor: pointer;
        }

        .filter-select:focus {
            border-color: #141414;
        }


        .data-table-section {
            background: white;
            border-radius: 16px;
            padding: 20px 24px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
            overflow-x: auto;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        .data-table th {
            background: #f8f8f8;
            padding: 12px 14px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #eee;
        }

        .data-table td {
            padding: 12px 14px;
            border-bottom: 1px solid #f0f0f0;
            color: #333;
        }

        .data-table tr:hover td {
            background: #fafafa;
        }

        .data-table .row-warning td {
            background: #fff8e1;
        }

        .data-table .row-danger td {
            background: #fce4ec;
        }

        .data-table .action-buttons {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }


        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }


        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #888;
        }

        .empty-state .icon {
            font-size: 48px;
            margin-bottom: 12px;
        }

        .empty-state h3 {
            color: #333;
            margin-bottom: 8px;
        }


        .alert-section {
            margin-top: 24px;
        }

        .alert {
            background: #fff3e0;
            border-radius: 16px;
            padding: 16px 24px;
            border: 1px solid #ffe0b2;
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }

        .alert .alert-icon {
            font-size: 24px;
        }

        .alert .alert-content {
            flex: 1;
        }

        .alert .alert-content h4 {
            color: #e65100;
            margin-bottom: 4px;
        }

        .alert .alert-content p {
            color: #555;
            font-size: 14px;
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

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 999;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: white;
            border-radius: 24px;
            max-width: 450px;
            width: 90%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }

        .modal-header {
            padding: 20px 24px 16px;
            border-bottom: 2px solid #eaeaea;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h2 {
            font-size: 20px;
            font-weight: 700;
            color: #141414;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 28px;
            cursor: pointer;
            color: #888;
            transition: 0.15s;
        }

        .modal-close:hover {
            color: #141414;
        }

        .modal-body {
            padding: 24px;
        }

        .modal-body .form-group {
            margin-bottom: 16px;
        }

        .modal-body .form-group label {
            display: block;
            font-weight: 600;
            font-size: 14px;
            color: #2c2c2c;
            margin-bottom: 4px;
        }

        .modal-body .form-group input {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #ddd;
            border-radius: 12px;
            font-size: 14px;
            outline: none;
            transition: 0.2s;
            font-family: Arial, sans-serif;
        }

        .modal-body .form-group input:focus {
            border-color: #141414;
            box-shadow: 0 0 0 3px rgba(20,20,20,0.06);
        }

        .modal-body .current-stock {
            background: #f5f5f5;
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 14px;
            color: #555;
            margin-bottom: 12px;
        }

        .modal-body .current-stock strong {
            color: #141414;
        }

        .modal-footer {
            padding: 16px 24px 20px;
            border-top: 1px solid #eaeaea;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }

        .btn-modal-save {
            background: #141414;
            color: white;
            border: none;
            padding: 10px 28px;
            border-radius: 60px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: 0.15s;
            font-family: Arial, sans-serif;
        }

        .btn-modal-save:hover {
            background: #333;
        }

        .btn-modal-cancel {
            background: transparent;
            border: 1px solid #ccc;
            padding: 10px 24px;
            border-radius: 60px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            color: #2f2f2f;
            transition: 0.15s;
            font-family: Arial, sans-serif;
        }

        .btn-modal-cancel:hover {
            background: #f0efea;
            border-color: #aaa;
        }


        @media (max-width: 768px) {
            .sidebar { width: 200px; }
            .main-content { margin-left: 200px; padding: 20px; }
            .stats-row { grid-template-columns: 1fr 1fr; }
            .filter-section { flex-direction: column; align-items: stretch; }
            .search-box { width: 100%; }
            .filter-group { flex-wrap: wrap; }
            .filter-group .filter-select { flex: 1; }
            .modal-content { width: 95%; }
        }

        @media (max-width: 480px) {
            .sidebar { width: 100%; height: auto; position: relative; }
            .main-content { margin-left: 0; padding: 16px; }
            .dashboard-container { flex-direction: column; }
            .stats-row { grid-template-columns: 1fr; }
            .content-header { flex-direction: column; text-align: center; }
            .header-actions { width: 100%; justify-content: center; }
            .data-table { font-size: 12px; }
            .data-table th, .data-table td { padding: 8px 10px; }
            .modal-footer { flex-direction: column; }
            .modal-footer button { width: 100%; justify-content: center; }
        }
    </style>
</head>
<body>
<div class="dashboard-container">

    <!-- 側邊欄 -->
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
                <li><a href="insert_items.php">➕ Insert Item</a></li>
                <li><a href="material_list.php" class="active">🧱 Materials</a></li>
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

        <div class="content-header">
            <h1>📦 Material List</h1>
            <div class="header-actions">
                <a href="insert_material.php" class="btn btn-primary">Add New Material</a>
            </div>
        </div>


        <?php if ($update_message) { ?>
            <div class="msg <?php echo $update_message_type; ?>">
                <?php echo $update_message; ?>
            </div>
        <?php } ?>


        <section class="stats-row">
            <div class="stat-card">
                <div class="stat-icon">📦</div>
                <div class="stat-info">
                    <span class="stat-number"><?php echo $total_materials; ?></span>
                    <span class="stat-label">Total Materials</span>
                </div>
            </div>
            <div class="stat-card <?php echo $low_stock_count > 0 ? 'stat-warning' : 'stat-success'; ?>">
                <div class="stat-icon">⚠️</div>
                <div class="stat-info">
                    <span class="stat-number"><?php echo $low_stock_count; ?></span>
                    <span class="stat-label">Low Stock</span>
                </div>
            </div>
            <div class="stat-card stat-success">
                <div class="stat-icon">✅</div>
                <div class="stat-info">
                    <span class="stat-number"><?php echo $in_stock_count; ?></span>
                    <span class="stat-label">In Stock</span>
                </div>
            </div>
            <div class="stat-card <?php echo $out_of_stock_count > 0 ? 'stat-danger' : 'stat-success'; ?>">
                <div class="stat-icon"></div>
                <div class="stat-info">
                    <span class="stat-number"><?php echo $out_of_stock_count; ?></span>
                    <span class="stat-label">Out of Stock</span>
                </div>
            </div>
        </section>


        <section class="filter-section">
            <div class="search-box">
                <input type="text" placeholder="Search by Material ID or Name..." class="search-input" id="searchInput" onkeyup="filterTable()">
                <button class="btn-search" onclick="filterTable()">🔍 Search</button>
            </div>
            <div class="filter-group">
                <select class="filter-select" id="statusFilter" onchange="filterTable()">
                    <option value="">All Status</option>
                    <option value="in-stock">In Stock</option>
                    <option value="low-stock">Low Stock</option>
                    <option value="out-of-stock">Out of Stock</option>
                </select>
            </div>
        </section>


        <section class="data-table-section">
            <table class="data-table" id="materialTable">
                <thead>
                <tr>
                    <th>Material ID</th>
                    <th>Name</th>
                    <th>Unit</th>
                    <th>Stock</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($material_list)) { ?>
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <div class="icon">📭</div>
                                <h3>No materials found</h3>
                                <p>Click "Add New Material" to add your first material.</p>
                            </div>
                        </td>
                    </tr>
                <?php } else { ?>
                    <?php foreach ($material_list as $material) {
                        $status = 'in-stock';
                        $status_text = 'In Stock';
                        $row_class = '';

                        if ($material['mqty'] <= 0) {
                            $status = 'out-of-stock';
                            $status_text = 'Out of Stock';
                            $row_class = 'row-danger';
                        } elseif ($material['mqty'] < 20) {
                            $status = 'low-stock';
                            $status_text = 'Low Stock';
                            $row_class = 'row-warning';
                        }
                        ?>
                        <tr class="<?php echo $row_class; ?>" data-status="<?php echo $status; ?>" data-name="<?php echo strtolower($material['mname']); ?>" data-id="<?php echo $material['mid']; ?>">
                            <td><strong>#<?php echo $material['mid']; ?></strong></td>
                            <td><?php echo htmlspecialchars($material['mname']); ?></td>
                            <td><?php echo htmlspecialchars($material['munit']); ?></td>
                            <td><strong id="stock_display_<?php echo $material['mid']; ?>"><?php echo $material['mqty']; ?></strong></td>
                            <td><span class="status-badge status-<?php echo $status; ?>"><?php echo $status_text; ?></span></td>
                            <td class="action-buttons">
                                <button class="btn btn-edit btn-sm" onclick="openEditModal(<?php echo $material['mid']; ?>, '<?php echo addslashes($material['mname']); ?>', <?php echo $material['mqty']; ?>, '<?php echo $material['munit']; ?>')">
                                    ✏️ Edit
                                </button>
                            </td>
                        </tr>
                    <?php } ?>
                <?php } ?>
                </tbody>
            </table>
        </section>

        <?php if ($low_stock_count > 0 || $out_of_stock_count > 0) { ?>
            <section class="alert-section">
                <div class="alert">
                    <div class="alert-icon">⚠️</div>
                    <div class="alert-content">
                        <h4>Stock Alert</h4>
                        <p>
                            <strong><?php echo $out_of_stock_count; ?> out of stock</strong> and
                            <strong><?php echo $low_stock_count; ?> low stock</strong> materials.
                            Please consider restocking.
                        </p>
                    </div>
                </div>
            </section>
        <?php } ?>

    </main>
</div>


<div id="editModal" class="modal" role="dialog" aria-labelledby="modalTitle" aria-modal="true">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">✏ Edit Stock Quantity</h2>
            <button class="modal-close" onclick="closeEditModal()" aria-label="Close modal">✕</button>
        </div>
        <form method="POST" action="material_list.php">
            <div class="modal-body">
                <input type="hidden" name="mid" id="edit_mid">
                <input type="hidden" name="update_stock" value="1">

                <div class="form-group">
                    <label>Material Name</label>
                    <input type="text" id="edit_name" readonly style="background:#f5f5f5; color:#555;">
                </div>

                <div class="current-stock">
                    Current Stock: <strong id="edit_current_stock">0</strong> <span id="edit_unit">pcs</span>
                </div>

                <div class="form-group">
                    <label for="new_qty">New Stock Quantity *</label>
                    <input type="number" id="edit_new_qty" name="new_qty" min="0" required placeholder="Enter new quantity">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-modal-cancel" onclick="closeEditModal()">Cancel</button>
                <button type="submit" class="btn-modal-save">💾 Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>

    function filterTable() {
        const input = document.getElementById('searchInput');
        const filter = input.value.toLowerCase();
        const statusFilter = document.getElementById('statusFilter').value;
        const table = document.getElementById('materialTable');
        const tr = table.getElementsByTagName('tr');

        for (let i = 1; i < tr.length; i++) {
            const tdName = tr[i].getElementsByTagName('td')[1];
            const tdId = tr[i].getElementsByTagName('td')[0];
            const status = tr[i].getAttribute('data-status');

            if (tdName || tdId) {
                const name = tdName ? tdName.textContent.toLowerCase() : '';
                const id = tdId ? tdId.textContent.toLowerCase() : '';
                const matchesSearch = name.includes(filter) || id.includes(filter);
                const matchesStatus = statusFilter === '' || status === statusFilter;

                tr[i].style.display = (matchesSearch && matchesStatus) ? '' : 'none';
            }
        }
    }


    function openEditModal(mid, name, currentQty, unit) {
        document.getElementById('edit_mid').value = mid;
        document.getElementById('edit_name').value = name;
        document.getElementById('edit_current_stock').textContent = currentQty;
        document.getElementById('edit_unit').textContent = unit;
        document.getElementById('edit_new_qty').value = currentQty;
        document.getElementById('editModal').classList.add('show');


        setTimeout(function() {
            document.getElementById('edit_new_qty').focus();
            document.getElementById('edit_new_qty').select();
        }, 200);
    }

    function closeEditModal() {
        document.getElementById('editModal').classList.remove('show');
    }


    document.getElementById('editModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeEditModal();
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeEditModal();
        }
    });


    document.getElementById('edit_new_qty').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            document.querySelector('#editModal form').submit();
        }
    });
</script>

</body>
</html>