<?php 
session_start();

if (isset($_SESSION['user_id']) && 
    isset($_SESSION['role'])    && 
    $_SESSION['role'] == 'Manager' && 
    isset($_GET['id'])) {
    
    include "../DB_connection.php";
    $car_id = $_GET['id'];

    /* --- FETCH SETTING --- */
    $set_sql = "SELECT current_year FROM setting LIMIT 1";
    $set_stmt = $conn->prepare($set_sql);
    $set_stmt->execute();
    $setting = $set_stmt->fetch();
    $curr_year = $setting['current_year'];


    /* --- CATEGORY LOGIC (Add/Update/Delete) --- */
    
    if (isset($_POST['category_name']) && !isset($_POST['category_id_edit'])) {
        $cat_name = $_POST['category_name'];
        if (!empty($cat_name)) {
            $sql_cat = "INSERT INTO categories (category_name, type) VALUES (?, 'Car')";
            $stmt_cat = $conn->prepare($sql_cat);
            $stmt_cat->execute([$cat_name]);
            header("Location: car-edit.php?id=$car_id&success=Category added");
            exit;
        }
    }

    if (isset($_GET['delete_cat'])) {
        $del_id = $_GET['delete_cat'];
        $sql_del = "DELETE FROM categories WHERE id = ?";
        $stmt_del = $conn->prepare($sql_del);
        $stmt_del->execute([$del_id]);
        header("Location: car-edit.php?id=$car_id&success=Category deleted");
        exit;
    }

    if (isset($_POST['category_id_edit'])) {
        $edit_cat_id = $_POST['category_id_edit'];
        $new_name = $_POST['category_name'];
        $sql_upd = "UPDATE categories SET category_name = ? WHERE id = ?";
        $stmt_upd = $conn->prepare($sql_upd);
        $stmt_upd->execute([$new_name, $edit_cat_id]);
        header("Location: car-edit.php?id=$car_id&success=Category updated");
        exit;
    }


    /* --- FETCH CURRENT VEHICLE DETAILS --- */
    $sql = "SELECT * FROM cars WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$car_id]);
    $car = $stmt->fetch();

    if (!$car) {
        header("Location: showroom-manage.php");
        exit;
    }

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
    <title>Edit Vehicle - <?php echo $car['model']; ?></title>
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
                <div class="page-header-icon"><i class="fa fa-pencil-square-o"></i></div>
                <h2 class="page-header-title">Edit Vehicle</h2>
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
                                           min="1" max="999999999" value="<?php echo $car['mileage']; ?>" required>
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
                                <label class="form-label fw-bold">Update Image (Leave blank to keep current)</label>
                                <input type="file" name="car_image" class="form-control">
                                <div class="mt-2">
                                    <small class="text-muted">Current image:</small><br>
                                    <img src="../uploads/<?php echo $car['image']; ?>" style="width: 150px; border-radius: 5px;" class="shadow-sm">
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">Update Vehicle Details</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-5">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white fw-bold">Manage Categories</div>
                    <div class="card-body">
                        <form action="car-edit.php?id=<?php echo $car_id; ?>" method="POST" id="catForm">
                            <input type="hidden" name="category_id_edit" id="cat_id_edit">
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

                <div class="card shadow-sm border-0">
                    <div class="card-body p-0">
                        <div class="table-responsive cat-list">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Name</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php for ($i = 0; $i < count($categories); $i = $i + 1) { ?>
                                    <tr>
                                        <td><?php echo $categories[$i]['category_name']; ?></td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-warning" 
                                                    onclick="editCategory('<?php echo $categories[$i]['id']; ?>', '<?php echo $categories[$i]['category_name']; ?>')">
                                                <i class="fa fa-pencil"></i>
                                            </button>
                                            <a href="car-edit.php?id=<?php echo $car_id; ?>&delete_cat=<?php echo $categories[$i]['id']; ?>" 
                                               class="btn btn-sm btn-danger" 
                                               onclick="return confirm('Are you sure you want to delete this category?')">
                                                <i class="fa fa-trash"></i>
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

        function editCategory(id, name) {
            document.getElementById('cat_id_edit').value = id;
            document.getElementById('cat_name_input').value = name;
            document.getElementById('catLabel').innerText = "Edit Category Name";
            document.getElementById('catBtn').innerText = "Update";
            document.getElementById('catBtn').className = "btn btn-warning";
            document.getElementById('cancelEdit').classList.remove('d-none');
        }

        function resetCatForm() {
            document.getElementById('cat_id_edit').value = "";
            document.getElementById('cat_name_input').value = "";
            document.getElementById('catLabel').innerText = "New Category Name";
            document.getElementById('catBtn').innerText = "Add";
            document.getElementById('catBtn').className = "btn btn-success";
            document.getElementById('cancelEdit').classList.add('d-none');
        }

        window.onload = function() { toggleQuantity(); };
    </script>
</body>
</html>

<?php 
} else {
    header("Location: showroom-manage.php");
    exit;
} ?>