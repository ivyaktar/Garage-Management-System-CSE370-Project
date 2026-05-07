<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] != 'Customer') {
    header("Location: ../login.php");
    exit;
}

include "../DB_connection.php";
$user_id = $_SESSION['user_id'];

// DEBUG - See submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['budget'])) {
    echo "<div style='position:fixed;top:0;left:0;right:0;background:#28a745;color:white;padding:10px;text-align:center;z-index:9999'>";
    echo "✅ Form submitted! Budget = $" . floatval($_POST['budget']);
    echo "</div>";
}

function knapsackBuildCar($budget, $conn) {
    $sql = "SELECT p.id, p.product_name, p.price, p.quantity, p.image, p.description, p.hp,
                   c.id AS cat_id, c.category_name, c.is_mandatory
            FROM products p
            JOIN categories c ON p.category_id = c.id
            WHERE p.status = 'In Stock' AND p.quantity > 0 AND c.type = 'Part'
            ORDER BY c.is_mandatory DESC, c.category_name ASC, p.price ASC";
    
    $stmt = $conn->prepare($sql); // prepare sql 
    $stmt->execute(); //run sql query 
    $all_products = $stmt->fetchAll();
    
    $categories = [];
    foreach ($all_products as $product) {
        $cat_id = $product['cat_id'];
        if (!isset($categories[$cat_id])) {
            $categories[$cat_id] = [
                'name' => $product['category_name'],
                'is_mandatory' => $product['is_mandatory'],
                'products' => []
            ];
        }
        $categories[$cat_id]['products'][] = $product;
    }
    
    $mandatory = [];
    $optional = [];
    foreach ($categories as $cat) {
        if ($cat['is_mandatory'] == 1) {
            $mandatory[] = $cat;
        } else {
            $optional[] = $cat;
        }
    }
    
    $selected = [];
    $remaining = $budget;
    
    foreach ($mandatory as $cat) {
        foreach ($cat['products'] as $part) {
            if ($part['price'] <= $remaining) {
                $selected[] = $part;
                $remaining -= $part['price'];
                break;
            }
        }
    }
    
    foreach ($optional as $cat) {
        foreach ($cat['products'] as $part) {
            if ($part['price'] <= $remaining) {
                $selected[] = $part;
                $remaining -= $part['price'];
                break;
            }
        }
    }
    
    $total_cost = 0;
    $total_hp = 0;
    foreach ($selected as $part) {
        $total_cost += $part['price'];
        $total_hp += $part['hp'];
    }
    
    return [
        'parts' => $selected,
        'total_cost' => $total_cost,
        'total_hp' => $total_hp,
        'remaining' => $budget - $total_cost
    ];
}

