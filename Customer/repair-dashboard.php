<?php 
session_start();

if (isset($_SESSION['user_id']) && 
    isset($_SESSION['role'])    && 
    $_SESSION['role'] == 'Customer') {
    
    include "../DB_connection.php";
    $u_id = $_SESSION['user_id'];

    $sql = "SELECT * FROM car_repairs WHERE customer_id = ? ORDER BY repair_id DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$u_id]);
    $repairs = $stmt->fetchAll();

    $active_count = 0;
    $completed_count = 0;
    $rejected_count = 0;
    $total_investment = 0;
    
    // Using complete for loop as requested
    for ($i = 0; $i < count($repairs); $i = $i + 1) {
        
        $status = $repairs[$i]['status'];

        // If rejected, the cost investment for the customer is 0
        if ($status != 'Rejected') {
            $total_investment = $total_investment + ($repairs[$i]['labor_fee'] + $repairs[$i]['parts_total']);
        }

        // Count rejected separately
        if ($status == 'Rejected') {
            $rejected_count = $rejected_count + 1;
        }

        // Count completed
        if ($status == 'Completed') {
            $completed_count = $completed_count + 1;
        }
        
        // Active Jobs are those not Completed and not Rejected
        if ($status != 'Completed' && $status != 'Rejected') {
            $active_count = $active_count + 1;
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Service Dashboard - Rev Nation</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    
    <style>
        body { background-color: #f4f7f6; color: #212529; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        
        /* Dashboard Header Styles */
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

        .repair-card { border: none; border-radius: 12px; background: #fff; margin-bottom: 12px; transition: 0.2s; }
        .repair-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        
        .spec-badge { background: #f8f9fa; color: #6c757d; font-size: 10px; padding: 2px 8px; border-radius: 4px; border: 1px solid #eee; font-weight: 600; }
        .price-tag { font-size: 1.1rem; font-weight: 800; color: #198754; letter-spacing: -0.5px; }

        /* Compact Progress System - Updated for 6 nodes */
        .progress-container { display: flex; justify-content: space-between; margin-top: 15px; position: relative; max-width: 450px; }
        .progress-line { position: absolute; top: 10px; left: 5%; right: 5%; height: 2px; background: #e9ecef; z-index: 1; }
        .step { z-index: 2; text-align: center; width: 16%; position: relative; }
        .step-icon { 
            width: 22px; height: 22px; border-radius: 50%; background: #fff; border: 2px solid #dee2e6;
            display: inline-flex; align-items: center; justify-content: center; 
            font-size: 9px; margin-bottom: 3px; color: #adb5bd; transition: 0.3s;
        }
        
        .step.active .step-icon { border-color: #212529; background: #212529; color: white; box-shadow: 0 0 0 3px rgba(0,0,0,0.1); }
        .step.done .step-icon { border-color: #198754; background: #198754; color: white; }
        .step.rejected .step-icon { border-color: #dc3545; background: #dc3545; color: white; }
        
        .step-text { font-size: 8px; text-transform: uppercase; color: #adb5bd; display: block; letter-spacing: 0.3px; font-weight: 600; }
        .step.active .step-text { color: #212529; font-weight: 800; }
        .step.rejected .step-text { color: #dc3545; font-weight: 800; }

        .technician-note { 
            background-color: #f8f9fa; 
            border-left: 3px solid #212529; 
            padding: 6px 10px; 
            border-radius: 4px; 
            font-size: 0.8rem;
            margin-top: 8px;
            color: #495057;
        }
        .img-compact { height: 90px; width: 100%; object-fit: cover; border-radius: 6px; border: 1px solid #eee; }
        
        .stat-card { background: #fff; border-radius: 12px; border: none; }
    </style>
</head>
<body>

    <?php include "inc/navbar.php"; ?>

    <div class="container mt-4">
        
        <div class="row mb-4 align-items-center">
            <div class="col-md-7">
                <div class="d-flex align-items-center">
                    <div class="repair-logo-badge me-3">
                        <i class="fa fa-wrench fa-lg"></i>
                    </div>
                    <div>
                        <h3 class="fw-bold mb-0">Service and Repairs Dashboard</h3>
                        <p class="text-muted small mb-0">Track your vehicle maintenance and performance upgrades.</p>
                    </div>
                </div>
            </div>

            <div class="col-md-5 text-md-end mt-3 mt-md-0">
                <div class="d-inline-block text-start me-4 border-end pe-4">
                    <small class="text-muted d-block fw-bold" style="font-size: 9px; text-transform: uppercase; letter-spacing: 1px;">Total Investment</small>
                    <span class="fw-bold text-dark" style="font-size: 1.2rem;">$<?php echo number_format($total_investment, 2); ?></span>
                </div>
                <a href="repair-add.php" class="btn btn-dark btn-sm fw-bold px-4 py-2 rounded-pill shadow-sm">
                    <i class="fa fa-plus-circle me-1"></i> Book Service
                </a>
            </div>
        </div>

        <hr class="mb-4" style="opacity: 0.1;">

        <div class="row">
            <div class="col-md-3">
                <div class="card stat-card shadow-sm p-3 mb-3">
                    <h6 class="fw-bold small mb-3 text-uppercase" style="letter-spacing: 1px;">My Garage Stats</h6>
                    
                    <div class="d-flex justify-content-between mb-2 small">
                        <span class="text-muted"><i class="fa fa-clock-o me-2"></i>Active Jobs</span>
                        <span class="fw-bold text-primary"><?php echo $active_count; ?></span>
                    </div>

                    <div class="d-flex justify-content-between mb-2 small">
                        <span class="text-muted"><i class="fa fa-check-circle-o me-2"></i>Completed</span>
                        <span class="fw-bold text-success"><?php echo $completed_count; ?></span>
                    </div>

                    <div class="d-flex justify-content-between small">
                        <span class="text-muted"><i class="fa fa-times-circle-o me-2"></i>Rejected</span>
                        <span class="fw-bold text-danger"><?php echo $rejected_count; ?></span>
                    </div>

                    <hr class="my-3">

                    <div class="d-grid mb-3">
                         <a href="index.php" class="btn btn-outline-dark btn-sm fw-bold">
                             <i class="fa fa-home me-1"></i> Back to Home
                         </a>
                    </div>
                    
                    <div class="bg-light p-2 rounded">
                        <p class="mb-0" style="font-size: 10px; color: #6c757d; line-height: 1.4;">
                            View real-time status updates from our technicians as they work on your vehicle.
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-md-9">
                <?php 
                if (count($repairs) > 0) {
                    
                    for ($i = 0; $i < count($repairs); $i = $i + 1) {
                        
                        $r = $repairs[$i];
                        $status = $r['status'];
                        
                        // Financial logic
                        if ($status == 'Rejected') {
                            $total_bill = 0;
                        } else {
                            $total_bill = $r['labor_fee'] + $r['parts_total'];
                        }

                        // Node Logic Matching Manager's progress tracking
                        // Step 1: Logic for Booked/Rejected
                        $s1 = ($status == "Rejected") ? "rejected" : "done"; 
                        $label1 = ($status == "Rejected") ? "Rejected" : "Booked";
                        $icon1 = ($status == "Rejected") ? "fa-times" : "fa-calendar";

                        // Hide progress if rejected, otherwise map normally
                        if ($status == "Rejected") {
                            $s2 = $s3 = $s4 = $s5 = $s6 = "";
                        } else {
                            $s2 = ($status != "Pending") ? "done" : "active";
                            
                            $s3 = "";
                            if ($status == "Awaiting Payment") { $s3 = "active"; } 
                            else if ($status == "Repairing" || $status == "Ready" || $status == "Completed") { $s3 = "done"; }

                            $s4 = "";
                            if ($status == "Repairing") { $s4 = "active"; } 
                            else if ($status == "Ready" || $status == "Completed") { $s4 = "done"; }

                            $s5 = "";
                            if ($status == "Ready") { $s5 = "active"; } 
                            else if ($status == "Completed") { $s5 = "done"; }

                            $s6 = ($status == "Completed") ? "done" : "";
                        }
                ?>

                    <div class="card repair-card shadow-sm">
                        <div class="card-body p-3">
                            <div class="row g-2 align-items-center">
                                
                                <div class="col-md-2 text-center">
                                    <img src="../uploads/<?php echo $r['car_image']; ?>" 
                                         class="img-compact mb-1" 
                                         onerror="this.src='https://via.placeholder.com/150?text=Vehicle'">
                                    <div class="spec-badge d-inline-block">ID #<?php echo $r['repair_id']; ?></div>
                                </div>

                                <div class="col-md-7 px-3">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <h6 class="fw-bold mb-0"><?php echo $r['brand']; ?> <?php echo $r['model']; ?></h6>
                                        <span class="text-muted" style="font-size: 11px;">(<?php echo $r['year']; ?>)</span>
                                    </div>
                                    <div class="text-muted mb-2" style="font-size: 11px;">
                                        <i class="fa fa-tachometer me-1"></i> <?php echo number_format($r['mileage']); ?> KM
                                    </div>

                                    <div class="progress-container">
                                        <div class="progress-line"></div>
                                        
                                        <div class="step <?php echo $s1; ?>">
                                            <div class="step-icon"><i class="fa <?php echo $icon1; ?>"></i></div>
                                            <span class="step-text"><?php echo $label1; ?></span>
                                        </div>

                                        <div class="step <?php echo $s2; ?>">
                                            <div class="step-icon"><i class="fa fa-search"></i></div>
                                            <span class="step-text">Review</span>
                                        </div>

                                        <div class="step <?php echo $s3; ?>">
                                            <div class="step-icon"><i class="fa fa-usd"></i></div>
                                            <span class="step-text">Payment</span>
                                        </div>

                                        <div class="step <?php echo $s4; ?>">
                                            <div class="step-icon"><i class="fa fa-wrench"></i></div>
                                            <span class="step-text">Repair</span>
                                        </div>

                                        <div class="step <?php echo $s5; ?>">
                                            <div class="step-icon"><i class="fa fa-car"></i></div>
                                            <span class="step-text">Ready</span>
                                        </div>

                                        <div class="step <?php echo $s6; ?>">
                                            <div class="step-icon"><i class="fa fa-handshake-o"></i></div>
                                            <span class="step-text">Picked Up</span>
                                        </div>
                                    </div>

                                    <?php if ($r['repair_notes'] != "") { ?>
                                        <div class="technician-note">
                                            <strong>Tech Log:</strong> <?php echo $r['repair_notes']; ?>
                                        </div>
                                    <?php } ?>
                                </div>

                                <div class="col-md-3 text-end border-start ps-3">
                                    <small class="text-muted d-block" style="font-size: 10px;">Total Bill</small>
                                    <div class="price-tag mb-2">$<?php echo number_format($total_bill, 2); ?></div>

                                    <div class="d-grid gap-1">
                                        <?php if ($status == 'Awaiting Payment') { ?>
                                            <a href="repair-transaction.php?id=<?php echo $r['repair_id']; ?>&action=pay_full" 
                                               class="btn btn-primary btn-sm fw-bold">Proceed to Payment</a>
                                        <?php } else { ?>
                                            <span class="badge bg-light text-dark border py-2 mb-1" style="font-size: 10px; font-weight: 700;">
                                                <?php echo strtoupper($status); ?>
                                            </span>
                                        <?php } ?>
                                        
                                        <a href="repair-view.php?id=<?php echo $r['repair_id']; ?>" class="btn btn-outline-dark btn-sm py-1" style="font-size: 11px; font-weight: 600;">
                                            VIEW DETAILS
                                        </a>

                                        <?php if ($status == 'Pending') { ?>
                                            <a href="req/repair-cancel.php?id=<?php echo $r['repair_id']; ?>" 
                                               class="btn btn-danger btn-sm py-1 mt-1" 
                                               style="font-size: 11px; font-weight: 600;">
                                                CANCEL REQUEST
                                            </a>
                                        <?php } ?>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                <?php 
                    } 
                } else { ?>
                    <div class="card border-0 shadow-sm p-5 text-center" style="border-radius: 12px;">
                        <i class="fa fa-wrench fa-3x text-light mb-3"></i>
                        <h5 class="text-muted">No service history found.</h5>
                        <p class="small text-muted">Book your first repair or maintenance check-up today.</p>
                        <div class="mt-2">
                            <a href="repair-add.php" class="btn btn-dark btn-sm rounded-pill px-4">New Request</a>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>

</body>
</html>

<?php 
} else {
    header("Location: ../login.php");
    exit;
} 
?>