<?php 
session_start();

if (isset($_SESSION['user_id']) && $_SESSION['role'] == 'Customer') {
    
    include "../DB_connection.php";
    $u_id = $_SESSION['user_id'];
    
    // Determine if we are looking at a Product or a Car
    $is_car = isset($_GET['car_id']);
    $item_id = $is_car ? $_GET['car_id'] : (isset($_GET['product_id']) ? $_GET['product_id'] : null);

    if ($item_id == null) {
        header("Location: store.php");
        exit;
    }

    // 1. Get Item Details for the header
    if ($is_car == true) {
        $sql_item = "SELECT brand AS name, model AS subname FROM cars WHERE id = ?";
        $back_link = "car-view.php?id=" . $item_id;
    } else {
        $sql_item = "SELECT product_name AS name, '' AS subname FROM products WHERE id = ?";
        $back_link = "product-view.php?id=" . $item_id;
    }

    $stmt_item = $conn->prepare($sql_item);
    $stmt_item->execute([$item_id]);
    $item = $stmt_item->fetch();

    // 2. Fetch orders for this item
    // We join with warranty_claims to see if a record already exists
    if ($is_car == true) {
        $sql_orders = "SELECT o.id, o.date_ordered, w.id AS claim_id 
                       FROM orders o 
                       LEFT JOIN warranty_claims w ON o.id = w.order_id 
                       WHERE o.customer_id = ? AND o.car_id = ? 
                       ORDER BY o.date_ordered DESC";
    } else {
        $sql_orders = "SELECT o.id, o.date_ordered, w.id AS claim_id 
                       FROM orders o 
                       LEFT JOIN warranty_claims w ON o.id = w.order_id 
                       WHERE o.customer_id = ? AND o.product_id = ? 
                       ORDER BY o.date_ordered DESC";
    }
                       
    $stmt_orders = $conn->prepare($sql_orders);
    $stmt_orders->execute([$u_id, $item_id]);
    $user_orders = $stmt_orders->fetchAll();

    if (count($user_orders) == 0) {
        header("Location: store.php?error=No purchase record found");
        exit;
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Select Order - Rev Nation</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<body class="bg-light">
    <?php include "inc/navbar.php"; ?>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow-sm border-0" style="border-radius: 15px;">
                    <div class="card-header bg-dark text-white py-3">
                        <h5 class="mb-0 text-center">Which purchase is this for?</h5>
                    </div>
                    <div class="card-body p-4">
                        <p class="mb-4 text-center">
                            Target Item: <br>
                            <span class="h4 fw-bold">
                                <?php echo $item['name'] . " " . $item['subname']; ?>
                            </span>
                        </p>
                        
                        <div class="list-group">
                            <?php for ($i = 0; $i < count($user_orders); $i = $i + 1) { 
                                $order = $user_orders[$i];
                                $already_claimed = !empty($order['claim_id']);
                                
                                // Construct the link based on item type
                                $claim_url = "claim-warranty.php?order_id=" . $order['id'];
                                if ($is_car) {
                                    $claim_url .= "&car_id=" . $item_id;
                                } else {
                                    $claim_url .= "&product_id=" . $item_id;
                                }
                            ?>
                                
                                <?php if ($already_claimed == true) { ?>
                                    <div class="list-group-item list-group-item-light d-flex justify-content-between align-items-center opacity-75">
                                        <div>
                                            <span class="text-muted small">Order #<?php echo $order['id']; ?></span><br>
                                            <small class="text-muted">Purchased: <?php echo $order['date_ordered']; ?></small>
                                        </div>
                                        <span class="badge bg-secondary">Claim Already Filed</span>
                                    </div>
                                <?php } else { ?>
                                    <a href="<?php echo $claim_url; ?>" 
                                       class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3">
                                        <div>
                                            <span class="fw-bold">Order #<?php echo $order['id']; ?></span><br>
                                            <small class="text-muted">Purchased: <?php echo $order['date_ordered']; ?></small>
                                        </div>
                                        <i class="fa fa-chevron-right text-warning"></i>
                                    </a>
                                <?php } ?>

                            <?php } ?>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-0 p-3">
                        <a href="<?php echo $back_link; ?>" class="btn btn-outline-secondary w-100">Cancel</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

<?php 
} else {
    header("Location: store.php");
    exit;
} 
?>