<?php 
session_start();

if (isset($_SESSION['user_id']) && 
    isset($_SESSION['role'])    && 
    $_SESSION['role'] == 'Customer' &&
    isset($_GET['id'])) {
    
    include "../DB_connection.php";
    $car_id = $_GET['id'];
    $u_id = $_SESSION['user_id'];

    /* --- 1. FETCH CAR DETAILS --- */
    $sql = "SELECT cars.*, categories.category_name 
            FROM cars 
            JOIN categories ON cars.category_id = categories.id 
            WHERE cars.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$car_id]);
    $car = $stmt->fetch();

    if (!$car) {
        header("Location: showroom.php?error=Car not found");
        exit;
    }

    /* --- 2. CHECK PURCHASE STATUS --- */
    $order_sql = "SELECT id FROM orders WHERE customer_id = ? AND car_id = ?";
    $order_stmt = $conn->prepare($order_sql);
    $order_stmt->execute([$u_id, $car_id]);
    $has_purchased = $order_stmt->fetch();

    /* --- 3. CHECK FOR EXISTING REVIEW --- */
    $my_rev_sql = "SELECT * FROM reviews WHERE user_id = ? AND car_id = ?";
    $my_rev_stmt = $conn->prepare($my_rev_sql);
    $my_rev_stmt->execute([$u_id, $car_id]);
    $my_review = $my_rev_stmt->fetch();

    /* --- 4. CHECK WISHLIST STATUS --- */
    $wish_sql = "SELECT id FROM wishlist WHERE user_id = ? AND car_id = ?";
    $wish_stmt = $conn->prepare($wish_sql);
    $wish_stmt->execute([$u_id, $car_id]);
    $is_wishlisted = $wish_stmt->fetch();

    /* --- 5. FETCH ALL REVIEWS --- */
    $rev_sql = "SELECT r.*, c.fname, c.lname 
                FROM reviews r 
                JOIN customer c ON r.user_id = c.user_id 
                WHERE r.car_id = ? 
                ORDER BY r.date_posted DESC";
    $rev_stmt = $conn->prepare($rev_sql);
    $rev_stmt->execute([$car_id]);
    $reviews = $rev_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $car['brand'] . " " . $car['model']; ?> | Rev Nation</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        .car-main-img { border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); width: 100%; height: auto; object-fit: cover; }
        .spec-card { background: #fff; border: 1px solid #eee; border-radius: 12px; transition: 0.3s; }
        .spec-card:hover { transform: translateY(-5px); border-color: #0d6efd; }
        .price-tag { font-size: 2.5rem; font-weight: 800; color: #198754; }
        .review-card { border: none; border-radius: 15px; background: #fff; }
        .sticky-sidebar { position: sticky; top: 20px; }
        .badge-premium { padding: 8px 16px; border-radius: 50px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; }
        .company-reply { background: #f0f7ff; border-left: 4px solid #0d6efd; padding: 15px; border-radius: 0 10px 10px 0; }
    </style>
</head>
<body class="bg-light">
    <?php include "inc/navbar.php"; ?>

    <div class="container mt-5 mb-5">
        
        <?php if (isset($_GET['success'])) { ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 mb-4">
                <i class="fa fa-check-circle me-2"></i> <?php echo $_GET['success']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php } ?>

        <div class="row g-5">
            <div class="col-lg-7">
                <img src="../uploads/<?php echo $car['image']; ?>" class="car-main-img mb-4">
                
                <div class="card border-0 shadow-sm p-4 mb-4" style="border-radius: 15px;">
                    <h4 class="fw-bold mb-3">Description</h4>
                    <p class="text-secondary" style="line-height: 1.8; font-size: 1.1rem;">
                        <?php echo nl2br($car['description']); ?>
                    </p>
                </div>

                <div class="row g-3 mb-5">
                    <div class="col-md-4">
                        <div class="spec-card p-3 text-center">
                            <i class="fa fa-cog text-primary mb-2 fa-2x"></i>
                            <small class="text-muted d-block">Transmission</small>
                            <span class="fw-bold"><?php echo $car['transmission']; ?></span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="spec-card p-3 text-center">
                            <i class="fa fa-dashboard text-primary mb-2 fa-2x"></i>
                            <small class="text-muted d-block">Mileage</small>
                            <span class="fw-bold"><?php echo number_format($car['mileage']); ?> km</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="spec-card p-3 text-center">
                            <i class="fa fa-cubes text-primary mb-2 fa-2x"></i>
                            <small class="text-muted d-block">Inventory</small>
                            <span class="fw-bold"><?php echo $car['quantity']; ?> Units</span>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="fw-bold m-0">Customer Reviews</h4>
                    <span class="badge bg-white text-dark border p-2"><?php echo count($reviews); ?> Feedback</span>
                </div>

                <?php if ($has_purchased) { ?>
                    <div class="card p-4 mb-4 shadow-sm border-0" style="border-radius: 15px; background: #f8f9fa;">
                        <h5 class="fw-bold mb-3"><?php echo ($my_review) ? 'Manage Your Review' : 'Share Your Experience'; ?></h5>
                        <form action="req/post-review.php" method="POST">
                            <input type="hidden" name="car_id" value="<?php echo $car_id; ?>">
                            <?php if ($my_review) { ?>
                                <input type="hidden" name="review_id" value="<?php echo $my_review['id']; ?>">
                                <input type="hidden" name="action" value="update">
                            <?php } else { ?>
                                <input type="hidden" name="action" value="add">
                            <?php } ?>

                            <div class="row g-2 mb-3">
                                <div class="col-md-4">
                                    <select name="rating" class="form-select border-0 shadow-sm">
                                        <?php for ($i = 5; $i >= 1; $i = $i - 1) { 
                                            $selected = ($my_review && $i == $my_review['rating']) ? "selected" : "";
                                        ?>
                                            <option value="<?php echo $i; ?>" <?php echo $selected; ?>>
                                                <?php echo $i; ?> Stars
                                            </option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="col-md-12">
                                    <textarea name="comment" class="form-control border-0 shadow-sm" rows="3" placeholder="How is the car performing?" required><?php echo ($my_review) ? $my_review['comment'] : ''; ?></textarea>
                                </div>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-dark px-4"><?php echo ($my_review) ? 'Update' : 'Submit'; ?></button>
                                <?php if ($my_review) { ?>
                                    <a href="req/post-review.php?review_id=<?php echo $my_review['id']; ?>&car_id=<?php echo $car_id; ?>&action=delete" 
                                       class="btn btn-outline-danger" onclick="return confirm('Delete permanently?')">Delete</a>
                                <?php } ?>
                            </div>
                        </form>
                    </div>
                <?php } ?>

                <?php for ($i = 0; $i < count($reviews); $i = $i + 1) { 
                    $r = $reviews[$i];
                ?>
                    <div class="card p-4 mb-3 review-card shadow-sm <?php echo ($r['user_id'] == $u_id) ? 'border-start border-primary border-5' : ''; ?>">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h6 class="fw-bold mb-0"><?php echo $r['fname'] . " " . $r['lname']; ?> 
                                    <?php if ($r['user_id'] == $u_id) echo '<span class="badge bg-primary ms-1">You</span>'; ?>
                                </h6>
                                <small class="text-muted"><?php echo date("F j, Y", strtotime($r['date_posted'])); ?></small>
                            </div>
                            <div class="text-warning">
                                <?php for ($j = 0; $j < $r['rating']; $j = $j + 1) { echo '<i class="fa fa-star"></i>'; } ?>
                            </div>
                        </div>
                        <p class="mt-3 text-secondary"><?php echo $r['comment']; ?></p>
                        
                        <?php if (!empty($r['company_reply'])) { ?>
                            <div class="company-reply mt-3">
                                <small class="fw-bold text-primary text-uppercase">Rev Nation Response</small>
                                <p class="mb-0 mt-1 small text-dark"><?php echo $r['company_reply']; ?></p>
                            </div>
                        <?php } ?>
                    </div>
                <?php } ?>
            </div>

            <div class="col-lg-5">
                <div class="sticky-sidebar">
                    <div class="card border-0 shadow-sm p-4" style="border-radius: 20px;">
                        <span class="badge bg-primary-soft text-primary badge-premium mb-2" style="background: #e7f1ff; width: fit-content;">
                            <?php echo $car['category_name']; ?>
                        </span>
                        <h1 class="fw-bold display-6"><?php echo $car['brand']; ?></h1>
                        <h3 class="text-muted mb-4"><?php echo $car['model'] . " (" . $car['year'] . ")"; ?></h3>
                        
                        <div class="price-tag mb-4">$<?php echo number_format($car['price']); ?></div>

                        <hr class="mb-4">

                        <div class="mb-4">
                            <h6 class="fw-bold"><i class="fa fa-shield text-success me-2"></i> Warranty Included</h6>
                            <ul class="list-unstyled mt-2">
                                <?php if ($car['regular_warranty'] == 1) { ?>
                                    <li class="mb-2"><i class="fa fa-check text-success me-2"></i> Standard Service Warranty</li>
                                <?php } ?>
                                <?php if ($car['replacement_warranty'] == 1) { ?>
                                    <li><i class="fa fa-check text-success me-2"></i> Full Parts Replacement Coverage</li>
                                <?php } ?>
                            </ul>
                        </div>

                        <div class="d-grid gap-3">
                            <?php if ($car['quantity'] > 0) { ?>
                                <a href="car-transaction.php?id=<?php echo $car_id; ?>" class="btn btn-success btn-lg py-3 fw-bold shadow">
                                    PROCEED TO PURCHASE
                                </a>
                            <?php } else { ?>
                                <button class="btn btn-secondary btn-lg py-3 fw-bold" disabled>OUT OF STOCK</button>
                            <?php } ?>

                            <?php 
                                $wish_action = ($is_wishlisted) ? "remove" : "add";
                                $wish_btn_class = ($is_wishlisted) ? "btn-danger" : "btn-outline-danger";
                                $wish_icon = ($is_wishlisted) ? "fa-heart" : "fa-heart-o";
                            ?>
                            <a href="req/wishlist-handle.php?id=<?php echo $car_id; ?>&action=<?php echo $wish_action; ?>&type=car&source=car-view" 
                               class="btn <?php echo $wish_btn_class; ?> btn-lg py-3 fw-bold">
                                <i class="fa <?php echo $wish_icon; ?> me-2"></i> 
                                <?php echo ($is_wishlisted) ? 'REMOVE FROM WISHLIST' : 'ADD TO WISHLIST'; ?>
                            </a>
                        </div>

                        <div class="text-center mt-4">
                            <small class="text-muted">
                                <i class="fa fa-info-circle me-1"></i> Secure checkout powered by Rev Nation
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php 
} else {
    header("Location: showroom.php");
    exit;
} 
?>