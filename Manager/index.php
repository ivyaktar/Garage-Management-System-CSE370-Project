<?php 
session_start();

if (isset($_SESSION['user_id']) && 
    isset($_SESSION['role'])    && 
    $_SESSION['role'] == 'Manager') {

    include "../DB_connection.php";
    
    $user_id = $_SESSION['user_id'];

    // Fetch Manager Profile Info
    $sql = "SELECT users.username, manager.* FROM manager 
            INNER JOIN users ON manager.user_id = users.uid 
            WHERE manager.user_id = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->execute([$user_id]);
    $manager = $stmt->fetch();

    // Fetch 5 most recent orders for the manager view
    $sql_recent = "SELECT o.*, p.product_name, p.image AS p_image, c.brand, c.model, c.image AS c_image, cust.fname as cust_name
                    FROM orders o 
                    LEFT JOIN products p ON o.product_id = p.id
                    LEFT JOIN cars c ON o.car_id = c.id
                    LEFT JOIN customer cust ON o.customer_id = cust.user_id
                    ORDER BY o.id DESC LIMIT 5";
    $stmt_recent = $conn->prepare($sql_recent);
    $stmt_recent->execute();
    $recent_orders = $stmt_recent->fetchAll();

    // Fetch 5 most recent repairs for the manager view
    $sql_repairs = "SELECT r.*, cust.fname as cust_name 
                    FROM car_repairs r
                    LEFT JOIN customer cust ON r.customer_id = cust.user_id
                    ORDER BY r.repair_id DESC LIMIT 5";
    $stmt_repairs = $conn->prepare($sql_repairs);
    $stmt_repairs->execute();
    $recent_repairs = $stmt_repairs->fetchAll();

    // Count inquiries for stats
    $sql_msg = "SELECT COUNT(*) AS unread FROM message WHERE status = 'Unread'";
    $stmt_msg = $conn->prepare($sql_msg);
    $stmt_msg->execute();
    $unread_data = $stmt_msg->fetch();
    
    // Fixed: Ensure the key matches the SQL alias 'unread'
    $unread_count = 0;
    if ($unread_data) {
        $unread_count = $unread_data['unread'];
    }

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager - Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="icon" href="../logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    
    <style>
        .stat-card-link { text-decoration: none; display: block; transition: transform 0.2s ease-in-out; }
        .stat-card-link:hover { transform: translateY(-5px); }
        /* Generic Profile Icon Styling */
        .profile-icon-placeholder { 
            width: 120px; 
            height: 120px; 
            background-color: #e9ecef; 
            color: #adb5bd; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-size: 4rem; 
            border: 5px solid #fff; 
        }
        .icon-bg { font-size: 2rem; opacity: 0.3; position: absolute; right: 15px; top: 15px; }
        .card { position: relative; overflow: hidden; }
        .deleted-item-icon {
            width: 45px;
            height: 45px;
            background-color: #6c757d;
            color: white;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
        }
    </style>
</head>
<body class="bg-light">
    <?php include "inc/navbar.php"; ?>

    <div class="container mt-5">
        
        <div class="p-5 mb-4 bg-white rounded-3 shadow-sm d-flex align-items-center">
            <div class="rounded-circle shadow-sm me-4 profile-icon-placeholder">
                <i class="fa fa-user"></i>
            </div>
            <div>
                <h1 class="display-6 fw-bold text-dark mb-1">
                    <i class="fa fa-dashboard text-primary"></i> Manager Dashboard
                </h1>
                <p class="text-muted mb-0">Welcome back, <?php echo $manager['fname']; ?>. Monitor your inventory, auctions, and repairs.</p>
                <a href="profile.php" class="btn btn-sm btn-outline-primary mt-2 rounded-pill">Manage Profile</a>
            </div>
        </div>

        <div class="row mb-4 text-center">
            <div class="col-md-3 mb-3">
                <a href="inventory-view.php" class="stat-card-link">
                    <div class="card shadow-sm border-0 bg-primary text-white p-3 h-100 d-flex flex-column justify-content-center">
                        <i class="fa fa-cubes icon-bg"></i>
                        <h5 class="text-uppercase small mb-1" style="letter-spacing: 1px;">Inventory</h5>
                        <h2 class="mb-0">Manage Stock</h2>
                    </div>
                </a>
            </div>
            
            <div class="col-md-3 mb-3">
                <a href="showroom-manage.php" class="stat-card-link">
                    <div class="card shadow-sm border-0 bg-info text-white p-3 h-100 d-flex flex-column justify-content-center">
                        <i class="fa fa-car icon-bg"></i>
                        <h5 class="text-uppercase small mb-1" style="letter-spacing: 1px;">Showroom</h5>
                        <h2 class="mb-0">Manage Cars</h2>
                    </div>
                </a>
            </div>
            
            <div class="col-md-3 mb-3">
                <a href="auction-dashboard.php" class="stat-card-link">
                    <div class="card shadow-sm border-0 bg-dark text-white p-3 h-100 d-flex flex-column justify-content-center">
                        <i class="fa fa-gavel icon-bg text-warning"></i>
                        <h5 class="text-uppercase small mb-1 text-warning" style="letter-spacing: 1px;">Auctions</h5>
                        <h2 class="mb-0 text-white">Live Control</h2>
                    </div>
                </a>
            </div>
            
            <div class="col-md-3 mb-3">
                <a href="sales-history.php" class="stat-card-link">
                    <div class="card shadow-sm border-0 bg-success text-white p-3 h-100 d-flex flex-column justify-content-center">
                        <i class="fa fa-money icon-bg"></i>
                        <h5 class="text-uppercase small mb-1" style="letter-spacing: 1px;">Revenue</h5>
                        <h2 class="mb-0">View Sales</h2>
                    </div>
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="card shadow-sm border-0 mb-5">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fa fa-shopping-cart text-muted me-2"></i> Recent Orders</h5>
                        <a href="sales-history.php" class="btn btn-sm btn-outline-secondary">View All</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Item</th>
                                        <th>Customer</th>
                                        <th>Date</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $recent_count = count($recent_orders);
                                    if ($recent_count > 0) {
                                        for ($j = 0; $j < $recent_count; $j = $j + 1) { 
                                            $order = $recent_orders[$j];
                                            
                                            $display_name = "";
                                            $img_path = "";
                                            $is_deleted = false;

                                            if (!empty($order['car_id']) && !empty($order['brand'])) {
                                                $display_name = $order['brand'] . " " . $order['model'];
                                                $img_path = "../uploads/" . $order['c_image'];
                                            } else if (!empty($order['product_id']) && !empty($order['product_name'])) {
                                                $display_name = $order['product_name'];
                                                $img_path = "../uploads/" . $order['p_image'];
                                            } else {
                                                $display_name = "Deleted Item";
                                                $is_deleted = true;
                                            }
                                    ?>
                                    <tr>
                                        <td>
                                            <?php if ($is_deleted == false) { ?>
                                                <img src="<?php echo $img_path; ?>" width="45" height="45" class="rounded me-2 border" style="object-fit: cover;">
                                            <?php } else { ?>
                                                <div class="deleted-item-icon me-2">
                                                    <i class="fa fa-archive"></i>
                                                </div>
                                            <?php } ?>
                                            <span class="fw-semibold text-truncate d-inline-block" style="max-width: 150px;"><?php echo $display_name; ?></span>
                                        </td>
                                        <td class="small"><?php echo $order['cust_name']; ?></td>
                                        <td class="small text-muted"><?php echo $order['date_ordered']; ?></td>
                                        <td class="fw-bold text-success">$<?php echo number_format($order['total_price'], 2); ?></td>
                                    </tr>
                                    <?php 
                                        } 
                                    } else { ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">No recent orders found.</td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm border-0 mb-5">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fa fa-wrench text-muted me-2"></i> Recent Repairs</h5>
                        <a href="repair-house.php" class="btn btn-sm btn-outline-secondary">View All</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Vehicle</th>
                                        <th>Customer</th>
                                        <th>Status</th>
                                        <th>Total Cost</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $repairs_count = count($recent_repairs);
                                    if ($repairs_count > 0) {
                                        for ($k = 0; $k < $repairs_count; $k = $k + 1) { 
                                            $rep = $recent_repairs[$k];
                                    ?>
                                    <tr>
                                        <td>
                                            <img src="../uploads/<?php echo $rep['car_image']; ?>" width="45" height="45" class="rounded me-2 border" style="object-fit: cover;" onerror="this.src='https://via.placeholder.com/100'">
                                            <span class="fw-semibold"><?php echo $rep['brand'] . " " . $rep['model']; ?></span>
                                        </td>
                                        <td class="small"><?php echo $rep['cust_name']; ?></td>
                                        <td>
                                            <?php 
                                                $status_class = "bg-light text-dark";
                                                if ($rep['status'] == "Completed") { $status_class = "bg-success text-white"; }
                                                if ($rep['status'] == "Repairing") { $status_class = "bg-primary text-white"; }
                                                if ($rep['status'] == "Pending") { $status_class = "bg-warning text-dark"; }
                                                if ($rep['status'] == "Rejected") { $status_class = "bg-danger text-white"; }
                                            ?>
                                            <span class="badge <?php echo $status_class; ?> border">
                                                <?php echo $rep['status']; ?>
                                            </span>
                                        </td>
                                        <td class="fw-bold text-dark">
                                            $<?php echo number_format($rep['labor_fee'] + $rep['parts_total'], 2); ?>
                                        </td>
                                    </tr>
                                    <?php 
                                        } 
                                    } else { ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">No recent repairs found.</td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0"><i class="fa fa-bolt text-warning me-2"></i> Management Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-3">
                            <a href="inventory.php" class="btn btn-primary p-3 text-start">
                                <i class="fa fa-plus-circle me-2"></i> Add New Parts
                            </a>
                            <a href="message.php" class="btn btn-danger text-white p-3 text-start">
                                <i class="fa fa-envelope me-2"></i> Inbox (<?php echo $unread_count; ?>)
                            </a>
                            <a href="repair-house.php" class="btn btn-success text-white p-3 text-start">
                                <i class="fa fa-university me-2"></i> Access Repair House
                            </a>
                            <a href="warranty-manage.php" class="btn btn-secondary text-white p-3 text-start">
                                <i class="fa fa-shield me-2"></i> Review Warranties
                            </a>
                            <a href="employees.php" class="btn btn-outline-dark p-3 text-start">
                                <i class="fa fa-users me-2"></i> Employee Directory
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm border-0 bg-dark text-white">
                    <div class="card-body text-center p-4">
                        <i class="fa fa-cog fa-2x mb-2 text-warning"></i>
                        <h5>System Settings</h5>
                        <p class="small opacity-75">Configure garage settings.</p>
                        <a href="settings.php" class="btn btn-sm btn-warning">Edit Settings</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php 
} else {
    header("Location: ../login.php");
    exit;
} 
?>