<?php 
session_start();

if (isset($_SESSION['user_id']) && 
    isset($_SESSION['role'])    && 
    $_SESSION['role'] == 'Employee') {

    include "../DB_connection.php";
    
    $user_id = $_SESSION['user_id'];

    // Joining employees table with users table to get the username
    $sql = "SELECT users.username, employees.* FROM employees 
            INNER JOIN users ON employees.user_id = users.uid 
            WHERE employees.user_id = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->execute([$user_id]);
    $employee = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Employee - Profile</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<body class="bg-light">
    <?php include "inc/navbar.php"; ?>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                
                <div class="card shadow mb-4 border-0">
                    <div class="card-header bg-success text-white text-center py-3">
                        <h4 class="mb-0">Staff Profile</h4>
                    </div>
                    <div class="card-body text-center">
                        <i class="fa fa-user-circle fa-5x text-secondary mb-3"></i>
                        <h5 class="mt-2">@<?php echo $employee['username']; ?></h5>
                        <p class="badge bg-success">Garage Staff</p>
                        <hr>
                        
                        <div class="row text-start ps-4">
                            <div class="col-md-6 mb-3">
                                <p class="mb-1 text-muted small uppercase">Full Name</p>
                                <h6><?php echo $employee['fname'] . " " . $employee['lname']; ?></h6>
                            </div>
                            <div class="col-md-6 mb-3">
                                <p class="mb-1 text-muted small uppercase">Email Address</p>
                                <h6><?php echo $employee['email_address']; ?></h6>
                            </div>
                            <div class="col-md-6 mb-3">
                                <p class="mb-1 text-muted small uppercase">Position</p>
                                <h6>Employee</h6>
                            </div>
                            <div class="col-md-6 mb-3">
                                <p class="mb-1 text-muted small uppercase">Monthly Salary</p>
                                <h6 class="text-success fw-bold">$<?php echo number_format($employee['salary'], 2); ?></h6>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-white text-end">
                        <a href="profile-edit.php" class="btn btn-success btn-sm">Edit Profile Settings</a>
                    </div>
                </div>

                <div class="card shadow mb-5 border-start border-success border-4">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">Privacy & Security</h5>
                            <small class="text-muted">Update your login credentials and password.</small>
                        </div>
                        <i class="fa fa-shield fa-2x text-light"></i>
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