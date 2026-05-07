<?php 
session_start();

// Ensure user is logged in and has the Employee role
if (isset($_SESSION['user_id']) && 
    isset($_SESSION['role'])    && 
    $_SESSION['role'] == 'Employee') {

    include "../DB_connection.php";
    $user_id = $_SESSION['user_id'];

    // Fetch employee specific details including salary
    $sql = "SELECT * FROM employees WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$user_id]);
    $employee = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile | Staff Portal</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .edit-container { max-width: 750px; margin-top: 50px; }
        .card { border: none; border-radius: 15px; }
        .section-title { font-size: 0.9rem; font-weight: bold; color: #6c757d; text-transform: uppercase; letter-spacing: 1px; }
        .form-control:focus { border-color: #198754; box-shadow: 0 0 0 0.25rem rgba(25, 135, 84, 0.15); }
        .btn-random { background-color: #e9ecef; border: 1px solid #ced4da; color: #495057; }
        .btn-random:hover { background-color: #dee2e6; }
        .salary-badge { background-color: #f0fdf4; color: #198754; border: 1px solid #d1fae5; padding: 15px; border-radius: 10px; }
    </style>
</head>
<body>
    <?php include "inc/navbar.php"; ?>

    <div class="container edit-container mb-5">
        <div class="card shadow-sm p-4 bg-white">
            <div class="text-center mb-4">
                <i class="fa fa-id-card fa-3x text-success mb-2"></i>
                <h4 class="fw-bold">Employee Profile</h4>
                <p class="text-muted small">Manage your personal information and security</p>
            </div>

            <?php if (isset($_GET['error'])) { ?>
                <div class="alert alert-danger p-2 small"><?php echo $_GET['error']; ?></div>
            <?php } ?>

            <?php if (isset($_GET['success'])) { ?>
                <div class="alert alert-success p-2 small"><?php echo $_GET['success']; ?></div>
            <?php } ?>

            <div class="salary-badge mb-4 d-flex justify-content-between align-items-center">
                <div>
                    <span class="section-title d-block mb-1">Current Salary</span>
                    <h3 class="mb-0 fw-bold">$<?php echo number_format($employee['salary'], 2); ?></h3>
                </div>
                <i class="fa fa-money fa-2x opacity-25"></i>
            </div>

            <form action="req/profile-edit.php" method="post">
                
                <p class="section-title border-bottom pb-1 mb-3">Personal Details</p>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold">First Name</label>
                        <input type="text" name="fname" class="form-control" value="<?php echo $employee['fname']; ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold">Last Name</label>
                        <input type="text" name="lname" class="form-control" value="<?php echo $employee['lname']; ?>" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold">Email Address</label>
                        <input type="email" name="email_address" class="form-control" value="<?php echo $employee['email_address']; ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold">Phone Number</label>
                        <input type="text" name="phone_number" class="form-control" value="<?php echo $employee['phone_number']; ?>">
                    </div>
                </div>

                <p class="section-title border-bottom pb-1 mt-4 mb-3">Security Verification</p>

                <div class="mb-3">
                    <label class="form-label small fw-bold text-danger">Current Password (Required to save changes)</label>
                    <input type="password" name="old_password" class="form-control" placeholder="Verify identity" required>
                </div>

                <p class="section-title border-bottom pb-1 mt-4 mb-3">Change Password</p>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold">New Password</label>
                        <div class="input-group">
                            <input type="password" name="new_password" id="new_pass" class="form-control" placeholder="Leave blank to keep current">
                            <button class="btn btn-random" type="button" onclick="generatePassword()" title="Generate Random Password">
                                <i class="fa fa-refresh"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" placeholder="Repeat new password">
                    </div>
                </div>

                <div class="mt-4 pt-2">
                    <button type="submit" class="btn btn-success w-100 fw-bold shadow-sm">Update Profile</button>
                    <a href="index.php" class="btn btn-light w-100 mt-2 border">Back to Dashboard</a>
                </div>
            </form>
        </div>
    </div>

    <script>
    function generatePassword() {
        var chars = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*()";
        var passwordLength = 12;
        var password = "";
        
        // Loop to construct random password
        for (var i = 0; i < passwordLength; i = i + 1) {
            var randomNumber = Math.floor(Math.random() * chars.length);
            password = password + chars.substring(randomNumber, randomNumber + 1);
        }
        
        document.getElementById("new_pass").value = password;
        
        var passInput = document.getElementById("new_pass");
        passInput.type = "text";
        
        // Hide password again after 3 seconds
        setTimeout(function(){ 
            passInput.type = "password"; 
        }, 3000);
        
        alert("Generated Password: " + password + "\n\nPassword will be visible in the field for 3 seconds.");
    }
    </script>
</body>
</html>
<?php 
} else {
    header("Location: ../login.php");
    exit;
} 
?>