<?php 
session_start();

// 1. Updated role check: Allow both Manager and Employee, and check for product ID
if (isset($_SESSION['user_id']) && 
    isset($_SESSION['role'])    && 
    ($_SESSION['role'] == 'Manager' || $_SESSION['role'] == 'Employee') && 
    isset($_GET['id'])) {
    
    // Adjust this path if your Manager files are in a different folder depth
    include "../DB_connection.php"; 
    $p_id = $_GET['id'];

    // 2. Fetch Product Name for the header
    $sql_p = "SELECT product_name FROM products WHERE id = ?";
    $stmt_p = $conn->prepare($sql_p);
    $stmt_p->execute([$p_id]);
    $product = $stmt_p->fetch();

    // If product doesn't exist, go back to inventory
    if (!$product) {
        header("Location: inventory-view.php");
        exit;
    }

    // 3. Fetch all reviews for this product
    $sql_rev = "SELECT r.*, cust.fname, cust.lname 
                FROM reviews r 
                JOIN customer cust ON r.user_id = cust.user_id 
                WHERE r.product_id = ? 
                ORDER BY r.id DESC";
    $stmt_rev = $conn->prepare($sql_rev);
    $stmt_rev->execute([$p_id]);
    $reviews = $stmt_rev->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $_SESSION['role']; ?> - Reviews: <?php echo $product['product_name']; ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<body class="bg-light">
    <?php include "inc/navbar.php"; ?>

    <div class="container mt-5">
        <div class="mb-4">
            <a href="inventory-view.php" class="btn btn-secondary btn-sm">
                <i class="fa fa-arrow-left"></i> Back to Inventory
            </a>
            <h3 class="mt-3">Manage Reviews: <span class="text-primary"><?php echo $product['product_name']; ?></span></h3>
        </div>

        <?php if (isset($_GET['success'])) { ?>
            <div class="alert alert-success"><?php echo $_GET['success']; ?></div>
        <?php } ?>
        
        <?php if (isset($_GET['error'])) { ?>
            <div class="alert alert-danger"><?php echo $_GET['error']; ?></div>
        <?php } ?>

        <div class="row">
            <div class="col-12">
                <?php 
                $review_count = count($reviews);
                
                if ($review_count > 0) {
                    for ($i = 0; $i < $review_count; $i = $i + 1) { 
                        $r = $reviews[$i];
                ?>
                    <div class="card mb-3 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <h5 class="mb-1"><?php echo $r['fname'] . " " . $r['lname']; ?></h5>
                                <span class="text-warning">
                                    <?php 
                                    $rating_val = $r['rating'];
                                    for ($j = 0; $j < $rating_val; $j = $j + 1) { 
                                        echo "★"; 
                                    } 
                                    ?>
                                </span>
                            </div>
                            <p class="text-muted small"><?php echo $r['date_posted']; ?></p>
                            <p class="bg-light p-3 rounded">"<?php echo $r['comment']; ?>"</p>

                            <hr>

                            <?php if (!empty($r['company_reply'])) { ?>
                                <div id="reply-display-<?php echo $r['id']; ?>" class="p-3 border-start border-primary border-4 bg-white">
                                    <small class="text-primary fw-bold text-uppercase">Current Company Reply:</small>
                                    <p class="mb-2 mt-1"><?php echo $r['company_reply']; ?></p>
                                    <button class="btn btn-sm btn-outline-primary" 
                                            onclick="toggleEditReply(<?php echo $r['id']; ?>)">
                                        <i class="fa fa-pencil"></i> Change Reply
                                    </button>
                                </div>

                                <div id="reply-edit-<?php echo $r['id']; ?>" style="display: none;" class="bg-white p-3 border rounded">
                                    <form action="req/reply-review.php" method="post">
                                        <input type="hidden" name="review_id" value="<?php echo $r['id']; ?>">
                                        <input type="hidden" name="product_id" value="<?php echo $p_id; ?>">
                                        <input type="hidden" name="is_edit" value="1">
                                        
                                        <div class="mb-2">
                                            <label class="form-label small fw-bold">Edit Response</label>
                                            <textarea name="reply" class="form-control" rows="2" required><?php echo $r['company_reply']; ?></textarea>
                                        </div>
                                        <button type="submit" class="btn btn-success btn-sm">Save Changes</button>
                                        <button type="button" class="btn btn-light btn-sm" onclick="toggleEditReply(<?php echo $r['id']; ?>)">Cancel</button>
                                    </form>
                                </div>

                            <?php } else { ?>
                                <form action="req/reply-review.php" method="post">
                                    <input type="hidden" name="review_id" value="<?php echo $r['id']; ?>">
                                    <input type="hidden" name="product_id" value="<?php echo $p_id; ?>">
                                    
                                    <div class="mb-2">
                                        <label class="form-label small fw-bold">Official Response</label>
                                        <textarea name="reply" class="form-control" rows="2" placeholder="Write as the company..." required></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-sm">Post Reply</button>
                                </form>
                            <?php } ?>
                        </div>
                    </div>
                <?php 
                    } 
                } else {
                    echo "<div class='alert alert-info'>No reviews found for this product yet.</div>";
                }
                ?>
            </div>
        </div>
    </div>

    <script>
        function toggleEditReply(id) {
            var displayDiv = document.getElementById('reply-display-' + id);
            var editDiv = document.getElementById('reply-edit-' + id);
            
            if (editDiv.style.display === "none") {
                editDiv.style.display = "block";
                displayDiv.style.display = "none";
            } else {
                editDiv.style.display = "none";
                displayDiv.style.display = "block";
            }
        }
    </script>
</body>
</html>
<?php 
} else {
    header("Location: inventory-view.php");
    exit;
} 
?>