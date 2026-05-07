<?php 
session_start();

// Ensure user is logged in and has the Employee role
if (isset($_SESSION['user_id']) && 
    isset($_SESSION['role'])    && 
    $_SESSION['role'] == 'Employee') { 
    
    include "../DB_connection.php";

    /* --- FETCH REPAIR DATA --- */
    $sql = "SELECT car_repairs.*, customer.fname, customer.lname 
            FROM car_repairs 
            JOIN customer ON car_repairs.customer_id = customer.user_id 
            ORDER BY car_repairs.repair_id DESC";
            
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $all_repairs = $stmt->fetchAll();

    /* --- INITIALIZE STAT COUNTERS --- */
    $pending_count = 0;
    $active_jobs_count = 0;
    $ready_count = 0;
    $rejected_count = 0;
    $completed_count = 0;
    $total_revenue = 0;

    // Standard for loop as requested
    for ($i = 0; $i < count($all_repairs); $i = $i + 1) {
        $status = $all_repairs[$i]['status'];

        // Active Jobs are those not Completed and not Rejected
        if ($status != 'Completed' && $status != 'Rejected') {
            $active_jobs_count = $active_jobs_count + 1;
        }

        if ($status == 'Pending') {
            $pending_count = $pending_count + 1;
        }

        if ($status == 'Ready') {
            $ready_count = $ready_count + 1;
        }

        if ($status == 'Rejected') {
            $rejected_count = $rejected_count + 1;
        }

        if ($status == 'Completed') {
            $completed_count = $completed_count + 1;
        }

        // Only add to total revenue if the job is NOT Rejected
        if ($status != 'Rejected') {
            $total_revenue = $total_revenue + $all_repairs[$i]['labor_fee'] + $all_repairs[$i]['parts_total'];
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Workshop - Staff Console</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        body { background-color: #f4f7f6; font-family: 'Segoe UI', sans-serif; }
        .stats-container { background: #fff; border-radius: 15px; padding: 25px; margin-bottom: 30px; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .stat-box { border-right: 1px solid #eee; }
        .stat-box:last-child { border-right: none; }
        
        /* Progress Tracker */
        .progress-track { display: flex; justify-content: space-between; position: relative; width: 220px; margin: 0; }
        .progress-track::before { content: ""; position: absolute; top: 11px; left: 0; height: 2px; width: 100%; background: #dee2e6; z-index: 1; }
        
        .step { z-index: 2; text-align: center; width: 16%; position: relative; }
        .step-node { 
            width: 22px; height: 22px; background: #fff; border: 2px solid #dee2e6; border-radius: 50%; 
            display: inline-flex; align-items: center; justify-content: center; 
            font-size: 9px; color: #adb5bd; transition: all 0.3s; margin-bottom: 2px;
        }
        
        .step.active .step-node { border-color: #000; background: #000; color: #fff; box-shadow: 0 0 0 3px rgba(0,0,0,0.1); }
        .step.done .step-node { border-color: #198754; background: #198754; color: #fff; }
        .step.rejected .step-node { border-color: #dc3545; background: #dc3545; color: #fff; }
        
        .step-text { font-size: 7px; text-transform: uppercase; color: #adb5bd; display: block; letter-spacing: 0.2px; font-weight: 600; }
        .step.active .step-text { color: #212529; font-weight: 800; }
        .step.rejected .step-text { color: #dc3545; font-weight: 800; }

        .table-card { background: #fff; border-radius: 15px; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.05); overflow: hidden; }
        .thead-darker { background: #212529; color: #fff; }
        .car-thumb { width: 45px; height: 45px; object-fit: cover; border-radius: 8px; }
        .financial-text { font-size: 11px; line-height: 1.2; }

        /* Column width management */
        .col-id { width: 8%; }
        .col-vehicle { width: 22%; }
        .col-progress { width: 23%; }
        .col-status { width: 12%; }
        .col-finance { width: 15%; }
        .col-actions { width: 20%; }
    </style>
</head>
<body>

    <?php include "inc/navbar.php"; ?>

    <div class="container-fluid mt-4 px-lg-5">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-0">Workshop Command Center</h2>
                <p class="text-muted small">Manage lifecycle from Booking to Customer Pickup</p>
            </div>
            <div>
                <a href="index.php" class="btn btn-outline-dark px-4 py-2 fw-bold me-2">
                    <i class="fa fa-chevron-left me-2"></i>Back to Dashboard
                </a>
                <button class="btn btn-dark px-4 py-2 fw-bold" onclick="window.location.reload();">
                    <i class="fa fa-refresh me-2"></i>Refresh
                </button>
            </div>
        </div>

        <div class="stats-container">
            <div class="row text-center">
                <div class="col-md-2 stat-box">
                    <small class="text-uppercase text-muted fw-bold">Active Jobs</small>
                    <h3 class="fw-bold mb-0 mt-1"><?php echo $active_jobs_count; ?></h3>
                </div>
                <div class="col-md-2 stat-box text-warning">
                    <small class="text-uppercase text-muted fw-bold">Pending Approval</small>
                    <h3 class="fw-bold mb-0 mt-1"><?php echo $pending_count; ?></h3>
                </div>
                <div class="col-md-2 stat-box text-primary">
                    <small class="text-uppercase text-muted fw-bold">Ready for Pickup</small>
                    <h3 class="fw-bold mb-0 mt-1"><?php echo $ready_count; ?></h3>
                </div>
                <div class="col-md-2 stat-box text-success">
                    <small class="text-uppercase text-muted fw-bold">Completed</small>
                    <h3 class="fw-bold mb-0 mt-1"><?php echo $completed_count; ?></h3>
                </div>
                <div class="col-md-2 stat-box text-danger">
                    <small class="text-uppercase text-muted fw-bold">Rejected</small>
                    <h3 class="fw-bold mb-0 mt-1"><?php echo $rejected_count; ?></h3>
                </div>
                <div class="col-md-2 stat-box text-success">
                    <small class="text-uppercase text-muted fw-bold">Total Value</small>
                    <h3 class="fw-bold mb-0 mt-1">$<?php echo number_format($total_revenue, 2); ?></h3>
                </div>
            </div>
        </div>

        <div class="table-card">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="thead-darker">
                        <tr>
                            <th class="ps-4 col-id">Job ID</th>
                            <th class="col-vehicle">Customer & Vehicle</th>
                            <th class="col-progress">Lifecycle Progress</th>
                            <th class="col-status">Current State</th>
                            <th class="col-finance">Financial Breakdown</th>
                            <th class="text-end pe-4 col-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($all_repairs) > 0) { 
                            for ($i = 0; $i < count($all_repairs); $i = $i + 1) { 
                                $r = $all_repairs[$i];
                                $status = $r['status'];

                                // Node 1: Booked
                                $s1 = ($status == "Rejected") ? "rejected" : "done"; 
                                $label1 = ($status == "Rejected") ? "Rejected" : "Booked";
                                $icon1 = ($status == "Rejected") ? "fa-times" : "fa-calendar";

                                // Step Nodes Logic
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
                        <tr>
                            <td class="ps-4 fw-bold text-muted">#<?php echo $r['repair_id']; ?></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <img src="../uploads/<?php echo $r['car_image']; ?>" class="car-thumb me-3" onerror="this.src='https://via.placeholder.com/100'">
                                    <div>
                                        <div class="fw-bold text-dark" style="font-size: 14px;"><?php echo $r['fname'] . " " . $r['lname']; ?></div>
                                        <div class="text-muted small"><?php echo $r['brand'] . " " . $r['model']; ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="progress-track">
                                    <div class="step <?php echo $s1; ?>">
                                        <div class="step-node"><i class="fa <?php echo $icon1; ?>"></i></div>
                                        <span class="step-text"><?php echo $label1; ?></span>
                                    </div>

                                    <div class="step <?php echo $s2; ?>">
                                        <div class="step-node"><i class="fa fa-search"></i></div>
                                        <span class="step-text">Review</span>
                                    </div>

                                    <div class="step <?php echo $s3; ?>">
                                        <div class="step-node"><i class="fa fa-usd"></i></div>
                                        <span class="step-text">Payment</span>
                                    </div>

                                    <div class="step <?php echo $s4; ?>">
                                        <div class="step-node"><i class="fa fa-wrench"></i></div>
                                        <span class="step-text">Repair</span>
                                    </div>

                                    <div class="step <?php echo $s5; ?>">
                                        <div class="step-node"><i class="fa fa-car"></i></div>
                                        <span class="step-text">Ready</span>
                                    </div>

                                    <div class="step <?php echo $s6; ?>">
                                        <div class="step-node"><i class="fa fa-handshake-o"></i></div>
                                        <span class="step-text">Pickup</span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-dark text-white fw-bold text-uppercase" style="font-size: 9px; letter-spacing: 0.5px;">
                                    <?php echo $status; ?>
                                </span>
                            </td>
                            <td>
                                <div class="financial-text">
                                    <?php if ($status == 'Rejected') { ?>
                                        <div class="text-danger fw-bold">Rejected</div>
                                        <div class="text-muted fw-bold">Total: $0.00</div>
                                    <?php } else { ?>
                                        <div class="text-muted">Labor: <span class="text-dark fw-bold">$<?php echo number_format($r['labor_fee'], 2); ?></span></div>
                                        <div class="text-muted">Parts: <span class="text-dark fw-bold">$<?php echo number_format($r['parts_total'], 2); ?></span></div>
                                        <div class="text-success fw-bold" style="font-size: 13px;">Total: $<?php echo number_format($r['labor_fee'] + $r['parts_total'], 2); ?></div>
                                    <?php } ?>
                                </div>
                            </td>
                            <td class="text-end pe-4">
                                <a href="repair-edit.php?id=<?php echo $r['repair_id']; ?>" class="btn btn-outline-dark btn-sm fw-bold px-3">
                                    Manage
                                </a>
                            </td>
                        </tr>
                        <?php } } else { ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">No repairs in queue.</td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
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