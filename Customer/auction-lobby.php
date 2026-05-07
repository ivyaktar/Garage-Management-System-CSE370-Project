<?php 
session_start();

if (isset($_SESSION['user_id']) && 
    isset($_SESSION['role'])    && 
    $_SESSION['role'] == 'Customer') {
    
    include "../DB_connection.php";
    $u_id = $_SESSION['user_id'];

    // --- 1. Fetch System Settings & Data ---

    // Fetch Current Year from setting table
    $setting_sql = "SELECT current_year FROM setting LIMIT 1";
    $setting_stmt = $conn->prepare($setting_sql);
    $setting_stmt->execute();
    $setting_data = $setting_stmt->fetch();
    $current_system_year = $setting_data['current_year'];

    // Fetch Categories for the filter dropdown
    $cat_sql = "SELECT * FROM categories WHERE type = 'Car' ORDER BY category_name ASC";
    $cat_stmt = $conn->prepare($cat_sql);
    $cat_stmt->execute();
    $categories = $cat_stmt->fetchAll();

    $transmissions = ["Automatic", "Manual", "CVT", "DCT", "AMT"];

    // --- 2. Build Advanced Filtering Logic ---

    // Note: We filter based on Car attributes (c) but display Auction data (a)
    $where_clause = " WHERE a.status = 'Active' "; 
    $params = [];

    // Search (Brand or Model)
    if (!empty($_GET['search'])) {
        $where_clause = $where_clause . " AND (c.model LIKE ? OR c.brand LIKE ?) ";
        $params[] = "%" . $_GET['search'] . "%";
        $params[] = "%" . $_GET['search'] . "%";
    }

    // Category
    if (!empty($_GET['cat_id'])) {
        $where_clause = $where_clause . " AND c.category_id = ? ";
        $params[] = $_GET['cat_id'];
    }

    // Current Bid Range (Using current_bid from auction table)
    if (!empty($_GET['min_price'])) {
        $where_clause = $where_clause . " AND a.current_bid >= ? ";
        $params[] = $_GET['min_price'];
    }

    if (!empty($_GET['max_price'])) {
        $where_clause = $where_clause . " AND a.current_bid <= ? ";
        $params[] = $_GET['max_price'];
    }

    // Year Range
    if (!empty($_GET['min_year'])) {
        $where_clause = $where_clause . " AND c.year >= ? ";
        $params[] = $_GET['min_year'];
    }

    if (!empty($_GET['max_year'])) {
        $where_clause = $where_clause . " AND c.year <= ? ";
        $params[] = $_GET['max_year'];
    }

    // Mileage
    if (!empty($_GET['max_mileage'])) {
        $where_clause = $where_clause . " AND c.mileage <= ? ";
        $params[] = $_GET['max_mileage'];
    }

    // Transmission
    if (!empty($_GET['trans'])) {
        $where_clause = $where_clause . " AND c.transmission = ? ";
        $params[] = $_GET['trans'];
    }

    // --- 3. Fetch Auctions ---

    $sql = "SELECT a.*, c.model, c.brand, c.year, c.image, c.transmission, c.mileage, cat.category_name 
            FROM auctions a
            JOIN cars c ON a.car_id = c.id
            JOIN categories cat ON c.category_id = cat.id 
            $where_clause 
            ORDER BY a.end_time ASC";
            
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $auctions = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Auction Lobby - Rev Nation</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    
    <style>
        .car-card { transition: 0.3s; border: none; border-radius: 15px; overflow: hidden; }
        .car-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .filter-section { background: #fff; border-radius: 15px; padding: 20px; position: sticky; top: 20px; }
        .car-img-top { height: 200px; width: 100%; object-fit: cover; }
        .spec-badge { background: #f8f9fa; color: #6c757d; font-size: 0.75rem; padding: 4px 8px; border-radius: 5px; border: 1px solid #eee; }
        .bid-tag { font-size: 1.2rem; font-weight: 800; color: #0d6efd; }
        .timer-badge { font-size: 0.85rem; font-weight: bold; background: #fff3cd; color: #856404; padding: 5px 10px; border-radius: 20px; }
        
        a { text-decoration: none; color: inherit; }
    </style>
</head>
<body class="bg-light">
    <?php include "inc/navbar.php"; ?>

    <div class="container-fluid mt-4 px-4">
        <div class="row mb-4">
            <div class="col-12 d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="fw-bold"><i class="fa fa-gavel me-2"></i>Live Auction Lobby</h2>
                    <p class="text-muted">Find and bid on premium vehicles.</p>
                </div>
                <a href="auction-history.php" class="btn btn-dark rounded-pill px-4 shadow-sm">
                    <i class="fa fa-history me-2"></i>My Bids & History
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-md-3">
                <div class="filter-section shadow-sm mb-4">
                    <form action="auction-lobby.php" method="GET">
                        
                        <h6 class="fw-bold mb-3">Search</h6>
                        <div class="input-group mb-4">
                            <input type="text" name="search" class="form-control" placeholder="Brand or Model..." value="<?php echo $_GET['search'] ?? ''; ?>">
                            <button class="btn btn-primary" type="submit"><i class="fa fa-search"></i></button>
                        </div>

                        <h6 class="fw-bold mb-3">Specifications</h6>

                        <div class="mb-3">
                            <label class="small text-muted">Category</label>
                            <select name="cat_id" class="form-select">
                                <option value="">All Types</option>
                                <?php for ($i = 0; $i < count($categories); $i = $i + 1) { ?>
                                    <option value="<?php echo $categories[$i]['id']; ?>" <?php echo (isset($_GET['cat_id']) && $_GET['cat_id'] == $categories[$i]['id']) ? 'selected' : ''; ?>>
                                        <?php echo $categories[$i]['category_name']; ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="small text-muted">Current Bid Range</label>
                            <div class="input-group">
                                <input type="number" name="min_price" class="form-control" placeholder="Min" value="<?php echo $_GET['min_price'] ?? ''; ?>">
                                <input type="number" name="max_price" class="form-control" placeholder="Max" value="<?php echo $_GET['max_price'] ?? ''; ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="small text-muted">Year Range</label>
                            <div class="input-group">
                                <input type="number" name="min_year" class="form-control" placeholder="From" min="1900" max="<?php echo $current_system_year; ?>" value="<?php echo $_GET['min_year'] ?? ''; ?>">
                                <input type="number" name="max_year" class="form-control" placeholder="To" min="1900" max="<?php echo $current_system_year; ?>" value="<?php echo $_GET['max_year'] ?? ''; ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="small text-muted">Max Mileage (km)</label>
                            <input type="number" name="max_mileage" class="form-control" placeholder="Up to..." value="<?php echo $_GET['max_mileage'] ?? ''; ?>">
                        </div>

                        <div class="mb-4">
                            <label class="small text-muted">Transmission</label>
                            <select name="trans" class="form-select">
                                <option value="">Any</option>
                                <?php for ($i = 0; $i < count($transmissions); $i = $i + 1) { ?>
                                    <option value="<?php echo $transmissions[$i]; ?>" <?php echo (isset($_GET['trans']) && $_GET['trans'] == $transmissions[$i]) ? 'selected' : ''; ?>>
                                        <?php echo $transmissions[$i]; ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-dark">Apply Filters</button>
                            <a href="auction-lobby.php" class="btn btn-outline-secondary text-center">Clear All</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-md-9">
                <div class="row">
                    <?php if (count($auctions) > 0) { ?>
                        <?php for ($i = 0; $i < count($auctions); $i = $i + 1) { 
                            $auc = $auctions[$i]; 
                        ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="card car-card shadow-sm h-100">
                                <a href="auction-view.php?id=<?php echo $auc['id']; ?>">
                                    <img src="../uploads/<?php echo $auc['image']; ?>" class="car-img-top" onerror="this.src='https://via.placeholder.com/400x250?text=Vehicle+Image'">
                                </a>
                                
                                <div class="card-body">
                                    <div class="mb-2">
                                        <span class="timer-badge">
                                            <i class="fa fa-clock-o"></i> Ends: <?php echo date("M d, H:i", strtotime($auc['end_time'])); ?>
                                        </span>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <small class="text-primary text-uppercase fw-bold"><?php echo $auc['brand']; ?></small>
                                            <h5 class="fw-bold mb-0"><?php echo $auc['model']; ?></h5>
                                        </div>
                                        <div class="bid-tag">$<?php echo number_format($auc['current_bid']); ?></div>
                                    </div>
                                    
                                    <div class="d-flex flex-wrap gap-1 mb-3">
                                        <span class="spec-badge"><?php echo $auc['year']; ?></span>
                                        <span class="spec-badge"><?php echo $auc['transmission']; ?></span>
                                        <span class="spec-badge"><?php echo number_format($auc['mileage']); ?> km</span>
                                    </div>
                                </div>

                                <div class="card-footer bg-white border-0 pb-3">
                                    <div class="d-grid gap-2">
                                        <a href="auction-view.php?id=<?php echo $auc['id']; ?>" class="btn btn-primary btn-sm fw-bold">
                                            <i class="fa fa-gavel"></i> Bid Now
                                        </a>
                                        <a href="car-view.php?id=<?php echo $auc['car_id']; ?>" class="btn btn-outline-dark btn-sm">
                                            <i class="fa fa-eye"></i> Car Details
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php } ?>
                    <?php } else { ?>
                        <div class="col-12 text-center py-5">
                            <i class="fa fa-gavel fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No active auctions match your filters.</h5>
                        </div>
                    <?php } ?>
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