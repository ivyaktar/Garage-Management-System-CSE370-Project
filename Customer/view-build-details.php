<?php 
session_start();

if (isset($_SESSION['user_id']) && 
    isset($_SESSION['role'])    && 
    $_SESSION['role'] == 'Customer') {

    include "../DB_connection.php";
    
    if (isset($_GET['id'])) {
        $build_id = $_GET['id'];
        $user_id = $_SESSION['user_id'];

        // 1. Fetch Build Header Info
        $sql_build = "SELECT * FROM user_builds WHERE id = ? AND user_id = ?";
        $stmt_build = $conn->prepare($sql_build);
        $stmt_build->execute([$build_id, $user_id]);
        $build_info = $stmt_build->fetch();

        if (!$build_info) {
            header("Location: saved-builds.php?error=Build not found");
            exit;
        }

        // 2. Fetch all parts
        $sql_items = "SELECT p.id as product_id, p.product_name, p.price, p.image, p.tags, 
                             p.quantity, p.status, p.hp, c.category_name, c.id as category_id
                      FROM build_items bi
                      JOIN products p ON bi.product_id = p.id
                      LEFT JOIN categories c ON p.category_id = c.id
                      WHERE bi.build_id = ?";
        
        $stmt_items = $conn->prepare($sql_items);
        $stmt_items->execute([$build_id]);
        $items = $stmt_items->fetchAll();

        // 3. Fetch User's Wishlist
        $sql_wishlist = "SELECT product_id FROM wishlist WHERE user_id = ?";
        $stmt_wishlist = $conn->prepare($sql_wishlist);
        $stmt_wishlist->execute([$user_id]);
        $wishlist_data = $stmt_wishlist->fetchAll(PDO::FETCH_COLUMN, 0);

        // 4. Fetch Mandatory Categories
        $sql_mandatory = "SELECT id FROM categories WHERE is_mandatory = 1";
        $stmt_mandatory = $conn->prepare($sql_mandatory);
        $stmt_mandatory->execute();
        $mandatory_categories = $stmt_mandatory->fetchAll(PDO::FETCH_COLUMN, 0);

        // 5. LOGIC: Check Real-time Compatibility, Stock, and Completeness
        $is_compatible = true;
        $all_in_stock = true;
        $is_complete = true;
        $common_tags = null;
        $total_items = count($items);
        $wishlisted_parts_count = 0;

        // Check for completeness
        for ($m = 0; $m < count($mandatory_categories); $m = $m + 1) {
            $found_mandatory = false;
            for ($i = 0; $i < $total_items; $i = $i + 1) {
                if ($items[$i]['category_id'] == $mandatory_categories[$m]) {
                    $found_mandatory = true;
                }
            }
            if ($found_mandatory == false) {
                $is_complete = false;
            }
        }

        for ($i = 0; $i < $total_items; $i = $i + 1) {
            $item = $items[$i];

            for ($w = 0; $w < count($wishlist_data); $w = $w + 1) {
                if ($wishlist_data[$w] == $item['product_id']) {
                    $wishlisted_parts_count = $wishlisted_parts_count + 1;
                }
            }

            if ($item['quantity'] <= 0 || $item['status'] != 'In Stock') {
                $all_in_stock = false;
            }

            $raw_tags = $item['tags'];
            $current_product_tags = [];
            
            if (empty($raw_tags)) {
                $current_product_tags[] = 'universal';
            } else {
                $temp_tags = explode(',', $raw_tags);
                for ($t = 0; $t < count($temp_tags); $t = $t + 1) {
                    $cleaned = strtolower(trim($temp_tags[$t]));
                    if ($cleaned != "") {
                        $current_product_tags[] = $cleaned;
                    }
                }
            }

            if ($common_tags === null) {
                $common_tags = $current_product_tags;
            } else {
                $new_common = [];
                $has_universal = false;
                
                for ($u = 0; $u < count($common_tags); $u = $u + 1) {
                    if ($common_tags[$u] == 'universal') { $has_universal = true; }
                }

                $item_is_uni = false;
                for ($u = 0; $u < count($current_product_tags); $u = $u + 1) {
                    if ($current_product_tags[$u] == 'universal') { $item_is_uni = true; }
                }

                if ($item_is_uni) {
                    $new_common = $common_tags;
                } else if ($has_universal) {
                    $new_common = $current_product_tags;
                } else {
                    for ($j = 0; $j < count($common_tags); $j = $j + 1) {
                        for ($k = 0; $k < count($current_product_tags); $k = $k + 1) {
                            if ($common_tags[$j] == $current_product_tags[$k]) {
                                $new_common[] = $common_tags[$j];
                            }
                        }
                    }
                }
                $common_tags = $new_common;
            }
        }

        if ($total_items > 0) {
            if (is_array($common_tags)) {
                if (count($common_tags) == 0) {
                    $is_compatible = false;
                }
            } else {
                $is_compatible = false;
            }
        }

        $all_parts_wishlisted = ($total_items > 0 && $wishlisted_parts_count == $total_items);

    } else {
        header("Location: saved-builds.php");
        exit;
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Build Details - <?php echo htmlspecialchars($build_info['build_name']); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        .tag-badge { font-size: 0.7rem; padding: 2px 8px; border-radius: 50px; background: #e9ecef; margin-right: 4px; color: #495057; border: 1px solid #dee2e6; }
        .tag-universal { background: #d1e7dd; color: #0f5132; }
        .out-of-stock-row { background-color: #fff5f5; }
        .btn-wishlisted { background-color: #ffc107 !important; color: #000 !important; border-color: #ffc107 !important; }
    </style>
</head>

<body class="bg-light">

    <?php include "inc/navbar.php"; ?>

    <div class="container mt-5">

        <?php if (isset($_GET['success'])) { ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                <i class="fa fa-check-circle me-2"></i>
                <?php echo htmlspecialchars($_GET['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php } ?>

        <?php if (isset($_GET['error'])) { ?>
            <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                <i class="fa fa-exclamation-circle me-2"></i>
                <?php echo htmlspecialchars($_GET['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php } ?>

        <div class="mb-4 d-flex justify-content-between">
            <a href="saved-builds.php" class="btn btn-secondary btn-sm shadow-sm">
                <i class="fa fa-arrow-left"></i> Back to My Builds
            </a>
            
            <form action="req/builder-action.php" method="POST">
                <input type="hidden" name="action" value="wishlist_build">
                <input type="hidden" name="build_id" value="<?php echo $build_id; ?>">
                <button type="submit" class="btn <?php echo $all_parts_wishlisted ? 'btn-warning' : 'btn-outline-warning'; ?> btn-sm shadow-sm">
                    <i class="fa fa-heart"></i> <?php echo $all_parts_wishlisted ? 'Entire Build Wishlisted' : 'Wishlist Entire Build'; ?>
                </button>
            </form>
        </div>

        <div class="card shadow border-0">
            <div class="card-header bg-dark text-white p-3">
                <h3 class="mb-0">
                    <i class="fa fa-car text-warning"></i> 
                    <?php echo htmlspecialchars($build_info['build_name']); ?>
                </h3>
                <small class="text-muted">Saved on: <?php echo $build_info['created_at']; ?></small>
            </div>

            <div class="card-body">
                <h5 class="fw-bold mb-3">Selected Components</h5>
                <hr>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Image</th>
                                <th>Category & Tags</th>
                                <th>Part Name</th>
                                <th>HP</th>
                                <th>Status</th>
                                <th class="text-end">Price</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>

                            <?php 
                            $grand_total = 0;
                            $total_hp = 0;
                            for ($i = 0; $i < $total_items; $i = $i + 1) { 
                                
                                $item = $items[$i];
                                $grand_total = $grand_total + $item['price'];
                                $total_hp = $total_hp + (int)$item['hp'];
                                $img_path = "../uploads/" . $item['image'];
                                $in_stock = ($item['quantity'] > 0 && $item['status'] == 'In Stock');

                                $is_in_wishlist = false;
                                for ($w = 0; $w < count($wishlist_data); $w = $w + 1) {
                                    if ($wishlist_data[$w] == $item['product_id']) {
                                        $is_in_wishlist = true;
                                    }
                                }
                            ?>

                                <tr class="<?php echo !$in_stock ? 'out-of-stock-row' : ''; ?>">
                                    <td style="width: 100px;">
                                        <img src="<?php echo $img_path; ?>" class="img-thumbnail" style="width: 80px; height: 60px; object-fit: cover;" onerror="this.src='../uploads/default-cat.png'">
                                    </td>
                                    <td>
                                        <div class="text-uppercase small fw-bold text-muted mb-1">
                                            <?php echo !empty($item['category_name']) ? htmlspecialchars($item['category_name']) : "Component"; ?>
                                        </div>
                                        <div>
                                            <?php 
                                                if (!empty($item['tags'])) {
                                                    $p_tags = explode(',', $item['tags']);
                                                    for ($t = 0; $t < count($p_tags); $t = $t + 1) {
                                                        $t_name = trim($p_tags[$t]);
                                                        $is_u = (strtolower($t_name) == 'universal');
                                                        echo '<span class="tag-badge '.($is_u ? 'tag-universal' : '').'">'.$t_name.'</span>';
                                                    }
                                                } else {
                                                    echo '<span class="tag-badge tag-universal">universal</span>';
                                                }
                                            ?>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                    <td><span class="badge bg-dark"><?php echo (int)$item['hp']; ?> HP</span></td>
                                    <td>
                                        <?php if ($in_stock) { ?>
                                            <span class="badge bg-success">In Stock</span>
                                        <?php } else { ?>
                                            <span class="badge bg-danger">Unavailable</span>
                                        <?php } ?>
                                    </td>
                                    <td class="text-end fw-bold">$<?php echo number_format($item['price'], 2); ?></td>
                                    <td class="text-end">
                                        <form action="req/builder-action.php" method="POST" style="display:inline;">
                                            <input type="hidden" name="p_id" value="<?php echo $item['product_id']; ?>">
                                            <input type="hidden" name="build_id" value="<?php echo $build_id; ?>">
                                            <input type="hidden" name="action" value="wishlist_item">
                                            
                                            <button type="submit" 
                                                    class="btn btn-sm <?php echo $is_in_wishlist ? 'btn-wishlisted' : 'btn-outline-warning'; ?>"
                                                    title="<?php echo $is_in_wishlist ? 'In Wishlist' : 'Add to Wishlist'; ?>">
                                                <i class="fa <?php echo $is_in_wishlist ? 'fa-heart' : 'fa-heart-o'; ?>"></i>
                                            </button>
                                        </form>

                                        <form action="req/builder-action.php" method="POST" style="display:inline;">
                                            <input type="hidden" name="p_id" value="<?php echo $item['product_id']; ?>">
                                            <input type="hidden" name="build_id" value="<?php echo $build_id; ?>">
                                            <input type="hidden" name="action" value="cart_item">
                                            <button type="submit" class="btn btn-sm btn-outline-success" <?php echo !$in_stock ? 'disabled' : ''; ?>><i class="fa fa-shopping-cart"></i></button>
                                        </form>
                                    </td>
                                </tr>

                            <?php } ?>

                        </tbody>
                        <tfoot>
                            <tr class="table-dark">
                                <td colspan="3" class="text-end fw-bold">Total Power:</td>
                                <td colspan="1" class="fw-bold text-info"><?php echo $total_hp; ?> HP</td>
                                <td colspan="1" class="text-end fw-bold">Total Build Cost:</td>
                                <td class="text-end fw-bold text-warning">
                                    $<?php echo number_format($grand_total, 2); ?>
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <?php if (!$is_complete) { ?>
                    <div class="alert alert-danger mt-3">
                        <i class="fa fa-times-circle"></i> <strong>Invalid Build:</strong> Mandatory parts are missing. A component might have been removed from the shop.
                    </div>
                <?php } ?>

                <?php if ($is_complete && !$is_compatible) { ?>
                    <div class="alert alert-danger mt-3">
                        <i class="fa fa-warning"></i> <strong>Compatibility Error:</strong> Some parts in this build no longer share a common tag.
                    </div>
                <?php } ?>

                <?php if (!$all_in_stock) { ?>
                    <div class="alert alert-warning mt-2">
                        <i class="fa fa-exclamation-triangle"></i> <strong>Stock Warning:</strong> One or more items in this build are currently out of stock.
                    </div>
                <?php } ?>

                <div class="mt-4 d-flex justify-content-between align-items-center">
                    <div class="small text-muted">
                        <i class="fa fa-info-circle"></i> Status: 
                        <?php echo $is_complete ? '<span class="text-success">Complete</span>' : '<span class="text-danger">Incomplete</span>'; ?> | 
                        <?php echo $is_compatible ? '<span class="text-success">Compatible</span>' : '<span class="text-danger">Incompatible</span>'; ?>
                    </div>

                    <form action="req/builder-action.php" method="POST">
                        <input type="hidden" name="build_id" value="<?php echo $build_id; ?>">
                        <input type="hidden" name="action" value="add_build_to_cart">
                        <button type="submit" class="btn btn-success px-5 py-2 fw-bold shadow-sm" 
                                <?php echo (!$is_compatible || !$all_in_stock || !$is_complete) ? 'disabled' : ''; ?>>
                            <i class="fa fa-shopping-cart"></i> Add Full Build to Cart
                        </button>
                    </form>
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