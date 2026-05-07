<?php 
session_start();

if (isset($_SESSION['user_id']) && 
    isset($_SESSION['role'])    && 
    $_SESSION['role'] == 'Customer') {
    
    if (isset($_GET['id'])) {

        include "../DB_connection.php";
        $repair_id = $_GET['id'];
        $u_id = $_SESSION['user_id'];

        // Fetch repair details to show the customer what they are paying for
        $sql = "SELECT * FROM car_repairs WHERE repair_id = ? AND customer_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$repair_id, $u_id]);
        $repair = $stmt->fetch();

        if ($repair) {
            $labor = $repair['labor_fee'];
            $parts = $repair['parts_total'];
            $total_bill = $labor + $parts;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Complete Payment - Rev Nation</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        body { background-color: #f4f7f6; color: #212529; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .payment-card { border: none; border-radius: 15px; background: #fff; overflow: hidden; }
        .bill-header { background: #212529; color: #fff; padding: 20px; }
        .item-row { border-bottom: 1px solid #eee; padding: 10px 0; }
    </style>
</head>
<body>

    <?php include "inc/navbar.php"; ?>

    <div class="container mt-5 mb-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                
                <?php if (isset($_GET['success'])) { ?>
                    <div class="card payment-card shadow-lg text-center p-5">
                        <div class="mb-3">
                            <i class="fa fa-check-circle text-success" style="font-size: 60px;"></i>
                        </div>
                        <h2 class="fw-bold">Payment Successful!</h2>
                        <p class="text-muted">Your payment for Repair #<?php echo $repair_id; ?> has been processed.</p>
                        <hr>
                        <a href="repair-dashboard.php" class="btn btn-dark px-4 rounded-pill">Back to Dashboard</a>
                    </div>
                <?php } else { ?>
                    
                    <div class="card payment-card shadow-lg">
                        <div class="bill-header text-center">
                            <h4 class="mb-0 fw-bold">Payment Summary</h4>
                            <small class="opacity-75">Repair ID: #<?php echo $repair_id; ?></small>
                        </div>
                        
                        <div class="p-4">
                            <div class="text-center mb-4">
                                <h6 class="text-muted mb-1"><?php echo $repair['brand'] . " " . $repair['model']; ?></h6>
                                <p class="small text-muted">Service Year: <?php echo $repair['year']; ?></p>
                            </div>

                            <div class="item-row d-flex justify-content-between">
                                <span>Labor Charges</span>
                                <span class="fw-bold">$<?php echo number_format($labor, 2); ?></span>
                            </div>

                            <div class="item-row d-flex justify-content-between">
                                <span>Parts & Materials</span>
                                <span class="fw-bold">$<?php echo number_format($parts, 2); ?></span>
                            </div>

                            <div class="d-flex justify-content-between mt-4 p-3 bg-light rounded">
                                <span class="fw-bold">Total Amount Due</span>
                                <h4 class="fw-extrabold text-success mb-0">$<?php echo number_format($total_bill, 2); ?></h4>
                            </div>

                            <div class="mt-4">
                                <div class="alert alert-info py-2" style="font-size: 12px;">
                                    <i class="fa fa-info-circle me-2"></i> By clicking pay, you agree to the service terms.
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <a href="req/repair-transaction.php?id=<?php echo $repair_id; ?>" 
                                       class="btn btn-dark btn-lg fw-bold">
                                       <i class="fa fa-credit-card me-2"></i> Confirm and Pay
                                    </a>
                                    
                                    <a href="repair-dashboard.php" class="btn btn-link text-muted small text-decoration-none">Cancel Transaction</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>

            </div>
        </div>
    </div>

</body>
</html>

<?php 
        } else {
            header("Location: repair-dashboard.php");
            exit;
        }
    } else {
        header("Location: repair-dashboard.php");
        exit;
    }
} else {
    header("Location: ../login.php");
    exit;
} 
?>