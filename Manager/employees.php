<?php 
session_start();
if (isset($_SESSION['user_id']) && $_SESSION['role'] == 'Manager') {
    include "../DB_connection.php";

    // FETCHING ALL DATA INCLUDING PHONE NUMBER
    $sql = "SELECT u.uid, u.username, e.employee_id, e.fname, e.lname, e.salary, e.email_address, e.phone_number 
            FROM users u 
            JOIN employees e ON u.uid = e.user_id 
            WHERE u.user_type = 'employee' 
            ORDER BY e.employee_id DESC";
            
    $stmt = $conn->query($sql);
    $staff = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Staff Control | Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        body { background-color: #f4f7f6; }
        .card { border-radius: 15px; border: none; }
        .header-gradient { background: linear-gradient(45deg, #000000, #808080); color: white; border-radius: 15px 15px 0 0; }
        .btn-confirm { background: #000000; color: white; border: none; transition: 0.3s; }
        .btn-confirm:hover { background: #333333; color: white; }
        .table thead { background: #f8f9fa; }
        .badge-username { background-color: #e9ecef; color: #495057; font-weight: 600; padding: 5px 10px; border-radius: 5px; display: inline-block; margin-top: 5px; }
    </style>
</head>
<body>
    <?php include "inc/navbar.php"; ?>

    <div class="container mt-5">
        <?php if (isset($_GET['error'])) { ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fa fa-exclamation-triangle me-2"></i><?= $_GET['error'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php } ?>
        
        <?php if (isset($_GET['success'])) { ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fa fa-check-circle me-2"></i><?= $_GET['success'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php } ?>

        <div class="row">
            <div class="col-lg-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-header header-gradient py-3">
                        <h5 class="mb-0"><i class="fa fa-user-plus me-2"></i>Register Staff</h5>
                    </div>
                    <div class="card-body p-4">
                        <form action="req/employee-ops.php" method="post">
                            <input type="hidden" name="action" value="register">
                            
                            <div class="form-floating mb-3">
                                <input type="text" name="username" class="form-control" id="u" placeholder="Username" required>
                                <label for="u">Username</label>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="form-floating">
                                        <input type="text" name="fname" class="form-control" id="f" placeholder="First Name" required>
                                        <label for="f">First Name</label>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="form-floating">
                                        <input type="text" name="lname" class="form-control" id="l" placeholder="Last Name" required>
                                        <label for="l">Last Name</label>
                                    </div>
                                </div>
                            </div>

                            <div class="form-floating mb-3">
                                <input type="email" name="email" class="form-control" id="e" placeholder="Email" required>
                                <label for="e">Email Address</label>
                            </div>

                            <div class="form-floating mb-3">
                                <input type="text" name="phone" class="form-control" id="p" placeholder="Phone Number" required>
                                <label for="p">Phone Number</label>
                            </div>

                            <div class="form-floating mb-4">
                                <input type="number" name="salary" class="form-control" id="s" placeholder="Salary" step="0.01" min="0">
                                <label for="s">Initial Salary ($)</label>
                            </div>

                            <button type="submit" class="btn btn-confirm w-100 py-2 shadow-sm">
                                <strong>Confirm Hire</strong>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 text-dark"><i class="fa fa-id-badge me-2 text-primary"></i>Employee Roster</h5>
                        <span class="badge bg-dark text-white"><?= count($staff) ?> Total Employees</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Full Name & User</th>
                                    <th>Contact Info</th>
                                    <th>Salary Adjustment</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php for ($i = 0; $i < count($staff); $i = $i + 1) { ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="rounded-circle bg-secondary text-white d-flex justify-content-center align-items-center me-3" style="width: 45px; height: 45px;">
                                                <?= strtoupper($staff[$i]['fname'][0]) ?>
                                            </div>
                                            <div>
                                                <div class="fw-bold"><?= $staff[$i]['fname'] . " " . $staff[$i]['lname'] ?></div>
                                                <span class="badge badge-username">@<?= $staff[$i]['username'] ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="small">
                                            <div class="text-dark mb-1">
                                                <i class="fa fa-envelope-o me-2 text-muted"></i><?= $staff[$i]['email_address'] ?>
                                            </div>
                                            <div class="text-dark">
                                                <i class="fa fa-phone me-2 text-muted"></i><?= $staff[$i]['phone_number'] ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <form action="req/employee-ops.php" method="post" class="input-group input-group-sm" style="max-width: 150px;">
                                            <input type="hidden" name="action" value="update_salary">
                                            <input type="hidden" name="emp_id" value="<?= $staff[$i]['employee_id'] ?>">
                                            <span class="input-group-text">$</span>
                                            <input type="number" name="new_salary" class="form-control" value="<?= $staff[$i]['salary'] ?>" min="0">
                                            <button class="btn btn-outline-success"><i class="fa fa-save"></i></button>
                                        </form>
                                    </td>
                                    <td class="text-center">
                                        <a href="req/employee-ops.php?delete_id=<?= $staff[$i]['uid'] ?>" 
                                           class="btn btn-sm btn-outline-danger">
                                            <i class="fa fa-user-times"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php } else { header("Location: ../login.php"); exit; } ?>