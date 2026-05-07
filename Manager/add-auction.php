<?php 
session_start();

if (isset($_SESSION['user_id']) && 
    isset($_SESSION['role'])    && 
    $_SESSION['role'] == 'Manager') {
    
    include "../DB_connection.php";

    /* --- FETCH SETTING --- */
    $set_sql = "SELECT current_year FROM setting LIMIT 1";
    $set_stmt = $conn->prepare($set_sql);
    $set_stmt->execute();
    $setting = $set_stmt->fetch();
    $curr_year = $setting['current_year'];

    /* --- FETCHING DATA --- */
    $cat_sql = "SELECT * FROM categories WHERE type = 'Car' ORDER BY category_name ASC";
    $cat_stmt = $conn->prepare($cat_sql);
    $cat_stmt->execute();
    $categories = $cat_stmt->fetchAll();

    $transmissions = ["Automatic", "Manual", "CVT", "DCT", "AMT"];

    $inv_sql = "SELECT id, brand, model, year, price, quantity FROM cars 
                WHERE status = 'In Stock' 
                AND quantity > 0
                ORDER BY brand ASC";
    $inv_stmt = $conn->prepare($inv_sql);
    $inv_stmt->execute();
    $inventory_cars = $inv_stmt->fetchAll();

    $now = date('Y-m-d\TH:i');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manager - Launch Auction</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        body { background-color: #f4f7f6; }
        .form-container { 
            background: #fff; 
            padding: 35px; 
            border-radius: 20px; 
            box-shadow: 0 15px 35px rgba(0,0,0,0.05); 
            border: 1px solid #eef2f1;
            transition: transform 0.3s ease;
        }
        .page-header-icon { 
            background: linear-gradient(135deg, #198754, #146c43); 
            color: white; width: 60px; height: 60px; 
            border-radius: 15px; display: flex; align-items: center; 
            justify-content: center; margin-right: 20px; font-size: 1.8rem; 
        }
        #new_car_fields { display: none; border-left: 6px solid #198754; }
        .section-title { 
            font-size: 1rem; color: #495057; font-weight: 700; 
            border-bottom: 2px solid #f8f9fa; padding-bottom: 12px; 
            margin-bottom: 25px; text-transform: uppercase; letter-spacing: 1.2px; 
        }
        .compact-guide { 
            padding: 25px; border-radius: 20px; 
            background: #ffffff; border: 1px solid #eef2f1;
            box-shadow: 0 10px 25px rgba(0,0,0,0.03);
        }
        .guide-item { display: flex; align-items: flex-start; margin-bottom: 15px; }
        .guide-item i { color: #198754; margin-right: 12px; margin-top: 4px; }
        .form-label { color: #6c757d; font-size: 0.9rem; }
        .form-control:focus, .form-select:focus {
            border-color: #198754;
            box-shadow: 0 0 0 0.25rem rgba(25, 135, 84, 0.1);
        }
    </style>
</head>
<body>
    <?php include "inc/navbar.php"; ?>

    <div class="container mt-5 mb-5">
        <div class="row justify-content-center">
            <div class="col-md-11">
                
                <div class="d-flex align-items-center mb-5">
                    <div class="page-header-icon shadow"><i class="fa fa-gavel"></i></div>
                    <div>
                        <h2 class="fw-bold m-0 text-dark">INITIALIZE AUCTION</h2>
                        <p class="text-muted m-0">Setup a new bidding event for your premium fleet</p>
                    </div>
                </div>

                <?php if (isset($_GET['error'])) { ?>
                    <div class="alert alert-danger border-0 shadow-sm"><?php echo $_GET['error']; ?></div>
                <?php } ?>

                <form id="auctionForm" action="req/add-auction.php" method="POST" enctype="multipart/form-data">
                    <div class="row g-4">
                        <div class="col-md-5">
                            <div class="form-container">
                                <h5 class="section-title"><i class="fa fa-sliders me-2"></i> Auction Settings</h5>
                                
                                <div class="mb-4">
                                    <label class="form-label fw-bold">Source of Vehicle</label>
                                    <select name="source_type" id="source_type" class="form-select form-select-lg" onchange="toggleSource(this.value)">
                                        <option value="Inventory">Company Inventory</option>
                                        <option value="New">New Car Registration</option>
                                    </select>
                                </div>

                                <div id="inventory_select_div" class="mb-4 bg-light p-3 rounded-4 border border-dashed">
                                    <label class="form-label fw-bold">Select Car from Inventory</label>
                                    <select name="car_id" class="form-select">
                                        <option value="">-- Choose Car --</option>
                                        <?php 
                                        for ($i = 0; $i < count($inventory_cars); $i = $i + 1) { 
                                            $c = $inventory_cars[$i];
                                        ?>
                                            <option value="<?php echo $c['id']; ?>">
                                                <?php echo $c['brand']." ".$c['model']." (".$c['year'].") - Stock: ".$c['quantity']; ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Starting Bid ($)</label>
                                        <input type="number" id="start_price" name="start_price" class="form-control" placeholder="0.00" required min="1">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Reserve Price ($)</label>
                                        <input type="number" id="reserve_price" name="reserve_price" class="form-control" placeholder="0.00" required min="1">
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-bold">Min. Bid Increment ($)</label>
                                    <input type="number" id="min_increment" name="min_increment" class="form-control" placeholder="e.g. 50" required min="1">
                                    <small class="text-muted">The smallest amount a bid can be raised by.</small>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-bold">Auction End Date & Time</label>
                                    <input type="datetime-local" 
                                           name="end_time" 
                                           id="end_time" 
                                           class="form-control" 
                                           min="<?php echo $now; ?>" 
                                           required>
                                </div>

                                <div id="price_warning" class="alert alert-warning py-2 mb-4 border-0 shadow-sm" style="display: none; font-size: 0.85rem;">
                                    <i class="fa fa-exclamation-triangle me-2"></i> Reserve price cannot be lower than the starting bid.
                                </div>

                                <button type="submit" class="btn btn-success btn-lg w-100 fw-bold shadow-sm py-3">
                                    <i class="fa fa-paper-plane me-2"></i> LAUNCH AUCTION
                                </button>
                            </div>
                        </div>

                        <div class="col-md-7">
                            <div id="new_car_fields" class="form-container mb-4">
                                <h5 class="section-title text-success"><i class="fa fa-car me-2"></i> Vehicle Details</h5>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Brand</label>
                                        <input type="text" name="brand" class="form-control" placeholder="e.g. BMW">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Model</label>
                                        <input type="text" name="model" class="form-control" placeholder="e.g. M4">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Category</label>
                                        <select name="category_id" class="form-select">
                                            <option value="">Select Category</option>
                                            <?php for ($i = 0; $i < count($categories); $i = $i + 1) { ?>
                                                <option value="<?php echo $categories[$i]['id']; ?>">
                                                    <?php echo $categories[$i]['category_name']; ?>
                                                </option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Transmission</label>
                                        <select name="transmission" class="form-select">
                                            <?php for ($i = 0; $i < count($transmissions); $i = $i + 1) { ?>
                                                <option value="<?php echo $transmissions[$i]; ?>">
                                                    <?php echo $transmissions[$i]; ?>
                                                </option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Year</label>
                                        <input type="number" name="year" class="form-control" 
                                               min="1900" max="<?php echo $curr_year; ?>" 
                                               value="<?php echo $curr_year; ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Mileage (km)</label>
                                        <input type="number" name="mileage" class="form-control" placeholder="0">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Description</label>
                                    <textarea name="description" class="form-control" rows="3" placeholder="Tell bidders about this car..."></textarea>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Warranty Coverage</label>
                                    <div class="d-flex gap-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="regular_warranty" value="1" id="regW">
                                            <label class="form-check-label" for="regW">Regular</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="replacement_warranty" value="1" id="repW">
                                            <label class="form-check-label" for="repW">Replacement</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-0">
                                    <label class="form-label fw-bold">Vehicle Image</label>
                                    <input type="file" name="car_image" class="form-control">
                                </div>
                            </div>
                            
                            <div id="info_placeholder" class="compact-guide">
                                <h5 class="section-title"><i class="fa fa-info-circle me-2"></i> Operational Guide</h5>
                                
                                <div class="guide-item">
                                    <i class="fa fa-check-circle"></i>
                                    <div>
                                        <strong class="d-block">Inventory Management</strong>
                                        <span class="text-muted small">Selecting a car from inventory will automatically lock 1 unit from the showroom stock.</span>
                                    </div>
                                </div>

                                <div class="guide-item">
                                    <i class="fa fa-shield"></i>
                                    <div>
                                        <strong class="d-block">Reserve Price Protection</strong>
                                        <span class="text-muted small">If the final bid is lower than the reserve, the car won't be sold. Ensure it covers your base costs.</span>
                                    </div>
                                </div>

                                <div class="guide-item">
                                    <i class="fa fa-history"></i>
                                    <div>
                                        <strong class="d-block">Auto-Return Policy</strong>
                                        <span class="text-muted small">If an auction ends with 0 bids, the unit is automatically returned to the showroom inventory.</span>
                                    </div>
                                </div>

                                <div class="guide-item">
                                    <i class="fa fa-line-chart"></i>
                                    <div>
                                        <strong class="d-block">Incremental Logic</strong>
                                        <span class="text-muted small">Min. Increment ensures bids rise steadily. Recommended: 1-5% of starting price.</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('auctionForm').onsubmit = function() {
            var start = parseInt(document.getElementById('start_price').value);
            var reserve = parseInt(document.getElementById('reserve_price').value);
            var warningDiv = document.getElementById('price_warning');

            if (reserve < start) {
                warningDiv.style.display = 'block';
                window.scrollTo({ top: 0, behavior: 'smooth' });
                return false;
            }
            
            warningDiv.style.display = 'none';
            return true;
        };

        function updateMinTime() {
            var now = new Date();
            var year = now.getFullYear();
            var month = (now.getMonth() + 1).toString().padStart(2, '0');
            var day = now.getDate().toString().padStart(2, '0');
            var hours = now.getHours().toString().padStart(2, '0');
            var minutes = now.getMinutes().toString().padStart(2, '0');
            var formattedTime = year + '-' + month + '-' + day + 'T' + hours + ':' + minutes;
            document.getElementById('end_time').min = formattedTime;
        }
        setInterval(updateMinTime, 60000);
        updateMinTime();

        function toggleSource(val) {
            var newFields = document.getElementById('new_car_fields');
            var inventoryDiv = document.getElementById('inventory_select_div');

            if (val == 'New') {
                newFields.style.display = 'block';
                inventoryDiv.style.display = 'none';
            } else {
                newFields.style.display = 'none';
                inventoryDiv.style.display = 'block';
            }
        }
    </script>
</body>
</html>
<?php 
} else {
    header("Location: ../login.php");
    exit;
} 
?>