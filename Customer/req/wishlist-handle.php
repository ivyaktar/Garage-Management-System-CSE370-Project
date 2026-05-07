<?php 
session_start();

if (isset($_SESSION['user_id']) && 
    isset($_SESSION['role'])    && 
    $_SESSION['role'] == 'Customer') {

    include "../../DB_connection.php";

    if (isset($_GET['id'])) {
        
        $item_id = $_GET['id'];
        $user_id = $_SESSION['user_id'];
        
        // Default type is product unless specified
        $type = "product";
        if (isset($_GET['type'])) {
            $type = $_GET['type'];
        }
        
        // Determine where the user should go back to
        $redirect_to = "../store.php";

        if (isset($_GET['source'])) {
            $source = $_GET['source'];

            if ($source == 'view') {
                $redirect_to = "../product-view.php?id=" . $item_id;
            } else if ($source == 'wishlist') {
                $redirect_to = "../wishlist.php";
            } else if ($source == 'showroom') {
                $redirect_to = "../showroom.php";
            } else if ($source == 'car-view') {
                $redirect_to = "../car-view.php?id=" . $item_id;
            }
        }

        // Action can be 'add' (default) or 'remove'
        $action = "add";
        if (isset($_GET['action'])) {
            $action = $_GET['action'];
        }

        // Helper function to handle URL parameters correctly
        function get_redirect_url($base, $msg_type, $msg_text) {
            $separator = "?";
            if (strpos($base, '?') !== false) {
                $separator = "&";
            }
            return $base . $separator . $msg_type . "=" . urlencode($msg_text);
        }

        
        // --- REMOVE ACTION ---
        if ($action == "remove") {
            
            if ($type == "car") {
                $sql = "DELETE FROM wishlist WHERE user_id = ? AND car_id = ?";
            } else {
                $sql = "DELETE FROM wishlist WHERE user_id = ? AND product_id = ?";
            }
            
            $stmt = $conn->prepare($sql);
            $res = $stmt->execute([$user_id, $item_id]);

            if ($res) {
                $url = get_redirect_url($redirect_to, "success", "Removed from wishlist!");
                header("Location: $url");
                exit;
            }


        // --- ADD ACTION ---
        } else {
            
            // 1. Check if the item is already in the wishlist to prevent duplicates
            if ($type == "car") {
                $sql_check = "SELECT * FROM wishlist WHERE user_id = ? AND car_id = ?";
            } else {
                $sql_check = "SELECT * FROM wishlist WHERE user_id = ? AND product_id = ?";
            }

            $stmt_check = $conn->prepare($sql_check);
            $stmt_check->execute([$user_id, $item_id]);

            if ($stmt_check->rowCount() > 0) {
                $url = get_redirect_url($redirect_to, "info", "Item is already in your wishlist!");
                header("Location: $url");
                exit;
            } else {
                
                // 2. Insert into database. We set the other ID column to NULL explicitly.
                if ($type == "car") {
                    $sql_ins = "INSERT INTO wishlist(user_id, car_id, product_id) VALUES(?, ?, NULL)";
                } else {
                    $sql_ins = "INSERT INTO wishlist(user_id, product_id, car_id) VALUES(?, ?, NULL)";
                }

                $stmt_ins = $conn->prepare($sql_ins);
                $res = $stmt_ins->execute([$user_id, $item_id]);

                if ($res) {
                    $url = get_redirect_url($redirect_to, "success", "Added to wishlist!");
                    header("Location: $url");
                    exit;
                } else {
                    $url = get_redirect_url($redirect_to, "error", "Could not add to wishlist.");
                    header("Location: $url");
                    exit;
                }
            }
        }

    } else {
        header("Location: ../store.php");
        exit;
    }

} else {
    header("Location: ../../login.php");
    exit;
}
?>