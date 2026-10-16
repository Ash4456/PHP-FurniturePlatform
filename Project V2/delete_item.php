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
    die(mysqli_connect_error());
}


$message = '';
$message_type = '';
$delete_success = false;
$deleted_item = null;


if (isset($_GET['delete']) && isset($_GET['fid'])) {
    $fid = intval($_GET['fid']);


    $check_sql = "SELECT COUNT(*) as order_count FROM OrderFurnitures WHERE fid = $fid";
    $check_rs = mysqli_query($conn, $check_sql);
    $check_row = mysqli_fetch_assoc($check_rs);
    $order_count = $check_row['order_count'];

    if ($order_count > 0) {
        $message = "❌ Cannot delete this item because it has $order_count associated order(s).";
        $message_type = "error";
    } else {

        $name_sql = "SELECT fname FROM Furnitures WHERE fid = $fid";
        $name_rs = mysqli_query($conn, $name_sql);
        $name_row = mysqli_fetch_assoc($name_rs);
        $item_name = $name_row ? $name_row['fname'] : 'Unknown Item';


        mysqli_begin_transaction($conn);

        try {

            $sql_delete_materials = "DELETE FROM furniturematerials WHERE fid = $fid";
            if (!mysqli_query($conn, $sql_delete_materials)) {
                throw new Exception("Failed to delete material associations: " . mysqli_error($conn));
            }


            $sql_delete_item = "DELETE FROM Furnitures WHERE fid = $fid";
            if (!mysqli_query($conn, $sql_delete_item)) {
                throw new Exception("Failed to delete item: " . mysqli_error($conn));
            }


            mysqli_commit($conn);

            $delete_success = true;
            $deleted_item = array(
                    'fid' => $fid,
                    'fname' => $item_name
            );
            $message = "✅ Item '$item_name' (ID: $fid) and its material associations deleted successfully!";
            $message_type = "success";

        } catch (Exception $e) {

            mysqli_rollback($conn);
            $message = "❌ Failed to delete item: " . $e->getMessage();
            $message_type = "error";
        }
    }
}


$sql = "SELECT f.*, 
        (SELECT COUNT(*) FROM OrderFurnitures WHERE fid = f.fid) as order_count
        FROM Furnitures f 
        ORDER BY f.fid";
$rs = mysqli_query($conn, $sql);
$furnitures = [];
while ($row = mysqli_fetch_assoc($rs)) {
    $furnitures[] = $row;
}

mysqli_close($conn);


