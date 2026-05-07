<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Rev Nation</title>
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
            /* Transparent grey background */
            background: rgba(30, 30, 30, 0.45); 
            /* Glassmorphism blur effect */
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
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .form-label {
            font-weight: 600;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.8);
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
            width: 100%; /* Make button full width like signup */
        }
        .footer-text {
            color: #e0e0e0 !important;
            margin-top: 20px;
        }
    </style>
</head>
<body class="body-login">
    <div class="black-fill">
        <div class="d-flex justify-content-center align-items-center flex-column">
            
            <form class="login" method="post" action="req/login.php">
                <div class="text-center mb-3">
                    <img src="logo.png" width="100">
                </div>
                
                <h3>LOGIN</h3>

                <?php if (isset($_GET['error'])) { ?>
                <div class="alert alert-danger" role="alert">
                  <?=$_GET['error']?>
                </div>
                <?php } ?>

                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" class="form-control" name="uname" placeholder="Enter username">
                </div>
              
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" class="form-control" name="pass" placeholder="••••••••">
                </div>

                <div class="mb-4">
                    <label class="form-label">Login As</label>
                    <select class="form-control" name="role">
                        <option value="1">Manager</option>
                        <option value="2">Employee</option>
                        <option value="3">Customer</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary shadow mb-3">Login</button>
                
                <div class="text-center">
                    <a href="index.php" class="text-decoration-none fw-bold text-info">Back to Home</a>
                </div>
            </form>
            
            <div class="text-center footer-text">
                Copyright &copy; 2026 Rev Nation. All rights reserved.
            </div>

        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>    
</body>
</html>