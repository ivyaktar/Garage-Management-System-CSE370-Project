<?php
session_start();

if (isset($_SESSION['user_id']) &&
    isset($_SESSION['role'])    &&
    $_SESSION['role'] == 'Manager') {

    include "../../DB_connection.php";

    // --- HANDLE POST ACTIONS (FORMS) ---
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['type'])) {
        $type = $_POST['type'];

        // 1. Add New Category
        if ($type == "add_category") {
            $name = $_POST['category_name'];
            $is_mandatory = isset($_POST['is_mandatory']) ? 1 : 0;
            
            // Check for duplicate category name first
            $sql_check = "SELECT category_name FROM categories WHERE category_name = ? AND type = 'Part'";
            $stmt_check = $conn->prepare($sql_check);
            $stmt_check->execute([$name]);

            if ($stmt_check->rowCount() > 0) {
                header("Location: ../inventory.php?error=Category name already exists!");
                exit;
            }

            // Check if image is uploaded (Manager MUST provide a default image)
            if (isset($_FILES['category_img']['name']) && !empty($_FILES['category_img']['name'])) {
                $img_name = $_FILES['category_img']['name'];
                $tmp_name = $_FILES['category_img']['tmp_name'];
                $error    = $_FILES['category_img']['error'];

                if ($error === 0) {
                    $img_ex = pathinfo($img_name, PATHINFO_EXTENSION);
                    $img_ex_lc = strtolower($img_ex);
                    $new_img_name = uniqid("CAT-", true).'.'.$img_ex_lc;
                    
                    $img_upload_path = "../../uploads/" . $new_img_name;
                    
                    if (move_uploaded_file($tmp_name, $img_upload_path)) {
                        $category_img = $new_img_name;

                        if (!empty($name)) {
                            $sql = "INSERT INTO categories (category_name, type, is_mandatory, category_img) VALUES (?, 'Part', ?, ?)";
                            $stmt = $conn->prepare($sql);
                            $stmt->execute([$name, $is_mandatory, $category_img]);
                            
                            header("Location: ../inventory.php?success=Category created!");
                            exit;
                        } else {
                            header("Location: ../inventory.php?error=Category name is required.");
                            exit;
                        }
                    } else {
                        header("Location: ../inventory.php?error=Failed to move uploaded file.");
                        exit;
                    }
                } else {
                    header("Location: ../inventory.php?error=Image upload error code: $error");
                    exit;
                }
            } else {
                // If no image is uploaded, block creation
                header("Location: ../inventory.php?error=Default category image is required.");
                exit;
            }
        }

        // 2. Edit/Rename Category Name
        else if ($type == "edit_category") {
            $c_id = $_POST['category_id'];
            $new_name = $_POST['new_name'];

            if (!empty($new_name)) {
                // Check if new name already exists for another category
                $sql_check = "SELECT id FROM categories WHERE category_name = ? AND id != ? AND type = 'Part'";
                $stmt_check = $conn->prepare($sql_check);
                $stmt_check->execute([$new_name, $c_id]);

                if ($stmt_check->rowCount() > 0) {
                    header("Location: ../inventory.php?error=This category name is already in use.");
                    exit;
                }

                $sql = "UPDATE categories SET category_name = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$new_name, $c_id]);
                
                header("Location: ../inventory.php?success=Category updated!");
                exit;
            } else {
                header("Location: ../inventory.php?error=New name cannot be empty.");
                exit;
            }
        }

        // 2.5 Edit Category Image
        else if ($type == "edit_category_img") {
            $c_id = $_POST['category_id'];

            if (isset($_FILES['category_img']['name']) && !empty($_FILES['category_img']['name'])) {
                $img_name = $_FILES['category_img']['name'];
                $tmp_name = $_FILES['category_img']['tmp_name'];
                $error    = $_FILES['category_img']['error'];

                if ($error === 0) {
                    $img_ex = pathinfo($img_name, PATHINFO_EXTENSION);
                    $img_ex_lc = strtolower($img_ex);
                    $new_img_name = uniqid("CAT-", true).'.'.$img_ex_lc;

                    $img_upload_path = "../../uploads/" . $new_img_name;
                    
                    if (move_uploaded_file($tmp_name, $img_upload_path)) {
                        $sql = "UPDATE categories SET category_img = ? WHERE id = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->execute([$new_img_name, $c_id]);

                        header("Location: ../inventory.php?success=Category image updated!");
                        exit;
                    }
                } else {
                    header("Location: ../inventory.php?error=Upload failed.");
                    exit;
                }
            }
            
            header("Location: ../inventory.php?error=Please select a valid image.");
            exit;
        }

        // 3. Add Compatibility Tag
        else if ($type == "add_tag") {
            $tag_name = $_POST['tag_name'];

            if (!empty($tag_name)) {
                try {
                    $sql = "INSERT INTO tags (tag_name) VALUES (?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([$tag_name]);
                    
                    header("Location: ../inventory.php?success=Tag added!");
                    exit;
                } catch (PDOException $e) {
                    if ($e->getCode() == 23000) {
                        header("Location: ../inventory.php?error=Tag already exists!");
                    } else {
                        header("Location: ../inventory.php?error=Error occurred.");
                    }
                    exit;
                }
            } else {
                header("Location: ../inventory.php?error=Tag name is required.");
                exit;
            }
        }

        // 4. Edit Existing Tag
        else if ($type == "edit_tag") {
            $tag_id = $_POST['tag_id'];
            $new_tag_name = $_POST['new_tag_name'];

            if (!empty($new_tag_name)) {
                try {
                    $sql = "UPDATE tags SET tag_name = ? WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([$new_tag_name, $tag_id]);
                    
                    header("Location: ../inventory.php?success=Tag updated!");
                    exit;
                } catch (PDOException $e) {
                    if ($e->getCode() == 23000) {
                        header("Location: ../inventory.php?error=Tag name already exists!");
                    } else {
                        header("Location: ../inventory.php?error=Error occurred.");
                    }
                    exit;
                }
            } else {
                header("Location: ../inventory.php?error=Tag name cannot be empty.");
                exit;
            }
        }

        header("Location: ../inventory.php");
        exit;
    }

    // --- HANDLE GET ACTIONS ---

    if (isset($_GET['toggle_mandatory'])) {
        $c_id = $_GET['toggle_mandatory'];
        $current = $_GET['current'];
        $new_status = ($current == 1) ? 0 : 1;

        $sql = "UPDATE categories SET is_mandatory = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$new_status, $c_id]);

        header("Location: ../inventory.php?success=Category requirement updated!");
        exit;
    }

    if (isset($_GET['delete_category'])) {
        $c_id = $_GET['delete_category'];

        $sql_prod = "UPDATE products SET category_id = 0 WHERE category_id = ?";
        $stmt_prod = $conn->prepare($sql_prod);
        $stmt_prod->execute([$c_id]);

        $sql = "DELETE FROM categories WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$c_id]);

        header("Location: ../inventory.php?success=Category deleted!");
        exit;
    }

    if (isset($_GET['delete_tag'])) {
        $t_id = $_GET['delete_tag'];
        $sql = "DELETE FROM tags WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$t_id]);

        header("Location: ../inventory.php?success=Tag removed!");
        exit;
    }

} else {
    header("Location: ../../login.php");
    exit;
}
?>