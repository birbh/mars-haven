<?php
    include '../includes/auth.php';
    include '../config/db.php';
    if($_SESSION['role'] !== 'admin'){
        die("Access denied (dont be sneaky).");
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <h1>Admin Dashboard</h1>
    <button class="logoutbut" onclick="location.href='../logout.php'">Logout</button>
    <h2>Log the Solar storm data</h2>
    <div class="card">
    <form method="POST">
        <label>Storm Intensity(1-10):</label>
        <input type="number" name="intensityy" min="1" max="10" required><br><br>
        <label>Storm Description:</label><br>
        <textarea name="desc" placeholder="Describe storm(optional)....."></textarea><br><br>
        <input type="submit" name="submit" value="Log the data">
</form>
</div>
    <hr>
    <div class="card">
    <h2>Recently Logged Data</h2>


<?php
    if(isset($_POST['submit'])){
        $intense=$_POST['intensityy'];
        $desc=$_POST['desc'];
        $sql="INSERT INTO solar_storms(intensity,description) VALUES($intense,'$desc')";
        if($conn->query($sql)){
            echo "<p class='success'>Data logged successfully.</p>";
            $st_id=$conn->insert_id;
            $radiation=$intense*12.5;
            if($radiation<50){
                $stat="safe";
            }
            elseif($radiation<=90){
                $stat="warning";
            }
            else{
                $stat="danger";
                $conn->query("INSERT INTO events(storm_id,event_type,notes) VALUES($st_id,'Emergency Shelter Activated','Radiation exceeded safe threshold.')");
            }

            $radins="INSERT INTO radiation_logs (storm_id,radiation_level,status) VALUES($st_id,$radiation,'$stat')";
            if(!$conn->query($radins)){
                echo "<p class='error'>Error logging radiation data:</p>";
            }
            else{
                echo "<p class='success'>Radiation data logged successfully.</p>";
            }

            $solar = 100 - $intense * 8;
            $battery = 100 - $intense * 10;

            if ($solar < 40) {
                $mode = "critical";
            } 
            else {
                $mode = "normal";
            }

            $solardata = "INSERT INTO power_logs (storm_id,solar_output, battery_level, mode)
                          VALUES ($st_id,$solar, $battery, '$mode')";

            if ($conn->query($solardata)) {
                echo "<p class='success'>Power data logged successfully.</p>";
            } else {
                echo "<p class='error'>Error logging power data.</p>";
            }
        }
        else{
            echo "<p class='error'>Error logging storm data:</p>";
        }


    }
    $sql="SELECT * FROM solar_storms ORDER BY created_at DESC LIMIT 5";
    $res=$conn->query($sql);
    if($res->num_rows>0){
        echo"<table border=1 cellpadding=10>";
        echo "<tr><th>ID</th>
        <th>Intensity</th>
        <th>Description</th>
        <th>Time</th></tr>";
        while($row=$res->fetch_assoc()){
            echo "<tr>
            <td>".$row['id']."</td>
            <td>".$row['intensity']."</td>
            <td>".$row['description']."</td>
            <td>".$row['created_at']."</td>
            </tr>";
        }
        echo"</table>";
    }
    else
        echo"<p>No storm data found.<br>Log some data to see it.</p>";
?>
</div>
</body>
</html> 
