<?php 
session_start();

if (isset($_SESSION['user_id']) && 
    isset($_SESSION['role'])    && 
    $_SESSION['role'] == 'Manager') {

    if (isset($_GET['id'])) {
        
        include "../../DB_connection.php";
        $auction_id = $_GET['id'];

        // --- 1. Get Auction details and current car quantity ---
        // We need the car_id, final bid, and bidder ID to record the sale
        $sql_find = "SELECT auctions.car_id, auctions.current_bid, auctions.highest_bidder_id, cars.quantity 
                     FROM auctions 
                     JOIN cars ON auctions.car_id = cars.id 
                     WHERE auctions.id = ?";
        
        $stmt_find = $conn->prepare($sql_find);
        $stmt_find->execute([$auction_id]);
        $data = $stmt_find->fetch();


        if ($data) {
            $car_id = $data['car_id'];
            $qty = $data['quantity'];
            $price = $data['current_bid'];
            $cust_id = $data['highest_bidder_id'];

            // --- 2. Mark Auction as Paid and Closed ---
            $sql_auction = "UPDATE auctions 
                            SET winner_paid = 1, 
                                status = 'Closed' 
                            WHERE id = ?";
            
            $stmt_auction = $conn->prepare($sql_auction);
            $stmt_auction->execute([$auction_id]);


            // --- 3. Finalize Car Status ---
            // Quantity was already reduced when the auction was added.
            // We just ensure the status is 'Out of Stock' if quantity hit 0.
            $new_status = "In Stock";
            if ($qty <= 0) {
                $new_status = "Out of Stock";
            }

            $sql_car = "UPDATE cars SET status = ? WHERE id = ?";
            $stmt_car = $conn->prepare($sql_car);
            $stmt_car->execute([$new_status, $car_id]);


            // --- 4. Record the Revenue in auction_sales table ---
            $sql_sales = "INSERT INTO auction_sales (auction_id, car_id, customer_id, sale_price) 
                          VALUES (?, ?, ?, ?)";
            
            $stmt_sales = $conn->prepare($sql_sales);
            $result = $stmt_sales->execute([$auction_id, $car_id, $cust_id, $price]);


            if ($result) {
                header("Location: ../auction-pending-payment.php?success=Payment confirmed and recorded in Auction Sales.");
                exit;
            } else {
                header("Location: ../auction-pending-payment.php?error=Failed to record sale data.");
                exit;
            }

        } else {
            header("Location: ../auction-pending-payment.php?error=Data not found.");
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