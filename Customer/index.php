<?php 
session_start();

if (isset($_SESSION['user_id']) && 
    isset($_SESSION['role'])    && 
    $_SESSION['role'] == 'Customer') {

    include "../DB_connection.php";
    
    $user_id = $_SESSION['user_id'];

    // Fetch Customer Profile Info
    $sql = "SELECT users.username, customer.* FROM customer 
            INNER JOIN users ON customer.user_id = users.uid 
            WHERE customer.user_id = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->execute([$user_id]);
    $customer = $stmt->fetch();

    // Fetch 5 most recent orders including Cars
    $sql_recent = "SELECT o.*, p.product_name, p.image AS p_image, c.brand, c.model, c.image AS c_image
                    FROM orders o 
                    LEFT JOIN products p ON o.product_id = p.id
                    LEFT JOIN cars c ON o.car_id = c.id
                    WHERE o.customer_id = ? 
                    ORDER BY o.id DESC LIMIT 5";
    $stmt_recent = $conn->prepare($sql_recent);
    $stmt_recent->execute([$user_id]);
    $recent_orders = $stmt_recent->fetchAll();

    // Fetch 5 most recent repairs
    $sql_repairs = "SELECT * FROM car_repairs 
                    WHERE customer_id = ? 
                    ORDER BY repair_id DESC LIMIT 5";
    $stmt_repairs = $conn->prepare($sql_repairs);
    $stmt_repairs->execute([$user_id]);
    $recent_repairs = $stmt_repairs->fetchAll();

    // Logic to calculate cart count from Database
    $sql_cart_count = "SELECT SUM(quantity) as total FROM cart WHERE customer_id = ?";
    $stmt_cart_count = $conn->prepare($sql_cart_count);
    $stmt_cart_count->execute([$user_id]);
    $cart_res = $stmt_cart_count->fetch();
    
    $cart_count = 0;
    if ($cart_res['total'] > 0) {
        $cart_count = $cart_res['total'];
    }

    // Logic to calculate wishlist count
    $sql_wish_count = "SELECT w.id FROM wishlist w 
                       LEFT JOIN products p ON w.product_id = p.id 
                       LEFT JOIN cars c ON w.car_id = c.id
                       WHERE w.user_id = ? AND (p.id IS NOT NULL OR c.id IS NOT NULL)";
    $stmt_wish_count = $conn->prepare($sql_wish_count);
    $stmt_wish_count->execute([$user_id]);
    $wish_count = $stmt_wish_count->rowCount();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer - Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="icon" href="../logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    
    <style>
        .stat-card-link { text-decoration: none; display: block; transition: transform 0.2s ease-in-out; }
        .stat-card-link:hover { transform: translateY(-5px); }
        .profile-img { width: 120px; height: 120px; object-fit: cover; border: 5px solid #fff; }
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
            <img src="../img/Customer-<?php echo $customer['gender']; ?>.png" 
                 class="rounded-circle shadow-sm me-4 profile-img" alt="Profile">
            <div>
                <h1 class="display-6 fw-bold text-dark mb-1">
                    <i class="fa fa-user-circle-o text-primary"></i> Hello, <?php echo $customer['fname']; ?>!
                </h1>
                <p class="text-muted mb-0">Manage your garage, builds, and bids from one place.</p>
                <a href="profile.php" class="btn btn-sm btn-outline-primary mt-2 rounded-pill">Edit Profile</a>
            </div>
        </div>

        <div class="row mb-4 text-center">
            <div class="col-md-3 mb-3">
                <a href="cart.php" class="stat-card-link">
                    <div class="card shadow-sm border-0 bg-success text-white p-3 h-100 d-flex flex-column justify-content-center">
                        <i class="fa fa-shopping-cart icon-bg"></i>
                        <h5 class="text-uppercase small mb-1" style="letter-spacing: 1px;">Shopping Cart</h5>
                        <h2 class="mb-0"><?php echo $cart_count; ?> Items</h2>
                    </div>
                </a>
            </div>
            
            <div class="col-md-3 mb-3">
                <a href="wishlist.php" class="stat-card-link">
                    <div class="card shadow-sm border-0 bg-danger text-white p-3 h-100 d-flex flex-column justify-content-center">
                        <i class="fa fa-heart icon-bg"></i>
                        <h5 class="text-uppercase small mb-1" style="letter-spacing: 1px;">Saved Items</h5>
                        <h2 class="mb-0"><?php echo $wish_count; ?> Wishlisted</h2>
                    </div>
                </a>
            </div>
            
            <div class="col-md-3 mb-3">
                <a href="auction-lobby.php" class="stat-card-link">
                    <div class="card shadow-sm border-0 bg-dark text-white p-3 h-100 d-flex flex-column justify-content-center">
                        <i class="fa fa-gavel icon-bg text-warning"></i>
                        <h5 class="text-uppercase small mb-1 text-warning" style="letter-spacing: 1px;">Make a Bid</h5>
                        <h2 class="mb-0 text-white">Auctions</h2>
                    </div>
                </a>
            </div>
            
            <div class="col-md-3 mb-3">
                <a href="car-builder.php" class="stat-card-link">
                    <div class="card shadow-sm border-0 bg-warning p-3 h-100 d-flex flex-column justify-content-center">
                        <i class="fa fa-wrench icon-bg text-dark"></i>
                        <h5 class="text-uppercase small mb-1 text-dark" style="letter-spacing: 1px;">Custom Build</h5>
                        <h2 class="mb-0 text-dark">Build a Car</h2>
                    </div>
                </a>
            </div>
        </div>

            <!-- Budget Builder Button -->
        <div class="row mb-4">
            <div class="col-md-12">
                <a href="budget-knapsack.php" class="stat-card-link">
                    <div class="card shadow-sm border-0 text-center p-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                        <i class="fa fa-calculator fa-3x mb-2"></i>
                        <h3 class="mb-0 fw-bold">🎒 Budget Builder</h3>
                        <p class="mb-0 mt-2">Enter your budget and let AI auto-select the best parts for your car!</p>
                    </div>
                </a>
            </div>
        </div>


        
        <div class="row">
            <div class="col-md-8">
                <div class="card shadow-sm border-0 mb-5">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fa fa-history text-muted me-2"></i> Recent Orders</h5>
                        <a href="history.php" class="btn btn-sm btn-outline-secondary">View All</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Item</th>
                                        <th>Date</th>
                                        <th>Status</th>
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
                                            <span class="fw-semibold"><?php echo $display_name; ?></span>
                                        </td>
                                        <td class="small text-muted"><?php echo $order['date_ordered']; ?></td>
                                        <td><span class="badge bg-light text-dark border"><i class="fa fa-check-circle text-success"></i> Completed</span></td>
                                        <td class="fw-bold text-success">$<?php echo number_format($order['total_price'], 2); ?></td>
                                    </tr>
                                    <?php 
                                        } 
                                    } else { ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">No recent purchases found.</td>
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
                        <a href="repair-dashboard.php" class="btn btn-sm btn-outline-secondary">View All</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Vehicle</th>
                                        <th>Repair ID</th>
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
                                        <td class="small text-muted">#<?php echo $rep['repair_id']; ?></td>
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
                        <h5 class="mb-0"><i class="fa fa-bolt text-warning me-2"></i> Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-3">
                            <a href="store.php" class="btn btn-primary p-3 text-start">
                                <i class="fa fa-shopping-bag me-2"></i> Browse Parts Store
                            </a>
                            <a href="showroom.php" class="btn btn-info text-white p-3 text-start">
                                <i class="fa fa-car me-2"></i> Visit Showroom
                            </a>
                            <a href="repair-dashboard.php" class="btn btn-success text-white p-3 text-start">
                                <i class="fa fa-wrench me-2"></i> Repair Your Car
                            </a>
                            <a href="claim-warranty.php" class="btn btn-outline-dark p-3 text-start">
                                <i class="fa fa-shield me-2"></i> File Warranty Claim
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm border-0 bg-dark text-white">
                    <div class="card-body text-center p-4">
                        <i class="fa fa-envelope-o fa-2x mb-2 text-warning"></i>
                        <h5>Need Help?</h5>
                        <p class="small opacity-75">Have questions about an order or a custom build?</p>
                        <a href="contact.php" class="btn btn-sm btn-warning">Message Support</a>
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