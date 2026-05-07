<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Registration - Rev Nation</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" href="logo.png">
    <style>
        .body-login {
            background-color: #1a1a1a; /* Darker fallback */
        }
        .black-fill {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px 0;
        }
        .login {
            width: 750px !important; 
            /* Increased transparency to 0.45 */
            background: rgba(30, 30, 30, 0.45); 
            backdrop-filter: blur(8px); 
            -webkit-backdrop-filter: blur(8px);
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.6);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #fff; 
        }
        .login h3 {
            font-weight: 700;
            margin-bottom: 30px;
            text-align: center;
            color: #fff;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }
        .form-control {
            border-radius: 8px;
            padding: 10px 15px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(255, 255, 255, 0.9); 
        }
        .form-control:focus {
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
            border-color: #0d6efd;
            background: #fff;
        }
        .btn-primary {
            padding: 12px;
            font-weight: 600;
            border-radius: 8px;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: 0.3s;
        }
        .gender-section {
            background: rgba(0, 0, 0, 0.3);
            padding: 10px 15px;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .text-muted {
            color: #e0e0e0 !important;
        }
        .fw-semibold {
            text-shadow: 1px 1px 2px rgba(0,0,0,0.8);
        }
    </style>
</head>
<body class="body-login">
    <div class="black-fill">
        <div class="d-flex justify-content-center align-items-center flex-column">
            
            <form class="login" method="post" action="req/signup.php">
                <div class="text-center mb-3">
                    <img src="logo.png" width="100">
                </div>
                <h3>Customer Signup</h3>

                <?php if (isset($_GET['error'])) { ?>
                <div class="alert alert-danger" role="alert">
                  <?=$_GET['error']?>
                </div>
                <?php } ?>

                <?php if (isset($_GET['success'])) { ?>
                <div class="alert alert-success" role="alert">
                  <?=$_GET['success']?>
                </div>
                <?php } ?>

                <?php 
                    $fname = isset($_GET['fname']) ? $_GET['fname'] : '';
                    $lname = isset($_GET['lname']) ? $_GET['lname'] : '';
                    $uname = isset($_GET['uname']) ? $_GET['uname'] : '';
                    $email = isset($_GET['email']) ? $_GET['email'] : '';
                    $phone = isset($_GET['phone']) ? $_GET['phone'] : '';
                    $address = isset($_GET['address']) ? $_GET['address'] : '';
                ?>

                <div class="row gx-4">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">First Name</label>
                        <input type="text" class="form-control" name="fname" value="<?=$fname?>" placeholder="e.g. John">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Last Name</label>
                        <input type="text" class="form-control" name="lname" value="<?=$lname?>" placeholder="e.g. Doe">
                    </div>
                </div>

                <div class="row gx-4">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Username</label>
                        <input type="text" class="form-control" name="uname" value="<?=$uname?>" placeholder="Choose a unique username">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Password</label>
                        <input type="password" class="form-control" name="pass" placeholder="••••••••">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Email Address</label>
                    <input type="email" class="form-control" name="email" value="<?=$email?>" placeholder="name@example.com">
                </div>

                <div class="row gx-4">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Phone Number</label>
                        <input type="text" class="form-control" name="phone" value="<?=$phone?>" placeholder="+1 234 567 890">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Gender</label>
                        <div class="gender-section d-flex align-items-center h-100" style="min-height: 46px;">
                            <div class="form-check form-check-inline mb-0">
                                <input class="form-check-input" type="radio" name="gender" value="Male" id="male" checked>
                                <label class="form-check-label" for="male">Male</label>
                            </div>
                            <div class="form-check form-check-inline mb-0">
                                <input class="form-check-input" type="radio" name="gender" value="Female" id="female">
                                <label class="form-check-label" for="female">Female</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Home Address</label>
                    <input type="text" class="form-control" name="address" value="<?=$address?>" placeholder="Street, City, Country">
                </div>

                <button type="submit" class="btn btn-primary w-100 shadow">Create Account</button>
                
                <div class="mt-4 text-center">
                    <span class="text-muted">Already have an account?</span> 
                    <a href="login.php" class="text-decoration-none fw-bold text-info">Login here</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>