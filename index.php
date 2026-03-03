<?php
    session_start();
    if(isset($_SESSION['user_id']) && isset($_SESSION['role'])){
        header("Location: dashboard/".$_SESSION['role'].".php");
        exit();
    }
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mars Haven Control Panel</title>
    <link rel="stylesheet" href="css/index.css">
</head>
<body>
    <div class="ctr">
        <h1>Welcome to Mars Haven Control Panel</h1>
        <p> A real-time habitat monitoring and mission control dashboard.
            Track radiation levels, power systems, storm activity, and automated emergency responses
            through an intelligent predictive monitoring architecture.</p>
        <button onclick="window.location.href='login.php'" class="btn">Access Control Panel</button>
    </div>
    <div class="info">
        <p>Please read the readme file in <a href="https://github.com/birbh/mars-haven.git" target="_blank" style="color: #ff8c00;">github repository</a> for instructions on how to set up and use the control panel. This project is a simulation of a Mars habitat monitoring system, designed for educational and demonstration purposes.</p>
    </div>
    <footer>
        &copy; <?php echo date("Y"); ?> Mars Haven Project
    </footer>
</body>
</html>
        


