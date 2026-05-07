<?php 
session_start();

if (isset($_SESSION['user_id']) && 
    isset($_SESSION['role'])    && 
    $_SESSION['role'] == 'Customer') {
    
    include "../DB_connection.php";

    // Fetch the current year constraint from the setting table
    $sql_year = "SELECT current_year FROM setting LIMIT 1";
    $stmt_year = $conn->prepare($sql_year);
    $stmt_year->execute();
    $setting = $stmt_year->fetch();
    
    $max_year = ($setting) ? $setting['current_year'] : date("Y"); 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Book Service - Rev Nation</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    
    <style>
        body { background-color: #f4f7f6; color: #212529; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        
        .repair-logo-badge { 
            background: #212529; 
            color: #fff; 
            border-radius: 12px; 
            width: 50px; 
            height: 50px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .form-container { 
            max-width: 800px; 
            margin: 30px auto; 
            background: #fff; 
            padding: 40px; 
            border-radius: 15px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        }

        .form-label { font-weight: 700; font-size: 0.85rem; color: #212529; text-transform: uppercase; letter-spacing: 0.5px; }
        .form-control, .form-select { 
            border-radius: 8px; 
            border: 1px solid #e9ecef; 
            padding: 12px; 
            background-color: #f8f9fa;
        }
        .form-control:focus, .form-select:focus { 
            background-color: #fff;
            border-color: #212529; 
            box-shadow: none; 
        }

        .section-title {
            font-size: 1.1rem;
            font-weight: 800;
            border-bottom: 2px solid #f4f7f6;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .price-info-box {
            background-color: #e9ecef;
            border-radius: 10px;
            padding: 15px;
            font-size: 0.85rem;
            border-left: 4px solid #212529;
        }
    </style>
</head>
<body>

    <?php include "inc/navbar.php"; ?>

    <div class="container mt-5">
        
        <div class="row mb-2 align-items-center justify-content-center">
            <div class="col-md-8">
                <div class="d-flex align-items-center mb-4">
                    <div class="repair-logo-badge me-3">
                        <i class="fa fa-wrench fa-lg"></i>
                    </div>
                    <div>
                        <h3 class="fw-bold mb-0">Book New Service</h3>
                        <p class="text-muted small mb-0">Select a service package and provide vehicle details.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-container">
            <?php if (isset($_GET['error'])) { ?>
                <div class="alert alert-danger border-0 shadow-sm small py-3 mb-4">
                    <i class="fa fa-exclamation-circle me-2"></i><?php echo $_GET['error']; ?>
                </div>
            <?php } ?>

            <form action="req/repair-add-process.php" method="post" enctype="multipart/form-data">
                
                <div class="section-title">
                    <i class="fa fa-car me-2"></i>Vehicle Information
                </div>

                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Brand</label>
                        <input type="text" name="brand" class="form-control" placeholder="e.g. BMW" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Model</label>
                        <input type="text" name="model" class="form-control" placeholder="e.g. M4" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Year</label>
                        <input type="number" name="year" class="form-control" 
                               min="1900" max="<?php echo $max_year; ?>" 
                               placeholder="e.g. <?php echo $max_year; ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Mileage (KM)</label>
                        <input type="number" name="mileage" class="form-control" 
                               min="0" max="1000000000" 
                               placeholder="45000" required>
                    </div>
                </div>

                <div class="section-title">
                    <i class="fa fa-list-alt me-2"></i>Service Selection
                </div>

                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label">Service Type</label>
                        <select name="service_type" class="form-select" required>
                            <option value="">Select a package</option>
                            <option value="Regular Service">Regular Service ($500 Fixed)</option>
                            <option value="Premium Service">Premium Service ($1000 Fixed)</option>
                            <option value="Repair Car">Repair Car ($300 Labor + Parts)</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Vehicle Photo</label>
                        <input type="file" name="car_image" class="form-control" required>
                    </div>

                    <div class="col-12">
                        <div class="price-info-box mb-3">
                            <strong><i class="fa fa-info-circle"></i> Pricing Policy:</strong>
                            <ul class="mb-0 mt-1">
                                <li><strong>Regular:</strong> Flat rate service of $500. Parts cannot be added later.</li>
                                <li><strong>Premium:</strong> Flat rate service of $1000. Parts cannot be added later.</li>
                                <li><strong>Repair Car:</strong> $300 base labor fee. Technicians will add part costs after inspection.</li>
                            </ul>
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Issue / Request Description</label>
                        <textarea name="description" class="form-control" rows="4" placeholder="Describe the work needed..."></textarea>
                    </div>

                    <div class="col-12 mt-5 border-top pt-4 d-flex justify-content-between align-items-center">
                        <a href="repair-dashboard.php" class="text-decoration-none text-muted fw-bold small">
                            <i class="fa fa-arrow-left me-1"></i> CANCEL
                        </a>
                        <button type="submit" class="btn btn-dark fw-bold px-5 py-2 rounded-pill shadow-sm">
                            BOOK NOW <i class="fa fa-check ms-2"></i>
                        </button>
                    </div>
                </div>
                
            </form>
        </div>
    </div>

    <div class="mb-5"></div>

</body>
</html>

<?php 
} else {
    header("Location: ../login.php");
    exit;
} 
?>