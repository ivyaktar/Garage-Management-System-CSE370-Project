<?php 
session_start();

if (isset($_SESSION['user_id']) && 
    isset($_SESSION['role'])    && 
    $_SESSION['role'] == 'Customer') {
    
    include "../DB_connection.php";
    
    if (!isset($_GET['id'])) {
        header("Location: auction-lobby.php");
        exit;
    }

    $auction_id = $_GET['id'];
    $user_id = $_SESSION['user_id'];

    // 1. Fetch Auction and Car Details
    $sql = "SELECT a.*, c.id AS car_id, c.brand, c.model, c.year, c.image, c.transmission, 
                   c.mileage, c.description, c.quantity, c.price,
                   c.regular_warranty, c.replacement_warranty,
                   cat.category_name
            FROM auctions a
            JOIN cars c ON a.car_id = c.id
            JOIN categories cat ON c.category_id = cat.id
            WHERE a.id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$auction_id]);
    $auction = $stmt->fetch();

    if (!$auction) {
        header("Location: auction-lobby.php?error=This vehicle is currently unavailable");
        exit;
    }

    // 2. Fetch Bid History
    $bid_sql = "SELECT b.*, u.fname 
                FROM auction_bids b 
                JOIN customer u ON b.customer_id = u.customer_id 
                WHERE b.auction_id = ? 
                ORDER BY b.bid_time DESC";
    
    $bid_stmt = $conn->prepare($bid_sql);
    $bid_stmt->execute([$auction_id]);
    $bid_history = $bid_stmt->fetchAll();

    // --- FIX: Use highest_bidder_id to determine if an increment is needed ---
    if (empty($auction['highest_bidder_id']) || $auction['highest_bidder_id'] == 0) {
        // No bids yet, first bidder can bid the starting price
        $min_next_bid = $auction['start_price'];
    } else {
        // Bids exist, enforce the increment based on the current highest bid
        $min_next_bid = $auction['current_bid'] + $auction['min_increment'];
    }

    // Check if the current user is the leader
    $is_highest_bidder = ($auction['highest_bidder_id'] == $user_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $auction['brand'] . " " . $auction['model']; ?> | Auction Details</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        .auction-card { border-radius: 20px; overflow: hidden; border: none; background: #fff; }
        .timer-box { background: #111; color: #fff; padding: 20px; border-radius: 15px; text-align: center; border-bottom: 5px solid #0d6efd; }
        .bid-history-scroll { max-height: 300px; overflow-y: auto; }
        .current-bid-display { font-size: 2.5rem; font-weight: 800; color: #0d6efd; }
        .buy-now-display { font-size: 1.8rem; font-weight: 700; color: #198754; }
        .spec-item { background: #f8f9fa; padding: 15px; border-radius: 10px; border: 1px solid #eee; height: 100%; }
        .showroom-link { text-decoration: none; color: #198754; font-weight: bold; transition: 0.3s; }
        .showroom-link:hover { color: #146c43; text-decoration: underline; }
    </style>
</head>
<body class="bg-light">
    <?php include "inc/navbar.php"; ?>

    <div class="container mt-5 mb-5">
        
        <?php if (isset($_GET['success'])) { ?>
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                <i class="fa fa-check-circle me-2"></i> <?php echo $_GET['success']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php } ?>

        <?php if ($is_highest_bidder) { ?>
            <div class="alert alert-success d-flex align-items-center mb-4" style="border-radius: 15px;">
                <i class="fa fa-trophy fa-2x me-3"></i>
                <div>
                    <h6 class="mb-0 fw-bold">You are currently the highest bidder!</h6>
                    <small>Keep an eye on the timer. If no one outbids you, the car is yours.</small>
                </div>
            </div>
        <?php } ?>

        <div class="row">
            <div class="col-md-7">
                <div class="card auction-card shadow-sm mb-4">
                    <img src="../uploads/<?php echo $auction['image']; ?>" class="img-fluid" style="width: 100%; height: 450px; object-fit: cover;">
                    
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="badge bg-primary px-3 py-2"><?php echo $auction['category_name']; ?></span>
                            
                            <a href="car-view.php?id=<?php echo $auction['car_id']; ?>" class="showroom-link">
                                <i class="fa fa-university me-1"></i> View in Showroom 
                                <span class="badge bg-success ms-1"><?php echo $auction['quantity']; ?> In Stock</span>
                            </a>
                        </div>

                        <h1 class="fw-bold mb-1"><?php echo $auction['brand'] . " " . $auction['model']; ?></h1>
                        <p class="text-muted fs-5">Year: <?php echo $auction['year']; ?></p>
                        
                        <hr class="my-4">
                        
                        <h5 class="fw-bold mb-3">Vehicle Description</h5>
                        <p class="text-secondary" style="line-height: 1.7;">
                            <?php echo nl2br($auction['description']); ?>
                        </p>

                        <h5 class="fw-bold mt-5 mb-3">Technical Specifications</h5>
                        <div class="row g-3">
                            <div class="col-6 col-md-4">
                                <div class="spec-item text-center">
                                    <small class="text-muted text-uppercase d-block mb-1">Transmission</small>
                                    <span class="fw-bold"><?php echo $auction['transmission']; ?></span>
                                </div>
                            </div>
                            <div class="col-6 col-md-4">
                                <div class="spec-item text-center">
                                    <small class="text-muted text-uppercase d-block mb-1">Mileage</small>
                                    <span class="fw-bold"><?php echo number_format($auction['mileage']); ?> km</span>
                                </div>
                            </div>
                            <div class="col-6 col-md-4">
                                <div class="spec-item text-center">
                                    <small class="text-muted text-uppercase d-block mb-1">Stock ID</small>
                                    <span class="fw-bold">#CAR-<?php echo $auction['car_id']; ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-5">
                <div class="timer-box mb-4 shadow">
                    <h6 class="text-uppercase small mb-2" style="letter-spacing: 1px;">Time Remaining</h6>
                    <h2 id="countdown" class="fw-bold mb-0">--:--:--</h2>
                </div>

                <div class="card border-0 shadow-sm p-4 mb-4" style="border-radius: 20px;">
                    <p class="text-muted mb-1">Current Highest Bid</p>
                    <div class="current-bid-display mb-4">$<?php echo number_format($auction['current_bid']); ?></div>

                    <?php if ($auction['status'] == 'Active') { ?>
                        <form action="req/auction-bid-process.php" method="POST">
                            <input type="hidden" name="auction_id" value="<?php echo $auction['id']; ?>">
                            
                            <div class="mb-4">
                                <label class="form-label fw-bold">Enter Your Bid</label>
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text bg-white">$</span>
                                    <input type="number" name="bid_amount" class="form-control" 
                                           min="<?php echo $min_next_bid; ?>" 
                                           value="<?php echo $min_next_bid; ?>" 
                                           required 
                                           <?php echo ($is_highest_bidder) ? 'disabled' : ''; ?>>
                                </div>
                                <div class="form-text mt-2 text-primary">
                                    Minimum Bid: <strong>$<?php echo number_format($min_next_bid); ?></strong>
                                </div>
                            </div>
                            
                            <div class="d-grid mb-3">
                                <button type="submit" 
                                        class="btn btn-primary btn-lg fw-bold py-3 shadow-sm <?php echo ($is_highest_bidder) ? 'disabled opacity-50' : ''; ?>"
                                        <?php echo ($is_highest_bidder) ? 'disabled' : ''; ?>>
                                    <?php echo ($is_highest_bidder) ? 'YOU ARE LEADING' : 'PLACE BID'; ?>
                                </button>
                            </div>
                        </form>

                        <hr class="my-4">

                        <div class="text-center">
                            <p class="text-muted mb-1">Don't want to wait? Buy it instantly!</p>
                            <div class="buy-now-display mb-3">$<?php echo number_format($auction['reserve_price']); ?></div>
                            
                            <div class="d-grid">
                                <a href="car-transaction.php?id=<?php echo $auction['car_id']; ?>&auction_id=<?php echo $auction['id']; ?>" 
                                   class="btn btn-success btn-lg fw-bold py-3 shadow-sm">
                                    <i class="fa fa-bolt me-2"></i> BUY IT NOW
                                </a>
                            </div>
                        </div>

                    <?php } else { ?>
                        <div class="alert alert-secondary text-center fw-bold py-3 mb-0">
                            AUCTION <?php echo strtoupper($auction['status']); ?>
                        </div>
                    <?php } ?>
                </div>

                <div class="card border-0 shadow-sm p-4" style="border-radius: 20px;">
                    <h6 class="fw-bold mb-4"><i class="fa fa-history me-2 text-primary"></i>Live Bid History</h6>
                    <div class="bid-history-scroll">
                        <table class="table table-hover align-middle">
                            <tbody>
                                <?php for ($j = 0; $j < count($bid_history); $j = $j + 1) { 
                                    $b = $bid_history[$j];
                                ?>
                                    <tr class="<?php echo ($j == 0) ? 'table-primary fw-bold' : ''; ?>">
                                        <td><?php echo $b['fname']; ?></td>
                                        <td>$<?php echo number_format($b['bid_amount']); ?></td>
                                        <td class="text-muted small text-end">
                                            <?php echo date("H:i", strtotime($b['bid_time'])); ?>
                                        </td>
                                    </tr>
                                <?php } ?>

                                <?php if (count($bid_history) == 0) { ?>
                                    <tr>
                                        <td colspan="3" class="text-center py-4 text-muted small">
                                            No bids yet. Be the first!
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        var endTime = new Date("<?php echo $auction['end_time']; ?>").getTime();

        var x = setInterval(function() {
            var now = new Date().getTime();
            var distance = endTime - now;

            var days = Math.floor(distance / (1000 * 60 * 60 * 24));
            var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            var seconds = Math.floor((distance % (1000 * 60)) / 1000);

            document.getElementById("countdown").innerHTML = days + "d " + hours + "h " + minutes + "m " + seconds + "s ";

            if (distance < 0) {
                clearInterval(x);
                document.getElementById("countdown").innerHTML = "AUCTION CLOSED";
            }
        }, 1000);
    </script>
</body>
</html>

<?php 
} else {
    header("Location: ../login.php");
    exit;
} 
?>