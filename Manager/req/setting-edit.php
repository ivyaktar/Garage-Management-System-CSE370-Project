<?php 
session_start();

// Use 'role' and 'user_id' to match your login session variables
if (isset($_SESSION['user_id']) && 
    isset($_SESSION['role'])    && 
    $_SESSION['role'] == 'Manager') {

    if (isset($_POST['garage_name']) && 
        isset($_POST['slogan'])      && 
        isset($_POST['about'])       && 
        isset($_POST['current_year'])) {
        
        include "../../DB_connection.php";

        $garage_name  = $_POST['garage_name'];
        $slogan       = $_POST['slogan'];
        $about        = $_POST['about'];
        $current_year = $_POST['current_year'];

        // Validation - Checking if fields are empty
        if (empty($garage_name)) {
            $em = "Garage name is required";
            header("Location: ../settings.php?error=$em");
            exit;
        } else if (empty($slogan)) {
            $em = "Slogan is required";
            header("Location: ../settings.php?error=$em");
            exit;
        } else if (empty($about)) {
            $em = "About description is required";
            header("Location: ../settings.php?error=$em");
            exit;
        } else if (empty($current_year)) {
            $em = "Current year is required";
            header("Location: ../settings.php?error=$em");
            exit;
        } else {
            
            $id = 1;
            $sql = "UPDATE setting 
                    SET garage_name = ?,
                        slogan = ?,
                        about = ?,
                        current_year = ?
                    WHERE id = ?";

            $stmt = $conn->prepare($sql);
            $stmt->execute([$garage_name, $slogan, $about, $current_year, $id]);

            $sm = "Settings updated successfully";
            header("Location: ../settings.php?success=$sm");
            exit;
        }
        
    } else {
        $em = "An error occurred";
        header("Location: ../settings.php?error=$em");
        exit;
    }

} else {
    header("Location: ../../logout.php");
    exit;
}