<?php
    session_start();
    include "config/db.php";

    $error="";

    if(isset($_POST['submit'])){
        $login = $_POST['login'];
        $password = hash("sha256", $_POST['password']);

        $sql = "SELECT id,role FROM users
                WHERE (username='$login' OR email='$login') AND password_hash='$password'
                ";
        
        $result = $conn->query($sql);

        if($result && $result->num_rows === 1){
            $user = $result->fetch_assoc();

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];

            header("Location: dashboard.php" . $user['role'] . ".php");
            exit();
        }
        else{
            $error = "Invalid login credentials.";
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mars Haven - Login</title>
</head>
<body>
    <h2>Login to Mars Haven</h2>

    <form method="POST" action="">
        <input type="text" placeholder="Username or Email" name="login" required><br><br>
        <input type="password" placeholder="Password" name="password" required><br><br>
        <input type="submit" name="submit" value="Login">
    </form>

    <p style="color:red;"><?php echo $error; ?></p>
</body>
</html> 

