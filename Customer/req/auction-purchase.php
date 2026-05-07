<?php 
session_start();

if (isset($_SESSION['user_id']) && 
    isset($_SESSION['role'])    && 
    $_SESSION['role'] == 'Customer' &&
    isset($_POST['car_id'])     &&
    isset($_POST['auction_id']) &&
    isset($_POST['price'])) {

    include "../../DB_connection.php";

    $customer_id = $_SESSION['user_id'];
    $car_id      = $_POST['car_id'];
    $auction_id  = $_POST['auction_id'];
    $price       = $_POST['price'];

    try {
        // Start transaction to ensure data integrity
        $conn->beginTransaction();

        // 1. Fetch current car quantity and auction status
        // We lock the auction row specifically to prevent double-purchasing
        $sql_check = "SELECT status FROM auctions WHERE id = ? FOR UPDATE";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->execute([$auction_id]);
        $auction = $stmt_check->fetch();

        // Check if auction is still active (not already sold/closed)
        if ($auction && $auction['status'] == 'Active') {
            
            // 2. Reduce Car Quantity
            // We fetch current quantity. If it's already 0, we don't subtract, 
            // but we still allow the purchase to finish.
            $sql_stock = "SELECT quantity FROM cars WHERE id = ?";
            $stmt_stock = $conn->prepare($sql_stock);
            $stmt_stock->execute([$car_id]);
            $car = $stmt_stock->fetch();

            if ($car && $car['quantity'] > 0) {
                $new_quantity = $car['quantity'] - 1;
                $sql_update_car = "UPDATE cars SET quantity = ? WHERE id = ?";
                $stmt_car = $conn->prepare($sql_update_car);
                $stmt_car->execute([$new_quantity, $car_id]);
            }

            // 3. Mark Auction as 'Closed' and set winner_paid to 0
            // This locks the car from further bids and puts it in the Manager's "Pending" queue
            $sql_update_auc = "UPDATE auctions 
                               SET status = 'Closed', 
                                   current_bid = ?, 
                                   highest_bidder_id = ?, 
                                   winner_paid = 0 
                               WHERE id = ?";
            $stmt_auc = $conn->prepare($sql_update_auc);
            $stmt_auc->execute([$price, $customer_id, $auction_id]);

            // --- REMOVED: INSERT INTO auction_sales ---
            // We do NOT insert into auction_sales here because the manager has not confirmed payment yet.

            // 4. Insert into Auction Bids for Customer History
            // This records the "Winning Bid" so the customer sees it in their history
            $sql_bid = "INSERT INTO auction_bids (auction_id, customer_id, bid_amount) 
                        VALUES (?, ?, ?)";
            $stmt_bid = $conn->prepare($sql_bid);
            $stmt_bid->execute([$auction_id, $customer_id, $price]);

            // Commit the changes
            $conn->commit();
            header("Location: ../auction-lobby.php?success=Order placed! Please proceed with payment. Approval is pending manager confirmation.");
            exit;

        } else {
            // Auction is not active
            $conn->rollBack();
            header("Location: ../auction-lobby.php?error=Sorry, this auction is no longer available.");
            exit;
        }

    } catch (Exception $e) {
        if ($conn->inTransaction()) $conn->rollBack();
        header("Location: ../auction-lobby.php?error=An error occurred during the transaction.");
        exit;
    }

} else {
    header("Location: ../../login.php");
    exit;
}