
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $email = $_POST['email'] ?? '';
    $userType = $_POST['user'] ?? '';

    $isOwner = ($userType === 'owner') ? 1 : 0; 


    if (empty($user) || empty($password) || empty($email) ) {
        die('All fields are required.');
    }

    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    require 'DB_params.php'; 
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $stmt = $pdo->prepare("INSERT INTO users (username, password, email, isOwner) VALUES (:user, :password, :email, :isOwner)");
        $stmt->execute([
            ':user' => $user,
            ':password' => $hashedPassword,
            ':email' => $email,
            ':isOwner' => $isOwner
        ]);

        echo 'Sign in successful!';
        header('Location: index.php'); 
        exit;
    } catch (PDOException $e) {
        error_log('Database error: ' . $e->getMessage()); 
    die('Database error: ' . $e->getMessage()); 
    }
}
?>