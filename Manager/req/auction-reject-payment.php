<?php 
session_start();

if (isset($_SESSION['user_id']) && 
    isset($_SESSION['role'])    && 
    $_SESSION['role'] == 'Manager') {

    if (isset($_GET['id'])) {
        
        include "../../DB_connection.php";
        $auction_id = $_GET['id'];

        // --- 1. Find the car_id for this auction ---
        $sql_find = "SELECT car_id FROM auctions WHERE id = ?";
        $stmt_find = $conn->prepare($sql_find);
        $stmt_find->execute([$auction_id]);
        $auction_data = $stmt_find->fetch();

        if ($auction_data) {
            $car_id = $auction_data['car_id'];

            // --- 2. Update auction status to 'failed' ---
            $sql_auction = "UPDATE auctions 
                            SET status = 'failed' 
                            WHERE id = ?";
            $stmt_auction = $conn->prepare($sql_auction);
            $stmt_auction->execute([$auction_id]);

            // --- 3. Increase Quantity and Update Status ---
            // We increment quantity and force status to 'In Stock' 
            // because we just added a car back.
            $sql_car = "UPDATE cars 
                        SET quantity = quantity + 1,
                            status = 'In Stock' 
                        WHERE id = ?";
            
            $stmt_car = $conn->prepare($sql_car);
            $result = $stmt_car->execute([$car_id]);

            if ($result) {
                header("Location: ../auction-pending-payment.php?success=Payment rejected. Quantity increased and car is In Stock.");
                exit;
            } else {
                header("Location: ../auction-pending-payment.php?error=Failed to update car inventory.");
                exit;
            }
        } else {
            header("Location: ../auction-pending-payment.php?error=Auction record not found.");
            exit;
        }

    } else {
        header("Location: ../auction-pending-payment.php");
        exit;
    }

} else {
    header("Location: ../../login.php");
    exit;
}