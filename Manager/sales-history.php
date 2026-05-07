<?php 
session_start();

// Access check: Allow both Manager and Employee roles
if (isset($_SESSION['user_id']) && 
    isset($_SESSION['role'])    && 
    ($_SESSION['role'] == 'Manager' || $_SESSION['role'] == 'Employee')) {
    
    include "../DB_connection.php";

    $search = "";
    
    // Base SQL: Join products on product_id and cars on car_id
    $base_sql = "SELECT orders.*, users.username, 
                        products.product_name AS p_name,
                        cars.brand AS car_brand, cars.model AS car_model
                 FROM orders 
                 LEFT JOIN users ON orders.customer_id = users.uid 
                 LEFT JOIN products ON orders.product_id = products.id
                 LEFT JOIN cars ON orders.car_id = cars.id";

    if (isset($_GET['search']) && !empty($_GET['search'])) {
        
        $search = $_GET['search'];
        
        $sql = $base_sql . " WHERE orders.product_name LIKE ? 
                             OR products.product_name LIKE ? 
                             OR users.username LIKE ? 
                             OR cars.brand LIKE ? 
                             OR cars.model LIKE ?
                             ORDER BY orders.id DESC";
                             
        $stmt = $conn->prepare($sql);
        $stmt->execute(["%$search%", "%$search%", "%$search%", "%$search%", "%$search%"]);
        
    } else {
        
        $sql = $base_sql . " ORDER BY orders.id DESC";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        
    }
    
    $all_orders = $stmt->fetchAll();

    $total_revenue = 0;
    $order_count = count($all_orders);
    
    // Explicit for loop to calculate total revenue
    for ($i = 0; $i < $order_count; $i = $i + 1) {
        
        $total_revenue = $total_revenue + $all_orders[$i]['total_price'];
        
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $_SESSION['role']; ?> - Sales History</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<body class="bg-light">
    <?php include "inc/navbar.php"; ?>

    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3>Sales & Revenue Report</h3>
            
            <form action="sales-history.php" method="get" class="d-flex w-50">
                <input type="text" 
                       name="search" 
                       class="form-control me-2" 
                       placeholder="Search customer, car or part..." 
                       value="<?php echo $search; ?>">
                <button type="submit" class="btn btn-primary">Search</button>
            </form>
        </div>

        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card bg-success text-white shadow border-0">
                    <div class="card-body text-center">
                        <h5 class="text-uppercase small">Total Revenue</h5>
                        <h2 class="display-6 fw-bold">$<?php echo number_format($total_revenue, 2); ?></h2>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card bg-primary text-white shadow border-0">
                    <div class="card-body text-center">
                        <h5 class="text-uppercase small">Orders Processed</h5>
                        <h2 class="display-6 fw-bold"><?php echo $order_count; ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="table-responsive shadow-sm bg-white p-3 rounded">
            <table class="table table-hover text-center align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Item (Car/Part)</th>
                        <th>Qty</th>
                        <th>Total Paid</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    for ($j = 0; $j < $order_count; $j = $j + 1) { 
                        
                        $order = $all_orders[$j];
                        
                        // Handle Customer Name
                        $customer_display = '<span class="text-danger"><i>Deleted Account</i></span>';
                        if (!empty($order['username'])) {
                            $customer_display = $order['username'];
                        }

                        // Handle Product Display
                        $product_display = "Deleted Item";

                        if (!empty($order['car_id']) && !empty($order['car_model'])) {
                            
                            $product_display = $order['car_brand'] . " " . $order['car_model'] . " (Car)";
                            
                        } else if (!empty($order['product_id']) && !empty($order['p_name'])) {
                            
                            $product_display = $order['p_name'];
                            
                        } else if (!empty($order['product_name'])) {
                            
                            $product_display = $order['product_name'];
                            
                        }
                    ?>
                    <tr>
                        <td>#<?php echo $order['id']; ?></td>
                        <td><?php echo $customer_display; ?></td>
                        <td><strong><?php echo $product_display; ?></strong></td>
                        <td><?php echo $order['quantity']; ?></td>
                        <td class="text-success fw-bold">$<?php echo number_format($order['total_price'], 2); ?></td>
                        <td><?php echo $order['date_ordered']; ?></td>
                    </tr>
                    <?php 
                    } 
                    ?>

                    <?php if ($order_count == 0) { ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">No sales records found.</td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
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