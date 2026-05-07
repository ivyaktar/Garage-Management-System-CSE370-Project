<?php
session_start();

if (isset($_SESSION['user_id']) && isset($_POST['action'])) {
    include "../../DB_connection.php";
    
    $user_id = $_SESSION['user_id'];
    $action = $_POST['action'];

    // 1. Determine base redirect
    $redirect = "../car-builder.php";
    if (isset($_SERVER['HTTP_REFERER'])) {
        $redirect = $_SERVER['HTTP_REFERER'];
    }
    
    // 2. Clean up the redirect URL 
    $url_parts = parse_url($redirect);
    $path = isset($url_parts['path']) ? $url_parts['path'] : '../car-builder.php';
    $query_params = array();

    if (isset($url_parts['query'])) {
        parse_str($url_parts['query'], $query_params);
    }

    // Remove status messages so they don't stack up
    unset($query_params['success']);
    unset($query_params['error']);

    // Rebuild the query string
    $new_query = http_build_query($query_params);
    $final_redirect = $path;
    
    if (!empty($new_query)) {
        $final_redirect .= "?" . $new_query;
    }

    $separator = (strpos($final_redirect, '?') !== false) ? "&" : "?";

    // --- ACTIONS ---

    // Add or Update a specific part
    if ($action == "add_part" || $action == "select_part") {
        $cat_id = $_POST['cat_id'];
        $p_id = $_POST['p_id'];

        // First, check if this category already has a part for this user
        $check_sql = "SELECT id FROM builder_temporary WHERE user_id = ? AND category_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->execute([$user_id, $cat_id]);

        if ($check_stmt->rowCount() > 0) {
            // Update existing
            $sql = "UPDATE builder_temporary SET product_id = ? WHERE user_id = ? AND category_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$p_id, $user_id, $cat_id]);
        } else {
            // Insert new
            $sql = "INSERT INTO builder_temporary (user_id, category_id, product_id) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$user_id, $cat_id, $p_id]);
        }
        
        // REDIRECT UPDATE: Force back to the main builder page instead of the referer
        header("Location: ../car-builder.php?success=Part selected");
        exit;
    } 
    
    // Remove a single part
    else if ($action == "remove_part") {
        $cat_id = $_POST['cat_id'];
        
        $sql = "DELETE FROM builder_temporary WHERE user_id = ? AND category_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$user_id, $cat_id]);
        
        header("Location: " . $final_redirect . $separator . "success=Part removed");
        exit;
    }

    // Clear the entire temporary build
    else if ($action == "clear_all") {
        $sql = "DELETE FROM builder_temporary WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$user_id]);
        
        header("Location: " . $final_redirect . $separator . "success=Build cleared");
        exit;
    }

    // Toggle wishlist for a single product
    else if ($action == "wishlist_item") {
        $p_id = $_POST['p_id'];
        
        $check_sql = "SELECT * FROM wishlist WHERE user_id = ? AND product_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->execute([$user_id, $p_id]);
        
        if ($check_stmt->rowCount() > 0) {
            $sql = "DELETE FROM wishlist WHERE user_id = ? AND product_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$user_id, $p_id]);
            header("Location: " . $final_redirect . $separator . "success=Part removed from wishlist");
        } else {
            $sql = "INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$user_id, $p_id]);
            header("Location: " . $final_redirect . $separator . "success=Part added to wishlist");
        }
        exit;
    }

    // Add a single product to the cart
    else if ($action == "cart_item") {
        $p_id = $_POST['p_id'];
        
        $check_sql = "SELECT * FROM cart WHERE customer_id = ? AND product_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->execute([$user_id, $p_id]);
        
        if ($check_stmt->rowCount() > 0) {
            $sql = "UPDATE cart SET quantity = quantity + 1 WHERE customer_id = ? AND product_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$user_id, $p_id]);
        } else {
            $sql = "INSERT INTO cart (customer_id, product_id, quantity) VALUES (?, ?, 1)";
            $stmt = $conn->prepare($sql);
            // FIXED: Removed the extra '1' from the array because it's already hardcoded in the SQL string above
            $stmt->execute([$user_id, $p_id]);
        }
        
        header("Location: " . $final_redirect . $separator . "success=Part added to cart");
        exit;
    }

    // Save the entire current build
    else if ($action == "save_build") {
        $build_name = $_POST['build_name'];

        $sql_fetch = "SELECT product_id FROM builder_temporary WHERE user_id = ?";
        $stmt_fetch = $conn->prepare($sql_fetch);
        $stmt_fetch->execute([$user_id]);
        $parts = $stmt_fetch->fetchAll();

        if (count($parts) > 0) {
            $sql_user_build = "INSERT INTO user_builds (user_id, build_name) VALUES (?, ?)";
            $stmt_user_build = $conn->prepare($sql_user_build);
            $stmt_user_build->execute([$user_id, $build_name]);
            
            $last_build_id = $conn->lastInsertId();

            for ($i = 0; $i < count($parts); $i = $i + 1) {
                $prod_id = $parts[$i]['product_id'];
                $sql_items = "INSERT INTO build_items (build_id, product_id) VALUES (?, ?)";
                $stmt_items = $conn->prepare($sql_items);
                $stmt_items->execute([$last_build_id, $prod_id]);
            }

            header("Location: ../saved-builds.php?success=Build saved successfully");
        } else {
            header("Location: " . $final_redirect . $separator . "error=Cannot save an empty build");
        }
        exit;
    }

    // Add the entire build to the cart
    else if ($action == "add_build_to_cart") {
        if (isset($_POST['build_id'])) {
            $b_id = $_POST['build_id'];
            $sql_fetch = "SELECT product_id FROM build_items WHERE build_id = ?";
            $stmt_fetch = $conn->prepare($sql_fetch);
            $stmt_fetch->execute([$b_id]);
        } else {
            $sql_fetch = "SELECT product_id FROM builder_temporary WHERE user_id = ?";
            $stmt_fetch = $conn->prepare($sql_fetch);
            $stmt_fetch->execute([$user_id]);
        }
        
        $parts = $stmt_fetch->fetchAll();

        for ($i = 0; $i < count($parts); $i = $i + 1) {
            $curr_p_id = $parts[$i]['product_id'];

            $check_c = "SELECT id FROM cart WHERE customer_id = ? AND product_id = ?";
            $stmt_c = $conn->prepare($check_c);
            $stmt_c->execute([$user_id, $curr_p_id]);

            if ($stmt_c->rowCount() > 0) {
                $sql = "UPDATE cart SET quantity = quantity + 1 WHERE customer_id = ? AND product_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$user_id, $curr_p_id]);
            } else {
                $sql = "INSERT INTO cart (customer_id, product_id, quantity) VALUES (?, ?, 1)";
                $stmt = $conn->prepare($sql);
                // FIXED: Removed the extra '1' here as well to maintain consistency with the SQL tokens
                $stmt->execute([$user_id, $curr_p_id]);
            }
        }
        
        header("Location: " . $final_redirect . $separator . "success=Entire build added to cart");
        exit;
    }

    // Toggle wishlist for the ENTIRE build
    else if ($action == "wishlist_build") {
        if (isset($_POST['build_id'])) {
            $b_id = $_POST['build_id'];
            $sql_fetch = "SELECT product_id FROM build_items WHERE build_id = ?";
            $stmt_fetch = $conn->prepare($sql_fetch);
            $stmt_fetch->execute([$b_id]);
        } else {
            $sql_fetch = "SELECT product_id FROM builder_temporary WHERE user_id = ?";
            $stmt_fetch = $conn->prepare($sql_fetch);
            $stmt_fetch->execute([$user_id]);
        }
        
        $parts = $stmt_fetch->fetchAll();
        $total_parts = count($parts);

        if ($total_parts > 0) {
            $wish_count = 0;
            for ($i = 0; $i < $total_parts; $i = $i + 1) {
                $curr_p_id = $parts[$i]['product_id'];
                $check_w = "SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?";
                $stmt_w = $conn->prepare($check_w);
                $stmt_w->execute([$user_id, $curr_p_id]);
                
                if ($stmt_w->rowCount() > 0) {
                    $wish_count = $wish_count + 1;
                }
            }

            if ($wish_count == $total_parts) {
                for ($i = 0; $i < $total_parts; $i = $i + 1) {
                    $curr_p_id = $parts[$i]['product_id'];
                    $del = "DELETE FROM wishlist WHERE user_id = ? AND product_id = ?";
                    $d_stmt = $conn->prepare($del);
                    $d_stmt->execute([$user_id, $curr_p_id]);
                }
                header("Location: " . $final_redirect . $separator . "success=Build removed from wishlist");
            } else {
                for ($i = 0; $i < $total_parts; $i = $i + 1) {
                    $curr_p_id = $parts[$i]['product_id'];
                    $check_single = "SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?";
                    $cs_stmt = $conn->prepare($check_single);
                    $cs_stmt->execute([$user_id, $curr_p_id]);
                    
                    if ($cs_stmt->rowCount() == 0) {
                        $ins = "INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)";
                        $i_stmt = $conn->prepare($ins);
                        $i_stmt->execute([$user_id, $curr_p_id]);
                    }
                }
                header("Location: " . $final_redirect . $separator . "success=Entire build added to wishlist");
            }
        } else {
            header("Location: " . $final_redirect . $separator . "error=Empty build");
        }
        exit;
    }

} else {
    header("Location: ../car-builder.php");
    exit;
}