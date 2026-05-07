<?php 
session_start();

if (isset($_SESSION['user_id']) && 
    isset($_SESSION['role'])    && 
    $_SESSION['role'] == 'Customer' &&
    isset($_GET['id'])) { 
    
    include "../DB_connection.php";
    $car_id = $_GET['id'];
    
    // Check if this is an auction purchase
    $is_auction = isset($_GET['auction_id']);
    $auction_id = $is_auction ? $_GET['auction_id'] : null;

    // --- FIX: Logic Change ---
    // If it's an auction, we ignore quantity > 0 and status = 'In Stock' 
    // because the auction record itself validates availability.
    if ($is_auction) {
        $sql = "SELECT c.*, cat.category_name 
                FROM cars c 
                JOIN categories cat ON c.category_id = cat.id 
                WHERE c.id = ?";
    } else {
        $sql = "SELECT c.*, cat.category_name 
                FROM cars c 
                JOIN categories cat ON c.category_id = cat.id 
                WHERE c.id = ? AND c.quantity > 0 AND c.status = 'In Stock'";
    }

    $stmt = $conn->prepare($sql);
    $stmt->execute([$car_id]);
    $car = $stmt->fetch();

    if (!$car) {
        $redirect = $is_auction ? "auction-lobby.php" : "showroom.php";
        header("Location: $redirect?error=Vehicle not available");
        exit;
    }

    // Determine the price and purchase target
    $final_price = $car['price'];
    $purchase_action = "req/car-purchase.php";
    $heading_text = "Checkout Summary";

    if ($is_auction) {
        // Fetch the auction-specific price (reserve_price)
        $auc_sql = "SELECT reserve_price FROM auctions WHERE id = ? AND status = 'Active'";
        $auc_stmt = $conn->prepare($auc_sql);
        $auc_stmt->execute([$auction_id]);
        $auction_data = $auc_stmt->fetch();
        
        if ($auction_data) {
            $final_price = $auction_data['reserve_price'];
            $purchase_action = "req/auction-purchase.php"; // Different handler for auctions
            $heading_text = "Auction Settlement";
        } else {
            // If auction is no longer active, prevent purchase
            header("Location: auction-view.php?id=$auction_id&error=Auction is no longer active");
            exit;
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Complete Transaction - Rev Nation</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<body class="bg-light">
    <?php include "inc/navbar.php"; ?>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-sm border-0" style="border-radius: 15px;">
                    <div class="card-header bg-white py-3">
                        <h4 class="fw-bold mb-0 text-center">
                            <i class="fa <?php echo $is_auction ? 'fa-gavel' : 'fa-credit-card'; ?> me-2"></i>
                            <?php echo $heading_text; ?>
                        </h4>
                    </div>
                    <div class="card-body p-4">
                        <div class="row">
                            <div class="col-md-5">
                                <img src="../uploads/<?php echo $car['image']; ?>" class="img-fluid rounded shadow-sm" onerror="this.src='https://via.placeholder.com/400x250?text=Vehicle+Image'">
                            </div>
                            <div class="col-md-7">
                                <small class="text-muted text-uppercase"><?php echo $car['brand']; ?></small>
                                <h3 class="fw-bold"><?php echo $car['model']; ?></h3>
                                <hr>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Category:</span>
                                    <span class="fw-bold"><?php echo $car['category_name']; ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Year:</span>
                                    <span class="fw-bold"><?php echo $car['year']; ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-4">
                                    <span>Transmission:</span>
                                    <span class="fw-bold"><?php echo $car['transmission']; ?></span>
                                </div>
                                
                                <div class="bg-light p-3 rounded d-flex justify-content-between align-items-center">
                                    <span class="h5 mb-0">Total Amount:</span>
                                    <span class="h3 fw-bold text-success mb-0">$<?php echo number_format($final_price); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-0 p-4">
                        <form action="<?php echo $purchase_action; ?>" method="POST">
                            <input type="hidden" name="car_id" value="<?php echo $car['id']; ?>">
                            <input type="hidden" name="price" value="<?php echo $final_price; ?>">
                            <?php if ($is_auction) { ?>
                                <input type="hidden" name="auction_id" value="<?php echo $auction_id; ?>">
                            <?php } ?>
                            
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <a href="<?php echo $is_auction ? 'auction-view.php?id='.$auction_id : 'showroom.php'; ?>" class="btn btn-outline-secondary w-100 py-2">Cancel</a>
                                </div>
                                <div class="col-md-6">
                                    <button type="submit" class="btn btn-success w-100 py-2 fw-bold">
                                        Confirm & Pay <i class="fa fa-arrow-right ms-2"></i>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

<?php 
} else {
    header("Location: showroom.php");
    exit;
} 
?>