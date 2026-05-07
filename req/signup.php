<?php 
if (isset($_POST['fname']) && 
    isset($_POST['lname']) && 
    isset($_POST['uname']) && 
    isset($_POST['pass'])  && 
    isset($_POST['email']) &&
    isset($_POST['phone']) &&
    isset($_POST['address']) &&
    isset($_POST['gender'])) {

    include "../DB_connection.php";

    $fname = $_POST['fname'];
    $lname = $_POST['lname'];
    $uname = strtolower($_POST['uname']);
    $pass  = $_POST['pass'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $gender = $_POST['gender'];

    $data = "fname=$fname&lname=$lname&uname=$uname&email=$email&phone=$phone&address=$address";

    if (empty($fname)) {
        header("Location: ../signup.php?error=First name is required&$data");
        exit;
    } else if (empty($lname)) {
        header("Location: ../signup.php?error=Last name is required&$data");
        exit;
    } else if (empty($uname)) {
        header("Location: ../signup.php?error=Username is required&$data");
        exit;
    } else if (empty($pass)) {
        header("Location: ../signup.php?error=Password is required&$data");
        exit;
    } else {
        $sql_check = "SELECT username FROM users WHERE username = ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->execute([$uname]);
        $users_found = $stmt_check->fetchAll();

        $exists = false;
        for ($i = 0; $i < count($users_found); $i++) {
            if ($users_found[$i]['username'] == $uname) {
                $exists = true;
            }
        }

        if ($exists) {
            $em = "The username ($uname) is already taken.";
            header("Location: ../signup.php?error=$em&$data");
            exit;
        } else {
            $hashed_pass = password_hash($pass, PASSWORD_DEFAULT);
            $user_type = 'customer';

            // FIXED: Changed 'role' back to 'user_type' to match your database
            $sql1 = "INSERT INTO users(username, password, user_type) VALUES(?,?,?)";
            $stmt1 = $conn->prepare($sql1);
            $stmt1->execute([$uname, $hashed_pass, $user_type]);

            $user_id = $conn->lastInsertId();

            $sql2 = "INSERT INTO customer(user_id, fname, lname, phone_number, email_address, address, gender) 
                     VALUES(?,?,?,?,?,?,?)";
            $stmt2 = $conn->prepare($sql2);
            $stmt2->execute([$user_id, $fname, $lname, $phone, $email, $address, $gender]);

            $sm = "Account created successfully!";
            header("Location: ../signup.php?success=$sm");
            exit;
        }
    }
} else {
    header("Location: ../signup.php");
    exit;
}