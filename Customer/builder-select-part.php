<?php 
session_start();

if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && isset($_GET['cat_id'])) {
    
    include "../DB_connection.php";

    $user_id = $_SESSION['user_id'];
    $cat_id = $_GET['cat_id'];

    // 1. Fetch the category name
    $cat_sql = "SELECT category_name FROM categories WHERE id = ?";
    $cat_stmt = $conn->prepare($cat_sql);
    $cat_stmt->execute([$cat_id]);
    $category = $cat_stmt->fetch();

    // 2. Fetch tags from the ACTUAL tags table for the dropdown
    $tag_list_sql = "SELECT * FROM tags";
    $tag_list_stmt = $conn->prepare($tag_list_sql);
    $tag_list_stmt->execute();
    $db_tags = $tag_list_stmt->fetchAll();

    // 3. Determine Required Tags for compatibility (MODIFIED TO EXCLUDE CURRENT CATEGORY)
    $sel_sql = "SELECT p.tags FROM builder_temporary bt 
                JOIN products p ON bt.product_id = p.id 
                WHERE bt.user_id = ? AND bt.category_id != ?"; 
    $sel_stmt = $conn->prepare($sel_sql);
    $sel_stmt->execute([$user_id, $cat_id]);
    $current_parts_tags = $sel_stmt->fetchAll(PDO::FETCH_COLUMN);

    $required_tags = [];
    
    for ($i = 0; $i < count($current_parts_tags); $i = $i + 1) {
        $tags_array = explode(',', $current_parts_tags[$i]);
        $temp_specific = [];
        $has_brand = false;

        for ($j = 0; $j < count($tags_array); $j = $j + 1) {
            $t = trim($tags_array[$j]);
            if ($t !== "" && strtolower($t) !== "null" && strtolower($t) !== "universal") {
                $temp_specific[] = $t;
                $has_brand = true;
            }
        }

        if ($has_brand == true) {
            if (empty($required_tags)) {
                $required_tags = $temp_specific;
            } else {
                $intersect = [];
                for ($x = 0; $x < count($required_tags); $x = $x + 1) {
                    if (in_array($required_tags[$x], $temp_specific)) {
                        $intersect[] = $required_tags[$x];
                    }
                }
                $required_tags = $intersect;
            }
        }
    }

    // 4. Filtering and Sorting logic
    $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $tag_filter = isset($_GET['tag_filter']) ? $_GET['tag_filter'] : '';
    $sort = isset($_GET['sort']) ? $_GET['sort'] : 'name_asc';

    // 5. Build Main Query
    $params = [$cat_id];
    $sql = "SELECT * FROM products WHERE category_id = ?";

    if (!empty($required_tags)) {
        $sql = $sql . " AND (tags LIKE '%Universal%' OR tags IS NULL OR tags = ''";
        for ($k = 0; $k < count($required_tags); $k = $k + 1) {
            $sql = $sql . " OR tags LIKE ?";
            $params[] = "%" . trim($required_tags[$k]) . "%";
        }
        $sql = $sql . ")";
    }

    if ($filter == 'available') {
        $sql = $sql . " AND status = 'In Stock' AND quantity > 0";
    } else if ($filter == 'upcoming') {
        $sql = $sql . " AND status = 'Upcoming'";
    } else if ($filter == 'out_of_stock') {
        $sql = $sql . " AND status = 'Out of Stock'";
    }

    if ($search !== '') {
        $sql = $sql . " AND (product_name LIKE ? OR tags LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    if ($tag_filter !== '') {
        $sql = $sql . " AND tags LIKE ?";
        $params[] = "%$tag_filter%";
    }

    if ($sort == 'price_low') { $sql = $sql . " ORDER BY price ASC"; } 
    else if ($sort == 'price_high') { $sql = $sql . " ORDER BY price DESC"; } 
    else if ($sort == 'name_desc') { $sql = $sql . " ORDER BY product_name DESC"; } 
    else { $sql = $sql . " ORDER BY product_name ASC"; }

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Select <?php echo $category['category_name']; ?> | Garage GMS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        .filter-card { border: none; border-radius: 10px; }
        .product-table-card { border: none; border-radius: 10px; overflow: hidden; }
        .card-header-custom { background: #212529 !important; color: white !important; }
        .img-container { width: 60px; height: 60px; overflow: hidden; border-radius: 8px; border: 1px solid #eee; }
        .img-container img { width: 100%; height: 100%; object-fit: cover; }
        .disabled-row { opacity: 0.6; background-color: #f8f9fa; }
        .tag-badge { font-size: 0.7rem; font-weight: 500; }
        .btn-select { font-weight: bold; padding: 5px 20px; }
        /* HP Badge Styles */
        .hp-badge { font-size: 0.75rem; background: #e9ecef; color: #0d6efd; padding: 2px 8px; border-radius: 4px; font-weight: bold; border: 1px solid #0d6efd; margin-top: 4px; display: inline-block; }
        .hp-badge-neg { font-size: 0.75rem; background: #fff5f5; color: #dc3545; padding: 2px 8px; border-radius: 4px; font-weight: bold; border: 1px solid #dc3545; margin-top: 4px; display: inline-block; }
    </style>
</head>
<body class="bg-light">
    <?php include "inc/navbar.php"; ?>

    <div class="container mt-5 mb-5">
        <div class="row g-4">
            <div class="col-md-3">
                <div class="card p-4 shadow-sm filter-card">
                    <h6 class="fw-bold mb-3"><i class="fa fa-filter me-2"></i>Filter Parts</h6>
                    <form action="builder-select-part.php" method="GET">
                        <input type="hidden" name="cat_id" value="<?php echo $cat_id; ?>">
                        
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">Search Name</label>
                            <input type="text" name="search" class="form-control form-control-sm" placeholder="e.g. Turbo" value="<?php echo htmlspecialchars($search); ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">Filter by Tag</label>
                            <select name="tag_filter" class="form-select form-select-sm">
                                <option value="">All Tags</option>
                                <?php for ($t_idx = 0; $t_idx < count($db_tags); $t_idx = $t_idx + 1) { 
                                    $tagName = $db_tags[$t_idx]['tag_name'];
                                ?>
                                    <option value="<?php echo $tagName; ?>" <?php echo ($tag_filter == $tagName) ? 'selected' : ''; ?>>
                                        <?php echo $tagName; ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">Availability</label>
                            <select name="filter" class="form-select form-select-sm">
                                <option value="all" <?php echo ($filter == 'all') ? 'selected' : ''; ?>>All Items</option>
                                <option value="available" <?php echo ($filter == 'available') ? 'selected' : ''; ?>>In Stock</option>
                                <option value="upcoming" <?php echo ($filter == 'upcoming') ? 'selected' : ''; ?>>Upcoming</option>
                                <option value="out_of_stock" <?php echo ($filter == 'out_of_stock') ? 'selected' : ''; ?>>Out of Stock</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-bold text-muted">Sort By</label>
                            <select name="sort" class="form-select form-select-sm">
                                <option value="name_asc" <?php echo ($sort == 'name_asc') ? 'selected' : ''; ?>>Name (A-Z)</option>
                                <option value="name_desc" <?php echo ($sort == 'name_desc') ? 'selected' : ''; ?>>Name (Z-A)</option>
                                <option value="price_low" <?php echo ($sort == 'price_low') ? 'selected' : ''; ?>>Price (Low to High)</option>
                                <option value="price_high" <?php echo ($sort == 'price_high') ? 'selected' : ''; ?>>Price (High to Low)</option>
                            </select>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-sm fw-bold">Apply Filters</button>
                            <a href="builder-select-part.php?cat_id=<?php echo $cat_id; ?>" class="btn btn-outline-secondary btn-sm">Reset</a>
                            <hr>
                            <a href="car-builder.php" class="btn btn-dark btn-sm"><i class="fa fa-arrow-left me-2"></i>Back to Builder</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-md-9">
                <div class="card shadow-sm product-table-card">
                    <div class="card-header card-header-custom py-3">
                        <h5 class="mb-0 fw-bold">Available <?php echo $category['category_name']; ?></h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th class="ps-4" style="width: 40%;">Product Details</th>
                                        <th style="width: 25%;">Tags</th>
                                        <th style="width: 15%;">Price</th>
                                        <th class="text-end pe-4" style="width: 20%;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($products) > 0) { ?>
                                        <?php for ($m = 0; $m < count($products); $m = $m + 1) { 
                                            $p = $products[$m];
                                            $is_available = ($p['status'] == 'In Stock' && $p['quantity'] > 0);
                                            $is_upcoming = ($p['status'] == 'Upcoming');
                                            $p_hp = isset($p['hp']) ? (int)$p['hp'] : 0;
                                        ?>
                                            <tr class="<?php echo (!$is_available) ? 'disabled-row' : ''; ?>">
                                                <td class="ps-4">
                                                    <div class="d-flex align-items-center">
                                                        <div class="img-container me-3 shadow-sm">
                                                            <img src="../uploads/<?php echo $p['image']; ?>" onerror="this.src='../uploads/default.png'">
                                                        </div>
                                                        <div>
                                                            <div class="fw-bold text-dark"><?php echo $p['product_name']; ?></div>
                                                            <small class="text-muted"><?php echo $p['status']; ?> · Qty: <?php echo $p['quantity']; ?></small>
                                                            <br>
                                                            <?php if ($p_hp > 0) { ?>
                                                                <span class="hp-badge">+<?php echo $p_hp; ?> HP</span>
                                                            <?php } else if ($p_hp < 0) { ?>
                                                                <span class="hp-badge-neg"><?php echo $p_hp; ?> HP</span>
                                                            <?php } ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php 
                                                        $raw_tags = trim($p['tags']);
                                                        if ($raw_tags == "" || strtolower($raw_tags) == "null") {
                                                            echo "<span class='badge bg-success text-white tag-badge me-1'>Universal</span>";
                                                        } else {
                                                            $p_tags = explode(',', $raw_tags);
                                                            for ($t = 0; $t < count($p_tags); $t = $t + 1) {
                                                                $t_name = trim($p_tags[$t]);
                                                                if ($t_name != "" && strtolower($t_name) !== "null") {
                                                                    $badge_class = (strtolower($t_name) == 'universal') ? 'bg-success text-white' : 'bg-info text-white';
                                                                    echo "<span class='badge $badge_class tag-badge me-1'>$t_name</span>";
                                                                }
                                                            }
                                                        }
                                                    ?>
                                                </td>
                                                <td class="fw-bold text-primary">$<?php echo number_format($p['price'], 2); ?></td>
                                                <td class="text-end pe-4">
                                                    <?php if ($is_available) { ?>
                                                        <form action="req/builder-action.php" method="POST">
                                                            <input type="hidden" name="cat_id" value="<?php echo $cat_id; ?>">
                                                            <input type="hidden" name="p_id" value="<?php echo $p['id']; ?>">
                                                            <input type="hidden" name="action" value="select_part">
                                                            <button type="submit" class="btn btn-sm btn-primary btn-select">Select</button>
                                                        </form>
                                                    <?php } else if ($is_upcoming) { ?>
                                                        <button class="btn btn-sm btn-warning btn-select" disabled>Upcoming</button>
                                                    <?php } else { ?>
                                                        <button class="btn btn-sm btn-secondary btn-select" disabled>Sold Out</button>
                                                    <?php } ?>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    <?php } else { ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-5">
                                                <i class="fa fa-search fa-2x text-muted mb-3"></i>
                                                <p class="text-muted">No compatible products found for this category.</p>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<?php 
} else { header("Location: car-builder.php"); exit; } 
?>