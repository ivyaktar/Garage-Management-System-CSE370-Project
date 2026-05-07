<?php 
session_start();

if (isset($_SESSION['user_id']) && 
    isset($_SESSION['role'])    && 
    $_SESSION['role'] == 'Customer') {

    include "../DB_connection.php";
    
    $user_id = $_SESSION['user_id'];

    // Joining tables using 'uid' as required by your database
    $sql = "SELECT users.username, customer.* FROM customer 
            INNER JOIN users ON customer.user_id = users.uid 
            WHERE customer.user_id = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->execute([$user_id]);
    $customer = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customer - Profile</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<body class="bg-light">
    <?php include "inc/navbar.php"; ?>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                
                <div class="card shadow mb-4 border-0">
                    <div class="card-header bg-dark text-white text-center py-3">
                        <h4 class="mb-0">My Profile</h4>
                    </div>
                    <div class="card-body text-center">
                        <i class="fa fa-user-circle fa-5x text-secondary mb-3"></i>
                        <h5 class="mt-2">@<?php echo $customer['username']; ?></h5>
                        <p class="badge bg-info text-dark">Valued Customer</p>
                        <hr>
                        <div class="row text-start ps-4">
                            <div class="col-md-6 mb-2">
                                <p><strong>Full Name:</strong> <?php echo $customer['fname'] . " " . $customer['lname']; ?></p>
                                <p><strong>Email:</strong> <?php echo $customer['email_address']; ?></p>
                            </div>
                            <div class="col-md-6 mb-2">
                                <p><strong>Phone:</strong> <?php echo $customer['phone_number']; ?></p>
                                <p><strong>Address:</strong> <?php echo $customer['address']; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-white text-end">
                        <a href="profile-edit.php" class="btn btn-primary btn-sm">
                            <i class="fa fa-edit"></i> Edit Profile & Security
                        </a>
                    </div>
                </div>

                <div class="card shadow mb-4 border-0">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0 px-2">My Activity</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-6 mb-3">
                                <div class="border p-3 rounded bg-white">
                                    <i class="fa fa-car fa-2x mb-2 text-primary"></i>
                                    <h6>Car Builder</h6>
                                    <p class="small text-muted">View your custom configurations.</p>
                                    <a href="saved-builds.php" class="btn btn-outline-primary btn-sm">View Saved Builds</a>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="border p-3 rounded bg-white">
                                    <i class="fa fa-history fa-2x mb-2 text-success"></i>
                                    <h6>Order History</h6>
                                    <p class="small text-muted">Track your purchases and invoices.</p>
                                    <a href="history.php" class="btn btn-outline-success btn-sm">View History</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow border-danger mb-5">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="text-danger mb-0">Close Account</h5>
                            <small class="text-muted">Once deleted, your data cannot be recovered.</small>
                        </div>
                        <form action="req/account-deletion.php" method="post">
                            <button type="submit" class="btn btn-outline-danger btn-sm"
                                    onclick="return confirm('Are you absolutely sure? This will permanently delete your account.')">
                                <i class="fa fa-trash"></i> Delete Account
                            </button>
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