<?php 
session_start();
if (isset($_SESSION['user_id']) && 
    isset($_SESSION['role'])    && 
    $_SESSION['role'] == 'Customer') {
    
    include "../DB_connection.php";
    $user_id = $_SESSION['user_id'];
    
    $search = "";
    if (isset($_GET['search'])) {
        $search = $_GET['search'];
    }

    $cat_filter = "";
    if (isset($_GET['category_id'])) {
        $cat_filter = $_GET['category_id'];
    }

    $status_filter = "All";
    if (isset($_GET['status'])) {
        $status_filter = $_GET['status'];
    }

    $tag_filter = "";
    if (isset($_GET['tag_filter'])) {
        $tag_filter = $_GET['tag_filter'];
    }

    // 1. Fetch User's current wishlist (Matched to user_id)
    $wish_sql = "SELECT product_id FROM wishlist WHERE user_id = ? AND product_id IS NOT NULL";
    $wish_stmt = $conn->prepare($wish_sql);
    $wish_stmt->execute([$user_id]);
    $wishlist_items = $wish_stmt->fetchAll();
    
    $wishlist_ids = [];
    for ($i = 0; $i < count($wishlist_items); $i = $i + 1) {
        $wishlist_ids[] = $wishlist_items[$i]['product_id'];
    }

    // 2. Fetch ONLY 'Part' Categories
    $cat_sql = "SELECT * FROM categories WHERE type = 'Part' ORDER BY category_name ASC";
    $cat_stmt = $conn->prepare($cat_sql);
    $cat_stmt->execute();
    $categories = $cat_stmt->fetchAll();

    // 3. Fetch all unique tags
    $tag_list_sql = "SELECT * FROM tags ORDER BY tag_name ASC";
    $tag_list_stmt = $conn->prepare($tag_list_sql);
    $tag_list_stmt->execute();
    $all_available_tags = $tag_list_stmt->fetchAll();

    // 4. Build Product Query
    $sql = "SELECT p.*, c.category_name 
            FROM products p 
            INNER JOIN categories c ON p.category_id = c.id 
            WHERE c.type = 'Part'";
    $params = [];

    if (!empty($search)) {
        $sql = $sql . " AND (p.product_name LIKE ? OR p.description LIKE ? OR p.tags LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    if (!empty($cat_filter)) {
        $sql = $sql . " AND p.category_id = ?";
        $params[] = $cat_filter;
    }

    if ($status_filter != "All") {
        $sql = $sql . " AND p.status = ?";
        $params[] = $status_filter;
    }

    if (!empty($tag_filter)) {
        $sql = $sql . " AND p.tags LIKE ?";
        $params[] = "%$tag_filter%";
    }

    $sql = $sql . " ORDER BY p.id DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customer - Spare Parts Store</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        .product-link { color: inherit; text-decoration: none; transition: color 0.2s; }
        .product-link:hover { color: #0d6efd; }
        .btn-wishlist-add { border: 1px solid #ff4757; color: #ff4757; }
        .btn-wishlist-add:hover { background-color: #ff4757; color: white; }
        .btn-wishlist-remove { background-color: #ff4757; color: white; border: 1px solid #ff4757; }
        .btn-wishlist-remove:hover { background-color: #e84118; color: white; }
        .tag-badge { font-size: 0.65rem; border-radius: 10px; padding: 2px 8px; display: inline-block; margin-right: 3px; margin-bottom: 2px; }
    </style>
</head>
<body class="bg-light">
    <?php include "inc/navbar.php"; ?>

    <div class="container mt-5">
        <div class="row mb-4">
            <div class="col-md-12 text-center">
                <h3><i class="fa fa-gears"></i> Spare Parts Store</h3>
                <p class="text-muted">Find the best performance parts for your vehicle</p>
            </div>
        </div>

        <?php if (isset($_GET['success'])) { ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm mb-4">
                <i class="fa fa-check-circle"></i> <?php echo $_GET['success']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php } ?>

        <div class="card p-3 shadow-sm border-0 mb-4">
            <form action="store.php" method="get" class="row g-3">
                <div class="col-md-2">
                    <label class="small text-muted">Category</label>
                    <select name="category_id" class="form-select">
                        <option value="">All Categories</option>
                        <?php for ($i = 0; $i < count($categories); $i = $i + 1) { 
                            $c = $categories[$i]; ?>
                            <option value="<?php echo $c['id']; ?>" 
                                <?php if($cat_filter == $c['id']) echo 'selected'; ?>>
                                <?php echo $c['category_name']; ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="small text-muted">Class</label>
                    <select name="tag_filter" class="form-select">
                        <option value="">Any Class</option>
                        <?php for ($i = 0; $i < count($all_available_tags); $i = $i + 1) { 
                            $t = $all_available_tags[$i]; ?>
                            <option value="<?php echo $t['tag_name']; ?>" 
                                <?php if($tag_filter == $t['tag_name']) echo 'selected'; ?>>
                                <?php echo $t['tag_name']; ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="small text-muted">Availability</label>
                    <select name="status" class="form-select">
                        <option value="All" <?php if($status_filter == 'All') echo 'selected'; ?>>All Items</option>
                        <option value="In Stock" <?php if($status_filter == 'In Stock') echo 'selected'; ?>>Available</option>
                        <option value="Upcoming" <?php if($status_filter == 'Upcoming') echo 'selected'; ?>>Upcoming</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="small text-muted">Search Keywords</label>
                    <input type="text" name="search" class="form-control" placeholder="Search name, description or class..." value="<?php echo $search; ?>">
                </div>

                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                    <a href="store.php" class="btn btn-secondary">Reset</a>
                </div>
            </form>
        </div>

        <div class="row">
            <?php 
            if (count($products) > 0) {
                for ($i = 0; $i < count($products); $i = $i + 1) { 
                    $p = $products[$i];
                    
                    $is_fav = false;
                    for ($j = 0; $j < count($wishlist_ids); $j = $j + 1) {
                        if ($wishlist_ids[$j] == $p['id']) {
                            $is_fav = true;
                            break;
                        }
                    }

                    // CHECK CURRENT CART QUANTITY (Using 'customer_id' as per your screenshot)
                    $cart_sql = "SELECT SUM(quantity) AS in_cart FROM cart WHERE customer_id = ? AND product_id = ?";
                    $cart_stmt = $conn->prepare($cart_sql);
                    $cart_stmt->execute([$user_id, $p['id']]);
                    $cart_res = $cart_stmt->fetch();
                    
                    $current_in_cart = 0;
                    if ($cart_res['in_cart'] != null) {
                        $current_in_cart = $cart_res['in_cart'];
                    }
            ?>
                <div class="col-md-3 mb-4">
                    <div class="card h-100 shadow-sm border-0">
                        <a href="product-view.php?id=<?php echo $p['id']; ?>">
                            <img src="../uploads/<?php echo $p['image']; ?>" 
                                 class="card-img-top" 
                                 style="height: 180px; object-fit: cover;">
                        </a>
                        
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="badge bg-info text-dark">
                                    <?php echo $p['category_name']; ?>
                                </span>
                                
                                <?php if ($p['status'] == 'Upcoming') { ?>
                                    <span class="badge bg-primary">Upcoming</span>
                                <?php } else if ($p['status'] == 'Out of Stock' || $p['quantity'] <= 0) { ?>
                                    <span class="badge bg-danger">Sold Out</span>
                                <?php } else { ?>
                                    <span class="badge bg-success">In Stock</span>
                                <?php } ?>
                            </div>

                            <h5 class="card-title mb-1">
                                <a href="product-view.php?id=<?php echo $p['id']; ?>" class="product-link">
                                    <?php echo $p['product_name']; ?>
                                </a>
                            </h5>

                            <div class="mb-2">
                                <?php 
                                    $p_tags = explode(", ", $p['tags']);
                                    for ($k = 0; $k < count($p_tags); $k = $k + 1) {
                                        $tag_text = $p_tags[$k];
                                        if (!empty($tag_text)) {
                                            echo '<span class="tag-badge bg-secondary text-white">' . $tag_text . '</span>';
                                        }
                                    }
                                ?>
                            </div>
                            
                            <div class="mt-auto">
                                <h5 class="text-primary mb-1">$<?php echo number_format($p['price'], 2); ?></h5>
                                
                                <a href="product-view.php?id=<?php echo $p['id']; ?>" class="text-decoration-none small mb-3 d-block text-muted">
                                    <i class="fa fa-star text-warning"></i> View Reviews
                                </a>

                                <?php if ($p['status'] == 'In Stock' && $p['quantity'] > 0) { ?>
                                    
                                    <?php if ($current_in_cart >= $p['quantity']) { ?>
                                        <div class="alert alert-danger p-2 small mb-2 text-center">
                                            <i class="fa fa-warning"></i> Stock Limit in Cart
                                        </div>
                                    <?php } else { ?>
                                        <form action="req/add-to-cart.php" method="post">
                                            <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                                            <div class="input-group input-group-sm mb-2">
                                                <span class="input-group-text">Qty</span>
                                                <input type="number" name="qty" class="form-control" value="1" min="1" max="<?php echo ($p['quantity'] - $current_in_cart); ?>">
                                            </div>
                                            <div class="d-grid gap-2">
                                                <button type="submit" class="btn btn-dark">
                                                    <i class="fa fa-shopping-cart"></i> Add to Cart
                                                </button>
                                            </div>
                                        </form>
                                    <?php } ?>

                                    <div class="d-grid gap-2 mt-2">
                                        <?php if ($is_fav) { ?>
                                            <a href="req/wishlist-handle.php?id=<?php echo $p['id']; ?>&action=remove&type=product&source=store" class="btn btn-sm btn-wishlist-remove">
                                                <i class="fa fa-heart"></i> Remove
                                            </a>
                                        <?php } else { ?>
                                            <a href="req/wishlist-handle.php?id=<?php echo $p['id']; ?>&action=add&type=product&source=store" class="btn btn-sm btn-wishlist-add">
                                                <i class="fa fa-heart-o"></i> Wishlist
                                            </a>
                                        <?php } ?>
                                    </div>

                                <?php } else { ?>
                                    <button class="btn btn-secondary w-100 mb-2" disabled>
                                        <?php echo ($p['status'] == 'Upcoming') ? 'Upcoming' : 'Out of Stock'; ?>
                                    </button>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php 
                } 
            } else { ?>
                <div class="col-12 text-center py-5">
                    <div class="alert alert-info">No parts found.</div>
                </div>
            <?php } ?>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php 
} else {
    header("Location: ../login.php");
    exit;
} ?>