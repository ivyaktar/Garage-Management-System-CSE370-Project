<?php 
session_start();

if (isset($_SESSION['user_id']) && 
    isset($_SESSION['role'])    && 
    $_SESSION['role'] == 'Customer') {

    include "../DB_connection.php";
    
    $user_id = $_SESSION['user_id'];

    // Fetch builds belonging to the user
    $sql = "SELECT * FROM user_builds WHERE user_id = ? ORDER BY id DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$user_id]);
    $builds = $stmt->fetchAll();

    $total_build_count = count($builds);

    // Fetch IDs of mandatory categories to check build completeness
    $sql_mandatory = "SELECT id FROM categories WHERE is_mandatory = 1";
    $stmt_mandatory = $conn->prepare($sql_mandatory);
    $stmt_mandatory->execute();
    $mandatory_categories = $stmt_mandatory->fetchAll(PDO::FETCH_COLUMN, 0);
    $mandatory_count = count($mandatory_categories);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Saved Car Builds</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        .build-card {
            transition: transform 0.2s;
            border-radius: 12px;
            overflow: hidden;
        }
        .build-card:hover {
            transform: translateY(-5px);
        }
        .stat-card {
            border-radius: 12px;
            border-left: 5px solid #0d6efd;
        }
    </style>
</head>

<body class="bg-light">

    <?php include "inc/navbar.php"; ?>

    <div class="container mt-5">

        <div class="row mb-4 align-items-center">
            <div class="col-md-8">
                <h2 class="display-6 fw-bold">
                    <i class="fa fa-car text-primary"></i> Garage Inventory
                </h2>
                <p class="text-muted">Manage your custom configurations and compatibility checks.</p>
            </div>
            <div class="col-md-4 text-md-end">
                <a href="car-builder.php" class="btn btn-primary btn-lg shadow-sm">
                    <i class="fa fa-plus-circle"></i> Create New Build
                </a>
            </div>
        </div>

        <hr class="mb-5">

        <?php if ($total_build_count == 0) { ?>

            <div class="text-center py-5 bg-white rounded shadow-sm">
                <i class="fa fa-folder-open-o fa-4x text-muted mb-3"></i>
                <h4 class="text-secondary">Your garage is empty</h4>
                <p>You haven't saved any car configurations yet.</p>
                <a href="car-builder.php" class="btn btn-outline-primary mt-2">Start Building Now</a>
            </div>

        <?php } else { ?>
            
            <div class="row mb-5">
                <div class="col-md-4">
                    <div class="card stat-card shadow-sm p-3">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0 bg-primary rounded-circle p-3 text-white">
                                <i class="fa fa-list-ul fa-lg"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-0 text-muted">Saved Projects</h6>
                                <h3 class="mb-0 fw-bold"><?php echo $total_build_count; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">

                <?php 
                for ($i = 0; $i < $total_build_count; $i = $i + 1) { 
                    
                    $build = $builds[$i];
                    $current_build_id = $build['id'];

                    // 1. Logic to calculate Total Build Cost and HP
                    $sql_stats = "SELECT SUM(p.price) as total_sum, SUM(p.hp) as total_hp 
                                  FROM build_items bi 
                                  JOIN products p ON bi.product_id = p.id 
                                  WHERE bi.build_id = ?";
                    $stmt_stats = $conn->prepare($sql_stats);
                    $stmt_stats->execute([$current_build_id]);
                    $stats_result = $stmt_stats->fetch();
                    
                    $total_cost = $stats_result['total_sum'] ?? 0;
                    $total_hp = $stats_result['total_hp'] ?? 0;

                    // 2. Fetch tags and categories for validation
                    $sql_details = "SELECT p.tags, p.category_id FROM build_items bi 
                                    JOIN products p ON bi.product_id = p.id 
                                    WHERE bi.build_id = ?";
                    $stmt_details = $conn->prepare($sql_details);
                    $stmt_details->execute([$current_build_id]);
                    $build_item_details = $stmt_details->fetchAll();

                    $is_compatible = true;
                    $is_complete = true;
                    $common_tags = null;
                    $total_items = count($build_item_details);

                    // 3. Completeness Check: Ensure all mandatory categories are present
                    for ($m = 0; $m < $mandatory_count; $m = $m + 1) {
                        $category_found = false;
                        for ($it = 0; $it < $total_items; $it = $it + 1) {
                            if ($build_item_details[$it]['category_id'] == $mandatory_categories[$m]) {
                                $category_found = true;
                            }
                        }
                        if ($category_found == false) {
                            $is_complete = false;
                        }
                    }

                    // 4. Compatibility Check
                    for ($t_idx = 0; $t_idx < $total_items; $t_idx = $t_idx + 1) {
                        $raw_tags = $build_item_details[$t_idx]['tags'];
                        $current_product_tags = [];
                        
                        if (empty($raw_tags)) {
                            $current_product_tags[] = 'universal';
                        } else {
                            $temp_tags = explode(',', $raw_tags);
                            for ($k = 0; $k < count($temp_tags); $k = $k + 1) {
                                $cleaned = strtolower(trim($temp_tags[$k]));
                                if ($cleaned != "") { $current_product_tags[] = $cleaned; }
                            }
                        }

                        if ($common_tags === null) {
                            $common_tags = $current_product_tags;
                        } else {
                            $new_common = [];
                            $has_universal = false;
                            for ($u = 0; $u < count($common_tags); $u = $u + 1) {
                                if ($common_tags[$u] == 'universal') { $has_universal = true; }
                            }

                            $item_is_uni = false;
                            for ($u = 0; $u < count($current_product_tags); $u = $u + 1) {
                                if ($current_product_tags[$u] == 'universal') { $item_is_uni = true; }
                            }

                            if ($item_is_uni) {
                                $new_common = $common_tags;
                            } else if ($has_universal) {
                                $new_common = $current_product_tags;
                            } else {
                                for ($j = 0; $j < count($common_tags); $j = $j + 1) {
                                    for ($k = 0; $k < count($current_product_tags); $k = $k + 1) {
                                        if ($common_tags[$j] == $current_product_tags[$k]) {
                                            $new_common[] = $common_tags[$j];
                                        }
                                    }
                                }
                            }
                            $common_tags = $new_common;
                        }
                    }

                    if ($total_items > 0) {
                        if (is_array($common_tags)) {
                            if (count($common_tags) == 0) {
                                $is_compatible = false;
                            }
                        } else {
                            $is_compatible = false;
                        }
                    }

                    $display_name = !empty($build['build_name']) ? htmlspecialchars($build['build_name']) : "Unnamed Build #" . $current_build_id;
                ?>

                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card build-card shadow-sm border-0 h-100">
                        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                            <div class="small text-uppercase fw-bold text-primary">PROJECT</div>
                            <span class="badge rounded-pill bg-light text-dark border">
                                <i class="fa fa-calendar-o"></i> <?php echo date("M d, Y", strtotime($build['created_at'])); ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title fw-bold mb-3"><?php echo $display_name; ?></h5>
                            
                            <div class="mb-4">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted small">Total Investment:</span>
                                    <span class="fw-bold text-success h5 mb-0">
                                        <?php echo "$" . number_format($total_cost, 2); ?>
                                    </span>
                                </div>
                                
                                <div class="d-flex justify-content-between mb-3">
                                    <span class="text-muted small">Performance:</span>
                                    <span class="fw-bold text-dark">
                                        <i class="fa fa-bolt text-warning"></i> <?php echo number_format($total_hp); ?> HP
                                    </span>
                                </div>
                                
                                <div class="p-2 rounded bg-light border text-center">
                                    <?php if (!$is_complete) { ?>
                                        <small class="text-danger fw-bold">
                                            <i class="fa fa-times-circle"></i> Invalid (Missing Parts)
                                        </small>
                                    <?php } else if (!$is_compatible) { ?>
                                        <small class="text-danger fw-bold">
                                            <i class="fa fa-warning"></i> Conflict Detected
                                        </small>
                                    <?php } else { ?>
                                        <small class="text-success fw-bold">
                                            <i class="fa fa-check-circle"></i> Status: Optimized
                                        </small>
                                    <?php } ?>
                                </div>
                            </div>

                            <div class="row g-2">
                                <div class="col-9">
                                    <a href="view-build-details.php?id=<?php echo $current_build_id; ?>" 
                                       class="btn btn-outline-primary w-100">
                                         <i class="fa fa-search"></i> Inspect Build
                                    </a>
                                </div>
                                <div class="col-3">
                                    <a href="req/delete-build.php?id=<?php echo $current_build_id; ?>" 
                                       class="btn btn-outline-danger w-100"
                                       onclick="return confirm('Archive this project?')">
                                         <i class="fa fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <?php } ?>

            </div>

        <?php } ?>

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