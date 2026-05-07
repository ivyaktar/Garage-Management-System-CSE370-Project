<?php 
session_start();

if (isset($_SESSION['user_id']) && 
    isset($_SESSION['role'])    && 
    $_SESSION['role'] == 'Customer') {

    include "../DB_connection.php";
    $customer_id = $_SESSION['user_id'];

    /* --- 1. FETCH AUCTIONS THE CUSTOMER BID ON --- */
    // Joining with cars to get details and images
    $sql = "SELECT DISTINCT a.id, a.end_time, a.status, a.current_bid, a.highest_bidder_id, 
                    c.brand, c.model, c.year, c.image 
            FROM auction_bids b
            JOIN auctions a ON b.auction_id = a.id
            JOIN cars c ON a.car_id = c.id
            WHERE b.customer_id = ?
            ORDER BY a.end_time DESC";
            
    $stmt = $conn->prepare($sql);
    $stmt->execute([$customer_id]);
    $my_auctions = $stmt->fetchAll();

    $count = count($my_auctions);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auction History - Rev Nation</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        body { background-color: #f4f7f6; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .auc-card { border: none; border-radius: 12px; }
        .table thead { background-color: #212529; color: #fff; }
        .img-auc { object-fit: cover; width: 80px; height: 55px; border-radius: 8px; }
        .status-pill { font-size: 0.75rem; padding: 0.4em 1em; border-radius: 20px; font-weight: 600; text-transform: uppercase; }
        .price-tag { color: #2ecc71; font-weight: 700; }
    </style>
</head>
<body>
    <?php include "inc/navbar.php"; ?>

    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold">Auction History</h2>
                <p class="text-muted">Track your bids, wins, and active competitions</p>
            </div>
            <i class="fa fa-gavel fa-3x text-light"></i>
        </div>

        <?php if ($count > 0) { ?>
            <div class="card auc-card shadow-sm overflow-hidden mb-5">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4 py-3">Vehicle</th>
                                <th>End Date</th>
                                <th>My Highest Bid</th>
                                <th>Current/Final</th>
                                <th class="pe-4">Result</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            for ($i = 0; $i < $count; $i = $i + 1) { 
                                $row = $my_auctions[$i];
                                $aid = $row['id'];

                                /* --- 2. GET CUSTOMER'S PERSONAL MAX BID --- */
                                $sql_max = "SELECT MAX(bid_amount) as max_bid 
                                            FROM auction_bids 
                                            WHERE auction_id = ? AND customer_id = ?";
                                $stmt_max = $conn->prepare($sql_max);
                                $stmt_max->execute([$aid, $customer_id]);
                                $my_max = $stmt_max->fetch()['max_bid'];

                                /* --- 3. LOGIC FOR OUTCOME DISPLAY --- */
                                $status_text = "Pending";
                                $bg_class = "bg-secondary text-white";

                                if ($row['status'] == 'Active') {
                                    $status_text = "In Progress";
                                    $bg_class = "bg-primary text-white";
                                } else if ($row['status'] == 'Failed') {
                                    $status_text = "Cancelled";
                                    $bg_class = "bg-danger text-white";
                                } else if ($row['status'] == 'Closed') {
                                    if ($row['highest_bidder_id'] == $customer_id) {
                                        $status_text = "WINNER";
                                        $bg_class = "bg-success text-white";
                                    } else {
                                        $status_text = "Outbid";
                                        $bg_class = "bg-dark text-white";
                                    }
                                }
                            ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <img src="../uploads/<?php echo $row['image']; ?>" class="img-auc border shadow-sm me-3">
                                        <div>
                                            <a href="auction-details.php?id=<?php echo $aid; ?>" class="text-decoration-none text-dark fw-bold">
                                                <?php echo $row['brand'] . " " . $row['model']; ?>
                                            </a>
                                            <div class="text-muted small"><?php echo $row['year']; ?> Model</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <i class="fa fa-clock-o"></i> <?php echo date("M d, Y H:i", strtotime($row['end_time'])); ?>
                                    </small>
                                </td>
                                <td><strong class="text-dark">$<?php echo number_format($my_max); ?></strong></td>
                                <td class="price-tag">$<?php echo number_format($row['current_bid']); ?></td>
                                <td class="pe-4">
                                    <span class="status-pill <?php echo $bg_class; ?> shadow-sm">
                                        <?php echo $status_text; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php } else { ?>
            <div class="text-center bg-white p-5 shadow-sm rounded-3">
                <i class="fa fa-gavel fa-4x text-light mb-3"></i>
                <h5 class="fw-bold">No auction activity</h5>
                <p class="text-muted">You haven't placed any bids on vehicles yet.</p>
                <a href="showroom.php" class="btn btn-dark rounded-pill px-4">Visit Showroom</a>
            </div>
        <?php } ?>

        <div class="mb-5 text-center">
             <a href="index.php" class="btn btn-light border rounded-pill px-4">Back to Dashboard</a>
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