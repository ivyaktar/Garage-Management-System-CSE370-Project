<?php 
session_start();

if (isset($_SESSION['user_id']) && $_SESSION['role'] == 'Customer') {

    if (isset($_POST['order_id']) && 
        isset($_POST['claim_type']) && 
        isset($_POST['reason'])) {

        include "../../DB_connection.php";

        $order_id    = $_POST['order_id'];
        $claim_type  = $_POST['claim_type'];
        $reason      = $_POST['reason'];
        $customer_id = $_SESSION['user_id'];

        if (empty($reason)) {
            header("Location: ../claim-warranty.php?error=Reason is required&order_id=$order_id");
            exit;
        }

        // Check if a claim already exists for this order to prevent duplicates
        $sql_check = "SELECT id FROM warranty_claims WHERE order_id = ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->execute([$order_id]);
        $existing_claim = $stmt_check->fetch();

        if ($existing_claim) {
            header("Location: ../history.php?error=A claim has already been submitted for this order.");
            exit;
        }

        // Insert into warranty_claims with status set to 'Pending'
        $sql = "INSERT INTO warranty_claims (order_id, customer_id, claim_type, reason, status) 
                VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $status = "Pending";
        $res  = $stmt->execute([$order_id, $customer_id, $claim_type, $reason, $status]);

        if ($res) {
            header("Location: ../history.php?success=Warranty claim submitted successfully!");
            exit;
        } else {
            header("Location: ../history.php?error=Database error occurred");
            exit;
        }

    } else {
        header("Location: ../history.php");
        exit;
    }

} else {
    header("Location: ../../login.php");
    exit;
}