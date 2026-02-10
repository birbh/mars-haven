<?php
    include '../includes/auth.php';

    if($_SESSION['role'] !== 'astronaut'){
        die("Access denied.");
    }
?>
<h1>Astronaut Dashboard</h1>
<a href="../logout.php">Logout</a>