<?php
    include '../includes/auth.php';

    if($_SESSION['role'] !== 'admin'){
        die("Access denied.");
    }
?>
<h1>Admin Dashboard</h1>
<a href="../logout.php">Logout</a>