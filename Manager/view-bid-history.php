<?php 
session_start();

if (isset($_SESSION['user_id']) && 
    isset($_SESSION['role'])    && 
    $_SESSION['role'] == 'Manager') {
    
    include "../DB_connection.php";

    if (!isset($_GET['auction_id'])) {
        header("Location: auction-dashboard.php");
        exit;
    }

    $auction_id = $_GET['auction_id'];

    /* --- FETCH AUCTION & CAR DETAILS --- */
    // Using a.id because your screenshot shows 'id' as the PK for auctions
    $auc_sql = "SELECT a.*, c.brand, c.model, c.year 
                FROM auctions a 
                JOIN cars c ON a.car_id = c.id 
                WHERE a.id = ?";
    
    $auc_stmt = $conn->prepare($auc_sql);
    $auc_stmt->execute([$auction_id]);
    $auction = $auc_stmt->fetch();

    if (!$auction) {
        header("Location: auction-dashboard.php?error=Auction not found");
        exit;
    }

    /* --- FETCH BID HISTORY --- */
    // Your screenshot shows table 'auction_bids' and users table PK 'uid'
    $bid_sql = "SELECT b.*, u.username 
                FROM auction_bids b 
                JOIN users u ON b.customer_id = u.uid 
                WHERE b.auction_id = ? 
                ORDER BY b.bid_amount DESC, b.bid_time DESC";
    
    $bid_stmt = $conn->prepare($bid_sql);
    $bid_stmt->execute([$auction_id]);
    $bids = $bid_stmt->fetchAll();

    $total_bids = count($bids);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bid History - <?php echo $auction['brand']; ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        body { background-color: #f4f7f6; }
        .history-container { 
            background: #fff; 
            padding: 30px; 
            border-radius: 20px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.05); 
        }
        .badge-winner { background-color: #198754; color: white; }
    </style>
</head>
<body>
    <?php include "inc/navbar.php"; ?>

    <div class="container mt-5 mb-5">
        <div class="row justify-content-center">
            <div class="col-md-10">
                
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="fw-bold m-0 text-dark">
                            <i class="fa fa-history text-success me-2"></i> Bid History
                        </h2>
                        <p class="text-muted">
                            <?php echo $auction['year']; ?> <?php echo $auction['brand']; ?> <?php echo $auction['model']; ?> 
                            <span class="ms-2 badge bg-secondary">ID: #<?php echo $auction['id']; ?></span>
                        </p>
                    </div>
                    <a href="auction-dashboard.php" class="btn btn-outline-primary rounded-pill">
                        <i class="fa fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>

                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="history-container mb-4">
                            <h6 class="text-uppercase fw-bold text-muted mb-3" style="font-size: 0.8rem;">Auction Summary</h6>
                            
                            <div class="mb-3">
                                <label class="d-block text-muted small">Reserve Price</label>
                                <span class="fw-bold">$<?php echo number_format($auction['reserve_price']); ?></span>
                            </div>

                            <div class="mb-3">
                                <label class="d-block text-muted small">Min. Increment</label>
                                <span class="fw-bold">$<?php echo number_format($auction['min_increment']); ?></span>
                            </div>

                            <div class="mb-0">
                                <label class="d-block text-muted small">Total Bids</label>
                                <span class="fw-bold text-success" style="font-size: 1.5rem;"><?php echo $total_bids; ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-8">
                        <div class="history-container">
                            <h5 class="fw-bold mb-4">All Bids</h5>

                            <?php if ($total_bids > 0) { ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Bidder</th>
                                                <th>Amount</th>
                                                <th>Time</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            for ($i = 0; $i < $total_bids; $i = $i + 1) { 
                                                $b = $bids[$i];
                                                $is_highest = ($i == 0);
                                            ?>
                                                <tr class="<?php echo $is_highest ? 'table-success' : ''; ?>">
                                                    <td>
                                                        <div class="fw-bold text-dark">
                                                            <i class="fa fa-user-circle-o me-1 text-muted"></i>
                                                            <?php echo $b['username']; ?>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="fw-bold text-success">$<?php echo number_format($b['bid_amount']); ?></span>
                                                    </td>
                                                    <td class="text-muted small">
                                                        <?php echo date('M d, Y | h:i A', strtotime($b['bid_time'])); ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($is_highest) { ?>
                                                            <span class="badge badge-winner">Highest Bid</span>
                                                        <?php } else { ?>
                                                            <span class="badge bg-light text-muted border">Outbid</span>
                                                        <?php } ?>
                                                    </td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php } else { ?>
                                <div class="text-center py-5">
                                    <p class="text-muted">No bids have been placed yet for this auction.</p>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

</body>
</html>
<?php 
} else {
    header("Location: ../login.php");
    exit;
} 
?>