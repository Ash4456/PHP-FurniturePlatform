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

$report_type = isset($_GET['type']) ? $_GET['type'] : 'sales';
$from_date = isset($_GET['from']) ? $_GET['from'] : date('Y-m-01');
$to_date = isset($_GET['to']) ? $_GET['to'] : date('Y-m-d');

$report_title = array(
    'sales' => 'Sales Report',
    'inventory' => 'Inventory Report',
    'orders' => 'Order Report',
    'customers' => 'Customer Report'
);

// ===== 獲取報告數據 =====
$data = array();
$total_amount = 0;

if ($report_type == 'sales' || $report_type == 'orders') {
    $sql = "SELECT 
                o.oid,
                o.odate,
                o.ototalamount,
                o.ostatus,
                c.cname AS customer_name
            FROM orders o
            LEFT JOIN customers c ON o.cid = c.cid
            WHERE DATE(o.odate) BETWEEN '$from_date' AND '$to_date'
            ORDER BY o.odate DESC";
    $rs = mysqli_query($conn, $sql);
    if ($rs) {
        while ($row = mysqli_fetch_assoc($rs)) {
            $data[] = $row;
            $total_amount += isset($row['ototalamount']) ? $row['ototalamount'] : 0;
        }
    }
} elseif ($report_type == 'inventory') {
    $sql = "SELECT 
                mid,
                mname,
                mqty,
                munit,
                CASE 
                    WHEN mqty <= 0 THEN 'Out of Stock'
                    WHEN mqty < 20 THEN 'Low Stock'
                    ELSE 'In Stock'
                END AS status
            FROM materials
            ORDER BY mid";
    $rs = mysqli_query($conn, $sql);
    if ($rs) {
        while ($row = mysqli_fetch_assoc($rs)) {
            $data[] = $row;
        }
    }
} elseif ($report_type == 'customers') {
    $sql = "SELECT cid, cname, ctel, caddr, company FROM customers ORDER BY cid";
    $rs = mysqli_query($conn, $sql);
    if ($rs) {
        while ($row = mysqli_fetch_assoc($rs)) {
            $data[] = $row;
        }
    }
}

