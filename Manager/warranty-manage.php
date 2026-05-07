<?php 
session_start();
if (isset($_SESSION['user_id']) && $_SESSION['role'] == 'Manager') {
    include "../DB_connection.php";

    // 1. Fetch Product Warranty Claims (Only where car_id is NULL)
    $sql_p = "SELECT wc.*, c.fname, c.lname, p.product_name 
              FROM warranty_claims wc
              JOIN customer c ON wc.customer_id = c.user_id
              JOIN orders o ON wc.order_id = o.id
              JOIN products p ON o.product_id = p.id
              WHERE o.car_id IS NULL
              ORDER BY wc.id DESC";
    $stmt_p = $conn->prepare($sql_p);
    $stmt_p->execute();
    $product_claims = $stmt_p->fetchAll();

    // 2. Fetch Car Warranty Claims (Only where car_id is NOT NULL)
    $sql_c = "SELECT wc.*, c.fname, c.lname, cars.model AS car_name, cars.brand 
              FROM warranty_claims wc
              JOIN customer c ON wc.customer_id = c.user_id
              JOIN orders o ON wc.order_id = o.id
              JOIN cars ON o.car_id = cars.id
              WHERE o.car_id IS NOT NULL
              ORDER BY wc.id DESC";
    $stmt_c = $conn->prepare($sql_c);
    $stmt_c->execute();
    $car_claims = $stmt_c->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Warranties - Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<body class="bg-light">
    <?php include "inc/navbar.php"; ?>
    <div class="container mt-5">
        <h3 class="mb-4">Warranty Claims Management</h3>
        
        <?php if (isset($_GET['success'])) { ?>
            <div class="alert alert-success"><?php echo $_GET['success']; ?></div>
        <?php } ?>

        <h4 class="mt-5 text-primary"><i class="fa fa-gears"></i> Product Warranties</h4>
        <div class="table-responsive shadow-sm bg-white p-3 rounded mb-5">
            <table class="table table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Customer</th>
                        <th>Product</th>
                        <th>Type</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if (count($product_claims) > 0) {
                        for ($i = 0; $i < count($product_claims); $i = $i + 1) { 
                            $c = $product_claims[$i];
                    ?>
                    <tr class="align-middle">
                        <td>#<?php echo $c['id']; ?></td>
                        <td><?php echo $c['fname'] . " " . $c['lname']; ?></td>
                        <td><?php echo $c['product_name']; ?></td>
                        <td><span class="badge bg-secondary"><?php echo $c['claim_type']; ?></span></td>
                        <td><small><?php echo $c['reason']; ?></small></td>
                        <td>
                            <?php 
                                $badge = "bg-info text-dark";
                                if ($c['status'] == 'Approved') $badge = "bg-success";
                                if ($c['status'] == 'Rejected') $badge = "bg-danger";
                            ?>
                            <span class="badge <?php echo $badge; ?>"><?php echo $c['status']; ?></span>
                        </td>
                        <td>
                            <?php if ($c['status'] == 'Pending') { ?>
                                <a href="req/warranty-status.php?id=<?php echo $c['id']; ?>&status=Approved" class="btn btn-sm btn-success">Approve</a>
                                <a href="req/warranty-status.php?id=<?php echo $c['id']; ?>&status=Rejected" class="btn btn-sm btn-danger">Reject</a>
                            <?php } else { ?>
                                <span class="text-muted small">Processed</span>
                            <?php } ?>
                        </td>
                    </tr>
                    <?php } } else { ?>
                    <tr><td colspan="7" class="text-center py-3">No product claims found.</td></tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>

        <h4 class="mt-5 text-info"><i class="fa fa-car"></i> Car Warranties</h4>
        <div class="table-responsive shadow-sm bg-white p-3 rounded mb-5">
            <table class="table table-hover">
                <thead class="table-info">
                    <tr>
                        <th>ID</th>
                        <th>Customer</th>
                        <th>Car Model</th>
                        <th>Type</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if (count($car_claims) > 0) {
                        for ($j = 0; $j < count($car_claims); $j = $j + 1) { 
                            $cc = $car_claims[$j];
                    ?>
                    <tr class="align-middle">
                        <td>#<?php echo $cc['id']; ?></td>
                        <td><?php echo $cc['fname'] . " " . $cc['lname']; ?></td>
                        <td><?php echo $cc['brand'] . " " . $cc['car_name']; ?></td>
                        <td><span class="badge bg-secondary"><?php echo $cc['claim_type']; ?></span></td>
                        <td><small><?php echo $cc['reason']; ?></small></td>
                        <td>
                            <?php 
                                $badge_c = "bg-info text-dark";
                                if ($cc['status'] == 'Approved') $badge_c = "bg-success";
                                if ($cc['status'] == 'Rejected') $badge_c = "bg-danger";
                            ?>
                            <span class="badge <?php echo $badge_c; ?>"><?php echo $cc['status']; ?></span>
                        </td>
                        <td>
                            <?php if ($cc['status'] == 'Pending') { ?>
                                <a href="req/warranty-status.php?id=<?php echo $cc['id']; ?>&status=Approved" class="btn btn-sm btn-success">Approve</a>
                                <a href="req/warranty-status.php?id=<?php echo $cc['id']; ?>&status=Rejected" class="btn btn-sm btn-danger">Reject</a>
                            <?php } else { ?>
                                <span class="text-muted small">Processed</span>
                            <?php } ?>
                        </td>
                    </tr>
                    <?php } } else { ?>
                    <tr><td colspan="7" class="text-center py-3">No car claims found.</td></tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
<?php 
} else {
    header("Location: ../login.php");
    exit;
} ?>