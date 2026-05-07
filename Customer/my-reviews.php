<?php 
session_start();

if (isset($_SESSION['user_id']) && 
    isset($_SESSION['role'])    && 
    $_SESSION['role'] == 'Customer') {
    
    include "../DB_connection.php";
    $u_id = $_SESSION['user_id'];

    /* --- 1. FETCH REVIEWS WITH ITEM NAMES --- */
    $sql = "SELECT r.*, p.product_name, c.model, c.brand 
            FROM reviews r
            LEFT JOIN products p ON r.product_id = p.id
            LEFT JOIN cars c ON r.car_id = c.id
            WHERE r.user_id = ?
            ORDER BY r.date_posted DESC";
            
    $stmt = $conn->prepare($sql);
    $stmt->execute([$u_id]);
    $reviews = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reviews - Rev Nation</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        body { background-color: #f4f7f6; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .review-card { border: none; border-radius: 12px; }
        .table thead { background-color: #212529; color: #fff; }
        .star-rating { color: #f1c40f; letter-spacing: 2px; }
        .reply-box { background-color: #f8f9fa; border-left: 3px solid #2ecc71; padding: 10px; border-radius: 4px; }
    </style>
</head>
<body>
    <?php include "inc/navbar.php"; ?>

    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold">My Feedback</h2>
                <p class="text-muted">History of your ratings and company responses</p>
            </div>
            <span class="badge bg-dark rounded-pill px-3 py-2"><?php echo count($reviews); ?> Reviews</span>
        </div>

        <?php if (isset($_GET['success'])) { ?>
            <div class="alert alert-success border-0 shadow-sm rounded-3">
                <i class="fa fa-check-circle me-2"></i> <?php echo $_GET['success']; ?>
            </div>
        <?php } ?>

        <?php 
        $count = count($reviews);
        if ($count > 0) { 
        ?>
            <div class="card review-card shadow-sm overflow-hidden mb-5">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4 py-3">Item Details</th>
                                <th>Rating</th>
                                <th style="width: 30%;">Comment</th>
                                <th>Company Reply</th>
                                <th class="pe-4 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            for ($i = 0; $i < $count; $i = $i + 1) { 
                                $r = $reviews[$i];
                                
                                $item_name = "Unknown Item";
                                $view_link = "#";
                                
                                if (!empty($r['product_name'])) {
                                    $item_name = $r['product_name'];
                                    $view_link = "product-view.php?id=" . $r['product_id'];
                                } else if (!empty($r['brand'])) {
                                    $item_name = $r['brand'] . " " . $r['model'];
                                    $view_link = "car-view.php?id=" . $r['car_id'];
                                }
                            ?>
                            <tr>
                                <td class="ps-4">
                                    <a href="<?php echo $view_link; ?>" class="text-decoration-none fw-bold text-dark d-block">
                                        <?php echo $item_name; ?>
                                    </a>
                                    <small class="text-muted"><?php echo date("M d, Y", strtotime($r['date_posted'])); ?></small>
                                </td>
                                <td>
                                    <div class="star-rating">
                                        <?php 
                                        for ($j = 0; $j < $r['rating']; $j = $j + 1) { 
                                            echo "★"; 
                                        } 
                                        ?>
                                    </div>
                                </td>
                                <td>
                                    <p class="mb-0 small text-secondary italic">"<?php echo $r['comment']; ?>"</p>
                                </td>
                                <td>
                                    <?php if (!empty($r['company_reply'])) { ?>
                                        <div class="reply-box">
                                            <small class="d-block fw-bold text-success mb-1"><i class="fa fa-reply"></i> Rev Nation:</small>
                                            <small class="text-dark"><?php echo $r['company_reply']; ?></small>
                                        </div>
                                    <?php } else { ?>
                                        <span class="badge bg-light text-muted border fw-normal">Pending Reply</span>
                                    <?php } ?>
                                </td>
                                <td class="pe-4 text-center">
                                    <div class="d-flex gap-2 justify-content-center">
                                        <a href="<?php echo $view_link; ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                            Edit
                                        </a>
                                        <a href="req/review-delete.php?id=<?php echo $r['id']; ?>" 
                                           class="btn btn-sm btn-outline-danger rounded-pill px-3">
                                             <i class="fa fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php } else { ?>
            <div class="text-center bg-white p-5 shadow-sm rounded-3">
                <i class="fa fa-pencil-square-o fa-4x text-light mb-3"></i>
                <h5 class="fw-bold">No feedback yet</h5>
                <p class="text-muted">Share your experience with our products or vehicles!</p>
                <a href="index.php" class="btn btn-dark rounded-pill px-4">Start Shopping</a>
            </div>
        <?php } ?>
        
        <div class="mb-5 text-center">
             <a href="index.php" class="btn btn-light border rounded-pill px-4"><i class="fa fa-arrow-left me-2"></i>Back to Dashboard</a>
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