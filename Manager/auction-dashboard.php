<?php 
session_start();

if (isset($_SESSION['user_id']) && 
    isset($_SESSION['role'])    && 
    $_SESSION['role'] == 'Manager') {
    
    include "../DB_connection.php";

    // 1. Fetch Categories for Filter (Cars only)
    $cat_sql = "SELECT * FROM categories WHERE type = 'Car' ORDER BY category_name ASC";
    $cat_stmt = $conn->prepare($cat_sql);
    $cat_stmt->execute();
    $categories = $cat_stmt->fetchAll();


    // 2. Advanced Filtering Logic for Auctions
    $where_clause = " WHERE 1=1 "; 
    $params = [];

    if (!empty($_GET['search'])) {
        $where_clause = $where_clause . " AND (cars.model LIKE ? OR cars.brand LIKE ?) ";
        $params[] = "%" . $_GET['search'] . "%";
        $params[] = "%" . $_GET['search'] . "%";
    }

    if (!empty($_GET['status_filter'])) {
        $where_clause = $where_clause . " AND auctions.status = ? ";
        $params[] = $_GET['status_filter'];
    }

    if (!empty($_GET['category'])) {
        $where_clause = $where_clause . " AND categories.category_name = ? ";
        $params[] = $_GET['category'];
    }

    if (!empty($_GET['transmission'])) {
        $where_clause = $where_clause . " AND cars.transmission = ? ";
        $params[] = $_GET['transmission'];
    }

    if (!empty($_GET['min_price'])) {
        $where_clause = $where_clause . " AND auctions.current_bid >= ? ";
        $params[] = $_GET['min_price'];
    }
    
    if (!empty($_GET['max_price'])) {
        $where_clause = $where_clause . " AND auctions.current_bid <= ? ";
        $params[] = $_GET['max_price'];
    }

    if (!empty($_GET['min_year'])) {
        $where_clause = $where_clause . " AND cars.year >= ? ";
        $params[] = $_GET['min_year'];
    }
    
    if (!empty($_GET['max_year'])) {
        $where_clause = $where_clause . " AND cars.year <= ? ";
        $params[] = $_GET['max_year'];
    }

    if (!empty($_GET['max_mileage'])) {
        $where_clause = $where_clause . " AND cars.mileage <= ? ";
        $params[] = $_GET['max_mileage'];
    }

    if (!empty($_GET['start_date'])) {
        $where_clause = $where_clause . " AND auctions.end_time >= ? ";
        $params[] = $_GET['start_date'] . " 00:00:00";
    }
    
    if (!empty($_GET['end_date'])) {
        $where_clause = $where_clause . " AND auctions.end_time <= ? ";
        $params[] = $_GET['end_date'] . " 23:59:59";
    }


    // 3. Fetch Auctions with Car Details (Updated to check for car existence)
    $sql = "SELECT auctions.*, 
                   cars.id AS car_id_exists,
                   cars.model, cars.brand, cars.year, cars.image, cars.transmission, cars.mileage,
                   categories.category_name 
            FROM auctions 
            LEFT JOIN cars ON auctions.car_id = cars.id 
            LEFT JOIN categories ON cars.category_id = categories.id 
            $where_clause 
            ORDER BY auctions.created_at DESC";
            
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $auctions = $stmt->fetchAll();


    // 4. Calculate Stats
    $total_active = 0;
    $unpaid_auctions = 0;
    $auction_count = count($auctions);

    // Pull total earnings directly from the sales table for accuracy
    $sql_earn = "SELECT SUM(sale_price) as total FROM auction_sales";
    $stmt_earn = $conn->prepare($sql_earn);
    $stmt_earn->execute();
    $earn_res = $stmt_earn->fetch();
    $total_earnings = $earn_res['total'] ?? 0;

    for ($i = 0; $i < $auction_count; $i = $i + 1) {
        
        if ($auctions[$i]['status'] == 'Active') {
            $total_active = $total_active + 1;
        }

        if ($auctions[$i]['status'] == 'Closed' && $auctions[$i]['winner_paid'] == 0) {
            $unpaid_auctions = $unpaid_auctions + 1;
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manager - Auction Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        .stats-card { border: none; border-radius: 12px; transition: 0.3s; min-height: 100px; }
        .auction-card { transition: 0.3s; border: none; border-radius: 15px; overflow: hidden; position: relative; }
        .auction-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .filter-section { background: #fff; border-radius: 15px; padding: 20px; position: sticky; top: 20px; }
        .car-img-top { height: 180px; width: 100%; object-fit: cover; }
        .spec-badge { background: #f8f9fa; color: #6c757d; font-size: 0.72rem; padding: 4px 8px; border-radius: 5px; border: 1px solid #eee; }
        
        .page-header-container { display: flex; align-items: center; margin-bottom: 25px; padding: 10px 0; border-bottom: 1px solid #dee2e6; }
        .page-header-icon { background-color: #0d6efd; color: white; width: 50px; height: 50px; border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-right: 15px; font-size: 1.5rem; }
        .page-header-title { font-weight: 800; font-size: 1.8rem; color: #212529; margin: 0; text-transform: uppercase; letter-spacing: 1px; }
        
        .bid-display { background: #fdfdfd; border-top: 1px dashed #ddd; padding: 10px; text-align: center; }
        .bid-number { font-size: 1.4rem; font-weight: 800; display: block; }
        .bid-label { font-size: 0.65rem; text-transform: uppercase; letter-spacing: 1px; color: #888; }
        .card-header-text { font-size: 0.65rem; text-transform: uppercase; letter-spacing: 1px; opacity: 0.8; font-weight: 600; }
        .clickable-card-link { text-decoration: none; color: inherit; display: block; }
        .clickable-card-link:hover { color: #0d6efd; }
    </style>
</head>
<body class="bg-light">
    <?php include "inc/navbar.php"; ?>

    <div class="container-fluid mt-4 px-4">
        
        <div class="page-header-container">
            <div class="page-header-icon bg-primary">
                <i class="fa fa-gavel"></i>
            </div>
            <h2 class="page-header-title">Auction Management</h2>
        </div>

        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stats-card bg-primary text-white shadow-sm">
                    <div class="card-body d-flex align-items-center">
                        <i class="fa fa-gavel fa-2x me-3"></i>
                        <div>
                            <div class="card-header-text">Live Auctions</div>
                            <h4 class="mb-0 fw-bold"><?php echo $total_active; ?> Units</h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <a href="auction-sales.php" style="text-decoration: none;">
                    <div class="card stats-card bg-success text-white shadow-sm">
                        <div class="card-body d-flex align-items-center">
                            <i class="fa fa-money fa-2x me-3"></i>
                            <div>
                                <div class="card-header-text">Total Earnings</div>
                                <h4 class="mb-0 fw-bold">$<?php echo number_format($total_earnings); ?></h4>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-3">
                <a href="auction-pending-payment.php" style="text-decoration: none;">
                    <div class="card stats-card bg-warning text-dark shadow-sm">
                        <div class="card-body d-flex align-items-center">
                            <i class="fa fa-clock-o fa-2x me-3"></i>
                            <div>
                                <div class="card-header-text">Pending Payments</div>
                                <h4 class="mb-0 fw-bold"><?php echo $unpaid_auctions; ?></h4>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-3">
                <div class="card stats-card bg-dark text-white shadow-sm">
                    <div class="card-body d-flex align-items-center">
                        <i class="fa fa-history fa-2x me-3"></i>
                        <div>
                            <div class="card-header-text">Total Auctions</div>
                            <h4 class="mb-0 fw-bold"><?php echo $auction_count; ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-3">
                <div class="filter-section shadow-sm mb-4">
                    <form action="auction-dashboard.php" method="GET">
                        
                        <h6 class="fw-bold mb-3"><i class="fa fa-search"></i> Quick Search</h6>
                        <div class="mb-4">
                            <input type="text" name="search" class="form-control form-control-sm" placeholder="Model or Brand..." value="<?php echo $_GET['search'] ?? ''; ?>">
                        </div>

                        <h6 class="fw-bold mb-2">Category & Status</h6>
                        <div class="mb-2">
                            <select name="category" class="form-select form-select-sm">
                                <option value="">All Categories</option>
                                <?php 
                                $cat_count = count($categories);
                                for ($j = 0; $j < $cat_count; $j = $j + 1) { 
                                ?>
                                    <option value="<?php echo $categories[$j]['category_name']; ?>" <?php echo (isset($_GET['category']) && $_GET['category'] == $categories[$j]['category_name']) ? 'selected' : ''; ?>>
                                        <?php echo $categories[$j]['category_name']; ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="mb-4">
                             <select name="status_filter" class="form-select form-select-sm">
                                <option value="">All Statuses</option>
                                <option value="Active" <?php echo (isset($_GET['status_filter']) && $_GET['status_filter'] == 'Active') ? 'selected' : ''; ?>>Active</option>
                                <option value="Closed" <?php echo (isset($_GET['status_filter']) && $_GET['status_filter'] == 'Closed') ? 'selected' : ''; ?>>Closed</option>
                                <option value="Sold" <?php echo (isset($_GET['status_filter']) && $_GET['status_filter'] == 'Sold') ? 'selected' : ''; ?>>Sold</option>
                                <option value="Failed" <?php echo (isset($_GET['status_filter']) && $_GET['status_filter'] == 'Failed') ? 'selected' : ''; ?>>Failed</option>
                             </select>
                        </div>

                        <h6 class="fw-bold mb-2">Technical Specs</h6>
                        <div class="mb-2">
                            <select name="transmission" class="form-select form-select-sm">
                                <option value="">Transmission</option>
                                <option value="Automatic" <?php echo (isset($_GET['transmission']) && $_GET['transmission'] == 'Automatic') ? 'selected' : ''; ?>>Automatic</option>
                                <option value="Manual" <?php echo (isset($_GET['transmission']) && $_GET['transmission'] == 'Manual') ? 'selected' : ''; ?>>Manual</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <input type="number" name="max_mileage" class="form-control form-control-sm" placeholder="Max Mileage (km)" value="<?php echo $_GET['max_mileage'] ?? ''; ?>">
                        </div>

                        <h6 class="fw-bold mb-2">Bid Price Range</h6>
                        <div class="row g-2 mb-4">
                            <div class="col-6">
                                <input type="number" name="min_price" class="form-control form-control-sm" placeholder="Min $" value="<?php echo $_GET['min_price'] ?? ''; ?>">
                            </div>
                            <div class="col-6">
                                <input type="number" name="max_price" class="form-control form-control-sm" placeholder="Max $" value="<?php echo $_GET['max_price'] ?? ''; ?>">
                            </div>
                        </div>

                        <h6 class="fw-bold mb-2">Manufacturing Year</h6>
                        <div class="row g-2 mb-4">
                            <div class="col-6">
                                <input type="number" name="min_year" class="form-control form-control-sm" placeholder="From" value="<?php echo $_GET['min_year'] ?? ''; ?>">
                            </div>
                            <div class="col-6">
                                <input type="number" name="max_year" class="form-control form-control-sm" placeholder="To" value="<?php echo $_GET['max_year'] ?? ''; ?>">
                            </div>
                        </div>

                        <h6 class="fw-bold mb-2">Auction End Date</h6>
                        <div class="mb-2">
                            <label class="small text-muted">From:</label>
                            <input type="date" name="start_date" class="form-control form-control-sm" value="<?php echo $_GET['start_date'] ?? ''; ?>">
                        </div>
                        <div class="mb-4">
                            <label class="small text-muted">To:</label>
                            <input type="date" name="end_date" class="form-control form-control-sm" value="<?php echo $_GET['end_date'] ?? ''; ?>">
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-sm btn-dark">Apply Filters</button>
                            <a href="auction-dashboard.php" class="btn btn-sm btn-outline-secondary">Reset All</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-md-9">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="fw-bold m-0">Auction List</h3>
                    <div class="d-flex gap-2">
                        <a href="auction-sales.php" class="btn btn-outline-dark" style="font-weight:700; border-radius:10px; padding:10px 20px;">
                            <i class="fa fa-line-chart me-2"></i> AUCTION SALES
                        </a>
                        <a href="add-auction.php" class="btn btn-success" style="font-weight:700; border-radius:10px; padding:10px 20px;">
                            <i class="fa fa-plus-circle me-2"></i> START NEW AUCTION
                        </a>
                    </div>
                </div>

                <div class="row">
                    <?php if ($auction_count > 0) { ?>
                        <?php for ($i = 0; $i < $auction_count; $i = $i + 1) { 
                            $a = $auctions[$i]; 
                            
                            // Check if car still exists
                            $is_car_deleted = empty($a['car_id_exists']);

                            $status_color = "text-primary";
                            $display_status = $a['status'];

                            if ($is_car_deleted) {
                                $status_color = "text-danger";
                                $display_status = "CAR DELETED";
                            } else {
                                if ($a['status'] == 'Closed') { $status_color = "text-secondary"; }
                                if ($a['status'] == 'Failed') { $status_color = "text-danger"; }
                                if ($a['status'] == 'Sold') { 
                                    $status_color = "text-success"; 
                                    $display_status = "Sold / Closed";
                                }
                            }
                        ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="card auction-card shadow-sm h-100">
                                <?php if ($is_car_deleted): ?>
                                    <div class="car-img-top bg-secondary d-flex align-items-center justify-content-center text-white">
                                        <i class="fa fa-trash fa-3x"></i>
                                    </div>
                                <?php else: ?>
                                    <a href="car-view.php?id=<?php echo $a['car_id']; ?>">
                                        <img src="../uploads/<?php echo $a['image']; ?>" class="car-img-top" onerror="this.src='https://via.placeholder.com/300x180?text=No+Image'">
                                    </a>
                                <?php endif; ?>
                                
                                <div class="card-body">
                                    <div class="d-flex justify-content-between mb-1">
                                        <small class="text-primary fw-bold text-uppercase">
                                            <?php echo $is_car_deleted ? 'DELETED' : $a['brand']; ?>
                                        </small>
                                        <span class="badge bg-light text-dark border">
                                            <?php echo $is_car_deleted ? 'None' : $a['category_name']; ?>
                                        </span>
                                    </div>

                                    <?php if ($is_car_deleted): ?>
                                        <h5 class="card-title fw-bold mb-2 text-muted">Stock Record Removed</h5>
                                    <?php else: ?>
                                        <a href="car-view.php?id=<?php echo $a['car_id']; ?>" class="clickable-card-link">
                                            <h5 class="card-title fw-bold mb-2"><?php echo $a['year'] . " " . $a['model']; ?></h5>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <div class="d-flex flex-wrap gap-1 mb-3">
                                        <?php if (!$is_car_deleted): ?>
                                            <span class="spec-badge"><i class="fa fa-cog"></i> <?php echo $a['transmission']; ?></span>
                                            <span class="spec-badge"><i class="fa fa-dashboard"></i> <?php echo number_format($a['mileage']); ?> km</span>
                                        <?php endif; ?>
                                        <span class="spec-badge text-danger"><i class="fa fa-clock-o"></i> Ends: <?php echo date('M d, H:i', strtotime($a['end_time'])); ?></span>
                                    </div>

                                    <div class="p-2 rounded border bg-light text-center">
                                        <small class="text-muted d-block card-header-text">Current Status</small>
                                        <span class="fw-bold <?php echo $status_color; ?>"><?php echo $display_status; ?></span>
                                    </div>
                                    
                                    <?php if ($is_car_deleted): ?>
                                        <div class="text-center mt-2 small text-danger fw-bold">the car has been removed from the showroom</div>
                                    <?php endif; ?>
                                </div>

                                <div class="bid-display">
                                    <span class="bid-label">Current High Bid</span>
                                    <span class="bid-number text-success">
                                        $<?php echo number_format($a['current_bid']); ?>
                                    </span>
                                </div>

                                <div class="card-footer bg-transparent border-0 pb-3">
                                    <div class="d-flex flex-column gap-2">
                                        <a href="view-bid-history.php?auction_id=<?php echo $a['id']; ?>" class="btn btn-sm btn-outline-primary w-100">
                                            <i class="fa fa-list"></i> View Bid History
                                        </a>
                                        <?php if ($a['status'] == 'Active') { ?>
                                            <button onclick="endAuction(<?php echo $a['id']; ?>)" class="btn btn-sm btn-success">
                                                <i class="fa fa-check-circle"></i> End & Settle Winner
                                            </button>
                                            <button onclick="stopAuction(<?php echo $a['id']; ?>)" class="btn btn-sm btn-danger">
                                                <i class="fa fa-stop-circle"></i> Terminate Auction
                                            </button>
                                        <?php } else { ?>
                                            <a href="auction-sales.php" class="btn btn-sm btn-outline-secondary">
                                                <i class="fa fa-file-text-o"></i> View Sale Details
                                            </a>
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php } ?>
                    <?php } else { ?>
                        <div class="col-12 text-center py-5">
                            <i class="fa fa-gavel fa-3x text-muted"></i>
                            <h5 class="mt-3 text-muted">No auctions found matching filters.</h5>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function stopAuction(id) {
            window.location.href = "req/auction-delete.php?id=" + id;
        }

        function endAuction(id) {
            window.location.href = "req/auction-end.php?id=" + id;
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