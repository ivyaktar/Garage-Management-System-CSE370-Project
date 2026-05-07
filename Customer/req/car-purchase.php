<?php 
session_start();

if (isset($_SESSION['user_id']) && $_SESSION['role'] == 'Customer') {
    if (isset($_POST['car_id']) && isset($_POST['price'])) {
        include "../../DB_connection.php"; // Double dot because this is inside the 'req' folder

        $u_id = $_SESSION['user_id'];
        $car_id = $_POST['car_id'];
        $price = $_POST['price'];

        // 1. Check if car is still available
        $sql = "SELECT quantity FROM cars WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$car_id]);
        $car = $stmt->fetch();

        if ($car && $car['quantity'] > 0) {
            // 2. Insert into orders table (Matching your phpMyAdmin structure)
            $sql2 = "INSERT INTO orders (customer_id, car_id, quantity, total_price, date_ordered) 
                     VALUES (?, ?, 1, ?, CURDATE())";
            $stmt2 = $conn->prepare($sql2);
            $res2 = $stmt2->execute([$u_id, $car_id, $price]);

            if ($res2) {
                // 3. Update Car Stock
                $new_qty = $car['quantity'] - 1;
                $new_status = ($new_qty <= 0) ? 'Sold Out' : 'In Stock';

                $sql3 = "UPDATE cars SET quantity = ?, status = ? WHERE id = ?";
                $stmt3 = $conn->prepare($sql3);
                $stmt3->execute([$new_qty, $new_status, $car_id]);

                header("Location: ../showroom.php?success=Congratulations! Your vehicle purchase is complete.");
                exit;
            }
        } else {
            header("Location: ../showroom.php?error=Sorry, this vehicle just sold out.");
            exit;
        }
    }
} else {
    header("Location: ../login.php");
    exit;
}