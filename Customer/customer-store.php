<?php 
session_start();
if (isset($_SESSION['user_id']) && $_SESSION['role'] == 'Customer') {
    include "DB_connection.php";

    $sql = "SELECT * FROM products ORDER BY product_id DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Garage Store</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <h3 class="text-center mb-4">Available Parts & Products</h3>
        <div class="row">
            <?php 
            for ($i = 0; $i < count($products); $i++) { 
                $p = $products[$i];
            ?>
            <div class="col-md-3 mb-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body text-center">
                        <h5 class="card-title"><?=$p['product_name']?></h5>
                        <p class="text-muted"><?=$p['description']?></p>
                        <h6 class="text-primary">$<?=$p['price']?></h6>
                        <form action="req/add-to-cart.php" method="post">
                            <input type="hidden" name="product_id" value="<?=$p['product_id']?>">
                            <button type="submit" class="btn btn-success btn-sm">Add to Cart</button>
                        </form>
                    </div>
                </div>
            </div>
            <?php } ?>
        </div>
    </div>
</body>
</html>
<?php 
} else { header("Location: login.php"); exit; } 
?>