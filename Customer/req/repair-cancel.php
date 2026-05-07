<?php 
session_start();

if (isset($_SESSION['user_id']) && 
    isset($_SESSION['role'])    && 
    $_SESSION['role'] == 'Customer') {

    include "../../DB_connection.php";

    if (isset($_GET['id'])) {
        
        $repair_id = $_GET['id'];
        $customer_id = $_SESSION['user_id'];

        // Verify the repair exists and belongs to this customer
        $sql = "SELECT * FROM car_repairs WHERE repair_id = ? AND customer_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$repair_id, $customer_id]);
        $repair = $stmt->fetch();

        if ($repair) {
            
            $current_status = $repair['status'];

            // Only allow deletion if status is Pending
            if ($current_status == 'Pending') {
                
                // Delete the record completely
                $sql_delete = "DELETE FROM car_repairs WHERE repair_id = ?";
                $stmt_delete = $conn->prepare($sql_delete);
                $result = $stmt_delete->execute([$repair_id]);

                if ($result) {
                    $sm = "Request deleted successfully!";
                    header("Location: ../repair-dashboard.php?success=$sm");
                    exit;
                } else {
                    $em = "Unknown error occurred";
                    header("Location: ../repair-dashboard.php?error=$em");
                    exit;
                }

            } else {
                $em = "In-progress repairs cannot be deleted";
                header("Location: ../repair-dashboard.php?error=$em");
                exit;
            }

        } else {
            // Repair not found or doesn't belong to user
            header("Location: ../repair-dashboard.php");
            exit;
        }

    } else {
        // ID not set
        header("Location: ../repair-dashboard.php");
        exit;
    }

} else {
    // Unauthorized access
    header("Location: ../../login.php");
    exit;
}
?>