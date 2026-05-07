<?php 
session_start();

// Check for Manager or Employee role
$has_access = false;
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] == 'Manager' || $_SESSION['role'] == 'Employee') {
        $has_access = true;
    }
}

if ($has_access && isset($_GET['id'])) {
    
    include "../DB_connection.php"; 
    $car_id = $_GET['id'];

    /* --- 1. FETCH CAR DETAILS --- */
    $sql = "SELECT cars.*, categories.category_name 
            FROM cars 
            JOIN categories ON cars.category_id = categories.id 
            WHERE cars.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$car_id]);
    $car = $stmt->fetch();

    if (!$car) {
        header("Location: showroom-manage.php");
        exit;
    }

    /* --- 2. CHECK IF CAR IS LOCKED (ACTIVE OR UNPAID CLOSED AUCTION) --- */
    $lock_check_sql = "SELECT id FROM auctions 
                       WHERE car_id = ? 
                       AND (status = 'Active' OR (status = 'Closed' AND winner_paid = 0))";
    $lock_check_stmt = $conn->prepare($lock_check_sql);
    $lock_check_stmt->execute([$car_id]);
    $is_locked = $lock_check_stmt->fetch();

    /* --- 3. FETCH REVIEWS --- */
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
    <title>Staff View - <?php echo $car['brand']; ?> | Rev Nation</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        .manager-img { border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.08); width: 100%; height: auto; }
        .spec-badge { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 10px; padding: 15px; }
        .control-panel { position: sticky; top: 20px; border-radius: 20px; border: none; }
        .review-card { border-radius: 15px; transition: 0.2s; }
        .reply-box { border-left: 4px solid #6c757d; background-color: #f1f3f5; }
        .active-reply { border-left-color: #0d6efd; background-color: #e7f1ff; }
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

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="mb-4">
                    <img src="../uploads/<?php echo $car['image']; ?>" class="manager-img">
                </div>

                <div class="card border-0 shadow-sm p-4 mb-4">
                    <h4 class="fw-bold mb-3">Specifications</h4>
                    <div class="row g-3">
                        <div class="col-md-4 text-center">
                            <div class="spec-badge">
                                <small class="text-muted d-block text-uppercase">Year</small>
                                <span class="fw-bold"><?php echo $car['year']; ?></span>
                            </div>
                        </div>
                        <div class="col-md-4 text-center">
                            <div class="spec-badge">
                                <small class="text-muted d-block text-uppercase">Transmission</small>
                                <span class="fw-bold"><?php echo $car['transmission']; ?></span>
                            </div>
                        </div>
                        <div class="col-md-4 text-center">
                            <div class="spec-badge">
                                <small class="text-muted d-block text-uppercase">Mileage</small>
                                <span class="fw-bold"><?php echo number_format($car['mileage']); ?> km</span>
                            </div>
                        </div>
                    </div>
                    
                    <h5 class="fw-bold mt-4 mb-2">Description</h5>
                    <p class="text-secondary"><?php echo nl2br($car['description']); ?></p>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="fw-bold m-0">Review Management</h4>
                    <span class="badge bg-dark rounded-pill"><?php echo count($reviews); ?> Reviews</span>
                </div>

                <?php 
                $count = count($reviews);
                if ($count == 0) { 
                ?>
                    <div class="alert bg-white border shadow-sm text-center py-5">
                        <i class="fa fa-comments-o fa-3x text-muted mb-3"></i>
                        <p class="text-muted mb-0">No customers have reviewed this vehicle yet.</p>
                    </div>
                <?php 
                } 

                for ($i = 0; $i < $count; $i = $i + 1) { 
                    $r = $reviews[$i];
                    $is_editing = (isset($_GET['edit_id']) && $_GET['edit_id'] == $r['id']);
                ?>
                    <div class="card p-4 mb-3 review-card border-0 shadow-sm">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="fw-bold mb-0"><?php echo $r['fname'] . " " . $r['lname']; ?></h6>
                                <small class="text-muted">Posted: <?php echo date("M d, Y", strtotime($r['date_posted'])); ?></small>
                            </div>
                            <div class="text-warning">
                                <?php for ($j = 0; $j < $r['rating']; $j = $j + 1) { echo "★"; } ?>
                            </div>
                        </div>
                        <p class="mt-3 text-dark"><?php echo $r['comment']; ?></p>
                        
                        <div class="mt-3 p-3 rounded reply-box <?php echo (!empty($r['company_reply'])) ? 'active-reply' : ''; ?>">
                            <?php if (!empty($r['company_reply']) && !$is_editing) { ?>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <small class="fw-bold text-primary">STAFF RESPONSE</small>
                                    <div>
                                        <a href="car-view.php?id=<?php echo $car_id; ?>&edit_id=<?php echo $r['id']; ?>" 
                                           class="btn btn-sm btn-link text-decoration-none p-0 me-2">Edit</a>
                                        <a href="req/car-review-reply-delete.php?id=<?php echo $r['id']; ?>&car_id=<?php echo $car_id; ?>" 
                                           class="btn btn-sm btn-link text-danger text-decoration-none p-0"
                                           onclick="return confirm('Remove this reply?')">Delete</a>
                                    </div>
                                </div>
                                <p class="mb-0 small fst-italic text-secondary"><?php echo $r['company_reply']; ?></p>

                            <?php } else { ?>
                                <form action="req/car-review-reply.php" method="POST">
                                    <input type="hidden" name="review_id" value="<?php echo $r['id']; ?>">
                                    <input type="hidden" name="car_id" value="<?php echo $car_id; ?>">
                                    <label class="small fw-bold mb-2 text-uppercase"><?php echo ($is_editing) ? 'Update Reply' : 'New Reply'; ?></label>
                                    <div class="input-group">
                                        <input type="text" name="reply_text" class="form-control" 
                                               placeholder="Type your official response..." 
                                               value="<?php if ($is_editing) { echo $r['company_reply']; } ?>" required>
                                        <button class="btn btn-primary" type="submit">
                                            <?php echo ($is_editing) ? 'Update' : 'Post Reply'; ?>
                                        </button>
                                        <?php if ($is_editing) { ?>
                                            <a href="car-view.php?id=<?php echo $car_id; ?>" class="btn btn-secondary">Cancel</a>
                                        <?php } ?>
                                    </div>
                                </form>
                            <?php } ?>
                        </div>
                    </div>
                <?php } ?>
            </div>

            <div class="col-lg-5">
                <div class="control-panel card shadow-sm p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <span class="badge bg-secondary mb-2"><?php echo $car['category_name']; ?></span>
                            <h2 class="fw-bold mb-0"><?php echo $car['brand']; ?></h2>
                            <p class="text-muted"><?php echo $car['model']; ?></p>
                        </div>
                        <div class="text-end">
                            <h3 class="text-success fw-bold">$<?php echo number_format($car['price']); ?></h3>
                            <span class="badge <?php echo ($car['quantity'] > 0) ? 'bg-success' : 'bg-danger'; ?>">
                                <?php echo $car['quantity']; ?> In Stock
                            </span>
                        </div>
                    </div>

                    <hr>

                    <div class="d-grid gap-3 mt-4">
                        <?php if ($is_locked) { ?>
                            <div class="alert alert-danger mb-0 border-0 shadow-sm">
                                <h6 class="fw-bold mb-1"><i class="fa fa-lock"></i> Item Locked</h6>
                                <p class="small mb-0">This car is tied to an <strong>Active or Unpaid Auction</strong>. Inventory modifications are currently restricted.</p>
                            </div>
                            <button class="btn btn-secondary btn-lg py-3 disabled">
                                <i class="fa fa-edit me-2"></i> Edit Car Details
                            </button>
                        <?php } else { ?>
                            <a href="car-edit.php?id=<?php echo $car_id; ?>" class="btn btn-warning btn-lg py-3 fw-bold shadow-sm">
                                <i class="fa fa-edit me-2"></i> Edit Car Details
                            </a>
                        <?php } ?>

                        <a href="showroom-manage.php" class="btn btn-outline-dark btn-lg py-3 fw-bold">
                            <i class="fa fa-arrow-left me-2"></i> Back to Showroom
                        </a>
                    </div>

                    <div class="mt-5">
                        <h6 class="fw-bold text-muted text-uppercase small">Inventory Health</h6>
                        <div class="progress mt-2" style="height: 10px; border-radius: 50px;">
                            <?php 
                                $stock_percent = ($car['quantity'] > 10) ? 100 : $car['quantity'] * 10;
                                $bar_color = ($car['quantity'] < 3) ? 'bg-danger' : 'bg-success';
                            ?>
                            <div class="progress-bar <?php echo $bar_color; ?>" style="width: <?php echo $stock_percent; ?>%"></div>
                        </div>
                        <small class="text-muted d-block mt-1">Stock status: <?php echo $car['quantity']; ?> units remaining</small>
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
    header("Location: ../login.php");
    exit;
} 
?>