$result = null;
$error = null;
$budget_value = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['budget'])) {
    $budget_value = floatval($_POST['budget']);
    
    if ($budget_value <= 0) {
        $error = "Please enter a budget greater than zero.";
    } else {
        $result = knapsackBuildCar($budget_value, $conn);
        if (count($result['parts']) == 0) {
            $error = "No parts found within your budget. Try increasing it.";
            $result = null;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_cart']) && isset($_POST['part_ids'])) {
    $added = 0;
    foreach ($_POST['part_ids'] as $pid) {
        $check = $conn->prepare("SELECT id FROM cart WHERE customer_id = ? AND product_id = ?");
        $check->execute([$user_id, $pid]);
        if ($check->rowCount() > 0) {
            $conn->prepare("UPDATE cart SET quantity = quantity + 1 WHERE customer_id = ? AND product_id = ?")->execute([$user_id, $pid]);
        } else {
            $conn->prepare("INSERT INTO cart (customer_id, product_id, quantity) VALUES (?, ?, 1)")->execute([$user_id, $pid]);
        }
        $added++;
    }
    header("Location: budget-knapsack.php?success=" . urlencode("$added item(s) added to cart!"));
    exit;
}

$stats = $conn->query("SELECT COUNT(*) as total FROM products p JOIN categories c ON p.category_id = c.id WHERE p.status = 'In Stock' AND c.type = 'Part'")->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title> Budget Builder</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding-bottom: 50px; }
        .card-budget { background: white; border-radius: 20px; padding: 30px; margin-top: 30px; }
        .card-result { background: white; border-radius: 20px; padding: 20px; margin-top: 20px; }
        .btn-custom { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; color: white; }
        .btn-custom:hover { transform: translateY(-2px); color: white; }
    </style>
</head>
<body>
<?php include "inc/navbar.php"; ?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            
            <div class="card-budget shadow-lg text-center">
                <i class="fa fa-calculator fa-4x text-primary mb-3"></i>
                <h2 class="fw-bold">🎒 Budget Builder</h2>
                <p class="text-muted">Enter your budget - the algorithm selects the best parts automatically!</p>
                
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="" class="mt-4" novalidate>
                    <div class="row g-3">
                        <div class="col-md-8">
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-white fw-bold">$</span>
                                <input type="number" 
                                       name="budget" 
                                       class="form-control" 
                                       placeholder="5000" 
                                       min="1" 
                                       step="1"
                                       required>
                            </div>
                            <small class="text-muted">Enter amount in USD (minimum $1)</small>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-custom btn-lg w-100 fw-bold">
                                <i class="fa fa-magic me-2"></i> Build My Car
                            </button>
                        </div>
                    </div>
                </form>
                
                <div class="mt-4 text-muted small">
                    <i class="fa fa-database me-1"></i> <?php echo number_format($stats['total'] ?? 0); ?> parts available in inventory
                </div>
            </div>
            
            <?php if ($result && count($result['parts']) > 0): ?>
                <div class="card-result shadow-lg">
                    <div class="d-flex justify-content-between border-bottom pb-3 mb-3">
                        <h4 class="text-success"><i class="fa fa-check-circle me-2"></i> Your Optimal Build</h4>
                        <div class="text-end">
                            <div><strong>Budget:</strong> $<?php echo number_format($budget_value, 2); ?></div>
                            <div><strong>Total:</strong> $<?php echo number_format($result['total_cost'], 2); ?></div>
                            <div><strong>Leftover:</strong> $<?php echo number_format($result['remaining'], 2); ?></div>
                        </div>
                    </div>
                    
                    <form method="POST" action="">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr><th>Part</th><th>Category</th><th class="text-center">HP</th><th class="text-end">Price</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($result['parts'] as $part): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($part['product_name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($part['category_name']); ?></td>
                                        <td class="text-center"><span class="badge bg-dark"><?php echo $part['hp']; ?> HP</span></td>
                                        <td class="text-end fw-bold text-success">$<?php echo number_format($part['price'], 2); ?></td>
                                        <td><input type="hidden" name="part_ids[]" value="<?php echo $part['id']; ?>"></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot class="table-dark">
                                    <tr><td colspan="2"><strong>TOTAL</strong></td><td class="text-center"><strong><?php echo $result['total_hp']; ?> HP</strong></td><td class="text-end"><strong>$<?php echo number_format($result['total_cost'], 2); ?></strong></td><td></td></tr>
                                </tfoot>
                            </table>
                        </div>
                        <div class="mt-3 d-flex justify-content-between">
                            <a href="store.php" class="btn btn-outline-secondary">Browse Store</a>
                            <button type="submit" name="add_cart" value="1" class="btn btn-success btn-lg">
                                <i class="fa fa-shopping-cart me-2"></i> Add All to Cart
                            </button>
                        </div>
                    </form>
                </div>
            <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error): ?>
                <div class="card-result shadow-lg text-center">
                    <i class="fa fa-frown-o fa-4x text-muted mb-3"></i>
                    <h4>No parts found</h4>
                    <p>Try a higher budget or <a href="store.php">browse the store</a> to see available parts.</p>
                </div>
            <?php else: ?>
                <div class="card-result shadow-lg text-center">
                    <i class="fa fa-car fa-4x text-primary mb-3"></i>
                    <h4>Ready to build your dream car?</h4>
                    <p>Enter your budget above and click "Build My Car"</p>
                    <div class="alert alert-light border mt-3">
                        <strong>How it works:</strong><br>
                        1️⃣ Enter your budget<br>
                        2️⃣ Algorithm scans all parts<br>
                        3️⃣ Automatically selects best options<br>
                        4️⃣ One click adds everything to cart!
                    </div>
                </div>
            <?php endif; ?>
            
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>