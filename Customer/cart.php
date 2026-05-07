<?php 
session_start();

if (isset($_SESSION['user_id']) && 
    isset($_SESSION['role'])    && 
    $_SESSION['role'] == 'Customer') {
    
    include "../DB_connection.php";
    $u_id = $_SESSION['user_id'];

    // Fetch items from the cart table joined with products
    $sql = "SELECT cart.*, products.product_name, products.price, products.image, 
                   products.quantity as stock_qty, products.status as stock_status
            FROM cart 
            JOIN products ON cart.product_id = products.id 
            WHERE cart.customer_id = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->execute([$u_id]);
    $items = $stmt->fetchAll();

    $can_place_order = true;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Cart</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        .out-of-stock-bg { background-color: #fce4e4; }
        .qty-controls { display: flex; align-items: center; gap: 10px; }
        .stock-error { color: #dc3545; font-size: 0.85rem; margin-top: 5px; border: 1px solid #dc3545; padding: 5px; border-radius: 4px; background-color: #fff; }
    </style>
</head>
<body class="bg-light">
    <?php include "inc/navbar.php"; ?>

    <div class="container mt-5">
        <h3 class="mb-4"><i class="fa fa-shopping-cart text-primary"></i> Shopping Cart</h3>

        <?php if (isset($_GET['success'])) { ?>
            <div class="alert alert-success"><?php echo $_GET['success']; ?></div>
        <?php } ?>

        <div class="table-responsive shadow-sm bg-white p-3">
            <table class="table table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Image</th>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Subtotal</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $grand_total = 0;
                    $total_items = count($items);

                    if ($total_items > 0) {
                        
                        for ($i = 0; $i < $total_items; $i = $i + 1) { 
                            $item = $items[$i];
                            $subtotal = $item['price'] * $item['quantity'];
                            $grand_total = $grand_total + $subtotal;

                            $is_available = true;
                            $insufficient_stock = false;

                            // Check general status/stock existence
                            if ($item['stock_qty'] <= 0 || $item['stock_status'] != 'In Stock') {
                                $is_available = false;
                                $can_place_order = false; 
                            }

                            // Check if cart quantity exceeds available stock
                            if ($item['quantity'] > $item['stock_qty']) {
                                $insufficient_stock = true;
                                $can_place_order = false;
                            }
                    ?>
                    <tr class="<?php echo (!$is_available || $insufficient_stock) ? 'out-of-stock-bg' : ''; ?>">
                        <td>
                            <?php if (!empty($item['image'])) { ?>
                                <img src="../uploads/<?php echo $item['image']; ?>" width="50" class="rounded shadow-sm">
                            <?php } ?>
                        </td>
                        <td>
                            <strong><?php echo $item['product_name']; ?></strong>
                            
                            <?php if ($insufficient_stock && $is_available) { ?>
                                <div class="stock-error">
                                    <i class="fa fa-exclamation-triangle"></i> 
                                    Error: Only <?php echo $item['stock_qty']; ?> items left in stock.
                                </div>
                            <?php } ?>
                        </td>
                        <td>$<?php echo number_format($item['price'], 2); ?></td>
                        <td>
                            <div class="qty-controls">
                                <span class="fw-bold mx-1"><?php echo $item['quantity']; ?></span>

                                <?php if ($item['quantity'] > 1) { ?>
                                    <a href="req/reduce-quantity.php?cart_id=<?php echo $item['id']; ?>" 
                                       class="btn btn-sm btn-outline-danger"
                                       title="Reduce Quantity">
                                        <i class="fa fa-minus"></i>
                                    </a>
                                <?php } else { ?>
                                    <button class="btn btn-sm btn-outline-secondary" disabled>
                                        <i class="fa fa-minus"></i>
                                    </button>
                                <?php } ?>
                            </div>
                        </td>
                        <td class="fw-bold">$<?php echo number_format($subtotal, 2); ?></td>
                        <td>
                            <?php if ($is_available && !$insufficient_stock) { ?>
                                <span class="badge bg-success">Available</span>
                            <?php } else if ($insufficient_stock && $is_available) { ?>
                                <span class="badge bg-warning text-dark">Stock Low</span>
                            <?php } else { ?>
                                <span class="badge bg-danger">Out of Stock</span>
                            <?php } ?>
                        </td>
                        <td>
                            <a href="req/remove-from-cart.php?cart_id=<?php echo $item['id']; ?>" 
                               class="btn btn-danger btn-sm"
                               title="Remove Item">
                               <i class="fa fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php 
                        } 

                    } else { ?>
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <p class="text-muted mb-0">Your cart is empty.</p>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>

            <div class="d-flex justify-content-between align-items-center mt-4">
                <div class="d-flex gap-2">
                    <a href="index.php" class="btn btn-outline-primary">
                        <i class="fa fa-home"></i> Home
                    </a>
                    <a href="store.php" class="btn btn-outline-secondary">
                        <i class="fa fa-shopping-bag"></i> Store
                    </a>
                </div>

                <?php if ($grand_total > 0) { ?>
                <div class="text-end">
                    <?php if (!$can_place_order) { ?>
                        <div class="text-danger mb-2 small">
                            <i class="fa fa-warning"></i> Some items have stock issues.
                        </div>
                    <?php } ?>

                    <h4 class="mb-3">Total: <span class="text-success">$<?php echo number_format($grand_total, 2); ?></span></h4>
                    
                    <a href="checkout_review.php" 
                       class="btn btn-success btn-lg px-5 <?php echo !$can_place_order ? 'disabled' : ''; ?>">
                        Confirm Purchase <i class="fa fa-check"></i>
                    </a>
                </div>
                <?php } ?>
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