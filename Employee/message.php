<?php 
session_start();

// Updated role check to allow both Manager and Employee
if (isset($_SESSION['user_id']) && 
    isset($_SESSION['role'])    && 
    ($_SESSION['role'] == 'Manager' || $_SESSION['role'] == 'Employee')) {
    
    include "../DB_connection.php";

    // --- FIX: Mark messages as Read when the staff visits this page ---
    $sql_mark_read = "UPDATE message SET status = 'Read' WHERE status = 'Unread'";
    $conn->query($sql_mark_read);
    // --------------------------------------------------------------------
    
    // Check if a search query was submitted
    if (isset($_GET['search'])) {
        $search = $_GET['search'];
        $sql = "SELECT * FROM message 
                WHERE sender_full_name LIKE ? 
                OR sender_email LIKE ? 
                ORDER BY message_id DESC";
        $stmt = $conn->prepare($sql);
        $key = "%$search%";
        $stmt->execute([$key, $key]);
    } else {
        // Default: Show all messages
        $sql = "SELECT * FROM message ORDER BY message_id DESC";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
    }
    
    $messages = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $_SESSION['role']; ?> - Messages</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<body class="bg-light">
    <?php include "inc/navbar.php"; ?>

    <div class="container mt-5" style="width: 90%; max-width: 800px;">
        
        <div class="bg-white p-4 rounded shadow-sm">
            <h4 class="text-center mb-4">Customer Inquiries</h4>

            <form action="message.php" method="get" class="mb-4">
                <div class="input-group">
                    <input type="text" 
                           name="search" 
                           class="form-control" 
                           placeholder="Search by name or email..."
                           value="<?php if(isset($_GET['search'])) echo $_GET['search']; ?>">
                    <button class="btn btn-primary" type="submit">
                        <i class="fa fa-search"></i>
                    </button>
                    <?php if(isset($_GET['search'])) { ?>
                        <a href="message.php" class="btn btn-secondary">Clear</a>
                    <?php } ?>
                </div>
            </form>

            <?php if (isset($_GET['success'])) { ?>
                <div class="alert alert-success"><?php echo $_GET['success']; ?></div>
            <?php } ?>
            <?php if (isset($_GET['error'])) { ?>
                <div class="alert alert-danger"><?php echo $_GET['error']; ?></div>
            <?php } ?>

            <?php 
            if (count($messages) > 0) { 
            ?>
                <div class="accordion accordion-flush" id="accordionMessages">
                  
                  <?php 
                  for ($i = 0; $i < count($messages); $i = $i + 1) { 
                      $msg = $messages[$i];
                      $msg_id = $msg['message_id']; 
                      
                      // Check if this was a "New" message before we refreshed the page
                      $is_unread = ($msg['status'] == 'Unread');
                  ?>

                  <div class="accordion-item shadow-sm mb-3 border rounded">
                    <h2 class="accordion-header" id="flush-heading_<?php echo $msg_id; ?>">
                      <button class="accordion-button collapsed" 
                              type="button" 
                              data-bs-toggle="collapse" 
                              data-bs-target="#flush-collapse_<?php echo $msg_id; ?>">
                        
                        <i class="fa fa-envelope-o me-3 <?php echo ($is_unread) ? 'text-danger' : 'text-primary'; ?>"></i>
                        
                        <div class="d-flex justify-content-between w-100 me-3">
                            <span <?php if($is_unread) echo 'class="fw-bold"'; ?>>
                                <?php echo $msg['sender_full_name']; ?>
                            </span>
                            <small class="text-muted"><?php echo date("M d, Y", strtotime($msg['date_time'])); ?></small>
                        </div>

                      </button>
                    </h2>

                    <div id="flush-collapse_<?php echo $msg_id; ?>" 
                         class="accordion-collapse collapse" 
                         data-bs-parent="#accordionMessages">
                      <div class="accordion-body">
                        
                        <div class="p-3 bg-light rounded mb-3">
                            <p class="mb-0"><?php echo $msg['message']; ?></p>
                        </div>

                        <div class="row text-muted small">
                            <div class="col-sm-6">
                                <i class="fa fa-envelope"></i> <b>Email:</b> <?php echo $msg['sender_email']; ?>
                            </div>
                            <div class="col-sm-6 text-sm-end">
                                <i class="fa fa-clock-o"></i> <b>Received:</b> <?php echo $msg['date_time']; ?>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end border-top mt-3 pt-2">
                            <form action="req/message-delete.php" method="post">
                                <input type="text" name="message_id" value="<?php echo $msg_id; ?>" hidden>
                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                    <i class="fa fa-trash"></i> Delete
                                </button>
                            </form>
                        </div>

                      </div>
                    </div>
                  </div>

                  <?php 
                  } 
                  ?>

                </div>
            <?php 
            } else { 
            ?>
                <div class="alert alert-info m-5 text-center" role="alert">
                    No inquiries found.
                </div>
            <?php 
            } 
            ?>
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