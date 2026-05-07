<?php 
session_start();

if (isset($_SESSION['user_id']) && 
    isset($_SESSION['role'])    && 
    $_SESSION['role'] == 'Employee') {
    
    include "../DB_connection.php";

    // 1. Fetch Categories for Filter (Cars only)
    $cat_sql = "SELECT * FROM categories WHERE type = 'Car' ORDER BY category_name ASC";
    
    $cat_stmt = $conn->prepare($cat_sql);
    
    $cat_stmt->execute();
    
    $categories = $cat_stmt->fetchAll();


    // 2. Advanced Filtering Logic (Strictly Active Auctions)
    $where_clause = " WHERE auctions.status = 'Active' "; 
    
    $params = [];

    if (!empty($_GET['search'])) {
        $where_clause = $where_clause . " AND (cars.model LIKE ? OR cars.brand LIKE ?) ";
        $params[] = "%" . $_GET['search'] . "%";
        $params[] = "%" . $_GET['search'] . "%";
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

    // 3. Fetch Data
    $sql = "SELECT auctions.*, 
                   cars.model, cars.brand, cars.year, cars.image, cars.transmission, cars.mileage,
                   categories.category_name 
            FROM auctions 
            LEFT JOIN cars ON auctions.car_id = cars.id 
            LEFT JOIN categories ON cars.category_id = categories.id 
            $where_clause 
            ORDER BY auctions.end_time ASC";
            
    $stmt = $conn->prepare($sql);
    
    $stmt->execute($params);
    
    $auctions = $stmt->fetchAll();

    $auction_count = count($auctions);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Employee - Active Auctions</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        .stats-card { border: none; border-radius: 12px; transition: 0.3s; }
        .auction-card { transition: 0.3s; border: none; border-radius: 15px; overflow: hidden; }
        .auction-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .filter-section { background: #fff; border-radius: 15px; padding: 20px; position: sticky; top: 20px; }
        .car-img-top { height: 180px; width: 100%; object-fit: cover; }
        .spec-badge { background: #f8f9fa; color: #6c757d; font-size: 0.72rem; padding: 4px 8px; border-radius: 5px; border: 1px solid #eee; }
        .page-header-container { display: flex; align-items: center; margin-bottom: 25px; padding: 10px 0; border-bottom: 1px solid #dee2e6; }
        .page-header-icon { background-color: #198754; color: white; width: 50px; height: 50px; border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-right: 15px; font-size: 1.5rem; }
        .page-header-title { font-weight: 800; font-size: 1.8rem; color: #212529; margin: 0; text-transform: uppercase; }
        .bid-display { background: #fdfdfd; border-top: 1px dashed #ddd; padding: 10px; text-align: center; }
        .bid-number { font-size: 1.4rem; font-weight: 800; display: block; }
        .bid-label { font-size: 0.65rem; text-transform: uppercase; color: #888; }
        .clickable-card-link { text-decoration: none; color: inherit; }
    </style>
</head>
<body class="bg-light">
    <?php include "inc/navbar.php"; ?>

    <div class="container-fluid mt-4 px-4">
        
        <div class="page-header-container">
            <div class="page-header-icon">
                <i class="fa fa-gavel"></i>
            </div>
            <h2 class="page-header-title">Live Auctions</h2>
        </div>

        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card stats-card bg-success text-white shadow-sm">
                    <div class="card-body d-flex align-items-center">
                        <i class="fa fa-bolt fa-2x me-3"></i>
                        <div>
                            <div style="font-size: 0.7rem; text-transform: uppercase; opacity: 0.9;">Currently Active</div>
                            <h4 class="mb-0 fw-bold"><?php echo $auction_count; ?> Auctions Found</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-3">
                <div class="filter-section shadow-sm mb-4">
                    <form action="auction-dashboard.php" method="GET">
                        
                        <h6 class="fw-bold mb-3"><i class="fa fa-search"></i> Search</h6>
                        <div class="mb-4">
                            <input type="text" name="search" class="form-control form-control-sm" placeholder="Model or Brand..." value="<?php echo $_GET['search'] ?? ''; ?>">
                        </div>

                        <h6 class="fw-bold mb-2">Category</h6>
                        <div class="mb-4">
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

                        <h6 class="fw-bold mb-2">Transmission</h6>
                        <div class="mb-4">
                            <select name="transmission" class="form-select form-select-sm">
                                <option value="">Any</option>
                                <option value="Automatic" <?php echo (isset($_GET['transmission']) && $_GET['transmission'] == 'Automatic') ? 'selected' : ''; ?>>Automatic</option>
                                <option value="Manual" <?php echo (isset($_GET['transmission']) && $_GET['transmission'] == 'Manual') ? 'selected' : ''; ?>>Manual</option>
                            </select>
                        </div>

                        <h6 class="fw-bold mb-2">Price Range</h6>
                        <div class="row g-2 mb-4">
                            <div class="col-6">
                                <input type="number" name="min_price" class="form-control form-control-sm" placeholder="Min" value="<?php echo $_GET['min_price'] ?? ''; ?>">
                            </div>
                            <div class="col-6">
                                <input type="number" name="max_price" class="form-control form-control-sm" placeholder="Max" value="<?php echo $_GET['max_price'] ?? ''; ?>">
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-sm btn-dark">Apply Filters</button>
                            <a href="auction-dashboard.php" class="btn btn-sm btn-outline-secondary">Reset</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-md-9">
                <div class="row">
                    <?php if ($auction_count > 0) { ?>
                        <?php for ($i = 0; $i < $auction_count; $i = $i + 1) { 
                            $a = $auctions[$i]; 
                        ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="card auction-card shadow-sm h-100">
                                <a href="car-view.php?id=<?php echo $a['car_id']; ?>">
                                    <img src="../uploads/<?php echo $a['image']; ?>" class="car-img-top" onerror="this.src='https://via.placeholder.com/300x180?text=No+Image'">
                                </a>
                                
                                <div class="card-body">
                                    <div class="d-flex justify-content-between mb-1">
                                        <small class="text-success fw-bold"><?php echo $a['brand']; ?></small>
                                        <span class="badge bg-light text-dark border"><?php echo $a['category_name']; ?></span>
                                    </div>

                                    <a href="car-view.php?id=<?php echo $a['car_id']; ?>" class="clickable-card-link">
                                        <h5 class="card-title fw-bold mb-2"><?php echo $a['year'] . " " . $a['model']; ?></h5>
                                    </a>
                                    
                                    <div class="d-flex flex-wrap gap-1 mb-3">
                                        <span class="spec-badge"><i class="fa fa-cog"></i> <?php echo $a['transmission']; ?></span>
                                        <span class="spec-badge"><i class="fa fa-dashboard"></i> <?php echo number_format($a['mileage']); ?> km</span>
                                    </div>

                                    <div class="text-danger small fw-bold">
                                        <i class="fa fa-clock-o"></i> Ends: <?php echo date('M d, H:i', strtotime($a['end_time'])); ?>
                                    </div>
                                </div>

                                <div class="bid-display">
                                    <span class="bid-label">Current High Bid</span>
                                    <span class="bid-number text-success">$<?php echo number_format($a['current_bid']); ?></span>
                                </div>

                                <div class="card-footer bg-transparent border-0 pb-3">
                                    <div class="d-flex flex-column gap-2">
                                        <button onclick="endAuction(<?php echo $a['id']; ?>)" class="btn btn-sm btn-success">
                                            <i class="fa fa-check-circle"></i> End & Settle
                                        </button>
                                        <button onclick="stopAuction(<?php echo $a['id']; ?>)" class="btn btn-sm btn-danger">
                                            <i class="fa fa-stop-circle"></i> Terminate
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php } ?>
                    <?php } else { ?>
                        <div class="col-12 text-center py-5">
                            <i class="fa fa-gavel fa-3x text-muted"></i>
                            <h5 class="mt-3 text-muted">No active auctions found.</h5>
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