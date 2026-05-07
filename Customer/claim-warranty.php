<?php 
session_start();
if (isset($_SESSION['user_id']) && $_SESSION['role'] == 'Customer') {
    include "../DB_connection.php";

    if (isset($_GET['order_id']) && (isset($_GET['product_id']) || isset($_GET['car_id']))) {
        $order_id = $_GET['order_id'];
        
        $item_name = "";
        $has_regular = 0;
        $has_replacement = 0;

        // Check if we are claiming for a CAR
        if (isset($_GET['car_id'])) {
            $car_id = $_GET['car_id'];
            $sql = "SELECT brand, model, regular_warranty, replacement_warranty FROM cars WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$car_id]);
            $item = $stmt->fetch();
            
            if ($item) {
                $item_name = $item['brand'] . " " . $item['model'];
                $has_regular = $item['regular_warranty'];
                $has_replacement = $item['replacement_warranty'];
            }
        } 
        // Otherwise, check if we are claiming for a PRODUCT
        else if (isset($_GET['product_id'])) {
            $product_id = $_GET['product_id'];
            $sql = "SELECT product_name, regular_warranty, replacement_warranty FROM products WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$product_id]);
            $item = $stmt->fetch();
            
            if ($item) {
                $item_name = $item['product_name'];
                $has_regular = $item['regular_warranty'];
                $has_replacement = $item['replacement_warranty'];
            }
        }

        if ($item_name == "") {
            header("Location: history.php?error=Item not found");
            exit;
        }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Claim Warranty - Rev Nation</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<body class="bg-light">
    <?php include "inc/navbar.php"; ?>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-warning text-dark fw-bold">
                        <i class="fa fa-shield"></i> Warranty Claim Form
                    </div>
                    <div class="card-body">
                        <p>Claiming for: <strong><?php echo $item_name; ?></strong></p>
                        <hr>

                        <form action="req/submit-warranty.php" method="post">
                            <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                            
                            <div class="mb-3">
                                <label class="form-label">Warranty Type</label>
                                <select name="claim_type" class="form-select" required>
                                    <option value="" selected disabled>-- Choose Type --</option>
                                    
                                    <?php if ($has_regular == 1) { ?>
                                        <option value="Regular">Regular (Repair)</option>
                                    <?php } ?>

                                    <?php if ($has_replacement == 1) { ?>
                                        <option value="Replacement">Replacement</option>
                                    <?php } ?>

                                    <?php if ($has_regular == 0 && $has_replacement == 0) { ?>
                                        <option value="" disabled>No warranty available for this item</option>
                                    <?php } ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Reason for Claim</label>
                                <textarea name="reason" class="form-control" rows="5" placeholder="Describe the issue..." required></textarea>
                            </div>

                            <div class="d-grid gap-2">
                                <?php if ($has_regular == 1 || $has_replacement == 1) { ?>
                                    <button type="submit" class="btn btn-dark">Submit Claim</button>
                                <?php } else { ?>
                                    <button type="button" class="btn btn-secondary" disabled>Claim Unavailable</button>
                                <?php } ?>
                                <a href="history.php" class="btn btn-light border">Cancel</a>
                            </div>
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
        header("Location: history.php");
        exit;
    }
} else {
    header("Location: ../login.php");
    exit;
} 
?>