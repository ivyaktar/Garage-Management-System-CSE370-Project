<?php 
session_start();

if (isset($_SESSION['user_id']) && 
    isset($_SESSION['role'])    && 
    $_SESSION['role'] == 'Customer' &&
    isset($_POST['auction_id']) &&
    isset($_POST['bid_amount'])) {

    include "../../DB_connection.php";

    $customer_id = $_SESSION['user_id'];
    $auction_id  = $_POST['auction_id'];
    $bid_amount  = $_POST['bid_amount'];

    try {
        // START TRANSACTION FIRST to make FOR UPDATE work
        $conn->beginTransaction();

        // 1. Fetch current auction data
        $sql = "SELECT current_bid, min_increment, end_time, status, highest_bidder_id 
                FROM auctions 
                WHERE id = ? FOR UPDATE";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([$auction_id]);
        $auction = $stmt->fetch();

        if (!$auction) {
            throw new Exception("Auction not found.");
        }

        // 2. Logic Checks
        $now = date("Y-m-d H:i:s");

        // FIX: First bidder logic
        if (empty($auction['highest_bidder_id']) || $auction['highest_bidder_id'] == 0) {
            $min_required = $auction['current_bid'];
        } else {
            $min_required = $auction['current_bid'] + $auction['min_increment'];
        }

        if ($auction['status'] != 'Active' || $auction['end_time'] < $now) {
            header("Location: ../auction-view.php?id=$auction_id&error=This auction is no longer active.");
            exit;
        }

        if ($bid_amount < $min_required) {
            header("Location: ../auction-view.php?id=$auction_id&error=Bid too low. Minimum is $$min_required.");
            exit;
        }

        if ($auction['highest_bidder_id'] == $customer_id) {
            header("Location: ../auction-view.php?id=$auction_id&error=You are already the highest bidder.");
            exit;
        }

        // 3. Process the Bid
        $update_sql = "UPDATE auctions 
                       SET current_bid = ?, 
                           highest_bidder_id = ? 
                       WHERE id = ?";
        
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->execute([$bid_amount, $customer_id, $auction_id]);

        $history_sql = "INSERT INTO auction_bids (auction_id, customer_id, bid_amount) 
                        VALUES (?, ?, ?)";
        
        $history_stmt = $conn->prepare($history_sql);
        $history_stmt->execute([$auction_id, $customer_id, $bid_amount]);

        $conn->commit();
        header("Location: ../auction-view.php?id=$auction_id&success=Bid placed successfully!");
        exit;

    } catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        // It's helpful to pass the actual error during debugging:
        header("Location: ../auction-view.php?id=$auction_id&error=" . urlencode($e->getMessage()));
        exit;
    }

} else {
    header("Location: ../../login.php");
    exit;
}