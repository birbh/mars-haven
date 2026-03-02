<?php
    include '../includes/auth.php';
    include '../config/db.php';
    if($_SESSION['role'] !== 'user'){
        die("Access denied.");
    }
    $isRefresh = isset($_GET['refresh']);
?>
<?php if(!$isRefresh): ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Dashboard</title>
    <link rel="stylesheet" href="../css/user.css">
</head>
<body>
    <h1>User Dashboard</h1>
    <button class="logoutbut" onclick="location.href='../logout.php'">Logout</button>
    <div class="card"><h3>Latest Solar Storm Data</h3>
    <div id='dashboard-content'>
    <?php endif; ?>
        <?php

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
                    echo "<p class='status-danger'>Radiation levels are dangerous! </p>";
                }
                elseif($data['status']==="warning"){
                    echo "<p class='status-warning'>Radiation levels are elevated.</p>";
                }
                else{
                    echo "<p class='status-safe'>Radiation levels are within safe operational limits.</p>";
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
                    echo "<p class='status-safe'>Power systems are operating normally.</p>";
                }
                else
                    echo "<p class='status-danger'>Power systems are in critical mode.</p>";
            }
        ?>
    <?php if(!$isRefresh): ?>
    </div></div><script src="../js/user.js"></script>
</body>
</html>
<?php endif; ?>