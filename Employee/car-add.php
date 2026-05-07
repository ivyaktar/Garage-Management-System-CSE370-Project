<?php 
session_start();

// Check for Manager or Employee role
$has_access = false;
$is_manager = false;
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] == 'Manager') {
        $has_access = true;
        $is_manager = true;
    } else if ($_SESSION['role'] == 'Employee') {
        $has_access = true;
    }
}

if ($has_access && isset($_SESSION['user_id'])) {
    
    include "../DB_connection.php";

    /* --- FETCH SETTING --- */
    $set_sql = "SELECT current_year FROM setting LIMIT 1";
    $set_stmt = $conn->prepare($set_sql);
    $set_stmt->execute();
    $setting = $set_stmt->fetch();
    $curr_year = $setting['current_year'];


    /* --- CATEGORY LOGIC (Manager Only) --- */
    if ($is_manager) {
        // 1. DELETE Category
        if (isset($_GET['delete_cat'])) {
            $id = $_GET['delete_cat'];
            $sql_del = "DELETE FROM categories WHERE id = ?";
            $stmt_del = $conn->prepare($sql_del);
            $res_del = $stmt_del->execute([$id]);

            if ($res_del) {
                header("Location: car-add.php?success=Category deleted");
                exit;
            }
        }

        // 2. UPDATE Category
        if (isset($_POST['category_id_edit']) && !empty($_POST['category_id_edit'])) {
            $id = $_POST['category_id_edit'];
            $new_name = $_POST['category_name'];
            $sql_upd = "UPDATE categories SET category_name = ? WHERE id = ?";
            $stmt_upd = $conn->prepare($sql_upd);
            $res_upd = $stmt_upd->execute([$new_name, $id]);

            if ($res_upd) {
                header("Location: car-add.php?success=Category updated");
                exit;
            }
        }
    }


    /* --- VEHICLE SAVING LOGIC --- */
    if (isset($_POST['brand'])) {
        $brand        = $_POST['brand'];
        $model        = $_POST['model'];
        $year         = $_POST['year'];
        $category_id  = $_POST['category_id'];
        $transmission = $_POST['transmission'];
        $mileage      = $_POST['mileage'];
        $price        = $_POST['price'];
        $quantity     = $_POST['quantity'];
        $status       = $_POST['status'];
        $description  = $_POST['description'];

        $max_val = 1000000000;

        if ($year < 1900 || $year > $curr_year) {
            header("Location: car-add.php?error=Year must be between 1900 and $curr_year");
            exit;
        }

        if ($price <= 0 || $price >= $max_val) {
            header("Location: car-add.php?error=Price must be between 1 and 999,999,999");
            exit;
        }

        if ($mileage <= 0 || $mileage >= $max_val) {
            header("Location: car-add.php?error=Mileage must be between 1 and 999,999,999");
            exit;
        }

        $reg_w = isset($_POST['regular_warranty']) ? 1 : 0;
        $rep_w = isset($_POST['replacement_warranty']) ? 1 : 0;

        if (isset($_FILES['car_image']) && $_FILES['car_image']['error'] === 0) {
            $img_name = $_FILES['car_image']['name'];
            $tmp_name = $_FILES['car_image']['tmp_name'];
            
            $img_ex = pathinfo($img_name, PATHINFO_EXTENSION);
            $img_ex_lc = strtolower($img_ex);
            $allowed_exs = array("jpg", "jpeg", "png");

            if (in_array($img_ex_lc, $allowed_exs)) {
                $new_img_name = "CAR-" . uniqid() . "." . $img_ex_lc;
                $img_upload_path = "../uploads/" . $new_img_name;
                move_uploaded_file($tmp_name, $img_upload_path);

                $sql = "INSERT INTO cars (brand, model, year, category_id, price, quantity, status, transmission, mileage, description, regular_warranty, replacement_warranty, image) 
                        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)";
                $stmt = $conn->prepare($sql);
                $res  = $stmt->execute([$brand, $model, $year, $category_id, $price, $quantity, $status, $transmission, $mileage, $description, $reg_w, $rep_w, $new_img_name]);

                if ($res) {
                    header("Location: car-add.php?success=Vehicle added successfully");
                    exit;
                }
            }
        }
    }


    /* --- FETCHING DATA --- */
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
    <title>Staff - Vehicle Management</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        .page-header-container { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding: 10px 0; }
        .header-left { display: flex; align-items: center; }
        .page-header-icon { font-size: 1.8rem; margin-right: 10px; color: #6c757d; }
        .page-header-title { font-weight: 600; font-size: 1.6rem; color: #333; margin: 0; }
        .cat-list { max-height: 400px; overflow-y: auto; }
    </style>
</head>
<body class="bg-light">
    <?php include "inc/navbar.php"; ?>

    <div class="container mt-5">
        <div class="page-header-container">
            <div class="header-left">
                <div class="page-header-icon"><i class="fa fa-car"></i></div>
                <h2 class="page-header-title">Vehicle Management</h2>
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


        <div class="row">
            <div class="col-md-7">
                <div class="card shadow-sm border-0 mb-5">
                    <div class="card-header bg-white fw-bold">Add New Vehicle</div>
                    <div class="card-body p-4">
                        <form action="car-add.php" method="POST" enctype="multipart/form-data">
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Brand</label>
                                    <input type="text" name="brand" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Model</label>
                                    <input type="text" name="model" class="form-control" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Category</label>
                                    <select name="category_id" class="form-select" required>
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
                                    <select name="transmission" class="form-select" required>
                                        <?php for ($i = 0; $i < count($transmissions); $i = $i + 1) { ?>
                                            <option value="<?php echo $transmissions[$i]; ?>">
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
                                           value="<?php echo $curr_year; ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Mileage (km)</label>
                                    <input type="number" name="mileage" class="form-control" 
                                           min="1" max="999999999" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Price ($)</label>
                                    <input type="number" name="price" class="form-control" 
                                           min="1" max="999999999" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Stock Quantity</label>
                                    <input type="number" name="quantity" id="quantityInput" class="form-control" value="1" oninput="validateQty()" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Initial Status</label>
                                    <select name="status" id="statusSelect" class="form-select" onchange="toggleQuantity()">
                                        <option value="In Stock">In Stock</option>
                                        <option value="Upcoming">Upcoming</option>
                                        <option value="Sold Out">Sold Out</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Description</label>
                                <textarea name="description" class="form-control" rows="3"></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Warranty Types</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="regular_warranty" value="1" id="regW" checked>
                                    <label class="form-check-label" for="regW">Regular (Repair)</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="replacement_warranty" value="1" id="repW">
                                    <label class="form-check-label" for="repW">Replacement</label>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold">Vehicle Image</label>
                                <input type="file" name="car_image" class="form-control" required>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">Save Vehicle</button>
                        </form>
                    </div>
                </div>
            </div>


            <div class="col-md-5">
                
                <?php if ($is_manager) { ?>
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white fw-bold">Manage Categories</div>
                    <div class="card-body">
                        <form action="req/category-add.php" method="POST" id="catForm">
                            <input type="hidden" name="category_id_edit" id="cat_id_edit">
                            <input type="hidden" name="type" value="Car">
                            <input type="hidden" name="back_to" value="manager/car-add.php">

                            <div class="mb-3">
                                <label class="form-label" id="catLabel">New Category Name</label>
                                <div class="input-group">
                                    <input type="text" name="category_name" id="cat_name_input" class="form-control" required>
                                    <button type="submit" class="btn btn-success" id="catBtn">Add</button>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-link d-none" id="cancelEdit" onclick="resetCatForm()">Cancel Edit</button>
                        </form>
                    </div>
                </div>
                <?php } else { ?>
                <div class="alert alert-info border-0 shadow-sm">
                    <i class="fa fa-info-circle"></i> <strong>Note:</strong> If there are errors in Category, let management know
                </div>
                <?php } ?>

                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white fw-bold">Active Car Categories</div>
                    <div class="card-body p-0">
                        <div class="table-responsive cat-list">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Name</th>
                                        <?php if ($is_manager) { ?><th class="text-end">Action</th><?php } ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php for ($i = 0; $i < count($categories); $i = $i + 1) { ?>
                                    <tr>
                                        <td><?php echo $categories[$i]['category_name']; ?></td>
                                        <?php if ($is_manager) { ?>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-warning" 
                                                    onclick="editCategory('<?php echo $categories[$i]['id']; ?>', '<?php echo $categories[$i]['category_name']; ?>')">
                                                <i class="fa fa-pencil"></i>
                                            </button>
                                            <a href="car-add.php?delete_cat=<?php echo $categories[$i]['id']; ?>" 
                                               class="btn btn-sm btn-danger" 
                                               onclick="return confirm('Are you sure you want to delete this category?')">
                                                <i class="fa fa-trash"></i>
                                            </a>
                                        </td>
                                        <?php } ?>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
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
                if (qtyInput.value <= 0) { qtyInput.value = 1; }
                qtyInput.readOnly = false;
                qtyInput.style.backgroundColor = "#ffffff";
            }
        }

        function validateQty() {
            var status = document.getElementById('statusSelect').value;
            var qtyInput = document.getElementById('quantityInput');
            if (status === 'In Stock' && qtyInput.value < 1) { qtyInput.value = 1; }
        }

        <?php if ($is_manager) { ?>
        function editCategory(id, name) {
            document.getElementById('catForm').action = "car-add.php";
            document.getElementById('cat_id_edit').value = id;
            document.getElementById('cat_name_input').value = name;
            document.getElementById('catLabel').innerText = "Edit Category Name";
            document.getElementById('catBtn').innerText = "Update";
            document.getElementById('catBtn').className = "btn btn-warning";
            document.getElementById('cancelEdit').classList.remove('d-none');
        }

        function resetCatForm() {
            document.getElementById('catForm').action = "req/category-add.php";
            document.getElementById('cat_id_edit').value = "";
            document.getElementById('cat_name_input').value = "";
            document.getElementById('catLabel').innerText = "New Category Name";
            document.getElementById('catBtn').innerText = "Add";
            document.getElementById('catBtn').className = "btn btn-success";
            document.getElementById('cancelEdit').classList.add('d-none');
        }
        <?php } ?>

        window.onload = function() { toggleQuantity(); };
    </script>
</body>
</html>

<?php 
} else {
    header("Location: ../login.php");
    exit;
} 
?>