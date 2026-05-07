<?php 
session_start();

if (isset($_SESSION['user_id']) && 
    isset($_SESSION['role'])    && 
    $_SESSION['role'] == 'Manager') {

    if (isset($_GET['id'])) {
        include "../../DB_connection.php";
        $id = $_GET['id'];

        // 1. Get the image filename before deleting the record
        $sql_img = "SELECT image FROM cars WHERE id = ?";
        $stmt_img = $conn->prepare($sql_img);
        $stmt_img->execute([$id]);
        $car = $stmt_img->fetch();

        if ($car) {
            $image_name = $car['image'];
            $path = "../../uploads/" . $image_name;

            // 2. Delete the physical file from the folder
            if (file_exists($path)) {
                unlink($path);
            }

            // 3. Delete from Database
            $sql_del = "DELETE FROM cars WHERE id = ?";
            $stmt_del = $conn->prepare($sql_del);
            $res = $stmt_del->execute([$id]);

            if ($res) {
                $sm = "Vehicle and associated image deleted successfully!";
                header("Location: ../showroom-manage.php?success=$sm");
                exit;
            } else {
                $em = "Error occurred while deleting from database.";
                header("Location: ../showroom-manage.php?error=$em");
                exit;
            }
        } else {
            header("Location: ../showroom-manage.php");
            exit;
        }

    } else {
        header("Location: ../showroom-manage.php");
        exit;
    }

} else {
    header("Location: ../../login.php");
    exit;
}
?>