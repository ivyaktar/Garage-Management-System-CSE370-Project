<?php 
session_start();

// Check if user is logged in and is either a Manager or an Employee
if (isset($_SESSION['user_id']) && 
    isset($_SESSION['role'])    && 
    ($_SESSION['role'] == 'Manager' || $_SESSION['role'] == 'Employee')) {

    include "../../DB_connection.php";

    if (isset($_GET['id'])) {
        $auction_id = $_GET['id'];

        // 1. Fetch auction details
        $sql_get = "SELECT car_id, current_bid, start_price, highest_bidder_id 
                    FROM auctions 
                    WHERE id = ?";
        
        $stmt_get = $conn->prepare($sql_get);
        $stmt_get->execute([$auction_id]);
        $auction = $stmt_get->fetch();


        if ($auction) {
            $car_id      = $auction['car_id'];
            $final_bid   = $auction['current_bid'];
            $start_price = $auction['start_price'];
            $winner_id   = $auction['highest_bidder_id'];

            // 2. Scenario Check: Did anyone bid?
            if ($winner_id == NULL) {
                
                // No bids: Return the vehicle to showroom stock
                $sql_car = "UPDATE cars 
                            SET quantity = quantity + 1, 
                                status = 'In Stock', 
                                in_auction = 0 
                            WHERE id = ?";
                $stmt_car = $conn->prepare($sql_car);
                $stmt_car->execute([$car_id]);

                // Update auction status to Failed
                $sql_close = "UPDATE auctions SET status = 'Failed' WHERE id = ?";
                $stmt_close = $conn->prepare($sql_close);
                $stmt_close->execute([$auction_id]);

                header("Location: ../auction-dashboard.php?info=Auction failed. No bidders found.");
                exit;

            } else {
                // Scenario: Winner exists
                // Set status to Closed. winner_paid remains 0 until they pay.
                $sql_close = "UPDATE auctions SET status = 'Closed' WHERE id = ?";
                $stmt_close = $conn->prepare($sql_close);
                $res = $stmt_close->execute([$auction_id]);

                if ($res) {
                    header("Location: ../auction-dashboard.php?success=Auction settled. Winner is waiting for payment.");
                    exit;
                } else {
                    header("Location: ../auction-dashboard.php?error=Failed to settle auction.");
                    exit;
                }
            }

        } else {
            header("Location: ../auction-dashboard.php?error=Auction not found.");
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
?>