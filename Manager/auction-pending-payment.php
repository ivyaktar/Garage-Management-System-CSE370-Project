<?php 
session_start();

if (isset($_SESSION['user_id']) && 
    isset($_SESSION['role'])    && 
    $_SESSION['role'] == 'Manager') {
    
    include "../DB_connection.php";

    // --- 1. Fetch System Settings & Categories ---
    $setting_sql = "SELECT current_year FROM setting LIMIT 1";
    $setting_stmt = $conn->prepare($setting_sql);
    $setting_stmt->execute();
    $setting_data = $setting_stmt->fetch();
    $current_system_year = $setting_data['current_year'];

    $cat_sql = "SELECT * FROM categories WHERE type = 'Car' ORDER BY category_name ASC";
    $cat_stmt = $conn->prepare($cat_sql);
    $cat_stmt->execute();
    $categories = $cat_stmt->fetchAll();

    $transmissions = ["Automatic", "Manual", "CVT", "DCT", "AMT"];


    // --- 2. Initialize Filter Variables ---
    $search_name = isset($_GET['search_name']) ? $_GET['search_name'] : "";
    $brand = isset($_GET['brand']) ? $_GET['brand'] : "";
    $model = isset($_GET['model']) ? $_GET['model'] : "";
    $cat_id = isset($_GET['cat_id']) ? $_GET['cat_id'] : "";
    $min_price = isset($_GET['min_price']) ? $_GET['min_price'] : "";
    $max_price = isset($_GET['max_price']) ? $_GET['max_price'] : "";
    $min_year = isset($_GET['min_year']) ? $_GET['min_year'] : "";
    $max_year = isset($_GET['max_year']) ? $_GET['max_year'] : "";
    $max_mileage = isset($_GET['max_mileage']) ? $_GET['max_mileage'] : "";
    $transmission = isset($_GET['transmission']) ? $_GET['transmission'] : "";


    // --- 3. Build Filtered Query ---
    $sql = "SELECT auctions.*, 
                   cars.model, cars.brand, cars.image, cars.transmission, cars.year, cars.mileage,
                   customer.fname, customer.lname, customer.email_address, customer.phone_number
            FROM auctions 
            JOIN cars ON auctions.car_id = cars.id 
            JOIN customer ON auctions.highest_bidder_id = customer.user_id
            WHERE auctions.status = 'Closed' 
            AND auctions.winner_paid = 0";

    $params = [];

    if ($search_name != "") { 
        $sql = $sql . " AND (customer.fname LIKE ? OR customer.lname LIKE ?)"; 
        $params[] = "%$search_name%";
        $params[] = "%$search_name%";
    }

    if ($brand != "") { 
        $sql = $sql . " AND cars.brand LIKE ?"; 
        $params[] = "%$brand%";
    }

    if ($model != "") { 
        $sql = $sql . " AND cars.model LIKE ?"; 
        $params[] = "%$model%";
    }

    if ($cat_id != "") {
        $sql = $sql . " AND cars.category_id = ?";
        $params[] = $cat_id;
    }

    if ($transmission != "") { 
        $sql = $sql . " AND cars.transmission = ?"; 
        $params[] = $transmission;
    }

    if ($min_price != "") { 
        $sql = $sql . " AND auctions.current_bid >= ?"; 
        $params[] = $min_price;
    }

    if ($max_price != "") { 
        $sql = $sql . " AND auctions.current_bid <= ?"; 
        $params[] = $max_price;
    }

    if ($min_year != "") {
        $sql = $sql . " AND cars.year >= ?";
        $params[] = $min_year;
    }

    if ($max_year != "") {
        $sql = $sql . " AND cars.year <= ?";
        $params[] = $max_year;
    }

    if ($max_mileage != "") {
        $sql = $sql . " AND cars.mileage <= ?";
        $params[] = $max_mileage;
    }

    $sql = $sql . " ORDER BY auctions.end_time DESC";


    // --- 4. Execute and Calculate ---
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $pending = $stmt->fetchAll();
    $pending_count = count($pending);

    $total_revenue = 0;
    for ($x = 0; $x < $pending_count; $x = $x + 1) {
        $total_revenue = $total_revenue + $pending[$x]['current_bid'];
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Financial Overview - Rev Nation</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        .payment-card { transition: 0.3s; border: none; border-radius: 15px; overflow: hidden; background: #fff; }
        .payment-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .filter-section { background: #fff; border-radius: 15px; padding: 20px; }
        .car-img-aside { width: 100%; height: 100%; min-height: 220px; object-fit: cover; }
        .spec-badge { background: #f8f9fa; color: #6c757d; font-size: 0.75rem; padding: 4px 8px; border-radius: 5px; border: 1px solid #eee; }
        .price-tag { font-size: 1.5rem; font-weight: 800; color: #198754; }
        .stats-banner { background: #fff; border-radius: 15px; border-left: 5px solid #0d6efd; }
        .section-label { font-size: 0.7rem; font-weight: 700; color: #adb5bd; text-transform: uppercase; letter-spacing: 1px; }
        
        .main-content { max-width: 1300px; margin: 0 auto; }
    </style>
</head>
<body class="bg-light">
    <?php include "inc/navbar.php"; ?>

    <div class="container mt-4 px-3">
        <div class="main-content">
            
            <?php if (isset($_GET['success'])) { ?>
                <div class="alert alert-success alert-dismissible fade show shadow-sm mb-4" role="alert">
                    <i class="fa fa-check-circle me-2"></i>
                    <?php echo $_GET['success']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php } ?>

            <?php if (isset($_GET['error'])) { ?>
                <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-4" role="alert">
                    <i class="fa fa-exclamation-triangle me-2"></i>
                    <?php echo $_GET['error']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php } ?>
            
            <div class="row mb-4 align-items-end">
                <div class="col-lg-8 col-md-7">
                    <h2 class="fw-bold"><i class="fa fa-university me-2 text-primary"></i>Financial Overview</h2>
                    <p class="text-muted mb-0">Manage pending auction payments and vehicle verification for Rev Nation.</p>
                </div>
                <div class="col-lg-4 col-md-5 mt-3 mt-md-0">
                    <div class="stats-banner p-3 shadow-sm d-flex justify-content-between align-items-center">
                        <div>
                            <span class="section-label">Total Outstanding</span>
                            <h3 class="fw-bold mb-0 text-primary">$<?php echo number_format($total_revenue); ?></h3>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-warning text-dark rounded-pill px-3"><?php echo $pending_count; ?> Items</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-3 col-md-4 mb-4">
                    <div class="filter-section shadow-sm">
                        <form action="auction-pending-payment.php" method="GET">
                            
                            <h6 class="fw-bold mb-3"><i class="fa fa-filter me-2"></i>Filters</h6>
                            
                            <div class="mb-3">
                                <label class="small text-muted fw-bold">Bidder Name</label>
                                <input type="text" name="search_name" class="form-control form-control-sm" placeholder="Search customer..." value="<?php echo $search_name; ?>">
                            </div>

                            <hr>

                            <div class="mb-3">
                                <label class="small text-muted fw-bold">Brand & Model</label>
                                <div class="mb-2">
                                    <input type="text" name="brand" class="form-control form-control-sm mb-1" placeholder="Brand" value="<?php echo $brand; ?>">
                                    <input type="text" name="model" class="form-control form-control-sm" placeholder="Model" value="<?php echo $model; ?>">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="small text-muted fw-bold">Category</label>
                                <select name="cat_id" class="form-select form-select-sm">
                                    <option value="">All Types</option>
                                    <?php 
                                    $cat_count = count($categories);
                                    for ($i = 0; $i < $cat_count; $i = $i + 1) { 
                                    ?>
                                        <option value="<?php echo $categories[$i]['id']; ?>" <?php echo ($cat_id == $categories[$i]['id']) ? 'selected' : ''; ?>>
                                            <?php echo $categories[$i]['category_name']; ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="small text-muted fw-bold">Transmission</label>
                                <select name="transmission" class="form-select form-select-sm">
                                    <option value="">Any</option>
                                    <?php 
                                    $trans_count = count($transmissions);
                                    for ($i = 0; $i < $trans_count; $i = $i + 1) { 
                                    ?>
                                        <option value="<?php echo $transmissions[$i]; ?>" <?php if($transmission == $transmissions[$i]) echo "selected"; ?>>
                                            <?php echo $transmissions[$i]; ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="small text-muted fw-bold">Winning Bid ($)</label>
                                <div class="input-group input-group-sm">
                                    <input type="number" name="min_price" class="form-control" placeholder="Min" value="<?php echo $min_price; ?>">
                                    <input type="number" name="max_price" class="form-control" placeholder="Max" value="<?php echo $max_price; ?>">
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="small text-muted fw-bold">Year (1900 - <?php echo $current_system_year; ?>)</label>
                                <div class="input-group input-group-sm">
                                    <input type="number" name="min_year" class="form-control" placeholder="From" value="<?php echo $min_year; ?>">
                                    <input type="number" name="max_year" class="form-control" placeholder="To" value="<?php echo $max_year; ?>">
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-dark btn-sm">Apply Filters</button>
                                <a href="auction-pending-payment.php" class="btn btn-outline-secondary btn-sm">Reset</a>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="col-lg-9 col-md-8">
                    <?php if ($pending_count > 0) { ?>
                        <?php for ($i = 0; $i < $pending_count; $i = $i + 1) { 
                            $p = $pending[$i];
                        ?>
                            <div class="card payment-card shadow-sm mb-4">
                                <div class="row g-0">
                                    <div class="col-xl-4 col-lg-5">
                                        <img src="../uploads/<?php echo $p['image']; ?>" class="car-img-aside" onerror="this.src='https://via.placeholder.com/400x250?text=Vehicle'">
                                    </div>
                                    <div class="col-xl-5 col-lg-7 p-4">
                                        <div class="mb-3">
                                            <span class="section-label text-success">Winning Bidder</span>
                                            <h5 class="fw-bold mb-1"><?php echo $p['fname'] . " " . $p['lname']; ?></h5>
                                            <div class="text-muted small">
                                                <i class="fa fa-envelope me-2"></i><?php echo $p['email_address']; ?><br>
                                                <i class="fa fa-phone me-2"></i><?php echo $p['phone_number']; ?>
                                            </div>
                                        </div>
                                        <div class="d-flex flex-wrap gap-2 mt-3">
                                            <span class="spec-badge"><i class="fa fa-car me-1"></i> <?php echo $p['brand'] . " " . $p['model']; ?></span>
                                            <span class="spec-badge"><i class="fa fa-calendar me-1"></i> <?php echo $p['year']; ?></span>
                                            <span class="spec-badge"><i class="fa fa-road me-1"></i> <?php echo number_format($p['mileage']); ?> km</span>
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-lg-12 p-4 bg-light d-flex flex-column justify-content-center border-start">
                                        <div class="text-center mb-3">
                                            <span class="section-label">Final Bid Amount</span>
                                            <div class="price-tag">$<?php echo number_format($p['current_bid']); ?></div>
                                        </div>
                                        <div class="d-grid gap-2">
                                            <button onclick="confirmPaid(<?php echo $p['id']; ?>)" class="btn btn-success fw-bold">
                                                <i class="fa fa-check-circle me-1"></i> MARK PAID
                                            </button>

                                            <button onclick="rejectPayment(<?php echo $p['id']; ?>)" class="btn btn-danger fw-bold">
                                                <i class="fa fa-times-circle me-1"></i> REJECT BID
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                    <?php } else { ?>
                        <div class="card border-0 shadow-sm p-5 text-center">
                            <i class="fa fa-search-minus fa-3x text-muted mb-3"></i>
                            <h4 class="text-muted">No pending payments found</h4>
                            <p>Try adjusting your filters or check back later.</p>
                        </div>
                    <?php } ?>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmPaid(id) {
            window.location.href = "req/auction-confirm-payment.php?id=" + id;
        }

        function rejectPayment(id) {
            window.location.href = "req/auction-reject-payment.php?id=" + id;
        }
    </script>
</body>
</html>
<?php 
} else {
    header("Location: ../login.php");
    exit;
} 
?>