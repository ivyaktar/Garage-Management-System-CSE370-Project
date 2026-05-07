<?php 
session_start();

// SEAL THE LEAK: Redirect Managers away from the Customer view
if (isset($_SESSION['role']) && $_SESSION['role'] == 'Manager') {
    header("Location: ../Manager/inventory-view.php");
    exit;
}

if (isset($_GET['id'])) {
    
    include "../DB_connection.php";
    $p_id = $_GET['id'];

    // 1. Fetch Product Details
    $sql = "SELECT p.*, c.category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$p_id]);
    $product = $stmt->fetch();

    // CUSTOM ERROR PAGE IF PRODUCT IS DELETED
    if (!$product) { ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <title>Product Unavailable</title>
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
        </head>
        <body class="bg-light">
            <div class="container mt-5">
                <div class="row justify-content-center text-center">
                    <div class="col-md-6 card p-5 shadow-sm border-0" style="border-radius: 20px;">
                        <i class="fa fa-chain-broken fa-4x text-muted mb-4"></i>
                        <h2 class="fw-bold">Sorry, Product Unavailable</h2>
                        <p class="text-muted">The product you are looking for may have been removed or is no longer in our catalog.</p>
                        <a href="store.php" class="btn btn-primary mt-3 px-4 py-2 rounded-pill">Continue Shopping</a>
                    </div>
                </div>
            </div>
        </body>
        </html>
    <?php 
        exit;
    }

    // NEW LOGIC: Check if adding more would exceed stock based on what is already in cart
    if (isset($_SESSION['user_id']) && isset($_POST['qty'])) {
        $u_id = $_SESSION['user_id'];
        $qty_to_add = $_POST['qty'];

        // Find how many are already in the cart for this user
        $sql_cart_check = "SELECT quantity FROM cart WHERE customer_id = ? AND product_id = ?";
        $stmt_cart_check = $conn->prepare($sql_cart_check);
        $stmt_cart_check->execute([$u_id, $p_id]);
        $existing_cart_item = $stmt_cart_check->fetch();

        $current_in_cart = 0;
        if ($existing_cart_item) {
            $current_in_cart = $existing_cart_item['quantity'];
        }

        if (($current_in_cart + $qty_to_add) > $product['quantity']) {
            header("Location: view-product.php?id=$p_id&error=Cannot add more. You already have $current_in_cart in cart and total stock is " . $product['quantity']);
            exit;
        }
    }

    // 2. Fetch Reviews for this product
    $sql_rev = "SELECT r.*, cust.fname, cust.lname 
                FROM reviews r 
                JOIN customer cust ON r.user_id = cust.user_id 
                WHERE r.product_id = ? 
                ORDER BY r.id DESC";
    $stmt_rev = $conn->prepare($sql_rev);
    $stmt_rev->execute([$p_id]);
    $reviews = $stmt_rev->fetchAll();

    // 3. Logic to check purchase AND existing review
    $can_review = false;
    $my_existing_review = null;
    $is_wishlisted = false;

    if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
        $u_id = $_SESSION['user_id'];
        
        if ($_SESSION['role'] == 'Customer') {
            
            // Check if they ever bought this product
            $sql_check_purchase = "SELECT id FROM orders 
                                   WHERE customer_id = ? AND product_id = ? LIMIT 1";
            $stmt_purchase = $conn->prepare($sql_check_purchase);
            $stmt_purchase->execute([$u_id, $p_id]);
            $order_found = $stmt_purchase->fetch();
            
            if ($order_found) {
                $can_review = true;

                // Check for existing review
                $sql_check_rev = "SELECT * FROM reviews 
                                  WHERE product_id = ? AND user_id = ?";
                $stmt_check_rev = $conn->prepare($sql_check_rev);
                $stmt_check_rev->execute([$p_id, $u_id]);
                $my_existing_review = $stmt_check_rev->fetch();
            }

            // Check Wishlist status
            $sql_wish = "SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?";
            $stmt_wish = $conn->prepare($sql_wish);
            $stmt_wish->execute([$u_id, $p_id]);
            if ($stmt_wish->rowCount() > 0) {
                $is_wishlisted = true;
            }
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $product['product_name']; ?> - Rev Nation</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .product-card-wrap { border-radius: 20px; overflow: hidden; }
        .product-img-main { border-radius: 15px; width: 100%; object-fit: cover; }
        .tag-badge { font-size: 0.75rem; border-radius: 50px; padding: 5px 15px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; }
        .price-text { font-size: 2rem; font-weight: 800; color: #0d6efd; }
        .purchase-zone { position: sticky; top: 20px; }
        .review-item { transition: 0.3s; border-radius: 15px; }
        .review-item:hover { background-color: #f8f9fa !important; }
        .company-reply { border-left: 4px solid #0d6efd; background: #f0f7ff; border-radius: 0 10px 10px 0; }
        .qty-input { border-radius: 10px 0 0 10px !important; max-width: 80px; text-align: center; }
    </style>
</head>
<body class="bg-light">
    <?php include "inc/navbar.php"; ?>

    <div class="container mt-5">
        <div class="row">
            <div class="col-12">
                <?php if (isset($_GET['success'])) { ?>
                    <div class="alert alert-success border-0 shadow-sm rounded-pill px-4"><?php echo $_GET['success']; ?></div>
                <?php } ?>
                
                <?php if (isset($_GET['error'])) { ?>
                    <div class="alert alert-danger border-0 shadow-sm rounded-pill px-4"><?php echo $_GET['error']; ?></div>
                <?php } ?>

                <?php if (isset($_GET['info'])) { ?>
                    <div class="alert alert-info border-0 shadow-sm rounded-pill px-4"><?php echo $_GET['info']; ?></div>
                <?php } ?>
            </div>
        </div>

        <div class="row g-5">
            <div class="col-md-6">
                <div class="p-2 bg-white product-card-wrap shadow-sm">
                    <img src="../uploads/<?php echo $product['image']; ?>" class="product-img-main shadow-sm">
                </div>
                
                <div class="mt-4 p-4 bg-white shadow-sm" style="border-radius: 20px;">
                    <h5 class="fw-bold mb-3"><i class="fa fa-info-circle me-2 text-primary"></i>Description</h5>
                    <p class="text-secondary" style="line-height: 1.8;"><?php echo nl2br($product['description']); ?></p>
                </div>
            </div>

            <div class="col-md-6">
                <div class="purchase-zone">
                    <div class="card border-0 shadow-sm p-4" style="border-radius: 25px;">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-2">
                                <li class="breadcrumb-item"><a href="store.php" class="text-decoration-none">Store</a></li>
                                <li class="breadcrumb-item active"><?php echo !empty($product['category_name']) ? $product['category_name'] : "General"; ?></li>
                            </ol>
                        </nav>
                        
                        <h1 class="fw-bold mb-2"><?php echo $product['product_name']; ?></h1>
                        
                        <div class="mb-4">
                            <?php 
                                $p_tags = explode(", ", $product['tags']);
                                for ($k = 0; $k < count($p_tags); $k = $k + 1) {
                                    $tag_text = $p_tags[$k];
                                    if (!empty($tag_text)) {
                                        echo '<span class="tag-badge bg-light text-dark border me-1">' . $tag_text . '</span>';
                                    }
                                }
                                if (empty($product['tags'])) {
                                    echo '<span class="tag-badge bg-light text-muted border">Universal Fit</span>';
                                }
                            ?>
                        </div>

                        <div class="price-text mb-4">$<?php echo number_format($product['price'], 2); ?></div>

                        <div class="actions">
                            <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'Customer') { 
                                $wish_action = $is_wishlisted ? 'remove' : 'add';
                                $wish_icon = $is_wishlisted ? 'fa-heart' : 'fa-heart-o';
                                $wish_btn_class = $is_wishlisted ? 'btn-danger' : 'btn-outline-danger';
                                $wish_text = $is_wishlisted ? 'Wishlisted' : 'Add to Wishlist';
                            ?>

                                <?php if ($product['status'] == 'Upcoming') { ?>
                                    <div class="alert alert-primary border-0 rounded-4">
                                        <strong>Coming Soon!</strong> This item isn't available for purchase yet.
                                    </div>
                                    <a href="req/wishlist-handle.php?id=<?php echo $product['id']; ?>&action=<?php echo $wish_action; ?>&source=view" 
                                       class="btn <?php echo $wish_btn_class; ?> btn-lg w-100 py-3 rounded-4 shadow-sm fw-bold">
                                         <i class="fa <?php echo $wish_icon; ?> me-2"></i> <?php echo $wish_text; ?>
                                    </a>

                                <?php } else if ($product['status'] == 'In Stock' && $product['quantity'] > 0) { ?>
                                    
                                    <form action="view-product.php?id=<?php echo $product['id']; ?>" method="post">
                                        <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                                        <div class="d-flex align-items-center gap-2 mb-3">
                                            <div class="input-group input-group-lg" style="width: 160px;">
                                                <span class="input-group-text bg-white border-end-0 qty-input"><i class="fa fa-cubes"></i></span>
                                                <input type="number" name="qty" class="form-control border-start-0 text-center fw-bold" value="1" min="1" max="<?php echo $product['quantity']; ?>" style="border-radius: 0 10px 10px 0;">
                                            </div>
                                            <small class="text-success fw-bold"><?php echo $product['quantity']; ?> available</small>
                                        </div>

                                        <div class="row g-2">
                                            <div class="col-8">
                                                <button type="submit" formaction="req/add-to-cart.php" class="btn btn-dark btn-lg w-100 py-3 rounded-4 fw-bold shadow">
                                                    <i class="fa fa-shopping-cart me-2"></i> Add to Cart
                                                </button>
                                            </div>
                                            <div class="col-4">
                                                <a href="req/wishlist-handle.php?id=<?php echo $product['id']; ?>&action=<?php echo $wish_action; ?>&source=view" 
                                                   class="btn <?php echo $wish_btn_class; ?> btn-lg w-100 py-3 rounded-4 shadow-sm">
                                                     <i class="fa <?php echo $wish_icon; ?>"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </form>

                                    <?php if ($can_review) { ?>
                                        <div class="mt-4 pt-4 border-top">
                                            <a href="select-order.php?product_id=<?php echo $product['id']; ?>" class="btn btn-warning w-100 py-2 rounded-pill fw-bold">
                                                <i class="fa fa-shield me-2"></i> Claim Warranty
                                            </a>
                                        </div>
                                    <?php } ?>

                                <?php } else { ?>
                                    <div class="alert alert-danger border-0 rounded-4">
                                        <strong>Sold Out!</strong> We are currently out of stock.
                                    </div>
                                    <a href="req/wishlist-handle.php?id=<?php echo $product['id']; ?>&action=<?php echo $wish_action; ?>&source=view" 
                                       class="btn <?php echo $wish_btn_class; ?> btn-lg w-100 py-3 rounded-4 shadow-sm">
                                         <i class="fa <?php echo $wish_icon; ?> me-2"></i> Save for Later
                                    </a>
                                <?php } ?>

                            <?php } else { ?>
                                <div class="bg-light p-4 rounded-4 text-center border">
                                    <p class="mb-3 text-muted">Want to buy this item?</p>
                                    <a href="../login.php" class="btn btn-primary px-4 rounded-pill">Login to Shop</a>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-5 pt-5 pb-5">
            <div class="col-lg-8">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="fw-bold m-0">Customer Feedback</h3>
                    <span class="badge bg-dark rounded-pill"><?php echo count($reviews); ?> Reviews</span>
                </div>

                <?php if ($can_review) { ?>
                    <div class="card border-0 shadow-sm p-4 mb-5" style="border-radius: 20px;">
                        <?php if ($my_existing_review) { ?>
                            <h5 class="text-primary fw-bold mb-3">Update Your Review</h5>
                            <form action="req/post-review.php" method="post">
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="review_id" value="<?php echo $my_existing_review['id']; ?>">
                                <input type="hidden" name="product_id" value="<?php echo $p_id; ?>">
                                
                                <div class="mb-3">
                                    <label class="small fw-bold text-uppercase text-muted">Your Rating</label>
                                    <select name="rating" class="form-select w-25 rounded-3">
                                        <?php for ($k = 5; $k >= 1; $k = $k - 1) { ?>
                                            <option value="<?php echo $k; ?>" <?php if($my_existing_review['rating'] == $k) echo "selected"; ?>><?php echo $k; ?> Stars</option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <textarea name="comment" class="form-control mb-3 rounded-4 p-3" rows="3" required><?php echo $my_existing_review['comment']; ?></textarea>
                                
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary px-4 rounded-pill">Update Review</button>
                                    <a href="req/post-review.php?action=delete&review_id=<?php echo $my_existing_review['id']; ?>&product_id=<?php echo $p_id; ?>" 
                                       class="btn btn-outline-danger px-4 rounded-pill">Delete</a>
                                </div>
                            </form>
                        <?php } else { ?>
                            <h5 class="fw-bold mb-3">Rate this Product</h5>
                            <form action="req/post-review.php" method="post">
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="product_id" value="<?php echo $p_id; ?>">
                                <div class="mb-3 w-25">
                                    <select name="rating" class="form-select rounded-3">
                                        <option value="5">5 Stars</option>
                                        <option value="4">4 Stars</option>
                                        <option value="3">3 Stars</option>
                                        <option value="2">2 Stars</option>
                                        <option value="1">1 Star</option>
                                    </select>
                                </div>
                                <textarea name="comment" class="form-control mb-3 rounded-4 p-3" rows="3" placeholder="Share your experience with other car enthusiasts..." required></textarea>
                                <button type="submit" class="btn btn-primary px-5 py-2 rounded-pill fw-bold">Post Review</button>
                            </form>
                        <?php } ?>
                    </div>
                <?php } ?>

                <div class="bg-white p-4 shadow-sm" style="border-radius: 20px;">
                    <?php 
                    if (count($reviews) > 0) {
                        for ($i = 0; $i < count($reviews); $i = $i + 1) { 
                            $r = $reviews[$i];
                            $is_my_review = (isset($u_id) && $r['user_id'] == $u_id);
                    ?>
                        <div class="review-item p-4 mb-2 <?php echo $is_my_review ? 'bg-light border border-primary-subtle' : ''; ?>">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="d-flex align-items-center">
                                    <div class="bg-dark text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                        <?php echo strtoupper($r['fname'][0]); ?>
                                    </div>
                                    <div>
                                        <strong class="d-block text-dark"><?php echo $r['fname'] . " " . $r['lname']; ?> <?php if($is_my_review) echo '<span class="badge bg-primary ms-1">You</span>'; ?></strong>
                                        <small class="text-muted"><?php echo date("M d, Y", strtotime($r['date_posted'])); ?></small>
                                    </div>
                                </div>
                                <div class="text-warning">
                                    <?php for ($j = 0; $j < $r['rating']; $j = $j + 1) { echo '<i class="fa fa-star"></i>'; } ?>
                                </div>
                            </div>
                            <p class="mb-0 text-secondary ps-5"><?php echo $r['comment']; ?></p>

                            <?php if (!empty($r['company_reply'])) { ?>
                                <div class="company-reply ms-5 p-3 mt-3 shadow-sm">
                                    <small class="fw-bold text-primary d-block mb-1"><i class="fa fa-official me-1"></i> Rev Nation Response:</small>
                                    <p class="mb-0 small fst-italic text-dark"><?php echo $r['company_reply']; ?></p>
                                </div>
                            <?php } ?>
                        </div>
                    <?php 
                        } 
                    } else {
                        echo "<div class='text-center py-4'><i class='fa fa-comments-o fa-3x text-light mb-2 d-block'></i>";
                        if ($can_review) {
                            echo "<p class='text-muted'>No reviews yet. Be the first to share your thoughts!</p>";
                        } else {
                            echo "<p class='text-muted'>No reviews yet. Buy the product and be the first to share your thoughts!</p>";
                        }
                        echo "</div>";
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php 
} else {
    header("Location: store.php");
    exit;
} 
?>