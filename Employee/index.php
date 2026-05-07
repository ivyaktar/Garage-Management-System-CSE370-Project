<?php 
session_start();

// Ensure user is logged in and has the Employee role
if (isset($_SESSION['user_id']) && 
    isset($_SESSION['role'])    && 
    $_SESSION['role'] == 'Employee') {

    include "../DB_connection.php";
    
    $user_id = $_SESSION['user_id'];

    /* --- 1. FETCH EMPLOYEE PROFILE INFO --- */
    $sql = "SELECT users.username, employees.* FROM employees 
            INNER JOIN users ON employees.user_id = users.uid 
            WHERE employees.user_id = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->execute([$user_id]);
    $employee = $stmt->fetch();


    /* --- 2. FETCH RECENT ORDERS/SALES --- */
    $sql_recent = "SELECT o.*, p.product_name, p.image AS p_image, c.brand, c.model, c.image AS c_image, cust.fname as cust_name
                    FROM orders o 
                    LEFT JOIN products p ON o.product_id = p.id
                    LEFT JOIN cars c ON o.car_id = c.id
                    LEFT JOIN customer cust ON o.customer_id = cust.user_id
                    ORDER BY o.id DESC LIMIT 5";
    $stmt_recent = $conn->prepare($sql_recent);
    $stmt_recent->execute();
    $recent_orders = $stmt_recent->fetchAll();


    /* --- 3. FETCH RECENT REPAIRS --- */
    $sql_repairs = "SELECT r.*, cust.fname as cust_name 
                    FROM car_repairs r
                    LEFT JOIN customer cust ON r.customer_id = cust.user_id
                    ORDER BY r.repair_id DESC LIMIT 5";
    $stmt_repairs = $conn->prepare($sql_repairs);
    $stmt_repairs->execute();
    $recent_repairs = $stmt_repairs->fetchAll();


    /* --- 4. FETCH TOP WISHLISTED ITEMS (New Section) --- */
    $sql_wish = "SELECT * FROM (
                    (SELECT p.product_name AS name, p.image, p.id, COUNT(w.id) AS wish_count, 'Part' AS category
                     FROM products p 
                     INNER JOIN wishlist w ON p.id = w.product_id 
                     GROUP BY p.id)
                    UNION
                    (SELECT CONCAT(c.brand, ' ', c.model) AS name, c.image, c.id, COUNT(w.id) AS wish_count, 'Car' AS category
                     FROM cars c 
                     INNER JOIN wishlist w ON c.id = w.product_id 
                     GROUP BY c.id)
                ) AS combined_wishlist
                ORDER BY wish_count DESC LIMIT 5";
    $stmt_wish = $conn->prepare($sql_wish);
    $stmt_wish->execute();
    $top_wishlisted = $stmt_wish->fetchAll();


    /* --- 5. COUNT UNREAD MESSAGES --- */
    $sql_msg = "SELECT COUNT(*) AS unread FROM message WHERE status = 'Unread'";
    $stmt_msg = $conn->prepare($sql_msg);
    $stmt_msg->execute();
    $unread_data = $stmt_msg->fetch();
    
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
    <title>Employee - Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="icon" href="../logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    
    <style>
        .stat-card-link { text-decoration: none; display: block; transition: transform 0.2s ease-in-out; }
        .stat-card-link:hover { transform: translateY(-5px); }
        
        .profile-icon-placeholder { 
            width: 120px; 
            height: 120px; 
            background-color: #f0fdf4; 
            color: #198754; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-size: 4rem; 
            border: 5px solid #fff; 
        }
        
        .icon-bg { font-size: 2rem; opacity: 0.2; position: absolute; right: 15px; top: 15px; }
        .card { position: relative; overflow: hidden; border-radius: 12px; }
        
        .deleted-item-icon {
            width: 45px; height: 45px;
            background-color: #6c757d; color: white;
            display: inline-flex; align-items: center; justify-content: center;
            border-radius: 4px;
        }
    </style>
