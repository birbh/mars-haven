<?php
    include '../includes/auth.php';
    include '../config/db.php';
    if($_SESSION['role'] !== 'astronaut'){
        die("Access denied.");
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Astronaut Dashboard</title>
</head>
<body>
    <h1>Astronaut Dashboard</h1>
    <a href="../logout.php">Logout</a>

    <?php
        $radlog="SELECT * FROM radiation_logs ORDER BY created_at DESC LIMIT 1";
        $result=$conn->query($radlog);
        if($result->num_rows>0){
            $data=$result->fetch_assoc();
            echo"<p>Radiation Updates:</p>";
            echo "<ul>
            <li>Radiation Level: ".$data['radiation_level']."</li>
            <li>Status: ".$data['status']."</li>
            <li>Recorded time: ".$data['created_at']."</li></ul>";
            if($data['status']==="danger"){
                echo "<p style='color:red;'>Danger: Radiation levels are dangerous! Go to the shelter immediately.</p>";
            }
            elseif($data['status']==="warning"){
                echo "<p style='color:orange;'>Warning: Radiation levels are elevated. Limit outdoor activities.</p>";
            }
            else{
                echo "<p style='color:green;'>Radiation levels are safe. You can go outside and chill.</p>";
        }}
        else{
            echo "<p>No radiation data yet....</p>";
        }
        
    ?>
</body>
</html>