$total_count = count($data);
$status_map = array(1 => 'Pending', 2 => 'Processing', 3 => 'Shipped', 4 => 'Completed', 5 => 'Cancelled');
$status_color = array(1 => 'pending', 2 => 'processing', 3 => 'shipped', 4 => 'completed', 5 => 'cancelled');

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Preview - <?php echo isset($report_title[$report_type]) ? $report_title[$report_type] : 'Report'; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f0f0f0; padding: 20px; }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 2px 20px rgba(0,0,0,0.08);
        }

        .report-header {
            text-align: center;
            border-bottom: 2px solid #141414;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .report-header h1 {
            font-size: 28px;
            color: #141414;
        }
        .report-header .sub-title {
            font-size: 20px;
            font-weight: 400;
            color: #555;
            margin-top: 4px;
        }
        .report-header p {
            color: #888;
            font-size: 14px;
            margin-top: 4px;
        }

        .report-meta {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            background: #f8f9fa;
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 24px;
        }
        .report-meta .meta-item {
            font-size: 14px;
            color: #555;
        }
        .report-meta .meta-item strong {
            color: #141414;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }
        .summary-card {
            background: #f8f9fa;
            padding: 16px 20px;
            border-radius: 12px;
            text-align: center;
        }
        .summary-card .value {
            font-size: 24px;
            font-weight: 700;
            color: #141414;
        }
        .summary-card .label {
            font-size: 13px;
            color: #888;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        .data-table th {
            background: #f8f8f8;
            padding: 10px 14px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #eee;
        }
        .data-table td {
            padding: 10px 14px;
            border-bottom: 1px solid #f0f0f0;
            color: #333;
        }
        .data-table tr:hover td {
            background: #fafafa;
        }

        .status-badge {
            padding: 2px 14px;
            border-radius: 40px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }

        .report-footer {
            border-top: 1px solid #eee;
            padding-top: 16px;
            margin-top: 24px;
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            font-size: 13px;
            color: #888;
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
        .btn-primary { background: #141414; color: white; }
        .btn-primary:hover { background: #333; }
        .btn-secondary { background: transparent; border: 1px solid #ccc; color: #2f2f2f; }
        .btn-secondary:hover { background: #f0efea; border-color: #aaa; }

        .actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        @media print {
            body { background: white; padding: 0; }
            .container { box-shadow: none; padding: 20px; }
            .btn, .actions { display: none !important; }
            .no-print { display: none !important; }
            .report-meta { background: #f5f5f5; }
        }

        @media (max-width: 768px) {
            .container { padding: 20px; }
            .summary-grid { grid-template-columns: 1fr 1fr; }
            .report-meta { flex-direction: column; }
        }
        @media (max-width: 480px) {
            .container { padding: 16px; }
            .summary-grid { grid-template-columns: 1fr; }
            .data-table { font-size: 12px; }
            .data-table th, .data-table td { padding: 6px 8px; }
        }
    </style>
</head>
<body>

<div class="container">

    <!-- Report Header -->
    <div class="report-header">
        <h1>Premium Living Furniture</h1>
        <div class="sub-title"><?php echo isset($report_title[$report_type]) ? $report_title[$report_type] : 'Report'; ?></div>
        <p>Period: <?php echo $from_date; ?> to <?php echo $to_date; ?></p>
    </div>

    <!-- Report Meta -->
    <div class="report-meta">
        <div class="meta-item">
            <strong>Report ID:</strong> RPT-<?php echo date('Ymd') . '-' . strtoupper(substr($report_type, 0, 3)); ?>
        </div>
        <div class="meta-item">
            <strong>Generated On:</strong> <?php echo date('Y-m-d H:i'); ?>
        </div>
        <div class="meta-item">
            <strong>Generated By:</strong> <?php echo htmlspecialchars(isset($_SESSION['staff_name']) ? $_SESSION['staff_name'] : 'Staff'); ?>
        </div>
        <div class="meta-item">
            <strong>Total Records:</strong> <?php echo $total_count; ?>
        </div>
    </div>

    <!-- Summary -->
    <div class="summary-grid">
        <div class="summary-card">
            <div class="value"><?php echo $total_count; ?></div>
            <div class="label">Total Records</div>
        </div>
        <?php if ($report_type == 'sales' || $report_type == 'orders'): ?>
            <div class="summary-card">
                <div class="value">$<?php echo number_format($total_amount, 2); ?></div>
                <div class="label">Total Amount</div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Data Table -->
    <table class="data-table">
        <thead>
        <tr>
            <?php if ($report_type == 'sales' || $report_type == 'orders'): ?>
                <th>Order ID</th>
                <th>Date</th>
                <th>Customer</th>
                <th>Amount</th>
                <th>Status</th>
            <?php elseif ($report_type == 'inventory'): ?>
                <th>ID</th>
                <th>Name</th>
                <th>Stock</th>
                <th>Unit</th>
                <th>Status</th>
            <?php elseif ($report_type == 'customers'): ?>
                <th>ID</th>
                <th>Name</th>
                <th>Phone</th>
                <th>Address</th>
                <th>Company</th>
            <?php endif; ?>
        </tr>
        </thead>
        <tbody>
        <?php if (count($data) > 0): ?>
            <?php foreach ($data as $row): ?>
                <tr>
                    <?php if ($report_type == 'sales' || $report_type == 'orders'): ?>
                        <td><strong>#<?php echo $row['oid']; ?></strong></td>
                        <td><?php echo date('Y-m-d', strtotime($row['odate'])); ?></td>
                        <td><?php echo isset($row['customer_name']) ? htmlspecialchars($row['customer_name']) : 'N/A'; ?></td>
                        <td>$<?php echo number_format($row['ototalamount'], 2); ?></td>
                        <td>
                                <span class="status-badge <?php echo isset($status_color[$row['ostatus']]) ? $status_color[$row['ostatus']] : 'pending'; ?>">
                                    <?php echo isset($status_map[$row['ostatus']]) ? $status_map[$row['ostatus']] : 'Unknown'; ?>
                                </span>
                        </td>
                    <?php elseif ($report_type == 'inventory'): ?>
                        <td>#<?php echo $row['mid']; ?></td>
                        <td><?php echo htmlspecialchars($row['mname']); ?></td>
                        <td><strong><?php echo $row['mqty']; ?></strong></td>
                        <td><?php echo htmlspecialchars($row['munit']); ?></td>
                        <td>
                                <span class="status-badge <?php echo strtolower(str_replace(' ', '-', $row['status'])); ?>">
                                    <?php echo $row['status']; ?>
                                </span>
                        </td>
                    <?php elseif ($report_type == 'customers'): ?>
                        <td>#<?php echo $row['cid']; ?></td>
                        <td><?php echo htmlspecialchars($row['cname']); ?></td>
                        <td><?php echo htmlspecialchars($row['ctel']); ?></td>
                        <td><?php echo htmlspecialchars($row['caddr']); ?></td>
                        <td><?php echo isset($row['company']) ? htmlspecialchars($row['company']) : 'N/A'; ?></td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="5" style="text-align:center;color:#999;padding:20px;">
                    No data found for this period
                </td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>

    <!-- Footer -->
    <div class="report-footer">
        <span>Report ID: RPT-<?php echo date('Ymd') . '-' . strtoupper(substr($report_type, 0, 3)); ?></span>
        <span>Page 1 of 1</span>
        <span>© <?php echo date('Y'); ?> Premium Living Furniture</span>
    </div>

    <!-- Actions -->
    <div class="actions">
        <button class="btn btn-primary" onclick="window.print()">
            <i class="fas fa-print"></i> Print
        </button>
        <a href="report_generate.php?type=<?php echo $report_type; ?>&from=<?php echo $from_date; ?>&to=<?php echo $to_date; ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back
        </a>
        <a href="report_dashboard.php" class="btn btn-secondary">
            <i class="fas fa-home"></i> Dashboard
        </a>
    </div>

</div>

</body>
</html>