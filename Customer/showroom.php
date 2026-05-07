<?php 
session_start();

if (isset($_SESSION['user_id']) && 
    isset($_SESSION['role'])    && 
    $_SESSION['role'] == 'Customer') {
    
    include "../DB_connection.php";
    $u_id = $_SESSION['user_id'];

    // Fetch Current Year from setting table
    $setting_sql = "SELECT current_year FROM setting LIMIT 1";
    $setting_stmt = $conn->prepare($setting_sql);
    $setting_stmt->execute();
    $setting_data = $setting_stmt->fetch();
    $current_system_year = $setting_data['current_year'];


    // --- FIXED: Fetch User's current wishlist for CARS ---
    $wish_sql = "SELECT car_id FROM wishlist WHERE user_id = ? AND car_id IS NOT NULL";
    $wish_stmt = $conn->prepare($wish_sql);
    $wish_stmt->execute([$u_id]);
    $wishlist_items = $wish_stmt->fetchAll();
    
    $wishlist_ids = [];
    for ($i = 0; $i < count($wishlist_items); $i = $i + 1) {
        $wishlist_ids[] = $wishlist_items[$i]['car_id'];
    }


    // 1. Fetch Categories for Cars
    $cat_sql = "SELECT * FROM categories WHERE type = 'Car' ORDER BY category_name ASC";
    $cat_stmt = $conn->prepare($cat_sql);
    $cat_stmt->execute();
    $categories = $cat_stmt->fetchAll();

    $transmissions = ["Automatic", "Manual", "CVT", "DCT", "AMT"];


    // 2. Advanced Filtering Logic
    $where_clause = " WHERE 1=1 "; 
    $params = [];

    if (!empty($_GET['search'])) {
        $where_clause = $where_clause . " AND (c.model LIKE ? OR c.brand LIKE ?) ";
        $params[] = "%" . $_GET['search'] . "%";
        $params[] = "%" . $_GET['search'] . "%";
    }

    if (!empty($_GET['cat_id'])) {
        $where_clause = $where_clause . " AND c.category_id = ? ";
        $params[] = $_GET['cat_id'];
    }

    if (!empty($_GET['min_price'])) {
        $min_p = $_GET['min_price'];
        if ($min_p >= 0 && $min_p <= 1000000000) {
            $where_clause = $where_clause . " AND c.price >= ? ";
            $params[] = $min_p;
        }
    }

    if (!empty($_GET['max_price'])) {
        $max_p = $_GET['max_price'];
        if ($max_p >= 0 && $max_p <= 1000000000) {
            $where_clause = $where_clause . " AND c.price <= ? ";
            $params[] = $max_p;
        }
    }

    if (!empty($_GET['max_mileage'])) {
        $max_m = $_GET['max_mileage'];
        if ($max_m >= 0 && $max_m <= 1000000000) {
            $where_clause = $where_clause . " AND c.mileage <= ? ";
            $params[] = $max_m;
        }
    }

    if (!empty($_GET['min_year'])) {
        $min_y = $_GET['min_year'];
        if ($min_y >= 1900 && $min_y <= $current_system_year) {
            $where_clause = $where_clause . " AND c.year >= ? ";
            $params[] = $min_y;
        }
    }

    if (!empty($_GET['max_year'])) {
        $max_y = $_GET['max_year'];
        if ($max_y >= 1900 && $max_y <= $current_system_year) {
            $where_clause = $where_clause . " AND c.year <= ? ";
            $params[] = $max_y;
        }
    }

    if (!empty($_GET['trans'])) {
        $where_clause = $where_clause . " AND c.transmission = ? ";
        $params[] = $_GET['trans'];
    }

    if (!empty($_GET['status_filter'])) {
        $s_filter = $_GET['status_filter'];
        
        if ($s_filter == 'Upcoming') {
            $where_clause = $where_clause . " AND c.status = 'Upcoming' ";
        } else if ($s_filter == 'Out of Stock') {
            $where_clause = $where_clause . " AND (c.quantity <= 0 OR c.status = 'Sold Out') ";
        } else if ($s_filter == 'In Stock') {
            $where_clause = $where_clause . " AND c.quantity > 0 AND c.status = 'In Stock' ";
        }
    }


    // 3. Fetch Cars
    $sql = "SELECT c.*, cat.category_name 
            FROM cars c 
            JOIN categories cat ON c.category_id = cat.id 
            $where_clause 
            ORDER BY c.id DESC";
            
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $cars = $stmt->fetchAll();


    // 4. Fetch list of car IDs this specific user has purchased
    $purchased_cars = [];
    $sql_p = "SELECT car_id FROM orders WHERE customer_id = ? AND car_id IS NOT NULL";
    $stmt_p = $conn->prepare($sql_p);
    $stmt_p->execute([$u_id]);
    $p_res = $stmt_p->fetchAll();
    
    for ($i = 0; $i < count($p_res); $i = $i + 1) {
        $purchased_cars[] = $p_res[$i]['car_id'];
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Car Showroom - Rev Nation</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    
    <style>
        .car-card { transition: 0.3s; border: none; border-radius: 15px; overflow: hidden; }
        .car-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .filter-section { background: #fff; border-radius: 15px; padding: 20px; position: sticky; top: 20px; }
        .car-img-top { height: 200px; width: 100%; object-fit: cover; }
        .spec-badge { background: #f8f9fa; color: #6c757d; font-size: 0.75rem; padding: 4px 8px; border-radius: 5px; border: 1px solid #eee; }
        .price-tag { font-size: 1.2rem; font-weight: 800; color: #198754; }
        
        .btn-wishlist-add { border: 1px solid #ff4757; color: #ff4757; }
        .btn-wishlist-add:hover { background-color: #ff4757; color: white; }
        .btn-wishlist-remove { background-color: #ff4757; color: white; border: 1px solid #ff4757; }
        
        a { text-decoration: none; color: inherit; }
    </style>
</head>
<body class="bg-light">
    <?php include "inc/navbar.php"; ?>

    <div class="container-fluid mt-4 px-4">
        <div class="row mb-4">
            <div class="col-12">
                <h2 class="fw-bold"><i class="fa fa-car me-2"></i>Premium Car Showroom</h2>
                <p class="text-muted">Browse our exclusive collection of vehicles.</p>
                
                <?php if (isset($_GET['success'])) { ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?php echo $_GET['success']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php } ?>
                
                <?php if (isset($_GET['error'])) { ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?php echo $_GET['error']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php } ?>
            </div>
        </div>

        <div class="row">
            <div class="col-md-3">
                <div class="filter-section shadow-sm mb-4">
                    <form action="showroom.php" method="GET">
                        
                        <h6 class="fw-bold mb-3">Search</h6>
                        <div class="input-group mb-4">
                            <input type="text" name="search" class="form-control" placeholder="Brand or Model..." value="<?php echo $_GET['search'] ?? ''; ?>">
                            <button class="btn btn-primary" type="submit"><i class="fa fa-search"></i></button>
                        </div>

                        <h6 class="fw-bold mb-3">Availability</h6>
                        <select name="status_filter" class="form-select mb-4">
                            <option value="">All Vehicles</option>
                            <option value="In Stock" <?php echo (isset($_GET['status_filter']) && $_GET['status_filter'] == 'In Stock') ? 'selected' : ''; ?>>Available Now</option>
                            <option value="Upcoming" <?php echo (isset($_GET['status_filter']) && $_GET['status_filter'] == 'Upcoming') ? 'selected' : ''; ?>>Upcoming</option>
                            <option value="Out of Stock" <?php echo (isset($_GET['status_filter']) && $_GET['status_filter'] == 'Out of Stock') ? 'selected' : ''; ?>>Sold Out</option>
                        </select>

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
                            <label class="small text-muted">Price Range ($0 - $1B)</label>
                            <div class="input-group">
                                <input type="number" name="min_price" class="form-control" placeholder="Min" min="0" max="1000000000" value="<?php echo $_GET['min_price'] ?? ''; ?>">
                                <input type="number" name="max_price" class="form-control" placeholder="Max" min="0" max="1000000000" value="<?php echo $_GET['max_price'] ?? ''; ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="small text-muted">Year Range (1900 - <?php echo $current_system_year; ?>)</label>
                            <div class="input-group">
                                <input type="number" name="min_year" class="form-control" placeholder="From" min="1900" max="<?php echo $current_system_year; ?>" value="<?php echo $_GET['min_year'] ?? ''; ?>">
                                <input type="number" name="max_year" class="form-control" placeholder="To" min="1900" max="<?php echo $current_system_year; ?>" value="<?php echo $_GET['max_year'] ?? ''; ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="small text-muted">Max Mileage (0 - 1B km)</label>
                            <input type="number" name="max_mileage" class="form-control" placeholder="Up to..." min="0" max="1000000000" value="<?php echo $_GET['max_mileage'] ?? ''; ?>">
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
                            <a href="showroom.php" class="btn btn-outline-secondary">Clear All</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-md-9">
                <div class="row">
                    <?php if (count($cars) > 0) { ?>
                        <?php for ($i = 0; $i < count($cars); $i = $i + 1) { 
                            $car = $cars[$i]; 
                            $has_purchased = false;

                            for ($j = 0; $j < count($purchased_cars); $j = $j + 1) {
                                if ($purchased_cars[$j] == $car['id']) {
                                    $has_purchased = true;
                                    break;
                                }
                            }

                            // Check if car is in wishlist
                            $is_wished = false;
                            for ($k = 0; $k < count($wishlist_ids); $k = $k + 1) {
                                if ($wishlist_ids[$k] == $car['id']) {
                                    $is_wished = true;
                                    break;
                                }
                            }
                        ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="card car-card shadow-sm h-100">
                                <a href="car-view.php?id=<?php echo $car['id']; ?>">
                                    <img src="../uploads/<?php echo $car['image']; ?>" class="car-img-top" onerror="this.src='https://via.placeholder.com/400x250?text=Vehicle+Image'">
                                </a>
                                
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <small class="text-primary text-uppercase fw-bold"><?php echo $car['brand']; ?></small>
                                            <h5 class="fw-bold mb-0"><?php echo $car['model']; ?></h5>
                                        </div>
                                        <div class="price-tag">$<?php echo number_format($car['price']); ?></div>
                                    </div>
                                    
                                    <div class="d-flex flex-wrap gap-1 mb-3">
                                        <span class="spec-badge"><i class="fa fa-calendar"></i> <?php echo $car['year']; ?></span>
                                        <span class="spec-badge"><i class="fa fa-cog"></i> <?php echo $car['transmission']; ?></span>
                                        <span class="spec-badge"><i class="fa fa-road"></i> <?php echo number_format($car['mileage']); ?> km</span>
                                    </div>

                                    <?php if ($car['status'] == 'Upcoming') { ?>
                                        <div class="alert alert-primary py-1 px-2 mb-0 text-center small">Coming Soon</div>
                                    <?php } else if ($car['quantity'] <= 0) { ?>
                                        <div class="alert alert-danger py-1 px-2 mb-0 text-center small">Sold Out</div>
                                    <?php } ?>
                                </div>

                                <div class="card-footer bg-white border-0 pb-3">
                                    <div class="d-grid gap-2">
                                        
                                        <?php if ($has_purchased == true) { ?>
                                            <a href="select-order.php?car_id=<?php echo $car['id']; ?>" class="btn btn-warning btn-sm fw-bold">
                                                <i class="fa fa-shield"></i> Claim Warranty
                                            </a>
                                        <?php } ?>

                                        <?php if ($car['status'] == 'In Stock' && $car['quantity'] > 0) { ?>
                                            <form action="car-transaction.php" method="GET" class="d-grid">
                                                <input type="hidden" name="id" value="<?php echo $car['id']; ?>">
                                                <button type="submit" class="btn btn-success btn-sm fw-bold">
                                                    <i class="fa fa-shopping-cart"></i> Purchase Now
                                                </button>
                                            </form>
                                        <?php } ?>
                                        
                                        <a href="car-view.php?id=<?php echo $car['id']; ?>" class="btn btn-outline-dark btn-sm">
                                            <i class="fa fa-eye"></i> View Details & Reviews
                                        </a>

                                        <?php if ($is_wished == true) { ?>
                                            <a href="req/wishlist-handle.php?id=<?php echo $car['id']; ?>&action=remove&type=car&source=showroom" class="btn btn-sm btn-wishlist-remove">
                                                <i class="fa fa-heart"></i> Remove from Wishlist
                                            </a>
                                        <?php } else { ?>
                                            <a href="req/wishlist-handle.php?id=<?php echo $car['id']; ?>&action=add&type=car&source=showroom" class="btn btn-sm btn-wishlist-add">
                                                <i class="fa fa-heart-o"></i> Add to Wishlist
                                            </a>
                                        <?php } ?>

                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php } ?>
                    <?php } else { ?>
                        <div class="col-12 text-center py-5">
                            <i class="fa fa-car fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No vehicles found matching your criteria.</h5>
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