<?php 
session_start();

if (isset($_SESSION['user_id']) && 
    isset($_SESSION['role'])    && 
    $_SESSION['role'] == 'Customer') {
    
    include "../DB_connection.php";
    $u_id = $_SESSION['user_id'];

    $sql = "SELECT o.*, 
                   p.product_name, p.image AS product_img, 
                   c.brand, c.model, c.image AS car_img,
                   wc.status AS claim_status
            FROM orders o 
            LEFT JOIN products p ON o.product_id = p.id 
            LEFT JOIN cars c ON o.car_id = c.id
            LEFT JOIN warranty_claims wc ON o.id = wc.order_id
            WHERE o.customer_id = ? 
            ORDER BY o.id DESC";
            
    $stmt = $conn->prepare($sql);
    $stmt->execute([$u_id]);
    $orders = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Order History - Rev Nation</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        body { background-color: #f4f7f6; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .order-card { border: none; border-radius: 12px; transition: transform 0.2s; }
        .table thead { background-color: #212529; color: #fff; }
        .img-thumbnail-custom { object-fit: cover; width: 70px; height: 50px; border-radius: 8px; }
        .status-badge { font-weight: 500; padding: 0.5em 1em; border-radius: 20px; text-transform: uppercase; font-size: 0.75rem; }
        .price-text { color: #2ecc71; font-weight: 700; }
        .item-title { font-size: 1rem; margin-bottom: 0; }
    </style>
</head>
<body>
    <?php include "inc/navbar.php"; ?>

    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold">Order History</h2>
                <p class="text-muted">Manage your purchases and warranty claims</p>
            </div>
            <i class="fa fa-history fa-3x text-light"></i>
        </div>

        <?php if (isset($_GET['success'])) { ?>
            <div class="alert alert-success border-0 shadow-sm rounded-3">
                <i class="fa fa-check-circle me-2"></i> <?php echo $_GET['success']; ?>
            </div>
        <?php } ?>
        
        <div class="card order-card shadow-sm mb-5">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-dark">
                            <tr>
                                <th class="ps-4 py-3">ID</th>
                                <th>Item</th>
                                <th>Details</th>
                                <th>Qty</th>
                                <th>Amount</th>
                                <th>Date</th>
                                <th class="pe-4">Warranty</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $order_count = count($orders);

                            if ($order_count > 0) {
                                for ($i = 0; $i < $order_count; $i = $i + 1) { 
                                    $o = $orders[$i];

                                    $display_name = "Item Deleted";
                                    $display_img = "";
                                    $view_link = "#";
                                    $category = "Unknown";
                                    $is_deleted = true;

                                    if (!empty($o['product_name'])) {
                                        $display_name = $o['product_name'];
                                        $display_img = $o['product_img'];
                                        $view_link = "product-view.php?id=" . $o['product_id'];
                                        $category = "Parts & Acc.";
                                        $is_deleted = false;
                                    } else if (!empty($o['brand'])) {
                                        $display_name = $o['brand'] . " " . $o['model'];
                                        $display_img = $o['car_img'];
                                        $view_link = "car-view.php?id=" . $o['car_id'];
                                        $category = "Vehicle";
                                        $is_deleted = false;
                                    }

                                    $display_date = "N/A";
                                    if (isset($o['date_ordered'])) {
                                        $display_date = date("M d, Y", strtotime($o['date_ordered']));
                                    } else if (isset($o['date'])) {
                                        $display_date = date("M d, Y", strtotime($o['date']));
                                    }
                            ?>
                            <tr class="align-middle border-bottom">
                                <td class="ps-4 text-muted fw-bold">#<?php echo $o['id']; ?></td>
                                <td>
                                    <?php if (!empty($display_img)) { ?>
                                        <img src="../uploads/<?php echo $display_img; ?>" class="img-thumbnail-custom shadow-sm border">
                                    <?php } else { ?>
                                        <div class="img-thumbnail-custom bg-light d-flex align-items-center justify-content-center border">
                                            <i class="fa fa-image text-muted"></i>
                                        </div>
                                    <?php } ?>
                                </td>
                                <td>
                                    <p class="item-title">
                                        <a href="<?php echo $view_link; ?>" class="text-decoration-none text-dark fw-bold">
                                            <?php echo $display_name; ?>
                                        </a>
                                    </p>
                                    <span class="badge bg-light text-dark border font-monospace" style="font-size: 0.65rem;">
                                        <?php echo $category; ?>
                                    </span>
                                </td>
                                <td><span class="badge rounded-pill bg-light text-dark border px-3">x<?php echo $o['quantity']; ?></span></td>
                                <td class="price-text">$<?php echo number_format($o['total_price'], 2); ?></td>
                                <td class="text-muted" style="font-size: 0.9rem;"><?php echo $display_date; ?></td>
                                <td class="pe-4">
                                    <?php if (empty($o['claim_status'])) { ?>
                                        
                                        <?php if ($is_deleted == false) { ?>
                                            <?php 
                                                $claim_url = "claim-warranty.php?order_id=" . $o['id'];
                                                if (!empty($o['product_id'])) {
                                                    $claim_url .= "&product_id=" . $o['product_id'];
                                                } else {
                                                    $claim_url .= "&car_id=" . $o['car_id'];
                                                }
                                            ?>
                                            <a href="<?php echo $claim_url; ?>" class="btn btn-sm btn-outline-warning rounded-pill px-3">
                                                <i class="fa fa-shield"></i> Claim
                                            </a>
                                        <?php } else { ?>
                                            <span class="text-muted small italic">Unavailable</span>
                                        <?php } ?>

                                    <?php } else { 
                                        $badge_class = "bg-secondary";
                                        if ($o['claim_status'] == 'Pending') $badge_class = "bg-info text-dark";
                                        if ($o['claim_status'] == 'Approved') $badge_class = "bg-success";
                                        if ($o['claim_status'] == 'Rejected') $badge_class = "bg-danger";
                                    ?>
                                        <span class="status-badge <?php echo $badge_class; ?> shadow-sm">
                                            <?php echo $o['claim_status']; ?>
                                        </span>
                                    <?php } ?>
                                </td>
                            </tr>
                            <?php 
                                } 
                            } else { ?>
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <i class="fa fa-shopping-basket fa-3x text-light mb-3 d-block"></i>
                                    <p class="text-muted">You haven't placed any orders yet.</p>
                                    <a href="store.php" class="btn btn-primary btn-sm rounded-pill">Start Shopping</a>
                                </td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="mt-4 d-flex gap-3 mb-5 justify-content-center">
            <a href="index.php" class="btn btn-light border rounded-pill px-4"><i class="fa fa-home"></i> Dashboard</a>
            <a href="showroom.php" class="btn btn-dark rounded-pill px-4"><i class="fa fa-car"></i> View Cars</a>
            <a href="store.php" class="btn btn-outline-dark rounded-pill px-4"><i class="fa fa-shopping-bag"></i> Parts Store</a>
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