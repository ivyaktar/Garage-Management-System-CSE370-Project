<?php 
session_start();

// Access check: Allow both Manager and Employee
if (isset($_SESSION['user_id']) && 
    isset($_SESSION['role'])    && 
    ($_SESSION['role'] == 'Manager' || $_SESSION['role'] == 'Employee')) {
    
    include "../DB_connection.php";

    // Wrapping the UNION in a subquery to ensure ORDER BY works correctly on the final result set
    $sql = "SELECT * FROM (
                (SELECT p.product_name AS name, p.image, p.price, p.id, COUNT(w.id) AS wish_count, 'Part' AS category
                 FROM products p 
                 INNER JOIN wishlist w ON p.id = w.product_id 
                 GROUP BY p.id)
                UNION
                (SELECT CONCAT(c.brand, ' ', c.model) AS name, c.image, c.price, c.id, COUNT(w.id) AS wish_count, 'Car' AS category
                 FROM cars c 
                 INNER JOIN wishlist w ON c.id = w.product_id 
                 GROUP BY c.id)
            ) AS combined_wishlist
            ORDER BY wish_count DESC, price DESC";
            
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $all_wishlisted = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $_SESSION['role']; ?> - Demand Analysis</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<body class="bg-light">
    <?php include "inc/navbar.php"; ?>

    <div class="container mt-5">
        <div class="mb-4">
            <a href="index.php" class="btn btn-secondary btn-sm">
                <i class="fa fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h3 class="mb-0 text-dark">
                    <i class="fa fa-line-chart text-primary me-2"></i> Wishlist Analysis
                </h3>
                <p class="text-muted small mb-0">Sorted by Interest Count, then by Unit Price</p>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>Rank</th>
                                <th>Preview</th>
                                <th>Item Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Total Wishlists</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php 
                            $count = count($all_wishlisted);
                            
                            if ($count > 0) {
                                
                                for ($i = 0; $i < $count; $i = $i + 1) { 
                                    
                                    $item = $all_wishlisted[$i];
                                    $img_src = "../uploads/" . $item['image'];
                            ?>
                            <tr>
                                <td><?php echo ($i + 1); ?></td>
                                
                                <td>
                                    <?php if (!empty($item['image'])) { ?>
                                        <img src="<?php echo $img_src; ?>" width="60" height="60" class="rounded border" style="object-fit: cover;">
                                    <?php } ?>
                                </td>
                                
                                <td class="fw-bold"><?php echo $item['name']; ?></td>
                                
                                <td>
                                    <span class="badge <?php echo ($item['category'] == 'Car') ? 'bg-info' : 'bg-secondary'; ?>">
                                        <?php echo $item['category']; ?>
                                    </span>
                                </td>
                                
                                <td class="text-dark fw-bold">$<?php echo number_format($item['price'], 2); ?></td>
                                
                                <td>
                                    <span class="badge bg-primary rounded-pill fs-6 px-3">
                                        <i class="fa fa-heart me-1"></i> <?php echo $item['wish_count']; ?> 
                                    </span>
                                </td>
                            </tr>
                            <?php 
                                } 
                                
                            } else { 
                            ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    No wishlisted items found.
                                </td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php 
} else {
    header("Location: ../login.php");
    exit;
} ?>