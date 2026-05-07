<?php 
session_start();

// Allow both Manager and Employee to access this page
if (isset($_SESSION['user_id']) && 
    isset($_SESSION['role'])    && 
    ($_SESSION['role'] == 'Manager' || $_SESSION['role'] == 'Employee') && 
    isset($_GET['id'])) {
    
    include "../DB_connection.php";
    $car_id = $_GET['id'];

    /* --- FETCH SETTING --- */
    $set_sql = "SELECT current_year FROM setting LIMIT 1";
    $set_stmt = $conn->prepare($set_sql);
    $set_stmt->execute();
    $setting = $set_stmt->fetch();
    $curr_year = $setting['current_year'];

    /* --- FETCH CURRENT VEHICLE DETAILS --- */
    $sql = "SELECT * FROM cars WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$car_id]);
    $car = $stmt->fetch();

    if (!$car) {
        header("Location: showroom-manage.php");
        exit;
    }

    /* --- FETCH CATEGORIES (Read-only for selection) --- */
    $cat_sql = "SELECT * FROM categories WHERE type = 'Car' ORDER BY category_name ASC";
    $cat_stmt = $conn->prepare($cat_sql);
    $cat_stmt->execute();
    $categories = $cat_stmt->fetchAll();

    $transmissions = ["Automatic", "Manual", "CVT", "DCT", "AMT"];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Employee - Edit Vehicle</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        .page-header-container { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding: 10px 0; }
        .header-left { display: flex; align-items: center; }
        .page-header-icon { font-size: 1.8rem; margin-right: 10px; color: #6c757d; }
        .page-header-title { font-weight: 600; font-size: 1.6rem; color: #333; margin: 0; }
    </style>
</head>
<body class="bg-light">
    <?php include "inc/navbar.php"; ?>

    <div class="container mt-5">
        <div class="page-header-container">
            <div class="header-left">
                <div class="page-header-icon"><i class="fa fa-pencil-square-o"></i></div>
                <h2 class="page-header-title">Edit Vehicle (Employee Portal)</h2>
            </div>
            <a href="showroom-manage.php" class="btn btn-dark">
                <i class="fa fa-chevron-left"></i> Back to Showroom
            </a>
        </div>

        <?php if (isset($_GET['success'])) { ?>
            <div class="alert alert-success"><?php echo $_GET['success']; ?></div>
        <?php } ?>

        <?php if (isset($_GET['error'])) { ?>
            <div class="alert alert-danger"><?php echo $_GET['error']; ?></div>
        <?php } ?>

        <div class="row justify-content-center">
            <div class="col-md-9">
                <div class="card shadow-sm border-0 mb-5">
                    <div class="card-header bg-white fw-bold">Vehicle Information</div>
                    <div class="card-body p-4">
                        
                        <form action="req/car-edit.php" method="POST" enctype="multipart/form-data">
                            
                            <input type="hidden" name="id" value="<?php echo $car['id']; ?>">
                            <input type="hidden" name="old_image" value="<?php echo $car['image']; ?>">

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Brand</label>
                                    <input type="text" name="brand" class="form-control" value="<?php echo $car['brand']; ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Model</label>
                                    <input type="text" name="model" class="form-control" value="<?php echo $car['model']; ?>" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Category</label>
                                    <select name="category_id" class="form-select" required>
                                        <?php for ($i = 0; $i < count($categories); $i = $i + 1) { ?>
                                            <option value="<?php echo $categories[$i]['id']; ?>" 
                                                <?php echo ($categories[$i]['id'] == $car['category_id']) ? 'selected' : ''; ?>>
                                                <?php echo $categories[$i]['category_name']; ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                    <small class="text-muted">Note: Employees cannot create new categories.</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Transmission</label>
                                    <select name="transmission" class="form-select" required>
                                        <?php for ($i = 0; $i < count($transmissions); $i = $i + 1) { ?>
                                            <option value="<?php echo $transmissions[$i]; ?>"
                                                <?php echo ($transmissions[$i] == $car['transmission']) ? 'selected' : ''; ?>>
                                                <?php echo $transmissions[$i]; ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Year</label>
                                    <input type="number" name="year" class="form-control" 
                                           min="1900" max="<?php echo $curr_year; ?>" 
                                           value="<?php echo $car['year']; ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Mileage (km)</label>
                                    <input type="number" name="mileage" class="form-control" 
                                           min="0" max="999999999" value="<?php echo $car['mileage']; ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Price ($)</label>
                                    <input type="number" name="price" class="form-control" 
                                           min="1" max="999999999" value="<?php echo $car['price']; ?>" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Stock Quantity</label>
                                    <input type="number" name="quantity" id="quantityInput" class="form-control" 
                                           value="<?php echo $car['quantity']; ?>" oninput="validateQty()" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Status</label>
                                    <select name="status" id="statusSelect" class="form-select" onchange="toggleQuantity()">
                                        <option value="In Stock" <?php echo ($car['status'] == 'In Stock') ? 'selected' : ''; ?>>In Stock</option>
                                        <option value="Upcoming" <?php echo ($car['status'] == 'Upcoming') ? 'selected' : ''; ?>>Upcoming</option>
                                        <option value="Sold Out" <?php echo ($car['status'] == 'Sold Out') ? 'selected' : ''; ?>>Sold Out</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Description</label>
                                <textarea name="description" class="form-control" rows="3"><?php echo $car['description']; ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Warranty Types</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="regular_warranty" value="1" id="regW" <?php echo ($car['regular_warranty']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="regW">Regular (Repair)</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="replacement_warranty" value="1" id="repW" <?php echo ($car['replacement_warranty']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="repW">Replacement</label>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold">Update Image</label>
                                <input type="file" name="car_image" class="form-control">
                                <div class="mt-2">
                                    <small class="text-muted">Current image preview:</small><br>
                                    <img src="../uploads/<?php echo $car['image']; ?>" style="width: 150px; border-radius: 5px;" class="shadow-sm border">
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 p-2 fw-bold">Save Changes</button>
                        
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleQuantity() {
            var status = document.getElementById('statusSelect').value;
            var qtyInput = document.getElementById('quantityInput');
            
            if (status === 'Upcoming' || status === 'Sold Out') {
                qtyInput.value = 0;
                qtyInput.readOnly = true;
                qtyInput.style.backgroundColor = "#e9ecef";
            } else {
                if (qtyInput.value <= 0) { 
                    qtyInput.value = 1; 
                }
                qtyInput.readOnly = false;
                qtyInput.style.backgroundColor = "#ffffff";
            }
        }

        function validateQty() {
            var status = document.getElementById('statusSelect').value;
            var qtyInput = document.getElementById('quantityInput');
            if (status === 'In Stock' && qtyInput.value < 1) { 
                qtyInput.value = 1; 
            }
        }

        window.onload = function() { 
            toggleQuantity(); 
        };
    </script>
</body>
</html>

<?php 
} else {
    header("Location: showroom-manage.php");
    exit;
} ?>