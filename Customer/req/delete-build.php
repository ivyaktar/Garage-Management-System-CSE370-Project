<?php 
session_start();

if (isset($_SESSION['user_id']) && 
    isset($_SESSION['role'])    && 
    $_SESSION['role'] == 'Customer') {

    if (isset($_GET['id'])) {
        
        include "../../DB_connection.php";
        
        $user_id = $_SESSION['user_id'];
        $build_id = $_GET['id'];

        // 1. Security Check: Verify this build actually belongs to this user
        $sql_check = "SELECT id FROM user_builds WHERE id = ? AND user_id = ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->execute([$build_id, $user_id]);

        if ($stmt_check->rowCount() > 0) {

            // 2. Delete items associated with the build first (Foreign Key constraint)
            $sql_items = "DELETE FROM build_items WHERE build_id = ?";
            $stmt_items = $conn->prepare($sql_items);
            $stmt_items->execute([$build_id]);

            // 3. Delete the build itself
            $sql_build = "DELETE FROM user_builds WHERE id = ?";
            $stmt_build = $conn->prepare($sql_build);
            $res = $stmt_build->execute([$build_id]);

            if ($res) {
                $sm = "Build deleted successfully!";
                header("Location: ../saved-builds.php?success=$sm");
                exit;
            } else {
                $em = "Error occurred while deleting.";
                header("Location: ../saved-builds.php?error=$em");
                exit;
            }

        } else {
            // Unauthorized attempt
            $em = "You do not have permission to delete this build.";
            header("Location: ../saved-builds.php?error=$em");
            exit;
        }

    } else {
        header("Location: ../saved-builds.php");
        exit;
    }

} else {
    header("Location: ../login.php");
    exit;
}