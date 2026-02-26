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
            $radiationstat=$data['status'];
            echo "<div class='card'>";
            echo"<h2>Radiation Monitoring:</h2>";
            echo "<p><strong>Radiation Level:</strong> ".$data['radiation_level']."</p>";
            echo "<p><strong>Status:</strong> ".$data['status']."</p>";
            echo "<p><strong>Recorded time:</strong> ".$data['created_at']."</p>";
            if($radiationstat==="danger"){
                echo "<p class='status-danger'>Radiation levels are dangerous. Proceed to shelter immediately.</p>";            }
            elseif($radiationstat==="warning"){
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
            

            $latqrr = $conn->query("SELECT p.*, s.intensity FROM power_logs p LEFT JOIN solar_storms s ON p.storm_id=s.id ORDER BY p.created_at DESC LIMIT 2");
            if ($latqrr && $latqrr->num_rows > 0) {
                $power = $latqrr->fetch_assoc();
                $prevpower = $latqrr->fetch_assoc();
                $helth=100;
                    //radiation imp 
                    if(isset($radiationstat)){
                        if($radiationstat==="danger"){
                            $helth-=30;
                        }
                        elseif($radiationstat==="warning"){
                            $helth-=15;
                        }
                    }
                    //power imp
                    if($power['mode']==='critical'){
                        $helth-=25;
                    }
                    //battery imp
                    if($power['battery_level'] < 40){
                        $helth-=15;
                    }
                    if($power['battery_level'] < 20){
                        $helth-=10;
                    }
                    //storm imp
                    if(isset($power['intensity']) && $power['intensity'] > 7){
                        $helth-=15;
                    }
                    //negative health 
                    if($helth<0){
                        $helth=0;
                    }
                    // aes
                    if($helth<40){
                        $chk=$conn->query("SELECT *  FROM events WHERE event_type='Emergency Shelter Activated' ORDER BY created_at DESC LIMIT 1");
                        $insemer=true;
                        if($chk &&  $chk->num_rows>0){
                            $pevent=$chk->fetch_assoc();
                            $ptime=strtotime($pevent['created_at']);
                            $ctime=time();
                            if(($ctime-$ptime)<300){
                                $insemer=false;
                            }
                        }
                        if($insemer){
                            $conn->query("INSERT INTO events (event_type, notes)
                            SELECT 'System-wide Critical Condition','Combined system health dropped below 40%. Immediate intervention required.'
                            FROM dual
                            WHERE NOT EXISTS (
                                SELECT 1 FROM events 
                                WHERE event_type='System-wide Critical Condition'
                                AND created_at >= NOW() - INTERVAL 5 MINUTE
                            )
                            ");
                        }
                    }

                echo "<div class='card'>
                <h2>System Health:</h2>
                <p>Overall system health : <strong>".$helth."%</strong>.</p>";
                echo "<div class='healbar'>";
                    if($helth>=80){
                        $color="green";
                    }
                    elseif($helth>=50){
                        $color="orange";
                    }
                    else{
                        $color="red";
                    }
                echo "<div class='healfill' style='width:".$helth."%; background-color:".$color.";'></div>";
                echo "</div>";
                    if($helth>=80){
                        echo "<p class='status-safe'>Habitat system operating in optimal range.</p>";
                    }
                    else if($helth>=50){
                        echo "<p class='status-warning'>System under moderate stress.Monitor closely.</p>";
                    }
                    else{
                        echo "<p class='status-danger'>Habitat system health is critical. Immediate action required.</p>";
                    }
                echo"</div>";

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

                echo "<div class='card'>
                <h2>Battery Trend:</h2>";
                if($prevpower){
                    $diff=$power['battery_level']-$prevpower['battery_level'];
                    if($diff<=-15){
                        echo "<p class='status-warning'>Battery level declining rapidly.▼▼▼▼</p>";
                    }
                    elseif($diff<=-5){
                        echo "<p class='status-warning'>Battery level declining.▼▼</p>";
                    }
                    elseif($diff>5){
                        echo "<p class='status-safe'>Battery level improving.▲▲</p>";
                    }
                    elseif($diff<0){
                        echo "<p class='status-warning'>Battery level declining.▼▼</p>";
                    }
                    else{
                        echo "<p class='status-safe'>Battery level stable.→→</p>";
                    }
                }
                echo "</div>";

                echo "<div class='card'>
                <h2>Storm Forecast:</h2>";
                    $pesc=$conn->query("SELECT intensity FROM solar_storms ORDER BY created_at DESC LIMIT 3");
                    if ($pesc && $pesc->num_rows==3){
                        $i1=$pesc->fetch_assoc();
                        $i2=$pesc->fetch_assoc();
                        $i3=$pesc->fetch_assoc();
                        $i1=$i1['intensity'];
                        $i2=$i2['intensity'];   
                        $i3=$i3['intensity'];
                        if($i1>=$i2 && $i1>=$i3){
                            if($i1>=8){
                                // echo "<p class='status-danger'>Prepare for severe impacts.</p>";
                                $evntlog = $conn->query("SELECT * FROM events WHERE event_type='Storm Escalation Warning' ORDER BY created_at DESC LIMIT 1");
                                if($evntlog && $evntlog->num_rows>0){
                                    $pevent=$evntlog->fetch_assoc();
                                    echo "<p class='status-danger'><strong>Recent Event:</strong> ".$pevent['event_type']."<br>Notes: ".$pevent['notes']."</p>";
                                }
                            }
                            else{
                                $evntlog=$conn->query("SELECT * FROM events WHERE event_type='Storm Escalation Warning' ORDER BY created_at DESC LIMIT 1");
                                if($evntlog && $evntlog->num_rows>0){
                                    $pevent=$evntlog->fetch_assoc();
                                    echo "<p class='status-warning'>".$pevent['event_type']."</p>";
                                }
                            }
                            
                            $evnint=$conn->query("SELECT * FROM events WHERE event_type='Storm Escalation Warning' ORDER BY created_at DESC LIMIT 1");
                            $insevt=true;
                            if($evnint && $evnint->num_rows>0){
                                $pevent=$evnint->fetch_assoc();
                                $ptime=strtotime($pevent['created_at']);
                                $ctime=time();
                                if(($ctime-$ptime)<600){
                                    $insevt=false;
                                }
                            }
                            if($insevt){
                            $conn->query("INSERT INTO events (event_type, notes)
                            SELECT 'Storm Escalation Warning', 'Storm intensity rising toward extreme levels.'
                            FROM dual
                            WHERE NOT EXISTS (
                                SELECT 1 FROM events 
                                WHERE event_type='Storm Escalation Warning'
                                AND created_at >= NOW() - INTERVAL 10 MINUTE
                            )
                            ");
                        }
                        }                
                    }
                echo "</div>";
            }
        }
        else{
            echo "<p>No power data yet....</p>";
        }
        
    ?>
</body>
</html>