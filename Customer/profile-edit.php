<?php 
session_start();

if (isset($_SESSION['user_id']) && 
    isset($_SESSION['role'])    && 
    $_SESSION['role'] == 'Customer') {

    include "../DB_connection.php";
    
    $user_id = $_SESSION['user_id'];

    // Fetching current customer data
    $sql = "SELECT * FROM customer WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$user_id]);
    $customer = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Profile | Garage HQ</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .edit-container { max-width: 750px; margin-top: 50px; }
        .card { border: none; border-radius: 15px; }
        .section-title { font-size: 0.9rem; font-weight: bold; color: #6c757d; text-transform: uppercase; letter-spacing: 1px; }
        .form-control:focus { border-color: #0d6efd; box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15); }
        .btn-random { background-color: #e9ecef; border: 1px solid #ced4da; color: #495057; }
    </style>
</head>
<body>
    <?php include "inc/navbar.php"; ?>

    <div class="container edit-container mb-5">
        <div class="card shadow-sm p-4 bg-white">
            <div class="text-center mb-4">
                <i class="fa fa-user-circle-o fa-3x text-primary mb-2"></i>
                <h4 class="fw-bold">Account Settings</h4>
                <p class="text-muted small">Update your personal information and security</p>
            </div>

            <?php if (isset($_GET['error'])) { ?>
                <div class="alert alert-danger p-2 small">
                    <i class="fa fa-times-circle"></i> <?php echo $_GET['error']; ?>
                </div>
            <?php } ?>

            <?php if (isset($_GET['success'])) { ?>
                <div class="alert alert-success p-2 small">
                    <i class="fa fa-check-circle"></i> <?php echo $_GET['success']; ?>
                </div>
            <?php } ?>

            <form action="req/profile-edit.php" method="post">
                
                <p class="section-title border-bottom pb-1 mb-3">Personal Details</p>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold">First Name</label>
                        <input type="text" name="fname" class="form-control" value="<?php echo $customer['fname']; ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold">Last Name</label>
                        <input type="text" name="lname" class="form-control" value="<?php echo $customer['lname']; ?>" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold">Email Address</label>
                        <input type="email" name="email_address" class="form-control" value="<?php echo $customer['email_address']; ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold">Phone Number</label>
                        <input type="text" name="phone_number" class="form-control" value="<?php echo $customer['phone_number']; ?>">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Shipping Address</label>
                    <textarea name="address" class="form-control" rows="2"><?php echo $customer['address']; ?></textarea>
                </div>

                <p class="section-title border-bottom pb-1 mt-4 mb-3">Security Verification</p>

                <div class="mb-3">
                    <label class="form-label small fw-bold text-danger">Current Password (Required for any changes)</label>
                    <input type="password" name="old_password" class="form-control" placeholder="Enter current password" required>
                </div>

                <p class="section-title border-bottom pb-1 mt-4 mb-3">Change Password</p>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold">New Password</label>
                        <div class="input-group">
                            <input type="password" name="new_password" id="new_pass" class="form-control" placeholder="Leave blank to keep current">
                            <button class="btn btn-random" type="button" onclick="generatePassword()">
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
                    <button type="submit" class="btn btn-primary w-100 fw-bold shadow-sm">Save All Changes</button>
                    <a href="profile.php" class="btn btn-light w-100 mt-2 border">Back to Profile</a>
                </div>
            </form>
        </div>
    </div>

    <script>
    function generatePassword() {
        var chars = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*()";
        var passwordLength = 12;
        var password = "";
        
        // Full for loop as requested
        for (var i = 0; i < passwordLength; i = i + 1) {
            
            var randomNumber = Math.floor(Math.random() * chars.length);
            
            password = password + chars.substring(randomNumber, randomNumber + 1);
            
        }
        
        var passInput = document.getElementById("new_pass");
        
        // Put the generated password into the input field directly
        passInput.value = password;
        
        // Temporarily change type to text so the user can see it
        passInput.type = "text";
        
        // Hide it again (back to password dots) after 4 seconds
        setTimeout(function(){ 
            passInput.type = "password"; 
        }, 4000);
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