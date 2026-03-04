<?php
    if(session_status()===PHP_SESSION_NONE){
        session_start();
    }
    $title=$title ?? "Mars Haven Control";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($title); ?></title>
    <link rel="stylesheet" href="../assets/css/all.css">
</head>
<body>
    <nav class="navtop">
        <div class="navleft">
            <span class="brand">Mars Haven</span>
</div>
    <div class="navright">
        <?php if(isset($_SESSION['username'])): ?>
        <span class="navuser"><?php echo htmlspecialchars($_SESSION['username']); ?>
    (<?php echo htmlspecialchars($_SESSION['role']); ?>)</span>   
    <?php endif; ?>
    <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'astronaut'): ?>
        <a href="../dashboard/astronaut.php">Dashboard</a>
    <?php endif; ?>
    <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'user'): ?>
        <a href="../dashboard/user.php">Dashboard</a>
    <?php endif; ?>
    <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
        <a href="../dashboard/admin.php">Dashboard</a>
    <?php endif; ?>

    <?php if(isset($_SESSION['user_id'])): ?>
        <a href="../logout.php" class="navout">Logout</a>
        <?php endif; ?>
    </div>
    </nav>

    <div class="content">
        