<?php 
session_start();

if (isset($_SESSION['user_id']) && 
    isset($_SESSION['role'])    && 
    $_SESSION['role'] == 'Customer') {

    include "../../DB_connection.php";

    $u_id = $_SESSION['user_id'];
    $action = "";
    
    // Determine the action (add, update, delete)
    if (isset($_REQUEST['action'])) {
        $action = $_REQUEST['action'];
    }

    // Determine the source (Car or Product)
    $p_id = null;
    $c_id = null;
    $redirect_url = "../showroom.php"; // Default fallback

    if (isset($_REQUEST['product_id']) && !empty($_REQUEST['product_id'])) {
        $p_id = $_REQUEST['product_id'];
        $redirect_url = "../product-view.php?id=$p_id";
    } else if (isset($_REQUEST['car_id']) && !empty($_REQUEST['car_id'])) {
        $c_id = $_REQUEST['car_id'];
        $redirect_url = "../car-view.php?id=$c_id";
    }

    /* --- ACTION: DELETE --- */
    if ($action == "delete" && isset($_GET['review_id'])) {
        $r_id = $_GET['review_id'];

        $sql = "DELETE FROM reviews WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $res = $stmt->execute([$r_id, $u_id]);

        if ($res) {
            header("Location: $redirect_url&success=Review deleted successfully");
            exit;
        } else {
            header("Location: $redirect_url&error=Could not delete review");
            exit;
        }
    }

    /* --- ACTION: ADD OR UPDATE (Requires POST data) --- */
    if (isset($_POST['rating']) && isset($_POST['comment'])) {
        $rating = $_POST['rating'];
        $comment = $_POST['comment'];

        if ($action == "add") {
            $sql = "INSERT INTO reviews (product_id, car_id, user_id, rating, comment, date_posted) 
                    VALUES (?, ?, ?, ?, ?, NOW())";
            $stmt = $conn->prepare($sql);
            $res = $stmt->execute([$p_id, $c_id, $u_id, $rating, $comment]);

            if ($res) {
                header("Location: $redirect_url&success=Review posted!");
                exit;
            }
        } 
        
        else if ($action == "update" && isset($_POST['review_id'])) {
            $r_id = $_POST['review_id'];

            // Security check from your edit-review.php logic
            $sql_check = "SELECT id FROM reviews WHERE id = ? AND user_id = ?";
            $stmt_check = $conn->prepare($sql_check);
            $stmt_check->execute([$r_id, $u_id]);

            if ($stmt_check->rowCount() > 0) {
                $sql = "UPDATE reviews SET rating = ?, comment = ? WHERE id = ? AND user_id = ?";
                $stmt = $conn->prepare($sql);
                $res = $stmt->execute([$rating, $comment, $r_id, $u_id]);

                if ($res) {
                    header("Location: $redirect_url&success=Review updated!");
                    exit;
                }
            } else {
                header("Location: $redirect_url&error=Unauthorized action");
                exit;
            }
        }
    }

    // If no valid action was found
    header("Location: $redirect_url");
    exit;

} else {
    header("Location: ../../login.php");
    exit;
}