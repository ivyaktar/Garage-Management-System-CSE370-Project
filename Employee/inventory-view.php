<?php 
session_start();

// Updated role check to allow both Manager and Employee
if (isset($_SESSION['user_id']) && 
    isset($_SESSION['role'])    && 
    ($_SESSION['role'] == 'Manager' || $_SESSION['role'] == 'Employee')) {
    
    include "../DB_connection.php";
    
    $search = "";
    
    // Base SQL: include the 'tags' column from products
    $sql = "SELECT p.*, c.category_name, 
                    IFNULL(w.wish_count, 0) AS wishlist_count
            FROM products p 
            INNER JOIN categories c ON p.category_id = c.id 
            LEFT JOIN (
                SELECT product_id, COUNT(*) AS wish_count 
                FROM wishlist 
                GROUP BY product_id
            ) w ON p.id = w.product_id
            WHERE c.type = 'Part'";

    if (isset($_GET['search'])) {
        $search = $_GET['search'];
        $sql = $sql . " AND (p.product_name LIKE ? 
                        OR p.description LIKE ? 
                        OR c.category_name LIKE ?
                        OR p.tags LIKE ?)
                        ORDER BY p.id DESC";
        $stmt = $conn->prepare($sql);
        $stmt->execute(["%$search%", "%$search%", "%$search%", "%$search%"]);
    } else {
        $sql = $sql . " ORDER BY p.id DESC";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
    }
    
    $products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $_SESSION['role']; ?> - Parts Inventory</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        .table-container { background: #fff; border-radius: 15px; overflow: hidden; }
        .product-img { width: 60px; height: 60px; object-fit: cover; border-radius: 8px; }
        .tag-badge { font-size: 0.7rem; font-weight: 500; letter-spacing: 0.5px; border: 1px solid rgba(0,0,0,0.05); }
        .hover-row:hover { background-color: #f8f9fa !important; }
        .search-bar { border-radius: 20px 0 0 20px; border-right: none; }
        .search-btn { border-radius: 0 20px 20px 0; }
        .stat-icon { width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; border-radius: 10px; background: #f0f2f5; color: #555; }
    </style>
</head>
<body class="bg-light">
    <?php include "inc/navbar.php"; ?>

    <div class="container mt-5 px-4" style="max-width: 1400px;">
        
        <div class="row align-items-center mb-4">
            <div class="col-md-4">
                <h3 class="fw-bold mb-1"><i class="fa fa-cubes text-primary me-2"></i>Parts Inventory</h3>
                <p class="text-muted small">Total Items Tracked: <?php echo count($products); ?></p>
            </div>
            <div class="col-md-5">
                <form action="inventory-view.php" method="get" class="d-flex shadow-sm rounded-pill">
                    <input type="text" name="search" class="form-control search-bar px-4" placeholder="Search by name, category, or tags..." value="<?php echo $search; ?>">
                    <button type="submit" class="btn btn-primary search-btn px-4">
                        <i class="fa fa-search"></i>
                    </button>
                </form>
            </div>
            <div class="col-md-3 text-end">
                <a href="inventory.php" class="btn btn-dark rounded-pill px-4 shadow-sm">
                    <i class="fa fa-plus me-2"></i>Add New Part
                </a>
            </div>
        </div>

        <?php if (isset($_GET['success'])) { ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fa fa-check-circle me-2"></i><?php echo $_GET['success']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php } ?>

        <div class="table-responsive shadow-sm table-container border p-2">
            <table class="table table-borderless align-middle mb-0">
                <thead class="bg-light text-muted">
                    <tr>
                        <th class="ps-3" style="font-size: 0.85rem; text-transform: uppercase;">Image</th>
                        <th style="font-size: 0.85rem; text-transform: uppercase;">Product Details</th>
                        <th style="font-size: 0.85rem; text-transform: uppercase;">Category</th>
                        <th style="font-size: 0.85rem; text-transform: uppercase;">HP</th>
                        <th style="font-size: 0.85rem; text-transform: uppercase;">Compatibility Tags</th>
                        <th style="font-size: 0.85rem; text-transform: uppercase;">Price</th>
                        <th style="font-size: 0.85rem; text-transform: uppercase;">Stock Status</th>
                        <th style="font-size: 0.85rem; text-transform: uppercase;">Engagement</th>
                        <th class="text-end pe-3" style="font-size: 0.85rem; text-transform: uppercase;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $product_total = count($products);

                    for ($i = 0; $i < $product_total; $i = $i + 1) { 
                        $p = $products[$i];
                        
                        $display_cat = "Uncategorized";
                        if (!empty($p['category_name'])) {
                            $display_cat = $p['category_name'];
                        }

                        // Stock Badge Logic
                        $badge_class = "bg-success";
                        $status_text = $p['quantity'] . " in stock";

                        if ($p['status'] == "Upcoming") {
                            $badge_class = "bg-primary";
                            $status_text = "Upcoming";
                        } else {
                            if ($p['quantity'] <= 0) {
                                $badge_class = "bg-danger";
                                $status_text = "Out of Stock";
                            } else if ($p['quantity'] <= 5) {
                                $badge_class = "bg-warning text-dark";
                            }
                        }

                        $wish_count = $p['wishlist_count'];
                    ?>
                    <tr class="hover-row border-bottom">
                        <td class="ps-3">
                            <?php if(!empty($p['image'])) { ?>
                                <img src="../uploads/<?php echo $p['image']; ?>" class="product-img border">
                            <?php } else { ?>
                                <div class="product-img bg-secondary d-flex align-items-center justify-content-center text-white small">N/A</div>
                            <?php } ?>
                        </td>

                        <td>
                            <div class="fw-bold text-dark"><?php echo $p['product_name']; ?></div>
                            <div class="text-muted small">SKU: #<?php echo $p['id']; ?></div>
                        </td>

                        <td>
                            <span class="text-secondary small fw-bold"><i class="fa fa-folder-open-o me-1"></i><?php echo $display_cat; ?></span>
                        </td>

                        <td>
                            <span class="badge bg-dark rounded-pill"><?php echo !empty($p['hp']) ? $p['hp'] . " HP" : "0 HP"; ?></span>
                        </td>

                        <td>
                            <?php 
                                if (empty($p['tags'])) {
                                    echo '<span class="badge rounded-pill bg-light text-dark border"><i class="fa fa-globe text-muted me-1"></i> Universal</span>';
                                } else {
                                    $tags_list = explode(',', $p['tags']);
                                    $tags_total = count($tags_list);
                                    for ($j = 0; $j < $tags_total; $j = $j + 1) {
                                        echo '<span class="badge rounded-pill bg-info text-dark me-1 tag-badge">' . trim($tags_list[$j]) . '</span>';
                                    }
                                }
                            ?>
                        </td>

                        <td>
                            <span class="fw-bold text-dark">$<?php echo number_format($p['price'], 2); ?></span>
                        </td>

                        <td>
                            <span class="badge <?php echo $badge_class; ?> rounded-pill px-3 py-2" style="font-size: 0.75rem;">
                                <?php echo $status_text; ?>
                            </span>
                        </td>

                        <td>
                            <div class="d-flex align-items-center">
                                <div class="stat-icon me-2">
                                    <i class="fa <?php echo ($p['status'] == 'Upcoming') ? 'fa-heart text-danger' : 'fa-heart-o'; ?>"></i>
                                </div>
                                <span class="fw-bold <?php echo ($p['status'] == 'Upcoming') ? 'text-primary' : 'text-muted'; ?>">
                                    <?php echo $wish_count; ?>
                                </span>
                            </div>
                        </td>

                        <td class="text-end pe-3">
                            <div class="btn-group shadow-sm">
                                <a href="view-reviews.php?id=<?php echo $p['id']; ?>" class="btn btn-white btn-sm border" title="Reviews">
                                    <i class="fa fa-comments text-info"></i>
                                </a>
                                <a href="product-edit.php?id=<?php echo $p['id']; ?>" class="btn btn-white btn-sm border" title="Edit">
                                    <i class="fa fa-pencil text-warning"></i>
                                </a>
                                <a href="req/product-delete.php?id=<?php echo $p['id']; ?>" class="btn btn-white btn-sm border" title="Delete">
                                    <i class="fa fa-trash text-danger"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php } 
                    
                    if ($product_total == 0) { ?>
                        <tr>
                            <td colspan="9" class="text-center py-5">
                                <i class="fa fa-search-minus fa-3x text-light mb-3"></i>
                                <p class="text-muted">No parts found matching your criteria.</p>
                                <a href="inventory-view.php" class="btn btn-link text-decoration-none">Clear Search</a>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php 
} else { 
    header("Location: ../login.php"); 
    exit; 
} 
?>