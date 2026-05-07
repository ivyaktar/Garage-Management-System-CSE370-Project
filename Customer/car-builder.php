<?php 
session_start();

if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {

    include "../DB_connection.php";
    include "req/builder-functions.php"; 
    
    $user_id = $_SESSION['user_id'];


    // 1. Save new selection from GET to Database
    if (isset($_GET['cat_id']) && isset($_GET['p_id'])) {

        $c_id = $_GET['cat_id'];
        $p_id = $_GET['p_id'];

        $save_sql = "INSERT INTO builder_temporary (user_id, category_id, product_id) 
                     VALUES (?, ?, ?) 
                     ON DUPLICATE KEY UPDATE product_id = VALUES(product_id)";
        $save_stmt = $conn->prepare($save_sql);
        $save_stmt->execute([$user_id, $c_id, $p_id]);
        
        header("Location: car-builder.php");
        exit;
    }


    // 2. Fetch current progress
    $sel_sql = "SELECT category_id, product_id FROM builder_temporary WHERE user_id = ?";
    $sel_stmt = $conn->prepare($sel_sql);
    $sel_stmt->execute([$user_id]);
    $db_selections = $sel_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Fetch current cart items to check against stock levels
    $cart_sql = "SELECT product_id, quantity FROM cart WHERE customer_id = ?";
    $cart_stmt = $conn->prepare($cart_sql);
    $cart_stmt->execute([$user_id]);
    $current_cart = $cart_stmt->fetchAll(PDO::FETCH_KEY_PAIR);


    // 3. Fetch Categories (Filtering out Showroom types like Sedan/SUV as requested)
    $m_sql = "SELECT * FROM categories 
              WHERE is_mandatory = 1 
              AND categories.type = 'Part' 
              ORDER BY category_name ASC";
    $mandatory_cats = $conn->query($m_sql)->fetchAll();

    $o_sql = "SELECT * FROM categories 
              WHERE is_mandatory = 0 
              AND categories.type = 'Part'
              ORDER BY category_name ASC";
    $optional_cats = $conn->query($o_sql)->fetchAll();


    // 4. Logic to check if the ENTIRE build is already in the wishlist
    $build_wishlisted_sql = "SELECT id FROM wishlist WHERE user_id = ? AND product_id IS NULL";
    $build_wish_stmt = $conn->prepare($build_wishlisted_sql);
    $build_wish_stmt->execute([$user_id]);
    $is_build_wishlisted = $build_wish_stmt->fetch();

    $total_price = 0;
    $total_hp = 0; // Added for HP tracking
    $has_unavailable_parts = false;
    $build_stock_exceeded = false;

    // 5. --- COMPATIBILITY LOGIC ---
    // This ensures all parts share at least 1 common tag or are 'universal'
    $all_selected_product_ids = array_values($db_selections);
    $is_compatible = true;
    $contradiction_error = "";
    
    if (count($all_selected_product_ids) > 1) {
        $common_tags = null;
        // --- PART A: Common Tag Check ---
        for ($i = 0; $i < count($all_selected_product_ids); $i = $i + 1) {
            $current_p_id = $all_selected_product_ids[$i];
            // Get tags for this specific product
            $tag_sql = "SELECT tags FROM products WHERE id = ?";
            $tag_stmt = $conn->prepare($tag_sql);
            $tag_stmt->execute([$current_p_id]);
            $product_row = $tag_stmt->fetch();
            
            // If product was deleted, it won't have tags/row
            if (!$product_row) {
                $is_compatible = false;
                continue;
            }

            $tags_string = $product_row['tags'];
            $current_product_tags = [];
            // Handle Empty Tags or Universal
            if ($tags_string == null || $tags_string == "") {
                $current_product_tags[] = 'universal';
            } else {
                $temp_tags = explode(',', $tags_string);
                for ($t = 0; $t < count($temp_tags); $t = $t + 1) {
                    $cleaned_tag = strtolower(trim($temp_tags[$t]));
                    if ($cleaned_tag != "") {
                        $current_product_tags[] = $cleaned_tag;
                    }
                }
            }

            // Check if this specific part is universal
            $current_is_universal = false;
            for ($u = 0; $u < count($current_product_tags); $u = $u + 1) {
                if ($current_product_tags[$u] == 'universal') {
                    $current_is_universal = true;
                }
            }

            if ($common_tags === null) {
                // Initialize common tags with the first product's tags
                $common_tags = $current_product_tags;
            } else {
                $new_common = [];
                // Check if current common list contains 'universal'
                $common_is_universal = false;
                for ($u = 0; $u < count($common_tags); $u = $u + 1) {
                    if ($common_tags[$u] == 'universal') {
                        $common_is_universal = true;
                    }
                }

                if ($current_is_universal) {
                    // Universal parts don't restrict the common tag pool
                    $new_common = $common_tags;
                } else if ($common_is_universal) {
                    // If previous parts were all universal, this specific part sets the current requirement
                    $new_common = $current_product_tags;
                } else {
                    // Find Intersection between existing common tags and current product tags
                    for ($j = 0; $j < count($common_tags); $j = $j + 1) {
                        for ($k = 0; $k < count($current_product_tags); $k = $k + 1) 
                        {
                            if ($common_tags[$j] == $current_product_tags[$k]) {
                                $new_common[] = $common_tags[$j];
                            }
                        }
                    }
                }
                $common_tags = $new_common;
            }
        }

        // If no common ground is found after checking all parts
        if (count($common_tags) == 0) {
            $is_compatible = false;
        }


        // --- PART B: Contradiction Check (The "No-" Tag Logic) ---
        // Iterate through all selected products and their categories
        foreach ($db_selections as $cat_id_key => $prod_id_val) {
            
            // Get tags of the selected product
            $sql_tags = "SELECT tags FROM products WHERE id = ?";
            $stmt_tags = $conn->prepare($sql_tags);
            $stmt_tags->execute([$prod_id_val]);
            $p_row = $stmt_tags->fetch();
            
            if ($p_row && $p_row['tags'] != "") {
                $p_tags_arr = explode(',', $p_row['tags']);
                for ($i = 0; $i < count($p_tags_arr); $i = $i + 1) {
                    $tag = strtolower(trim($p_tags_arr[$i]));
                    // If tag starts with 'no-', check if the restricted category is present
                    if (strpos($tag, 'no-') === 0) {
                        $restricted_cat_name = substr($tag, 3);
                        // removes 'no-'
                        
                        // Look through all selected items to see if restricted category is there
                        foreach ($db_selections as $check_cat_id => $check_prod_id) {
                            $sql_cat = "SELECT category_name FROM categories WHERE id = ?";
                            $stmt_cat = $conn->prepare($sql_cat);
                            $stmt_cat->execute([$check_cat_id]);
                            $c_row = $stmt_cat->fetch();
                            
                            if ($c_row && strtolower($c_row['category_name']) == $restricted_cat_name) {
                                $is_compatible = false;
                                $contradiction_error = "One of your parts restricts the use of: " . ucfirst($restricted_cat_name);
                            }
                        }
                    }
                }
            }
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Car Builder | Garage GMS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        .builder-header { background: #fff; padding: 20px; border-bottom: 1px solid #ddd; margin-bottom: 30px; }
        .section-title { background: #343a40; padding: 10px 15px; font-weight: bold; color: #fff; border-radius: 5px 5px 0 0; margin-top: 20px; }
        .category-row { background: #fff; border-bottom: 1px solid #eee; padding: 15px; display: flex; align-items: center; }
        .category-row.unavailable { background: #fff5f5; }
        .icon-box { width: 70px; height: 70px; background: #f8f9fa; border: 1px solid #eee; border-radius: 8px; margin-right: 20px; display: flex; align-items: center; justify-content: center; overflow: hidden; }
        .icon-box img { width: 100%; height: 100%; object-fit: contain; padding: 5px; }
        .part-info { flex-grow: 1; }
        .mandatory-badge { font-size: 0.7rem; background: #dc3545; color: #fff; padding: 2px 6px; border-radius: 4px; margin-left: 10px; }
        .status-badge { font-size: 0.75rem; padding: 2px 8px; border-radius: 12px; font-weight: bold; }
        .individual-actions .btn { padding: 2px 8px; }
        .tag-container { margin: 5px 0; }
        .tag-badge { 
            font-size: 0.65rem;
            padding: 2px 8px; border-radius: 50px; margin-right: 5px; 
            background: #f1f3f5; color: #495057; border: 1px solid #dee2e6; 
            text-transform: uppercase; font-weight: 600;
            display: inline-block;
        }
        .tag-universal { background: #d1e7dd; color: #0f5132; border-color: #badbcc; }
        /* Style for the HP label */
        .hp-badge { font-size: 0.75rem; background: #e9ecef; color: #0d6efd; padding: 2px 8px; border-radius: 4px; font-weight: bold; border: 1px solid #0d6efd; margin-left: 10px; }
        /* Style for negative HP values */
        .hp-badge-neg { 
            font-size: 0.75rem; 
            background: #fff5f5; 
            color: #dc3545; 
            padding: 2px 8px; 
            border-radius: 4px; 
            font-weight: bold; 
            border: 1px solid #dc3545; 
            margin-left: 10px; 
        }
    </style>
</head>
<body class="bg-light" onload="checkSaveButton()">
    <?php include "inc/navbar.php"; ?>

    <div class="builder-header shadow-sm">
        <div class="container d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-0 fw-bold">Build Your Custom Vehicle</h4>
                <small class="text-muted">Part selection logic synced with Showroom standards</small>
            </div>
            <div class="text-end d-flex align-items-center">
                <a href="saved-builds.php" class="btn btn-outline-dark me-3">
                    <i class="fa fa-folder-open"></i> My Saved Builds
                </a>
                <div>
                    <div class="fw-bold text-primary fs-4" id="main-total">$0.00</div>
                    <span class="badge bg-dark"><?php echo count($db_selections); ?> items selected</span>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if (isset($_GET['success'])) { ?>
            <div class="alert alert-success"><?php echo $_GET['success']; ?></div>
        <?php } ?>
        
        <?php if (isset($_GET['error'])) { ?>
            <div class="alert alert-danger"><?php echo $_GET['error']; ?></div>
        <?php } ?>

        <div class="row">
            <div class="col-md-9">
                <div class="section-title">Core Components</div>
                <div class="card shadow-sm border-0 mb-4">
                    <?php 
                        for ($i = 0; $i < count($mandatory_cats); $i = $i + 1) { 
                        $cat = $mandatory_cats[$i];
                        $p_id = isset($db_selections[$cat['id']]) ? $db_selections[$cat['id']] : null;
                        $part = $p_id ? getProductForBuilder($p_id, $conn) : null;

                        $is_available = true;
                        $is_deleted = false;
                        $p_status = 'In Stock';
                        $is_part_wishlisted = false;
                        if ($p_id && !$part) {
                            $is_deleted = true;
                            $is_available = false;
                            $has_unavailable_parts = true;
                        }

                        if ($part) {
                            $p_qty = isset($part['quantity']) ? $part['quantity'] : 0;
                            
                            // Check if quantity is 0 or less, or if status says Out of Stock
                            if ($p_qty <= 0 || $part['status'] == 'Out of Stock' || $part['status'] == 'Upcoming') {
                                $is_available = false;
                                $has_unavailable_parts = true;
                                $p_status = $part['status'] == 'In Stock' ? 'Out of Stock' : $part['status'];
                            } else {
                                $p_status = 'In Stock';
                            }

                            // Check if adding this part would exceed stock levels in cart
                            $qty_in_cart = isset($current_cart[$p_id]) ? $current_cart[$p_id] : 0;
                            if (($qty_in_cart + 1) > $p_qty) {
                                $build_stock_exceeded = true;
                            }

                            $wish_check_sql = "SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?";
                            $wish_check_stmt = $conn->prepare($wish_check_sql);
                            $wish_check_stmt->execute([$user_id, $p_id]);
                            if ($wish_check_stmt->fetch()) {
                                $is_part_wishlisted = true;
                            }
                        }
                    ?>
                        <div class="category-row <?php echo (!$is_available && ($part || $is_deleted)) ? 'unavailable' : ''; ?>">
                            <div class="icon-box">
                                <?php 
                                    if ($is_deleted) {
                                        $img_src = "deleted-part.png";
                                    } else if ($part && isset($part['image'])) {
                                        $img_src = $part['image'];
                                    } else {
                                        $img_src = $cat['category_img'];
                                    }
                                ?>
                                <img src="../uploads/<?php echo $img_src; ?>" onerror="this.src='../uploads/default-cat.png'">
                            </div>

                            <div class="part-info">
                                <h6 class="mb-0 fw-bold"><?php echo $cat['category_name']; ?> <span class="mandatory-badge">Required</span></h6>
                                <?php if ($is_deleted) { ?>
                                    <div class="text-danger fw-bold mb-1">Item has been deleted</div>
                                    <span class="badge bg-danger status-badge">Unavailable</span>
                                <?php } else if ($part) { ?>
                                    <div class="text-primary fw-bold mb-1"><?php echo isset($part['product_name']) ? $part['product_name'] : 'Unknown Product'; ?></div>
                                    
                                    <div class="tag-container">
                                        <?php 
                                            if (isset($part['tags']) && $part['tags'] != "") {
                                                $p_tags = explode(',', $part['tags']);
                                                for ($t = 0; $t < count($p_tags); $t = $t + 1) {
                                                    $t_name = trim($p_tags[$t]);
                                                    $is_uni = (strtolower($t_name) == 'universal');
                                                    echo '<span class="tag-badge '.($is_uni ? 'tag-universal' : '').'"> <i class="fa fa-tag"></i> '.$t_name.'</span>';
                                                }
                                            } else {
                                                echo '<span class="tag-badge tag-universal"> <i class="fa fa-tag"></i> Universal</span>';
                                            }
                                        ?>
                                    </div>

                                    <?php if ($is_available) { 
                                        $price = isset($part['price']) ? $part['price'] : 0;
                                        $total_price = $total_price + $price;
                                        
                                        // HP Logic for mandatory parts [cite: 1]
                                        $p_hp = isset($part['hp']) ? (int)$part['hp'] : 0;
                                        $total_hp = $total_hp + $p_hp;

                                        echo "<div class='d-flex align-items-center'>";
                                        echo "<span class='fw-bold'>$" . number_format($price, 2) . "</span>";
                                        if ($p_hp > 0) {
                                            echo "<span class='hp-badge'>+" . $p_hp . " HP</span>";
                                        } else if ($p_hp < 0) {
                                            echo "<span class='hp-badge-neg'>" . $p_hp . " HP</span>";
                                        }
                                        echo "</div>";
                                    } else {
                                        echo "<span class='badge bg-danger status-badge'>" . $p_status . "</span>";
                                    } ?>
                                    
                                    <div class="individual-actions mt-1">
                                        <form action="req/builder-action.php" method="POST" style="display:inline;">
                                            <input type="hidden" name="p_id" value="<?php echo $p_id; ?>">
                                            <input type="hidden" name="action" value="wishlist_item">
                                            <button type="submit" class="btn btn-sm <?php echo $is_part_wishlisted ? 'btn-warning' : 'btn-outline-warning'; ?>">
                                                <i class="fa fa-heart"></i>
                                            </button>
                                        </form>
                                        <form action="req/builder-action.php" method="POST" style="display:inline;">
                                            <input type="hidden" name="p_id" value="<?php echo $p_id; ?>">
                                            <input type="hidden" name="action" value="cart_item">
                                            <?php 
                                                // Check individual cart vs stock limit
                                                $qty_in_cart = isset($current_cart[$p_id]) ? $current_cart[$p_id] : 0;
                                                $can_add_to_cart = ($is_available && ($qty_in_cart < $part['quantity']));
                                            ?>
                                            <button type="submit" class="btn btn-sm btn-outline-success" <?php echo (!$can_add_to_cart) ? 'disabled title="Max stock reached"' : ''; ?>>
                                                <i class="fa fa-shopping-cart"></i>
                                            </button>
                                        </form>
                                    </div>
                                <?php } else { echo "<small class='text-muted'>Not Selected</small>"; } ?>
                            </div>

                            <div class="action d-flex align-items-center">
                                <?php if ($part || $is_deleted) { ?>
                                    <form action="req/builder-action.php" method="POST" class="me-2">
                                        <input type="hidden" name="cat_id" value="<?php echo $cat['id']; ?>">
                                        <input type="hidden" name="action" value="remove_part">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
                                    </form>
                                <?php } ?>
                                <a href="builder-select-part.php?cat_id=<?php echo $cat['id']; ?>" class="btn btn-primary btn-sm px-4 fw-bold">
                                    <?php echo ($part || $is_deleted) ? 'Change' : 'Choose'; ?>
                                </a>
                            </div>
                        </div>
                    <?php } ?>
                </div>

                <div class="section-title">Upgrades & Aesthetics</div>
                <div class="card shadow-sm border-0 mb-5">
                    <?php 
                        for ($j = 0; $j < count($optional_cats); $j = $j + 1) { 
                        $cat = $optional_cats[$j];
                        $p_id = isset($db_selections[$cat['id']]) ? $db_selections[$cat['id']] : null;
                        $part = $p_id ? getProductForBuilder($p_id, $conn) : null;

                        $is_available = true;
                        $is_deleted = false;
                        $p_status = 'In Stock';
                        $is_part_wishlisted = false;
                        if ($p_id && !$part) {
                            $is_deleted = true;
                            $is_available = false;
                            $has_unavailable_parts = true;
                        }

                        if ($part) {
                            $p_qty = isset($part['quantity']) ? $part['quantity'] : 0;
                            
                            if ($p_qty <= 0 || $part['status'] == 'Out of Stock' || $part['status'] == 'Upcoming') {
                                $is_available = false;
                                $has_unavailable_parts = true;
                                $p_status = $part['status'] == 'In Stock' ? 'Out of Stock' : $part['status'];
                            } else {
                                $p_status = 'In Stock';
                            }

                            // Check if adding this part would exceed stock levels in cart
                            $qty_in_cart = isset($current_cart[$p_id]) ? $current_cart[$p_id] : 0;
                            if (($qty_in_cart + 1) > $p_qty) {
                                $build_stock_exceeded = true;
                            }

                            $wish_check_sql = "SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?";
                            $wish_check_stmt = $conn->prepare($wish_check_sql);
                            $wish_check_stmt->execute([$user_id, $p_id]);
                            if ($wish_check_stmt->fetch()) {
                                $is_part_wishlisted = true;
                            }
                        }
                    ?>
                        <div class="category-row <?php echo (!$is_available && ($part || $is_deleted)) ? 'unavailable' : ''; ?>">
                            <div class="icon-box">
                                <?php 
                                    if ($is_deleted) {
                                        $img_src = "deleted-part.png";
                                    } else if ($part && isset($part['image'])) {
                                        $img_src = $part['image'];
                                    } else {
                                        $img_src = $cat['category_img'];
                                    }
                                ?>
                                <img src="../uploads/<?php echo $img_src; ?>" onerror="this.src='../uploads/default-cat.png'">
                            </div>

                            <div class="part-info">
                                <h6 class="mb-0 fw-bold"><?php echo $cat['category_name']; ?></h6>
                                <?php if ($is_deleted) { ?>
                                    <div class="text-danger fw-bold mb-1">Item has been deleted</div>
                                    <span class="badge bg-danger status-badge">Removed by Admin</span>
                                <?php } else if ($part) { ?>
                                    <div class="text-primary fw-bold mb-1"><?php echo isset($part['product_name']) ? $part['product_name'] : 'Unknown Product'; ?></div>
                                    
                                    <div class="tag-container">
                                        <?php 
                                            if (isset($part['tags']) && $part['tags'] != "") {
                                                $p_tags = explode(',', $part['tags']);
                                                for ($t = 0; $t < count($p_tags); $t = $t + 1) {
                                                    $t_name = trim($p_tags[$t]);
                                                    $is_uni = (strtolower($t_name) == 'universal');
                                                    echo '<span class="tag-badge '.($is_uni ? 'tag-universal' : '').'"> <i class="fa fa-tag"></i> '.$t_name.'</span>';
                                                }
                                            } else {
                                                echo '<span class="tag-badge tag-universal"> <i class="fa fa-tag"></i> Universal</span>';
                                            }
                                        ?>
                                    </div>

                                    <?php if ($is_available) { 
                                        $price = isset($part['price']) ? $part['price'] : 0;
                                        $total_price = $total_price + $price;
                                        
                                        // HP Logic for optional parts [cite: 1]
                                        $p_hp = isset($part['hp']) ? (int)$part['hp'] : 0;
                                        $total_hp = $total_hp + $p_hp;

                                        echo "<div class='d-flex align-items-center'>";
                                        echo "<span class='fw-bold'>$" . number_format($price, 2) . "</span>";
                                        if ($p_hp > 0) {
                                            echo "<span class='hp-badge'>+" . $p_hp . " HP</span>";
                                        } else if ($p_hp < 0) {
                                            echo "<span class='hp-badge-neg'>" . $p_hp . " HP</span>";
                                        }
                                        echo "</div>";
                                    } else {
                                        echo "<span class='badge bg-danger status-badge'>" . $p_status . "</span>";
                                    } ?>

                                    <div class="individual-actions mt-1">
                                        <form action="req/builder-action.php" method="POST" style="display:inline;">
                                            <input type="hidden" name="p_id" value="<?php echo $p_id; ?>">
                                            <input type="hidden" name="action" value="wishlist_item">
                                            <button type="submit" class="btn btn-sm <?php echo $is_part_wishlisted ? 'btn-warning' : 'btn-outline-warning'; ?>">
                                                <i class="fa fa-heart"></i>
                                            </button>
                                        </form>
                                        <form action="req/builder-action.php" method="POST" style="display:inline;">
                                            <input type="hidden" name="p_id" value="<?php echo $p_id; ?>">
                                            <input type="hidden" name="action" value="cart_item">
                                            <?php 
                                                // Check individual cart vs stock limit
                                                $qty_in_cart = isset($current_cart[$p_id]) ? $current_cart[$p_id] : 0;
                                                $can_add_to_cart = ($is_available && ($qty_in_cart < $part['quantity']));
                                            ?>
                                            <button type="submit" class="btn btn-sm btn-outline-success" <?php echo (!$can_add_to_cart) ? 'disabled title="Max stock reached"' : ''; ?>>
                                                <i class="fa fa-shopping-cart"></i>
                                            </button>
                                        </form>
                                    </div>
                                <?php } else { echo "<small class='text-muted'>Optional Selection</small>"; } ?>
                            </div>

                            <div class="action d-flex align-items-center">
                                <?php if ($part || $is_deleted) { ?>
                                    <form action="req/builder-action.php" method="POST" class="me-2">
                                        <input type="hidden" name="cat_id" value="<?php echo $cat['id']; ?>">
                                        <input type="hidden" name="action" value="remove_part">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
                                    </form>
                                <?php } ?>
                                <a href="builder-select-part.php?cat_id=<?php echo $cat['id']; ?>" class="btn btn-outline-primary btn-sm px-4 fw-bold">
                                    <?php echo ($part || $is_deleted) ? 'Change' : 'Choose'; ?>
                                </a>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card shadow-sm border-0 sticky-top" style="top: 20px;">
                    <div class="card-body text-center">
                        <h6 class="fw-bold">Build Status</h6>
                        <hr>
                        
                        <div class="mb-3">
                            <small class="text-muted d-block">Estimated Total Performance</small>
                            <h3 class="fw-bold text-dark mb-0"><?php echo $total_hp; ?> <small style="font-size: 0.9rem;">HP</small></h3>
                        </div>
                        <hr>

                        <?php 
                            $missing = 0;
                            for ($k = 0; $k < count($mandatory_cats); $k = $k + 1) {
                                if(!isset($db_selections[$mandatory_cats[$k]['id']])) {
                                    $missing = $missing + 1;
                                }
                            }
                        ?>

                        <?php if ($missing > 0) { ?>
                            <p class="small text-danger"><i class="fa fa-times-circle"></i> Missing <?php echo $missing; ?> core parts</p>
                            <button class="btn btn-secondary w-100 fw-bold mb-2" disabled>Add Build to Cart</button>
                        <?php } else if ($has_unavailable_parts) { ?>
                            <p class="small text-danger"><i class="fa fa-exclamation-triangle"></i> Some parts are unavailable</p>
                            <button class="btn btn-secondary w-100 fw-bold mb-2" disabled>Add Build to Cart</button>
                        <?php } else if (!$is_compatible) { ?>
                            <div class="alert alert-warning p-2 small text-start">
                                <i class="fa fa-warning"></i> <strong>Compatibility Issue:</strong> 
                                <p class="mb-0 mt-1" style="font-size: 0.75rem;">
                                    <?php 
                                        if ($contradiction_error != "") {
                                            echo $contradiction_error;
                                        } else {
                                            echo "Selected parts do not share a common tag. Ensure all parts belong to the same tag group or are marked 'universal'.";
                                        }
                                    ?>
                                </p>
                            </div>
                            <button class="btn btn-secondary w-100 fw-bold mb-2" disabled>Add Build to Cart</button>
                        <?php } else if ($build_stock_exceeded) { ?>
                            <p class="small text-danger"><i class="fa fa-exclamation-circle"></i> Some parts exceed current stock levels in your cart</p>
                            <button class="btn btn-secondary w-100 fw-bold mb-2" disabled title="Check individual parts for stock limits">Add Build to Cart</button>
                        <?php } else { ?>
                            <p class="small text-success"><i class="fa fa-check-circle"></i> Build Complete & Compatible!</p>
                            <form action="req/builder-action.php" method="POST">
                                <input type="hidden" name="action" value="add_build_to_cart">
                                <button type="submit" class="btn btn-success w-100 fw-bold mb-2">Add Build to Cart</button>
                            </form>
                        <?php } ?>

                        <form action="req/builder-action.php" method="POST" class="mb-2">
                            <input type="hidden" name="action" value="save_build">
                            <div class="mb-2 text-start">
                                <label class="small fw-bold">Build Name:</label>
                                <input type="text" name="build_name" id="build_name" class="form-control form-control-sm" placeholder="e.g. My Fast Sedan" oninput="checkSaveButton()" required>
                            </div>
                            <button type="submit" id="save-build-btn" class="btn btn-primary w-100 fw-bold" <?php echo (!$is_compatible) ? 'disabled' : ''; ?>>
                                <i class="fa fa-save"></i> Save Build
                            </button>
                        </form>

                        <form action="req/builder-action.php" method="POST">
                            <input type="hidden" name="action" value="wishlist_build">
                            <button type="submit" class="btn <?php echo $is_build_wishlisted ? 'btn-warning' : 'btn-outline-warning'; ?> w-100 fw-bold" <?php echo (count($db_selections) == 0) ? 'disabled' : ''; ?>>
                                <i class="fa fa-heart"></i> Wishlist Build
                            </button>
                        </form>
                        
                        <form action="req/builder-action.php" method="POST" class="mt-2">
                            <input type="hidden" name="action" value="clear_all">
                            <button type="submit" class="btn btn-link btn-sm text-danger">Clear Progress</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('main-total').innerText = "$<?php echo number_format($total_price, 2); ?>";

        function checkSaveButton() {
            var nameInput = document.getElementById('build_name');
            var saveBtn = document.getElementById('save-build-btn');
            var selectionCount = <?php echo count($db_selections); ?>;
            var isCompatible = <?php echo $is_compatible ? 'true' : 'false'; ?>;

            if (nameInput && saveBtn) {
                if (nameInput.value.trim() !== "" && selectionCount > 0 && isCompatible === true) {
                    saveBtn.disabled = false;
                } else {
                    saveBtn.disabled = true;
                }
            }
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