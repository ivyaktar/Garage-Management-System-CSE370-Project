<?php 
session_start();

if (isset($_SESSION['user_id']) && $_SESSION['role'] == 'Manager' && isset($_GET['id'])) {
    include "../DB_connection.php";
    $id = $_GET['id'];

    // Fetch product details
    $sql = "SELECT * FROM products WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
    $product = $stmt->fetch();

    // FIXED: Only fetch categories where type is 'Part'
    $sql_cat = "SELECT * FROM categories WHERE type = 'Part' ORDER BY category_name ASC";
    $stmt_cat = $conn->prepare($sql_cat);
    $stmt_cat->execute();
    $categories = $stmt_cat->fetchAll();

    // Fetch all available compatibility tags
    $sql_tags = "SELECT * FROM tags ORDER BY tag_name ASC";
    $stmt_tags = $conn->prepare($sql_tags);
    $stmt_tags->execute();
    $all_tags = $stmt_tags->fetchAll();

    // Handle current tags for comparison
    $current_tags_string = "";
    if (isset($product['tags'])) {
        $current_tags_string = $product['tags'];
    }
    $current_tags_array = explode(',', $current_tags_string);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Product</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<body class="bg-light">
    <?php include "inc/navbar.php"; ?>
    <div class="container mt-5 mb-5" style="max-width: 800px;">
        <form action="req/product-edit.php" 
              method="post" 
              enctype="multipart/form-data" 
              class="shadow p-4 bg-white rounded border-top border-primary border-4">
            
            <h4 class="text-center">Edit Product Information</h4>
            <hr>

            <?php if (isset($_GET['error'])) { ?>
                <div class="alert alert-danger"><?php echo $_GET['error']; ?></div>
            <?php } ?>

            <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Product Name</label>
                        <input type="text" name="product_name" class="form-control" value="<?php echo $product['product_name']; ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select name="category_id" class="form-control">
                            <option value="0">-- Uncategorized --</option>
                            <?php
                            for ($i = 0; $i < count($categories); $i = $i + 1) {
                                $cat = $categories[$i];
                                $selected = "";
                                if ($cat['id'] == $product['category_id']) {
                                    $selected = "selected";
                                }
                            ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo $selected; ?>>
                                    <?php echo $cat['category_name']; ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Price ($)</label>
                        <input type="number" step="0.01" name="price" class="form-control" value="<?php echo $product['price']; ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Horsepower (hp)</label>
                        <input type="text" name="hp" class="form-control" value="<?php echo $product['hp']; ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="4"><?php echo $product['description']; ?></textarea>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Compatibility Tags</label>
                        <div class="border p-2 rounded bg-light" style="max-height: 150px; overflow-y: auto;">
                            <?php 
                            for ($i = 0; $i < count($all_tags); $i = $i + 1) { 
                                $t_name = $all_tags[$i]['tag_name'];
                                $is_checked = "";

                                for ($j = 0; $j < count($current_tags_array); $j = $j + 1) {
                                    if (trim($current_tags_array[$j]) == $t_name) {
                                        $is_checked = "checked";
                                    }
                                }
                            ?>
                                <div class="form-check">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           name="tags[]" 
                                           value="<?php echo $t_name; ?>" 
                                           id="tag_<?php echo $i; ?>"
                                           <?php echo $is_checked; ?>>
                                    <label class="form-check-label" for="tag_<?php echo $i; ?>">
                                        <?php echo $t_name; ?>
                                    </label>
                                </div>
                            <?php } ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label d-block">Warranty Types</label>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="regular_warranty" value="1" id="regW" 
                                   <?php if (isset($product['regular_warranty']) && $product['regular_warranty'] == 1) { echo "checked"; } ?>>
                            <label class="form-check-label" for="regW">Regular</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="replacement_warranty" value="1" id="repW" 
                                   <?php if (isset($product['replacement_warranty']) && $product['replacement_warranty'] == 1) { echo "checked"; } ?>>
                            <label class="form-check-label" for="repW">Replacement</label>
                        </div>
                    </div>

                    <div class="mb-3 border p-3 rounded">
                        <label class="form-label fw-bold">Product Image</label>
                        <input type="file" name="product_image" id="product_image_input" class="form-control mb-2">
                        
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="remove_image" id="removeImg" value="1" onchange="toggleImageInput(this)">
                            <label class="form-check-label text-danger" for="removeImg">
                                Remove current image
                            </label>
                        </div>

                        <div class="text-center">
                            <?php 
                                if (isset($product['image']) && !empty($product['image'])) {
                                    $img_path = "../uploads/" . $product['image'];
                                    echo '<img src="'.$img_path.'" width="100" class="img-thumbnail">';
                                } else {
                                    echo '<span class="badge bg-secondary">No Product Image</span>';
                                }
                            ?>
                        </div>
                    </div>
                </div>
            </div>

            <hr>
            <div class="row">
                <div class="col-md-6">
                    <a href="inventory-view.php" class="btn btn-secondary w-100">Cancel</a>
                </div>
                <div class="col-md-6">
                    <button type="submit" class="btn btn-primary w-100">Update Product</button>
                </div>
            </div>
        </form>
    </div>

    <script>
        function toggleImageInput(checkbox) {
            var fileInput = document.getElementById('product_image_input');
            if (checkbox.checked) {
                fileInput.value = ""; 
                fileInput.disabled = true;
                fileInput.style.opacity = "0.5";
            } else {
                fileInput.disabled = false;
                fileInput.style.opacity = "1";
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