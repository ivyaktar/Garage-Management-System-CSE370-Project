<?php 
session_start();
if (isset($_SESSION['user_id']) && $_SESSION['role'] == 'Customer') {
    include "../DB_connection.php";
    $u_id = $_SESSION['user_id'];

    // Fetch user details for the "Delivery Info" section
    $sql_user = "SELECT * FROM customer WHERE user_id = ?";
    $stmt_user = $conn->prepare($sql_user);
    $stmt_user->execute([$u_id]);
    $user = $stmt_user->fetch();

    // Fetch cart items for the "Order Summary" section
    $sql_cart = "SELECT cart.*, products.product_name, products.price 
                 FROM cart 
                 JOIN products ON cart.product_id = products.id 
                 WHERE cart.customer_id = ?";
    $stmt_cart = $conn->prepare($sql_cart);
    $stmt_cart->execute([$u_id]);
    $items = $stmt_cart->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Review Order</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<body class="bg-light">
    <?php include "inc/navbar.php"; ?>

    <div class="container mt-5">
        <h3 class="mb-4">Order Review</h3>
        
        <div class="row">
            <div class="col-md-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white"><strong>Items</strong></div>
                    <div class="card-body">
                        <?php 
                        $total = 0;
                        for ($i = 0; $i < count($items); $i++) { 
                            $item = $items[$i];
                            $subtotal = $item['price'] * $item['quantity'];
                            $total = $total + $subtotal;
                        ?>
                            <div class="d-flex justify-content-between mb-2">
                                <span><?php echo $item['product_name']; ?> (x<?php echo $item['quantity']; ?>)</span>
                                <span>$<?php echo number_format($subtotal, 2); ?></span>
                            </div>
                        <?php } ?>
                        <hr>
                        <div class="d-flex justify-content-between fw-bold h5">
                            <span>Total</span>
                            <span class="text-success">$<?php echo number_format($total, 2); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow-sm border-primary">
                    <div class="card-header bg-primary text-white"><strong>Shipping Details</strong></div>
                    <div class="card-body">
                        <p class="mb-1 small text-muted">Customer Name:</p>
                        <p class="fw-bold"><?php echo $user['fname'] . " " . $user['lname']; ?></p>
                        
                        <p class="mb-1 small text-muted">Contact Email:</p>
                        <p class="fw-bold mb-4"><?php echo $user['email_address']; ?></p>
                        
                        <form action="req/place_order.php" method="post">
                            <button type="submit" class="btn btn-success w-100 py-2">
                                Confirm & Purchase <i class="fa fa-check"></i>
                            </button>
                            <a href="cart.php" class="btn btn-link w-100 text-muted mt-2 text-decoration-none">
                                <i class="fa fa-edit"></i> Edit Cart
                            </a>
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
    header("Location: ../login.php");
    exit;
} 
?>