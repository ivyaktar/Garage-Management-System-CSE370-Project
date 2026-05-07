<?php 
session_start();
if (isset($_SESSION['user_id']) && $_SESSION['role'] == 'Manager') {

    if (isset($_GET['id']) && isset($_GET['status'])) {
        include "../../DB_connection.php";

        $id = $_GET['id'];
        $status = $_GET['status'];

        // Simple update query
        $sql = "UPDATE warranty_claims SET status = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $res = $stmt->execute([$status, $id]);

        if ($res) {
            header("Location: ../warranty-manage.php?success=Claim updated to $status");
            exit;
        } else {
            header("Location: ../warranty-manage.php?error=Error occurred");
            exit;
        }
    } else {
        header("Location: ../warranty-manage.php");
        exit;
    }
} else {
    header("Location: ../../login.php");
    exit;
}