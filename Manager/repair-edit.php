<?php 
session_start();

if (isset($_SESSION['user_id']) && 
    isset($_SESSION['role'])    && 
    $_SESSION['role'] == 'Manager') { 
    
    include "../DB_connection.php";

    if (!isset($_GET['id'])) {
        header("Location: repair-house.php");
        exit;
    }

    $repair_id = $_GET['id'];

    // Join on customer table to get names and email
    $sql = "SELECT car_repairs.*, customer.fname, customer.lname, customer.email_address 
            FROM car_repairs 
            JOIN customer ON car_repairs.customer_id = customer.user_id 
            WHERE repair_id = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->execute([$repair_id]);
    $repair = $stmt->fetch();

    if (!$repair) {
        header("Location: repair-house.php");
        exit;
    }

    // --- FETCH CURRENTLY SAVED PARTS ---
    $saved_parts_sql = "SELECT product_id FROM repair_parts_selected WHERE repair_id = ?";
    $saved_parts_stmt = $conn->prepare($saved_parts_sql);
    $saved_parts_stmt->execute([$repair_id]);
    $saved_parts_results = $saved_parts_stmt->fetchAll();
    
    // Store IDs in a simple array for easy checking
    $saved_part_ids = [];
    for ($i = 0; $i < count($saved_parts_results); $i = $i + 1) {
        $saved_part_ids[] = $saved_parts_results[$i]['product_id'];
    }

    // Default to 'Repair' if the column doesn't exist or is empty
    $repair_type = isset($repair['repair_type']) ? $repair['repair_type'] : 'Repair';

    // Fetch Categories for parts (type = 'Part')
    $cat_sql = "SELECT * FROM categories WHERE type = 'Part' ORDER BY category_name ASC";
    $cat_stmt = $conn->prepare($cat_sql);
    $cat_stmt->execute();
    $categories = $cat_stmt->fetchAll();

    // Fetch Products in stock
    $prod_sql = "SELECT * FROM products WHERE quantity > 0 ORDER BY product_name ASC";
    $prod_stmt = $conn->prepare($prod_sql);
    $prod_stmt->execute();
    $products = $prod_stmt->fetchAll();

    // Determine if the manager is allowed to edit costs/parts
    $can_edit_details = ($repair['status'] == 'Inspecting');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Job #<?php echo $repair_id; ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        body { background-color: #f4f7f6; font-family: 'Segoe UI', sans-serif; }
        .edit-card { background: #fff; border-radius: 15px; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.05); overflow: hidden; }
        .car-banner { background: #212529; color: #fff; padding: 20px; }
        .info-label { font-size: 11px; text-transform: uppercase; color: #6c757d; font-weight: bold; display: block; margin-bottom: 5px; }
        .status-box { padding: 15px; border-radius: 10px; border: 1px solid #eee; background: #fafafa; }
        input[readonly], select[disabled], textarea[readonly] { background-color: #f8f9fa !important; cursor: not-allowed; }
        
        .parts-selector { max-height: 450px; overflow-y: auto; border: 1px solid #eee; padding: 15px; border-radius: 10px; background: #fff; }
        .category-group { border-bottom: 1px solid #f0f0f0; padding-bottom: 15px; margin-bottom: 15px; }
        .category-group:last-child { border-bottom: none; }
        .part-option-container { display: flex; align-items: center; gap: 10px; margin-top: 5px; }
        .product-thumb { width: 40px; height: 40px; object-fit: cover; border-radius: 5px; border: 1px solid #ddd; }
    </style>
</head>
<body>

    <?php include "inc/navbar.php"; ?>

    <div class="container mt-5 mb-5">
        <a href="repair-house.php" class="btn btn-sm btn-secondary mb-3">
            <i class="fa fa-arrow-left me-2"></i>Back to Workshop
        </a>
        
        <div class="edit-card shadow-sm">
            <div class="car-banner d-flex align-items-center">
                <img src="../uploads/<?php echo $repair['car_image']; ?>" width="70" height="70" class="rounded me-3" style="object-fit: cover;" onerror="this.src='https://via.placeholder.com/70'">
                <div>
                    <h4 class="mb-0 fw-bold"><?php echo $repair['brand'] . " " . $repair['model']; ?> (<?php echo $repair['year']; ?>)</h4>
                    <p class="mb-0 opacity-75 small">Customer: <?php echo $repair['fname'] . " " . $repair['lname']; ?> | Job ID: #<?php echo $repair_id; ?></p>
                </div>
            </div>

            <div class="card-body p-4">
                <div class="row">
                    <div class="col-md-6 border-end">
                        <div class="mb-4">
                            <label class="info-label">Issue Reported by Customer</label>
                            <div class="p-3 bg-light rounded border" style="min-height: 80px; font-size: 14px;">
                                <?php echo nl2br($repair['issue_description']); ?>
                            </div>
                        </div>

                        <div class="status-box mb-4">
                            <div class="row">
                                <div class="col-6">
                                    <label class="info-label">Request Type</label>
                                    <span class="badge bg-secondary"><?php echo $repair_type; ?></span>
                                </div>
                                <div class="col-6">
                                    <label class="info-label">Current Status</label>
                                    <span class="text-primary fw-bold"><?php echo $repair['status']; ?></span>
                                </div>
                            </div>
                        </div>

                        <?php if ($repair_type == 'Repair' && ($repair['status'] == 'Inspecting' || $repair['status'] == 'Repairing' || $repair['status'] == 'Awaiting Payment' || $repair['status'] == 'Ready')) { ?>
                            <label class="info-label">Parts Required/Selected</label>
                            <div class="parts-selector">
                                <?php for ($i = 0; $i < count($categories); $i = $i + 1) { 
                                    $category_img = "../uploads/" . $categories[$i]['category_img'];
                                    
                                    $current_cat_img = $category_img;
                                    for ($k = 0; $k < count($products); $k = $k + 1) {
                                        if ($products[$k]['category_id'] == $categories[$i]['id'] && in_array($products[$k]['id'], $saved_part_ids)) {
                                            $current_cat_img = "../uploads/" . $products[$k]['image'];
                                        }
                                    }
                                ?>
                                    <div class="category-group">
                                        <label class="small fw-bold text-dark"><?php echo $categories[$i]['category_name']; ?></label>
                                        
                                        <div class="part-option-container">
                                            <select name="parts[]" 
                                                    form="mainUpdateForm"
                                                    class="form-select form-select-sm part-item" 
                                                    data-default-img="<?php echo $category_img; ?>" 
                                                    onchange="updateImage(this)"
                                                    <?php echo (!$can_edit_details) ? 'disabled' : ''; ?>>
                                                <option value="" data-price="0" data-img="<?php echo $category_img; ?>">-- No selection --</option>
                                                <?php for ($j = 0; $j < count($products); $j = $j + 1) { 
                                                    if ($products[$j]['category_id'] == $categories[$i]['id']) { 
                                                        $is_selected = in_array($products[$j]['id'], $saved_part_ids) ? 'selected' : '';
                                                    ?>
                                                        <option value="<?php echo $products[$j]['id']; ?>" 
                                                                data-price="<?php echo $products[$j]['price']; ?>"
                                                                data-img="../uploads/<?php echo $products[$j]['image']; ?>"
                                                                <?php echo $is_selected; ?>>
                                                            <?php echo $products[$j]['product_name']; ?> 
                                                            ($<?php echo number_format($products[$j]['price'], 2); ?>) 
                                                        </option>
                                                <?php } } ?>
                                            </select>
                                            <img src="<?php echo $current_cat_img; ?>" class="product-thumb preview-img">
                                        </div>
                                    </div>
                                <?php } ?>
                                
                                <?php if (!$can_edit_details) { ?>
                                    <p class="text-danger small mt-2"><i class="fa fa-lock me-1"></i> Parts selection is locked after invoice is sent.</p>
                                <?php } ?>
                            </div>
                        <?php } ?>
                    </div>

                    <div class="col-md-6 ps-md-4">
                        <form id="mainUpdateForm" action="req/repair-update.php" method="post">
                            <input type="hidden" name="repair_id" value="<?php echo $repair_id; ?>">

                            <?php if ($repair['status'] == 'Pending') { ?>
                                <div class="alert alert-info small">
                                    <i class="fa fa-info-circle me-2"></i>
                                    <strong>Pending Approval:</strong> Review the request details. Accepting will move this to the Inspection phase.
                                </div>
                                <button type="submit" name="action" value="accept" class="btn btn-dark w-100 py-2 mb-2 fw-bold">Accept Request</button>
                                <button type="submit" name="action" value="reject" class="btn btn-outline-danger w-100 py-2 fw-bold">Reject Request</button>

                            <?php } else if ($repair['status'] == 'Inspecting' || $repair['status'] == 'Repairing') { ?>
                                <div class="mb-3">
                                    <label class="form-label fw-bold small">Technician/Manager Notes</label>
                                    <textarea name="repair_notes" class="form-control" rows="4" placeholder="Detail the work performed..." <?php echo ($repair['status'] == 'Repairing') ? 'readonly' : ''; ?>><?php echo $repair['repair_notes']; ?></textarea>
                                </div>

                                <div class="row g-2 mb-4">
                                    <div class="col-6">
                                        <label class="info-label">Labor Fee ($)</label>
                                        <input type="number" step="0.01" min = 0 name="labor_fee" class="form-control" value="<?php echo $repair['labor_fee']; ?>" <?php echo ($repair_type == 'Service' || !$can_edit_details) ? 'readonly' : ''; ?> readonly>
                                    </div>
                                    <div class="col-6">
                                        <label class="info-label">Parts Total ($)</label>
                                        <input type="number" step="0.01" id="parts_total_display" name="parts_total" class="form-control" value="<?php echo $repair['parts_total']; ?>" readonly>
                                    </div>
                                </div>

                                <?php if ($repair['status'] == 'Inspecting') { ?>
                                    <div class="alert alert-warning small">
                                        <i class="fa fa-warning me-2"></i>Verify costs before sending the final invoice to the customer.
                                    </div>
                                    <button type="submit" name="action" value="request_payment" class="btn btn-warning w-100 py-2 fw-bold">Send Invoice to Customer</button>
                                <?php } else { ?>
                                    <div class="alert alert-info small">
                                        <i class="fa fa-cog fa-spin me-2"></i>Customer has paid. Proceed with repairs.
                                    </div>
                                    <button type="submit" name="action" value="set_ready" class="btn btn-success w-100 py-2 fw-bold">Mark Job as Ready</button>
                                <?php } ?>

                            <?php } else if ($repair['status'] == 'Awaiting Payment') { ?>
                                <div class="text-center py-5">
                                    <i class="fa fa-credit-card fa-3x text-muted mb-3"></i>
                                    <h5 class="fw-bold">Awaiting Payment</h5>
                                    <p class="text-muted small">The customer is reviewing the invoice.</p>
                                    <p class="text-dark fw-bold">Total Due: $<?php echo number_format($repair['labor_fee'] + $repair['parts_total'], 2); ?></p>
                                </div>

                            <?php } else if ($repair['status'] == 'Ready') { ?>
                                <div class="alert alert-success small">
                                    <i class="fa fa-check-circle me-2"></i>Work finished. Confirm when the customer picks up the vehicle.
                                </div>
                                <button type="submit" name="action" value="finalize" class="btn btn-primary w-100 py-2 fw-bold">Confirm Pick-up</button>

                            <?php } else if ($repair['status'] == 'Rejected') { ?>
                                <div class="text-center py-5">
                                    <i class="fa fa-ban fa-3x text-danger mb-3"></i>
                                    <h5 class="fw-bold text-danger">Request Rejected</h5>
                                </div>
                            <?php } else if ($repair['status'] == 'Canceled') { ?>
                                <div class="text-center py-5">
                                    <i class="fa fa-times-circle fa-3x text-secondary mb-3"></i>
                                    <h5 class="fw-bold text-secondary">Repair Canceled</h5>
                                    <p class="small text-muted">This job was canceled by the manager.</p>
                                </div>
                            <?php } else { ?>
                                <div class="text-center py-5">
                                    <i class="fa fa-handshake-o fa-3x text-success mb-3"></i>
                                    <h5 class="fw-bold text-success">Job Completed</h5>
                                    <p class="text-muted small">Vehicle returned to customer.</p>
                                </div>
                            <?php } ?>
                        </form>

                        <?php 
                        // UPDATED: Removed 'Pending' from allowed cancel statuses because we have Reject
                        $can_cancel = ['Inspecting', 'Awaiting Payment'];
                        if (in_array($repair['status'], $can_cancel)) { 
                        ?>
                            <hr class="my-4">
                            <form action="req/repair-update.php" method="POST">
                                <input type="hidden" name="repair_id" value="<?php echo $repair_id; ?>">
                                <input type="hidden" name="action" value="cancel_repair">
                                <button type="submit" class="btn btn-sm btn-outline-danger w-100">
                                    <i class="fa fa-trash me-2"></i>Cancel Entire Repair
                                </button>
                            </form>
                        <?php } ?>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const partSelects = document.querySelectorAll('.part-item');
        const partsTotalInput = document.getElementById('parts_total_display');

        function updateImage(selectElement) {
            const selectedOption = selectElement.options[selectElement.selectedIndex];
            let imgSrc = selectedOption.getAttribute('data-img');
            const defaultImg = selectElement.getAttribute('data-default-img');
            const imgPreview = selectElement.parentElement.querySelector('.preview-img');
            
            if (!selectElement.value) {
                imgSrc = defaultImg;
            }

            if (imgSrc) {
                imgPreview.src = imgSrc;
            }
        }

        for (let i = 0; i < partSelects.length; i = i + 1) {
            partSelects[i].addEventListener('change', function() {
                let total = 0;
                for (let j = 0; j < partSelects.length; j = j + 1) {
                    const selectedOption = partSelects[j].options[partSelects[j].selectedIndex];
                    const price = parseFloat(selectedOption.getAttribute('data-price')) || 0;
                    total = total + price;
                }
                partsTotalInput.value = total.toFixed(2);
            });
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