<?php 
session_start();

if (isset($_SESSION['user_id']) && 
    isset($_SESSION['role'])    && 
    $_SESSION['role'] == 'Manager') {
    
    include "../DB_connection.php";

    // Fetch the single row of settings from the 'setting' table
    $sql = "SELECT * FROM setting WHERE id = 1";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $setting = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manager - Garage Settings</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php include "inc/navbar.php"; ?>

    <div class="container mt-5" style="max-width: 700px;">
        <form action="req/setting-edit.php" method="post" class="shadow p-3 mt-5 form-w">
            <h3>Edit Garage Information</h3><hr>

            <?php if (isset($_GET['error'])) { ?>
                <div class="alert alert-danger"><?=$_GET['error']?></div>
            <?php } ?>
            <?php if (isset($_GET['success'])) { ?>
                <div class="alert alert-success"><?=$_GET['success']?></div>
            <?php } ?>

            <div class="mb-3">
                <label class="form-label">Garage Name</label>
                <input type="text" name="garage_name" class="form-control" value="<?=$setting['garage_name']?>">
            </div>

            <div class="mb-3">
                <label class="form-label">Slogan</label>
                <input type="text" name="slogan" class="form-control" value="<?=$setting['slogan']?>">
            </div>

            <div class="mb-3">
                <label class="form-label">About Us</label>
                <textarea name="about" class="form-control" rows="4"><?=$setting['about']?></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">Current Year</label>
                <input type="number" name="current_year" class="form-control" value="<?=$setting['current_year']?>">
            </div>

            <button type="submit" class="btn btn-primary">Save Changes</button>
        </form>
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