<?php

// Function to get common tags among all selected parts
function getBuildCommonTags($user_id, $conn) {

    $sql = "SELECT p.tags FROM builder_temporary bt 
            JOIN products p ON bt.product_id = p.id 
            WHERE bt.user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$user_id]);
    $all_tags = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($all_tags)) {
        return [];
    }

    $common = [];
    $is_first_set = true;

    for ($i = 0; $i < count($all_tags); $i = $i + 1) {
        
        $tags_string = $all_tags[$i];
        $specific_tags = [];

        // If tags are NULL or empty, we treat it as 'universal'
        if ($tags_string == null || $tags_string == "") {
            $specific_tags[] = 'universal';
        } else {
            $current_tags = explode(',', $tags_string);
            for ($j = 0; $j < count($current_tags); $j = $j + 1) {
                $tag = strtolower(trim($current_tags[$j]));
                
                if ($tag !== "" && $tag !== "null") {
                    $specific_tags[] = $tag;
                }
            }
        }

        if ($is_first_set == true) {
            $common = $specific_tags;
            $is_first_set = false;
        } else {
            // Check for Universal logic: if either side is universal, the other side's tags prevail
            $has_universal_in_common = false;
            for ($u = 0; $u < count($common); $u = $u + 1) {
                if ($common[$u] == 'universal') { $has_universal_in_common = true; }
            }

            $has_universal_in_current = false;
            for ($u = 0; $u < count($specific_tags); $u = $u + 1) {
                if ($specific_tags[$u] == 'universal') { $has_universal_in_current = true; }
            }

            if ($has_universal_in_current == true) {
                // Current part fits everything, keep existing common tags
                continue;
            } else if ($has_universal_in_common == true) {
                // Previous parts were universal, now this specific part sets the standard
                $common = $specific_tags;
            } else {
                // Manual intersection for common tags
                $new_common = [];
                for ($k = 0; $k < count($common); $k = $k + 1) {
                    for ($l = 0; $l < count($specific_tags); $l = $l + 1) {
                        if ($common[$k] == $specific_tags[$l]) {
                            $new_common[] = $common[$k];
                        }
                    }
                }
                $common = $new_common;
            }
        }
    }

    return $common;
}


// Function to get product details safely using 'quantity' and 'status' columns
function getProductForBuilder($id, $conn) {

    $sql = "SELECT id, product_name, image, price, quantity, status, tags, hp FROM products WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
    $result = $stmt->fetch();

    if ($result) {
        return $result;
    } else {
        return null;
    }
}
?>