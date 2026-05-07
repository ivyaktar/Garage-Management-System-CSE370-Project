<?php 
session_start();

if (isset($_SESSION['user_id']) && 
    isset($_SESSION['role'])    && 
    $_SESSION['role'] == 'Customer') {
    
    include "../DB_connection.php";

    if (isset($_GET['id'])) {
        $product_id = $_GET['id'];

        // Get product details
        $sql = "SELECT * FROM products WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();

        if (!$product) {
            header("Location: store.php");
            exit;
        }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Confirm Order</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
    <?php include "inc/navbar.php"; ?>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-dark text-white text-center">
                        <h4 class="mb-0">Confirm Your Order</h4>
                    </div>
                    <div class="card-body text-center">
                        <img src="../uploads/<?php echo $product['image']; ?>" 
                             width="200" 
                             class="rounded mb-3">
                        
                        <h3><?php echo $product['product_name']; ?></h3>
                        <p class="text-muted"><?php echo $product['description']; ?></p>
                        <h2 class="text-primary">$<?php echo $product['price']; ?></h2>
                        
                        <hr>
                        
                        <form action="req/place-order.php" method="post">
                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                            <input type="hidden" name="price" value="<?php echo $product['price']; ?>">
                            
                            <div class="mb-3 text-start">
                                <label class="form-label">Quantity</label>
                                <input type="number" 
                                       name="order_qty" 
                                       class="form-control" 
                                       value="1" 
                                       min="1" 
                                       max="<?php echo $product['quantity']; ?>">
                                <small class="text-muted">Available stock: <?php echo $product['quantity']; ?></small>
                            </div>

                            <button type="submit" class="btn btn-success btn-lg w-100">
                                Confirm Purchase
                            </button>
                        </form>

                        <a href="store.php" class="btn btn-link mt-2">Back to Store</a>
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
} else {
    header("Location: ../login.php");
    exit;
} 
?>