</head>
<body class="bg-light">
    <?php include "inc/navbar.php"; ?>

    <div class="container mt-5">
        
        <div class="p-5 mb-4 bg-white rounded-3 shadow-sm d-flex align-items-center">
            <div class="rounded-circle shadow-sm me-4 profile-icon-placeholder">
                <i class="fa fa-id-card-o"></i>
            </div>
            <div>
                <h1 class="display-6 fw-bold text-dark mb-1">
                    Staff Portal
                </h1>
                <p class="text-muted mb-0">Welcome, <?php echo $employee['fname']; ?>. Work on repairs, monitor the showroom, and manage live auctions.</p>
                <div class="mt-2">
                    <span class="badge bg-success">Employee Account</span>
                </div>
            </div>
        </div>

        <div class="row mb-4 text-center">
            <div class="col-md-3 mb-3">
                <a href="inventory-view.php" class="stat-card-link">
                    <div class="card shadow-sm border-0 bg-primary text-white p-3 h-100 d-flex flex-column justify-content-center">
                        <i class="fa fa-cubes icon-bg"></i>
                        <h5 class="text-uppercase small mb-1">Inventory</h5>
                        <h2 class="mb-0">Parts List</h2>
                    </div>
                </a>
            </div>
            
            <div class="col-md-3 mb-3">
                <a href="showroom-manage.php" class="stat-card-link">
                    <div class="card shadow-sm border-0 bg-info text-white p-3 h-100 d-flex flex-column justify-content-center">
                        <i class="fa fa-car icon-bg"></i>
                        <h5 class="text-uppercase small mb-1">Showroom</h5>
                        <h2 class="mb-0">Vehicles</h2>
                    </div>
                </a>
            </div>
            
            <div class="col-md-3 mb-3">
                <a href="auction-dashboard.php" class="stat-card-link">
                    <div class="card shadow-sm border-0 bg-dark text-white p-3 h-100 d-flex flex-column justify-content-center">
                        <i class="fa fa-gavel icon-bg text-warning"></i>
                        <h5 class="text-uppercase small mb-1 text-warning">Auctions</h5>
                        <h2 class="mb-0 text-white">Live Bidding</h2>
                    </div>
                </a>
            </div>
            
            <div class="col-md-3 mb-3">
                <a href="repair-house.php" class="stat-card-link">
                    <div class="card shadow-sm border-0 bg-success text-white p-3 h-100 d-flex flex-column justify-content-center">
                        <i class="fa fa-wrench icon-bg"></i>
                        <h5 class="text-uppercase small mb-1">Workshop</h5>
                        <h2 class="mb-0">Repairs</h2>
                    </div>
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                
                <div class="card shadow-sm border-0 mb-5">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 text-dark">Recent Sales</h5>
                        <a href="sales-history.php" class="btn btn-sm btn-outline-secondary">View All Orders</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr class="text-muted small">
                                        <th>ITEM</th>
                                        <th>CUSTOMER</th>
                                        <th>DATE</th>
                                        <th>TOTAL</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $recent_count = count($recent_orders);
                                    
                                    if ($recent_count > 0) {
                                        for ($i = 0; $i < $recent_count; $i = $i + 1) { 
                                            $order = $recent_orders[$i];
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
                                                <img src="<?php echo $img_path; ?>" width="40" height="40" class="rounded me-2 border" style="object-fit: cover;">
                                            <?php } else { ?>
                                                <div class="deleted-item-icon me-2"><i class="fa fa-archive"></i></div>
                                            <?php } ?>
                                            <span class="fw-bold"><?php echo $display_name; ?></span>
                                        </td>
                                        <td><?php echo $order['cust_name']; ?></td>
                                        <td class="text-muted small"><?php echo $order['date_ordered']; ?></td>
                                        <td class="fw-bold text-success">$<?php echo number_format($order['total_price'], 2); ?></td>
                                    </tr>
                                    <?php 
                                        } 
                                    } else { ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">No sales recorded yet.</td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm border-0 mb-5">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 text-dark">Recent Repairs</h5>
                        <a href="repair-house.php" class="btn btn-sm btn-outline-secondary">View Workshop</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr class="text-muted small">
                                        <th>VEHICLE</th>
                                        <th>CUSTOMER</th>
                                        <th>STATUS</th>
                                        <th>TOTAL COST</th>
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
                                            <img src="../uploads/<?php echo $rep['car_image']; ?>" width="40" height="40" class="rounded me-2 border" style="object-fit: cover;" onerror="this.src='https://via.placeholder.com/100'">
                                            <span class="fw-bold"><?php echo $rep['brand'] . " " . $rep['model']; ?></span>
                                        </td>
                                        <td><?php echo $rep['cust_name']; ?></td>
                                        <td>
                                            <?php 
                                                $status_class = "bg-light text-dark";
                                                if ($rep['status'] == "Completed") { $status_class = "bg-success text-white"; }
                                                if ($rep['status'] == "Repairing") { $status_class = "bg-primary text-white"; }
                                                if ($rep['status'] == "Pending") { $status_class = "bg-warning text-dark"; }
                                            ?>
                                            <span class="badge <?php echo $status_class; ?>">
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
                                        <td colspan="4" class="text-center py-4 text-muted">No repairs in progress.</td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm border-0 mb-5">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 text-dark"><i class="fa fa-heart text-danger me-2"></i> Most Wishlisted</h5>
                        <a href="wishlist-all.php" class="btn btn-sm btn-outline-secondary">View All</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr class="text-muted small">
                                        <th>ITEM</th>
                                        <th>CATEGORY</th>
                                        <th>INTEREST COUNT</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $wish_count = count($top_wishlisted);

                                    if ($wish_count > 0) {
                                        for ($j = 0; $j < $wish_count; $j = $j + 1) { 
                                            $w_item = $top_wishlisted[$j];
                                    ?>
                                    <tr>
                                        <td>
                                            <img src="../uploads/<?php echo $w_item['image']; ?>" width="40" height="40" class="rounded me-2 border" style="object-fit: cover;">
                                            <span class="fw-bold"><?php echo $w_item['name']; ?></span>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo ($w_item['category'] == 'Car') ? 'bg-info' : 'bg-secondary'; ?>">
                                                <?php echo $w_item['category']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="text-primary fw-bold">
                                                <i class="fa fa-users me-1"></i> <?php echo $w_item['wish_count']; ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php 
                                        }
                                    } else {
                                    ?>
                                    <tr>
                                        <td colspan="3" class="text-center py-4 text-muted">No wishlist data available.</td>
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
                        <h5 class="mb-0"><i class="fa fa-bolt text-warning me-2"></i> Employee Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-3">
                            <a href="inventory.php" class="btn btn-primary p-3 text-start">
                                <i class="fa fa-plus-circle me-2"></i> Restock Inventory
                            </a>
                            <a href="message.php" class="btn btn-danger text-white p-3 text-start">
                                <i class="fa fa-envelope me-2"></i> View Inquiries (<?php echo $unread_count; ?>)
                            </a>
                            <a href="warranty-manage.php" class="btn btn-warning p-3 text-start">
                                <i class="fa fa-shield me-2"></i> Manage Warranties
                            </a>
                            <a href="view-reviews.php" class="btn btn-dark p-3 text-start">
                                <i class="fa fa-comments me-2"></i> Manage Reviews
                            </a>
                            <a href="repair-house.php" class="btn btn-success text-white p-3 text-start">
                                <i class="fa fa-cogs me-2"></i> Update Repair Status
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm border-0 bg-secondary text-white">
                    <div class="card-body p-4">
                        <h6><i class="fa fa-info-circle me-2"></i> Help Desk</h6>
                        <p class="small mb-0 opacity-75">If you notice issues with the inventory database or auction timer, please contact the Manager immediately via the internal system.</p>
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