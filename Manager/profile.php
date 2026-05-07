<?php 
session_start();

if (isset($_SESSION['user_id']) && 
    isset($_SESSION['role'])    && 
    $_SESSION['role'] == 'Manager') {

    include "../DB_connection.php";
    
    $user_id = $_SESSION['user_id'];

    // Joining manager table with users table to get the username
    $sql = "SELECT users.username, manager.* FROM manager 
            INNER JOIN users ON manager.user_id = users.uid 
            WHERE manager.user_id = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->execute([$user_id]);
    $manager = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manager - Profile</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<body class="bg-light">
    <?php include "inc/navbar.php"; ?>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                
                <div class="card shadow mb-4 border-0">
                    <div class="card-header bg-dark text-white text-center py-3">
                        <h4 class="mb-0">Manager Profile</h4>
                    </div>
                    
                    <div class="card-body text-center">
                        <i class="fa fa-user-circle-o fa-5x text-secondary mb-3"></i>
                        <h5 class="mt-2">@<?php echo $manager['username']; ?></h5>
                        <p class="badge bg-primary">System Administrator</p>
                        <hr>
                        
                        <div class="row text-start ps-4">
                            <div class="col-md-6 mb-3">
                                <p class="mb-1 text-muted small text-uppercase">Full Name</p>
                                <h6><?php echo $manager['fname'] . " " . $manager['lname']; ?></h6>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <p class="mb-1 text-muted small text-uppercase">Email Address</p>
                                <h6><?php echo $manager['email_address']; ?></h6>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <p class="mb-1 text-muted small text-uppercase">Role</p>
                                <h6>Store Manager</h6>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <p class="mb-1 text-muted small text-uppercase">Phone</p>
                                <h6><?php echo $manager['phone_number']; ?></h6>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-footer bg-white text-end">
                        <a href="profile-edit.php" class="btn btn-primary btn-sm">
                            <i class="fa fa-pencil-square-o"></i> Edit Profile & Password
                        </a>
                    </div>
                </div>

                <div class="card shadow mb-5 border-start border-primary border-4">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">Administrative Access</h5>
                            <small class="text-muted">You have full permission to manage inventory, sales, and employees.</small>
                        </div>
                        <i class="fa fa-unlock-alt fa-2x text-light"></i>
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