$total_items = count($furnitures);
$deletable_items = 0;
$protected_items = 0;
foreach ($furnitures as $f) {
    if ($f['order_count'] > 0) {
        $protected_items++;
    } else {
        $deletable_items++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Item - Premium Living</title>
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

        .page-header p {
            color: #6b6b6b;
            font-size: 14px;
            margin-top: 4px;
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

        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
        }

        .stat-card .stat-icon {
            font-size: 32px;
        }

        .stat-card .stat-info h3 {
            font-size: 24px;
            font-weight: 700;
            color: #141414;
        }

        .stat-card .stat-info p {
            font-size: 13px;
            color: #6b6b6b;
        }

        .stat-card.stat-success .stat-info h3 {
            color: #27ae60;
        }

        .stat-card.stat-danger .stat-info h3 {
            color: #c0392b;
        }

        .filter-section {
            background: white;
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 24px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            align-items: flex-end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 4px;
            flex: 1;
            min-width: 150px;
        }

        .filter-group label {
            font-weight: 600;
            font-size: 13px;
            color: #2c2c2c;
        }

        .filter-group input,
        .filter-group select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            outline: none;
            font-family: Arial, sans-serif;
        }

        .filter-group input:focus,
        .filter-group select:focus {
            border-color: #141414;
        }

        .table-container {
            background: white;
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
            overflow-x: auto;
        }

        .table-container h2 {
            font-size: 18px;
            font-weight: 700;
            color: #141414;
            margin-bottom: 16px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        .data-table thead {
            background: #f8f8f8;
        }

        .data-table th {
            padding: 12px 14px;
            text-align: left;
            font-weight: 700;
            color: #2c2c2c;
            border-bottom: 2px solid #eee;
        }

        .data-table td {
            padding: 12px 14px;
            border-bottom: 1px solid #f0efec;
            vertical-align: middle;
        }

        .data-table tbody tr:hover {
            background: #faf9f7;
        }


        .table-img {
            width: 50px;
            height: 50px;
            object-fit: contain;
            background: #f3f2ed;
            border-radius: 8px;
            padding: 4px;
        }

        .order-count {
            font-weight: 600;
        }


        .status-badge {
            display: inline-block;
            padding: 4px 14px;
            border-radius: 40px;
            font-size: 12px;
            font-weight: 600;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 16px;
            border-radius: 40px;
            font-weight: 600;
            font-size: 12px;
            border: none;
            cursor: pointer;
            transition: 0.15s;
            font-family: Arial, sans-serif;
            text-decoration: none;
        }

        .btn-danger {
            background: #c0392b;
            color: white;
        }

        .btn-danger:hover {
            background: #a93226;
        }

        .btn-secondary {
            background: #e0e0e0;
            color: #888;
            cursor: not-allowed;
        }

        .btn-secondary:hover {
            background: #e0e0e0;
        }

        .alert strong {
            color: #0d47a1;
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
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
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

        .warning-icon {
            text-align: center;
            font-size: 48px;
            margin-bottom: 12px;
        }

        .modal-body p {
            font-size: 15px;
            color: #2c2c2c;
            text-align: center;
        }

        .item-details {
            background: #f8f8f8;
            border-radius: 12px;
            padding: 16px;
            margin: 16px 0;
        }

        .item-details p {
            padding: 4px 0;
            border-bottom: 1px solid #eee;
            text-align: left;
        }

        .item-details p:last-child {
            border-bottom: none;
        }

        .item-details p strong {
            color: #141414;
            min-width: 100px;
            display: inline-block;
        }

        .warning-message {
            background: #fff3e0;
            padding: 12px 16px;
            border-radius: 8px;
            border-left: 4px solid #f39c12;
            margin: 12px 0;
        }

        .warning-message p {
            text-align: left;
            font-size: 14px;
            color: #e67e22;
        }

        .confirm-checkbox {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 12px;
            padding: 10px 0;
        }

        .confirm-checkbox input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .confirm-checkbox label {
            font-size: 14px;
            color: #2c2c2c;
            cursor: pointer;
        }

        .modal-footer {
            padding: 16px 24px 20px;
            border-top: 1px solid #eaeaea;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }

        .btn-modal-delete {
            background: #c0392b;
            color: white;
            border: none;
            padding: 10px 24px;
            border-radius: 60px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: 0.15s;
            font-family: Arial, sans-serif;
        }

        .btn-modal-delete:hover:not(:disabled) {
            background: #a93226;
        }

        .btn-modal-delete:disabled {
            background: #e0e0e0;
            color: #888;
            cursor: not-allowed;
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
        }

        @media (max-width: 768px) {
            .sidebar { width: 200px; }
            .main-content { margin-left: 200px; padding: 20px; }
            .filter-section { flex-direction: column; }
            .filter-group { min-width: 100%; }
        }

        @media (max-width: 480px) {
            .sidebar { width: 100%; height: auto; position: relative; }
            .main-content { margin-left: 0; padding: 16px; }
            .dashboard-container { flex-direction: column; }
            .stats-row { grid-template-columns: 1fr 1fr; }
            .modal-content { width: 95%; }
            .modal-footer { flex-direction: column; }
            .modal-footer button { width: 100%; justify-content: center; }
        }
    </style>
</head>
<body>

<div class="dashboard-container">

    <!-- Sidebar -->
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
                <li><a href="material_list.php">🧱 Material</a></li>
                <li><a href="update_order.php">📝 Update Orders</a></li>
                <li><a href="report_dashboard.php">📊 Generate Report</a></li>
                <li><a href="delete_item.php" class="active">🗑️ Delete</a></li>
            </ul>
        </nav>

        <div class="sidebar-footer">
            <a href="logout.php" class="logout-btn">🚪 Logout</a>
        </div>
    </aside>


    <main class="main-content">

        <div class="page-header">
            <h2>🗑️ Delete Item</h2>
            <p>Items with existing orders cannot be deleted to maintain order history integrity.</p>
        </div>


        <?php if ($message){?>
            <div class="msg <?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php } ?>


        <section class="stats-row">
            <div class="stat-card">
                <span class="stat-icon">📦</span>
                <div class="stat-info">
                    <h3><?php echo $total_items; ?></h3>
                    <p>Total Items</p>
                </div>
            </div>
            <div class="stat-card stat-success">
                <span class="stat-icon">✅</span>
                <div class="stat-info">
                    <h3><?php echo $deletable_items; ?></h3>
                    <p>Deletable</p>
                </div>
            </div>
            <div class="stat-card stat-danger">
                <span class="stat-icon">🛡️</span>
                <div class="stat-info">
                    <h3><?php echo $protected_items; ?></h3>
                    <p>Protected</p>
                </div>
            </div>
        </section>


        <section class="filter-section">
            <div class="filter-group">
                <label for="searchInput">🔍 Search</label>
                <input type="text" id="searchInput" placeholder="Search by ID or Name..." onkeyup="filterTable()">
            </div>
            <div class="filter-group">
                <label for="statusFilter">📋 Status</label>
                <select id="statusFilter" onchange="filterTable()">
                    <option value="">All Status</option>
                    <option value="deletable">Deletable</option>
                    <option value="protected">Protected</option>
                </select>
            </div>
        </section>


        <div class="table-container">
            <h2>📋 Item List</h2>
            <table class="data-table" id="itemsTable">
                <thead>
                <tr>
                    <th>Item ID</th>
                    <th>Image</th>
                    <th>Item Name</th>
                    <th>Price (HKD)</th>
                    <th>Orders</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <?php if (count($furnitures) > 0) { ?>
                    <?php foreach ($furnitures as $f) {
                        $has_orders = ($f['order_count'] > 0);
                        $row_class = $has_orders ? 'row-protected' : '';
                        $status_class = $has_orders ? 'status-danger' : 'status-success';
                        $status_text = $has_orders ? '🛡️ Protected' : '✅ Deletable';
                        $order_display = $has_orders ? '⚠️ ' . $f['order_count'] : '0';
                        ?>
                        <tr data-status="<?php echo $has_orders ? 'protected' : 'deletable'; ?>" class="<?php echo $row_class; ?>">
                            <td><strong><?php echo $f['fid']; ?></strong></td>
                            <td><img src="<?php echo $f['fid']; ?>.png" alt="<?php echo $f['fname']; ?>" class="table-img"></td>
                            <td><?php echo $f['fname']; ?></td>
                            <td>$<?php echo number_format($f['fprice'], 2); ?></td>
                            <td><span class="order-count <?php echo $has_orders ? 'order-warning' : ''; ?>"><?php echo $order_display; ?></span></td>
                            <td><span class="status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span></td>
                            <td>
                                <?php if ($has_orders) { ?>
                                    <button class="btn btn-secondary" disabled>🔒 Protected</button>
                                <?php } else { ?>
                                    <button class="btn btn-danger" onclick="showDeleteModal(<?php echo $f['fid']; ?>, '<?php echo addslashes($f['fname']); ?>')">
                                        🗑️ Delete
                                    </button>
                                <?php } ?>
                            </td>
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr>
                        <td colspan="7" style="text-align:center; padding:40px; color:#888;">
                            📭 No items found in inventory.
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>




    </main>
</div>


<div id="deleteModal" class="modal" role="dialog" aria-labelledby="modalTitle" aria-modal="true">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">⚠️ Confirm Deletion</h2>
            <button class="modal-close" onclick="closeDeleteModal()" aria-label="Close modal">✕</button>
        </div>
        <div class="modal-body">
            <div class="warning-icon">⚠️</div>
            <p>Are you sure you want to delete this item?</p>
            <div class="item-details">
                <p><strong>Item ID:</strong> <span id="modalItemId"></span></p>
                <p><strong>Item Name:</strong> <span id="modalItemName"></span></p>
                <p><strong>Orders:</strong> <span id="modalOrderCount">0</span></p>
            </div>
            <div class="warning-message">
                <p>⚠️ <strong>Warning:</strong> This action cannot be undone. The item will be permanently removed from the inventory.</p>
            </div>
            <div class="confirm-checkbox">
                <input type="checkbox" id="confirmCheck" onchange="toggleDeleteButton()">
                <label for="confirmCheck">I understand that this action is irreversible</label>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-modal-cancel" onclick="closeDeleteModal()">Cancel</button>
            <button class="btn-modal-delete" id="confirmDeleteBtn" disabled>
                🗑️ Delete Item
            </button>
        </div>
    </div>
</div>

<script>

    function filterTable() {
        var searchValue = document.getElementById('searchInput').value.toLowerCase();
        var statusValue = document.getElementById('statusFilter').value;
        var rows = document.querySelectorAll('#itemsTable tbody tr');

        rows.forEach(function(row) {
            var itemId = row.cells[0].textContent.toLowerCase();
            var itemName = row.cells[2].textContent.toLowerCase();
            var status = row.getAttribute('data-status');

            var matchesSearch = itemId.includes(searchValue) || itemName.includes(searchValue);
            var matchesStatus = statusValue === '' || status === statusValue;

            row.style.display = (matchesSearch && matchesStatus) ? '' : 'none';
        });
    }


    var currentDeleteItem = {
        fid: 0,
        fname: ''
    };

    function showDeleteModal(fid, fname) {
        currentDeleteItem.fid = fid;
        currentDeleteItem.fname = fname;

        document.getElementById('modalItemId').textContent = fid;
        document.getElementById('modalItemName').textContent = fname;
        document.getElementById('modalOrderCount').textContent = '0';
        document.getElementById('confirmCheck').checked = false;
        document.getElementById('confirmDeleteBtn').disabled = true;
        document.getElementById('deleteModal').classList.add('show');


        document.getElementById('confirmDeleteBtn').onclick = function() {
            if (!this.disabled) {
                window.location.href = 'delete_item.php?delete=1&fid=' + currentDeleteItem.fid;
            }
        };
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.remove('show');
    }

    function toggleDeleteButton() {
        var checkbox = document.getElementById('confirmCheck');
        var deleteBtn = document.getElementById('confirmDeleteBtn');
        deleteBtn.disabled = !checkbox.checked;
    }


    document.getElementById('deleteModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeDeleteModal();
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeDeleteModal();
        }
    });
</script>

</body>
</html>