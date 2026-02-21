<?php
    include '../includes/auth.php';
    include '../config/db.php';

    if($_SESSION['role'] !== 'user'){
        die("Access denied.");
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Dashboard</title>
</head>
<body>
    <h1>User Dashboard</h1>
    <a href="../logout.php">Logout</a>

    <?php
        echo "<h3>Latest Solar Storm Data</h3>";
        $sql="SELECT * FROM solar_storms ORDER BY created_at DESC LIMIT 1";
            $res=$conn->query($sql);
            if($res &&$res->num_rows>0){
                $data=$res->fetch_assoc();
                echo"<ul>
                <li>Intensity: ".$data['intensity']."</li>
                <li>Description: ".$data['description']."</li>
                <li>Time: ".$data['created_at']."</li></ul>";
            }
            else
                echo"<p>No storm data found..</p>";


        echo "<hr><h3>Latest Radiation Data</h3>";
        $radlog="SELECT * FROM radiation_logs ORDER BY created_at DESC LIMIT 1";
            $result=$conn->query($radlog);
            if($result && $result->num_rows>0){
                $data=$result->fetch_assoc();
                echo"<p>Radiation Updates:</p>";
                echo "<ul>
                <li>Radiation Level: ".$data['radiation_level']."</li>
                <li>Status: ".$data['status']."</li>
                <li>Recorded At: ".$data['created_at']."</li></ul>";
                if($data['status']==="danger"){
                    echo "<p style='color:red;'>Danger: Radiation levels are dangerous! Go to the shelter immediately.</p>";
                }
                elseif($data['status']==="warning"){
                    echo "<p style='color:orange;'>Warning: Radiation levels are elevated. Limit outdoor activities.</p>";
                }
                else{
                    echo "<p style='color:green;'>Radiation levels are within safe operational limits.</p>";
            }}
            else{
                echo "<p>No radiation data yet....</p>";
            }


        echo "<hr><h3>Recent Power Logs</h3>";
        $strmlog="SELECT * FROM power_logs ORDER BY created_at DESC LIMIT 1";
            $power=$conn->query($strmlog);
            if($power && $power->num_rows>0){
                $data=$power->fetch_assoc();
                echo "<ul>
                <li>Solar Output: ".$data['solar_output']."</li>
                <li>Battery Level: ".$data['battery_level']."</li>
                <li>Mode: ".$data['mode']."</li>
                <li>Recorded At: ".$data['created_at']."</li></ul>";
                if ($data['mode']==="normal"){
                    echo "<p style='color:green;'>Power systems are operating normally.</p>";
                }
                else
                    echo "<p style='color:red;'>Power systems are in critical mode. Conserve energy where possible.</p>";
            }


    ?>
<body>
</html>


<!-- 🔵 Add automatic emergency event logging when radiation = danger
	•	🔵 Add power reaction to storm intensity
	•	🔵 Improve UI layout (make it look engineered) -->