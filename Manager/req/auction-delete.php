<?php 
session_start();

if (isset($_SESSION['user_id']) && 
    isset($_SESSION['role'])    && 
    $_SESSION['role'] == 'Manager') {

    include "../../DB_connection.php";

    if (isset($_GET['id'])) {
        $auction_id = $_GET['id'];

        // 1. Fetch auction details to get the car_id
        $sql_get = "SELECT car_id FROM auctions WHERE id = ?";
        $stmt_get = $conn->prepare($sql_get);
        $stmt_get->execute([$auction_id]);
        $auction = $stmt_get->fetch();


        if ($auction) {
            $car_id = $auction['car_id'];

            // 2. Restore the car to the showroom
            // We set in_auction to 0 and ensure it is 'In Stock'
            $sql_restore = "UPDATE cars 
                            SET quantity = quantity + 1, 
                                status = 'In Stock', 
                                in_auction = 0 
                            WHERE id = ?";
            
            $stmt_restore = $conn->prepare($sql_restore);
            $stmt_restore->execute([$car_id]);


            // 3. Delete the Auction record
            $sql_del_auc = "DELETE FROM auctions WHERE id = ?";
            $stmt_del_auc = $conn->prepare($sql_del_auc);
            $res = $stmt_del_auc->execute([$auction_id]);


            // 4. Redirect immediately
            // This redirect prevents the browser from asking for confirmation
            if ($res) {
                header("Location: ../auction-dashboard.php?success=Auction removed");
                exit;
            } else {
                header("Location: ../auction-dashboard.php?error=Delete failed");
                exit;
            }

        } else {
            header("Location: ../auction-dashboard.php?error=Not found");
            exit;
        }

    } else {
        header("Location: ../auction-dashboard.php");
        exit;
    }

} else {
    header("Location: ../../login.php");
    exit;
}