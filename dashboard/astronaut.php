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
    <link rel="stylesheet" href="../css/astro.css">
</head>
<body>
    <h1>Astronaut Dashboard</h1>
    <a href="../logout.php">Logout</a>

    <?php
    // radiation data
        $radlog="SELECT * FROM radiation_logs ORDER BY created_at DESC LIMIT 1";
        $result=$conn->query($radlog);
        if($result->num_rows>0){
            $data=$result->fetch_assoc();
            $radiationdata=$data['status'];
            echo "<div class='card'>";
            echo"<h2>Radiation Monitoring:</h2>";
            echo "<p><strong>Radiation Level:</strong> ".$data['radiation_level']."</p>";
            echo "<p><strong>Status:</strong> ".$data['status']."</p>";
            echo "<p><strong>Recorded time:</strong> ".$data['created_at']."</p>";
            if($data['status']==="danger"){
                echo "<p class='status-danger'>Radiation levels are dangerous. Proceed to shelter immediately.</p>";            }
            elseif($data['status']==="warning"){
                echo "<p class='status-warning'>Radiation elevated. Limit external activity.</p>";            }
            else{
                echo "<p class='status-safe'>Radiation within safe limits.</p>";
            }
            echo "</div>";
        }
        else{
            echo "<p>No radiation data yet....</p>";
        }

// power data
        $pwrlog="SELECT p.*, s.intensity FROM power_logs p LEFT JOIN solar_storms s ON p.storm_id=s.id ORDER BY p.created_at DESC LIMIT 5";
        $pwr=$conn->query($pwrlog);
        if($pwr && $pwr->num_rows>0){
            echo "<div class='card'>";
            echo "<h2>Power Systems:</h2>";
            echo "<table border='1' cellpadding=10>";
            echo "<tr><th>Solar Output</th><th>Battery Level</th><th>Mode</th><th>Intensity</th><th>Recorded</th></tr>";
            while($row=$pwr->fetch_assoc()){
               echo "<tr><td>".$row['solar_output']."</td>
            <td>".$row['battery_level']."</td>
            <td> ".$row['mode']."</td>
            <td>".$row['intensity']."</td>
            <td>".$row['created_at']."</td>
            </tr>";
            }
            echo "</table>";
            $pwr->data_seek(0); 
            $int=$pwr->fetch_assoc();
            if($int['intensity'] >= 9){
                echo "<p class='status-danger'>Extreme solar storm severely impacting power systems.</p>";
            }
            elseif($int['intensity'] >= 7){
                echo "<p class='status-warning'>Strong storm reducing solar efficiency significantly.</p>";
            }
            elseif($int['intensity'] >= 4){
                echo "<p class='status-warning'>Moderate storm affecting energy output.</p>";
            }
            else{
                echo "<p class='status-safe'>Mild storm. Power systems stable.</p>";
            }
            

            $latqrr = $conn->query("SELECT * FROM power_logs ORDER BY created_at DESC LIMIT 1");
            if ($latqrr && $latqrr->num_rows > 0) {
                $power = $latqrr->fetch_assoc();
                if($power['mode']==='critical'){
                    echo "<p class='status-danger'>Power system critical.Conserve energy immediately.</p>";
                    $emer=$conn->query("SELECT * FROM events ORDER BY created_at DESC LIMIT 1");
                    if ($emer && $emer->num_rows>0) {
                        $event=$emer->fetch_assoc();
                        echo "<p class='status-danger'><strong>Emergency:</strong> ".$event['event_type']."<br>Notes: ".$event['notes']."</p>";
                    }
                }
                if ($power['battery_level'] < 30) {
                    echo "<p class='status-warning'>Battery reserves below 30%.</p>";
                }

                if ($power['battery_level'] < 15) {
                    echo "<p class='status-danger'>Emergency battery depletion risk.</p>";
                }
            }
            echo "</div>";
        }
        else{
            echo "<p>No power data yet....</p>";
        }
        
    ?>
</body>
</html>