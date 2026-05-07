<?php 
session_start();

if (isset($_SESSION['user_id']) && 
    isset($_SESSION['role'])    && 
    $_SESSION['role'] == 'Customer') {
    
    include "../DB_connection.php";
    $user_id = $_SESSION['user_id'];

    // Fetch Customer Info to show on the page
    $sql = "SELECT * FROM customer WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$user_id]);
    $customer = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customer - Account Settings</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php include "inc/navbar.php"; ?>

    <div class="container mt-5" style="max-width: 600px;">
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h3>Account Information</h3>
                <hr>
                <p><strong>Name:</strong> <?php echo $customer['fname'] . " " . $customer['lname']; ?></p>
                <p><strong>Email:</strong> <?php echo $customer['email_address']; ?></p>
            </div>
        </div>

        <div class="card shadow-sm border-danger">
            <div class="card-header bg-danger text-white">
                <h4 class="mb-0">Danger Zone</h4>
            </div>
            <div class="card-body">
                <p class="text-muted">Once you delete your account, there is no going back. Please be certain.</p>
                
                <form action="req/account-delete.php" method="post">
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="confirmDelete" required>
                        <label class="form-check-label" for="confirmDelete">
                            I understand that my account and order history will be permanently removed.
                        </label>
                    </div>
                    
                    <button type="submit" class="btn btn-danger" 
                            onclick="return confirm('FINAL WARNING: Are you absolutely sure?')">
                        Delete My Account
                    </button>
                </form>
            </div>
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