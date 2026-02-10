<?php
    include '../includes/auth.php';

    if($_SESSION['role'] !== 'user'){
        die("Access denied.");
    }
?>
<h1>User Dashboard</h1>
<a href="../logout.php">Logout</a>