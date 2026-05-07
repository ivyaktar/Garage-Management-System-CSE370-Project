<?php 
session_start();

if (isset($_SESSION['user_id']) && 
    isset($_SESSION['role'])    && 
    $_SESSION['role'] == 'Manager') {
    
    include "../DB_connection.php";

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
        $where_clause = $where_clause . " AND c.price >= ? ";
        $params[] = $_GET['min_price'];
    }

    if (!empty($_GET['max_price'])) {
        $where_clause = $where_clause . " AND c.price <= ? ";
        $params[] = $_GET['max_price'];
    }

    if (!empty($_GET['min_year'])) {
        $where_clause = $where_clause . " AND c.year >= ? ";
        $params[] = $_GET['min_year'];
    }

    if (!empty($_GET['max_year'])) {
        $where_clause = $where_clause . " AND c.year <= ? ";
        $params[] = $_GET['max_year'];
    }

    if (!empty($_GET['max_mileage'])) {
        $where_clause = $where_clause . " AND c.mileage <= ? ";
        $params[] = $_GET['max_mileage'];
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

    // 3. Fetch Cars with Wishlist Count and Auction Status
    // UPDATED: in_auction now checks for Active status OR (Closed status AND winner_paid = 0)
    $sql = "SELECT c.*, cat.category_name,
            (SELECT COUNT(*) FROM wishlist w WHERE w.product_id = c.id) as wishlist_count,
            (SELECT COUNT(*) FROM auctions a 
             WHERE a.car_id = c.id 
             AND (a.status = 'Active' OR (a.status = 'Closed' AND a.winner_paid = 0))
            ) as in_auction
            FROM cars c 
            JOIN categories cat ON c.category_id = cat.id 
            $where_clause 
            ORDER BY c.id DESC";
            
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $cars = $stmt->fetchAll();

    // 4. Logic to count statuses and total value
    $count_in_stock = 0;
    $count_out_of_stock = 0;
    $count_upcoming = 0;
    $total_inventory_value = 0;

    for ($i = 0; $i < count($cars); $i = $i + 1) {
        
        $total_inventory_value = $total_inventory_value + ($cars[$i]['price'] * $cars[$i]['quantity']);

        if ($cars[$i]['status'] == 'Upcoming') {
            $count_upcoming = $count_upcoming + 1;
        } else if ($cars[$i]['quantity'] <= 0 || $cars[$i]['status'] == 'Sold Out') {
            $count_out_of_stock = $count_out_of_stock + 1;
        } else {
            $count_in_stock = $count_in_stock + 1;
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manager - Car Showroom</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    
    <style>
        .stats-card { border: none; border-radius: 12px; transition: 0.3s; min-height: 100px; }
        .car-card { transition: 0.3s; border: none; border-radius: 15px; overflow: hidden; position: relative; }
        .car-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .filter-section { background: #fff; border-radius: 15px; padding: 20px; position: sticky; top: 20px; }
        .car-img-top { height: 180px; width: 100%; object-fit: cover; cursor: pointer; }
        .spec-badge { background: #f8f9fa; color: #6c757d; font-size: 0.72rem; padding: 4px 8px; border-radius: 5px; border: 1px solid #eee; }
        
        .page-header-container {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
            padding: 10px 0;
            border-bottom: 1px solid #dee2e6;
        }
        .page-header-icon {
            background-color: #343a40;
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 1.5rem;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .page-header-title {
            font-weight: 800;
            font-size: 1.8rem;
            color: #212529;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .card-header-text {
            font-size: 0.65rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            opacity: 0.8;
            font-weight: 600;
        }

        .btn-add-large {
            padding: 12px 30px;
            font-size: 1.1rem;
            font-weight: 700;
            border-radius: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 4px 15px rgba(25, 135, 84, 0.3);
        }

        .wishlist-star {
            color: #ffc107;
            font-weight: bold;
            font-size: 0.9rem;
        }

        .qty-display-large {
            background: #fdfdfd;
            border-top: 1px dashed #ddd;
            padding: 10px;
            text-align: center;
        }
        .qty-number {
            font-size: 1.4rem;
            font-weight: 800;
            display: block;
        }
        .qty-label {
            font-size: 0.65rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #888;
        }
        a { text-decoration: none; color: inherit; }
        .car-link:hover h5 { color: #0d6efd; }

        .auction-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background: rgba(220, 53, 69, 0.9);
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 0.7rem;
            font-weight: bold;
            z-index: 10;
        }
    </style>
</head>
<body class="bg-light">
    <?php include "inc/navbar.php"; ?>

    <div class="container-fluid mt-4 px-4">

        <div class="page-header-container">
            <div class="page-header-icon">
                <i class="fa fa-car"></i>
            </div>
            <h2 class="page-header-title">Vehicle Inventory & Showroom</h2>
        </div>
        
        <?php if (isset($_GET['success'])) { ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                <i class="fa fa-check-circle me-2"></i>
                <?php echo $_GET['success']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php } ?>

        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stats-card bg-primary text-white shadow-sm">
                    <div class="card-body d-flex align-items-center">
                        <i class="fa fa-car fa-2x me-3"></i>
                        <div>
                            <div class="card-header-text">Total Fleet</div>
                            <h4 class="mb-0 fw-bold"><?php echo count($cars); ?> Units</h4>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card stats-card bg-info text-white shadow-sm">
                    <div class="card-body d-flex align-items-center">
                        <i class="fa fa-clock-o fa-2x me-3"></i>
                        <div>
                            <div class="card-header-text">Pending Arrivals</div>
                            <h4 class="mb-0 fw-bold"><?php echo $count_upcoming; ?></h4>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card stats-card bg-success text-white shadow-sm">
                    <div class="card-body d-flex align-items-center">
                        <i class="fa fa-money fa-2x me-3"></i>
                        <div>
                            <div class="card-header-text">Inventory Value</div>
                            <h4 class="mb-0 fw-bold">$<?php echo number_format($total_inventory_value / 1000, 1); ?>k</h4>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card stats-card bg-danger text-white shadow-sm">
                    <div class="card-body d-flex align-items-center">
                        <i class="fa fa-exclamation-triangle fa-2x me-3"></i>
                        <div>
                            <div class="card-header-text">Inventory Alert</div>
                            <h4 class="mb-0 fw-bold"><?php echo $count_out_of_stock; ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-3">
                <div class="filter-section shadow-sm mb-4">
                    <form action="showroom-manage.php" method="GET">
                        <h6 class="fw-bold mb-3">Search Vehicle</h6>
                        <div class="input-group input-group-sm mb-4">
                            <input type="text" name="search" class="form-control" placeholder="Brand or Model..." value="<?php echo $_GET['search'] ?? ''; ?>">
                            <button class="btn btn-primary" type="submit"><i class="fa fa-search"></i></button>
                        </div>

                        <h6 class="fw-bold mb-3">Filter by Availability</h6>
                        <div class="mb-4">
                             <select name="status_filter" class="form-select form-select-sm">
                                <option value="">All Availability</option>
                                <option value="In Stock" <?php echo (isset($_GET['status_filter']) && $_GET['status_filter'] == 'In Stock') ? 'selected' : ''; ?>>In Stock Only</option>
                                <option value="Upcoming" <?php echo (isset($_GET['status_filter']) && $_GET['status_filter'] == 'Upcoming') ? 'selected' : ''; ?>>Upcoming Only</option>
                                <option value="Out of Stock" <?php echo (isset($_GET['status_filter']) && $_GET['status_filter'] == 'Out of Stock') ? 'selected' : ''; ?>>Out of Stock Only</option>
                             </select>
                        </div>

                        <h6 class="fw-bold mb-3">Specifications</h6>
                        <div class="mb-3">
                            <label class="form-label small text-muted">Category</label>
                            <select name="cat_id" class="form-select form-select-sm">
                                <option value="">All Categories</option>
                                <?php for ($i = 0; $i < count($categories); $i = $i + 1) { ?>
                                    <option value="<?php echo $categories[$i]['id']; ?>" <?php echo (isset($_GET['cat_id']) && $_GET['cat_id'] == $categories[$i]['id']) ? 'selected' : ''; ?>>
                                        <?php echo $categories[$i]['category_name']; ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small text-muted">Price Range ($)</label>
                            <div class="input-group input-group-sm">
                                <input type="number" name="min_price" class="form-control" placeholder="Min" min="0" value="<?php echo $_GET['min_price'] ?? ''; ?>">
                                <input type="number" name="max_price" class="form-control" placeholder="Max" min="0" value="<?php echo $_GET['max_price'] ?? ''; ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small text-muted">Year Range (1900 - 2026)</label>
                            <div class="input-group input-group-sm">
                                <input type="number" name="min_year" class="form-control" placeholder="From" min="1900" max="2026" value="<?php echo $_GET['min_year'] ?? ''; ?>">
                                <input type="number" name="max_year" class="form-control" placeholder="To" min="1900" max="2026" value="<?php echo $_GET['max_year'] ?? ''; ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small text-muted">Max Mileage (km)</label>
                            <input type="number" name="max_mileage" class="form-control form-control-sm" placeholder="Up to..." min="0" value="<?php echo $_GET['max_mileage'] ?? ''; ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small text-muted">Transmission</label>
                            <select name="trans" class="form-select form-select-sm">
                                <option value="">All Transmissions</option>
                                <?php for ($i = 0; $i < count($transmissions); $i = $i + 1) { ?>
                                    <option value="<?php echo $transmissions[$i]; ?>" <?php echo (isset($_GET['trans']) && $_GET['trans'] == $transmissions[$i]) ? 'selected' : ''; ?>>
                                        <?php echo $transmissions[$i]; ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-sm btn-dark">Apply Filters</button>
                            <a href="showroom-manage.php" class="btn btn-sm btn-outline-secondary">Reset</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-md-9">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="fw-bold m-0">Available Vehicles</h3>
                    <div>
                        <a href="car-add.php" class="btn btn-success btn-add-large">
                            <i class="fa fa-plus-circle me-2"></i> Add New Vehicle
                        </a>
                    </div>
                </div>

                <div class="row">
                    <?php if (count($cars) > 0) { ?>
                        <?php for ($i = 0; $i < count($cars); $i = $i + 1) { 
                            $car = $cars[$i]; 
                            
                            $qty_text_color = "text-dark";
                            $qty_label = "Stock Quantity";

                            if ($car['status'] == 'Upcoming') {
                                $qty_text_color = "text-warning";
                                $qty_label = "Upcoming Order";
                            } else if ($car['quantity'] <= 0 || $car['status'] == 'Sold Out') {
                                $qty_text_color = "text-danger";
                                $qty_label = "Out of Stock";
                            }
                        ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="card car-card shadow-sm h-100">
                                <?php if ($car['in_auction'] > 0) { ?>
                                    <div class="auction-badge"><i class="fa fa-gavel"></i> IN AUCTION</div>
                                <?php } ?>

                                <a href="car-view.php?id=<?php echo $car['id']; ?>">
                                    <img src="../uploads/<?php echo $car['image']; ?>" 
                                         class="car-img-top"
                                         onerror="this.src='https://via.placeholder.com/300x180?text=No+Image'">
                                </a>
                                
                                <div class="card-body">
                                    <div class="d-flex justify-content-between mb-1">
                                        <small class="text-primary fw-bold text-uppercase"><?php echo $car['brand']; ?></small>
                                        <h6 class="text-success fw-bold m-0">$<?php echo number_format($car['price']); ?></h6>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <a href="car-view.php?id=<?php echo $car['id']; ?>" class="car-link">
                                            <h5 class="card-title fw-bold m-0"><?php echo $car['model']; ?></h5>
                                        </a>
                                        <span class="wishlist-star">
                                            <i class="fa fa-star"></i> <?php echo $car['wishlist_count']; ?>
                                        </span>
                                    </div>
                                    
                                    <div class="d-flex flex-wrap gap-1 mt-3">
                                        <span class="spec-badge"><i class="fa fa-calendar"></i> <?php echo $car['year']; ?></span>
                                        <span class="spec-badge"><i class="fa fa-cog"></i> <?php echo $car['transmission']; ?></span>
                                        <span class="spec-badge"><i class="fa fa-road"></i> <?php echo number_format($car['mileage']); ?> km</span>
                                    </div>
                                </div>

                                <div class="qty-display-large">
                                    <span class="qty-label"><?php echo $qty_label; ?></span>
                                    <span class="qty-number <?php echo $qty_text_color; ?>">
                                        <?php echo ($car['status'] == 'Upcoming') ? '---' : $car['quantity']; ?>
                                    </span>
                                </div>

                                <div class="card-footer bg-transparent border-0 pb-3">
                                    <div class="d-flex flex-column gap-2">
                                        <div class="d-flex gap-2">
                                            <?php if ($car['in_auction'] > 0) { ?>
                                                <button class="btn btn-sm btn-secondary flex-grow-1" disabled title="Locked: Car is in Auction">
                                                    <i class="fa fa-edit"></i> Edit
                                                </button>
                                                <button class="btn btn-sm btn-secondary" disabled title="Locked: Car is in Auction">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            <?php } else { ?>
                                                <a href="car-edit.php?id=<?php echo $car['id']; ?>" class="btn btn-sm btn-outline-primary flex-grow-1">
                                                    <i class="fa fa-edit"></i> Edit
                                                </a>
                                                <button onclick="deleteCar(<?php echo $car['id']; ?>)" class="btn btn-sm btn-outline-danger">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            <?php } ?>
                                        </div>
                                        <a href="car-view.php?id=<?php echo $car['id']; ?>" class="btn btn-sm btn-light border w-100">
                                            <i class="fa fa-comments"></i> Manage Reviews
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php } ?>
                    <?php } else { ?>
                        <div class="col-12 text-center py-5">
                            <i class="fa fa-search fa-3x text-muted"></i>
                            <h5 class="mt-3 text-muted">No vehicles match your search.</h5>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function deleteCar(id) {
            window.location.href = "req/car-delete.php?id=" + id;
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