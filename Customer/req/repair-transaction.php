<?php 
session_start();

if (isset($_SESSION['user_id']) && 
    isset($_SESSION['role'])    && 
    $_SESSION['role'] == 'Customer') {

    if (isset($_GET['id'])) {
        
        include "../../DB_connection.php";
        $repair_id = $_GET['id'];
        $u_id = $_SESSION['user_id'];

        // Verify the repair exists and belongs to the user
        $sql = "SELECT * FROM car_repairs WHERE repair_id = ? AND customer_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$repair_id, $u_id]);
        $repair = $stmt->fetch();

        if ($repair) {
            // Update the status to 'Repairing'
            $sql_update = "UPDATE car_repairs SET status = 'Repairing' WHERE repair_id = ?";
            $stmt_update = $conn->prepare($sql_update);
            $res = $stmt_update->execute([$repair_id]);

            if ($res) {
                // Redirect to repair-view.php with success message
                $sm = "Payment successful! Your car status is now 'Repairing'.";
                header("Location: ../repair-view.php?id=$repair_id&success=$sm");
                exit;
            } else {
                $em = "An error occurred during status update.";
                header("Location: ../repair-view.php?id=$repair_id&error=$em");
                exit;
            }
        } else {
            header("Location: ../repair-dashboard.php");
            exit;
        }
    } else {
        header("Location: ../repair-dashboard.php");
        exit;
    }
} else {
    header("Location: ../../login.php");
    exit;
}