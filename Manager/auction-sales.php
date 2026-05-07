<?php 
session_start();

if (isset($_SESSION['user_id']) && 
    isset($_SESSION['role'])    && 
    $_SESSION['role'] == 'Manager') {
    
    include "../DB_connection.php";

    // --- 1. Fetch Auction Sales Data ---
    // Changed JOIN to LEFT JOIN for 'cars' so the sale shows even if the car is deleted.
    $sql = "SELECT auction_sales.*, 
                   cars.brand, cars.model, cars.year, cars.image,
                   customer.fname, customer.lname, customer.email_address
            FROM auction_sales
            LEFT JOIN cars ON auction_sales.car_id = cars.id
            JOIN customer ON auction_sales.customer_id = customer.user_id
            ORDER BY auction_sales.sale_date DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $sales = $stmt->fetchAll();
    $sales_count = count($sales);


    // --- 2. Calculate Total Revenue ---
    $total_revenue = 0;
    for ($i = 0; $i < $sales_count; $i = $i + 1) {
        $total_revenue = $total_revenue + $sales[$i]['sale_price'];
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Auction Sales Report - Rev Nation</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        .stats-card { background: #fff; border-radius: 12px; border-left: 5px solid #198754; }
        .table-card { background: #fff; border-radius: 15px; overflow: hidden; }
        .car-thumb { width: 60px; height: 40px; object-fit: cover; border-radius: 4px; }
        .deleted-thumb { width: 60px; height: 40px; background: #e9ecef; display: inline-flex; align-items: center; justify-content: center; border-radius: 4px; color: #6c757d; }
    </style>
</head>
<body class="bg-light">
    <?php include "inc/navbar.php"; ?>

    <div class="container mt-4">
        
        <div class="row mb-4 align-items-center">
            <div class="col-md-7">
                <h2 class="fw-bold"><i class="fa fa-bar-chart me-2 text-success"></i>Auction Sales</h2>
                <p class="text-muted">History of all finalized auction transactions.</p>
            </div>
            <div class="col-md-5">
                <div class="stats-card p-3 shadow-sm d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-uppercase small fw-bold text-muted">Total Revenue Generated</span>
                        <h3 class="fw-bold mb-0 text-success">$<?php echo number_format($total_revenue, 2); ?></h3>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-success rounded-pill px-3"><?php echo $sales_count; ?> Sold</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="table-card shadow-sm">
            <table class="table table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th class="ps-4">Sale Date</th>
                        <th>Vehicle Details</th>
                        <th>Buyer Information</th>
                        <th class="text-end pe-4">Final Price</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($sales_count > 0) { ?>
                        
                        <?php for ($i = 0; $i < $sales_count; $i = $i + 1) { 
                            $sale = $sales[$i];
                            $is_deleted = empty($sale['model']); // Check if car record is missing
                        ?>
                            <tr class="align-middle">
                                <td class="ps-4">
                                    <div class="fw-bold"><?php echo date("F j, Y", strtotime($sale['sale_date'])); ?></div>
                                    <small class="text-muted"><?php echo date("g:i A", strtotime($sale['sale_date'])); ?></small>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php if ($is_deleted): ?>
                                            <div class="deleted-thumb me-3"><i class="fa fa-trash-o"></i></div>
                                            <div>
                                                <div class="fw-bold text-danger">Vehicle Information Missing</div>
                                                <small class="text-muted">the car has been removed from the showroom | ID: #<?php echo $sale['car_id']; ?></small>
                                            </div>
                                        <?php else: ?>
                                            <img src="../uploads/<?php echo $sale['image']; ?>" class="car-thumb me-3" onerror="this.src='https://via.placeholder.com/300x180?text=No+Image'">
                                            <div>
                                                <div class="fw-bold"><?php echo $sale['brand'] . " " . $sale['model']; ?></div>
                                                <small class="text-muted">Year: <?php echo $sale['year']; ?> | ID: #<?php echo $sale['car_id']; ?></small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-bold"><?php echo $sale['fname'] . " " . $sale['lname']; ?></div>
                                    <small class="text-muted"><?php echo $sale['email_address']; ?></small>
                                </td>
                                <td class="text-end pe-4">
                                    <span class="fs-5 fw-bold text-success">$<?php echo number_format($sale['sale_price'], 2); ?></span>
                                </td>
                            </tr>
                        <?php } ?>

                    <?php } else { ?>
                        <tr>
                            <td colspan="4" class="text-center py-5 text-muted">
                                <i class="fa fa-folder-open-o fa-3x mb-3"></i>
                                <p>No auction sales have been recorded yet.</p>
                            </td>
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