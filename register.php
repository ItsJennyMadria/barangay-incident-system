<?php
session_start();
require_once 'config/db.php';

$officerStmt = $pdo->query("
    SELECT o.Officer_ID, o.Officer_Name 
    FROM OFFICER o 
    LEFT JOIN USERS u ON o.Officer_ID = u.Officer_ID 
    WHERE u.User_ID IS NULL
");
$available_officers = $officerStmt->fetchAll();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    $officer_id = $_POST['officer_id'];

    if (!empty($username) && !empty($password) && !empty($role) && !empty($officer_id)) {
        $password_hash = password_hash($password, PASSWORD_BCRYPT);

        try {
            $stmt = $pdo->prepare("INSERT INTO USERS (Username, Password_Hash, Role, Officer_ID) VALUES (?, ?, ?, ?)");
            $stmt->execute([$username, $password_hash, $role, $officer_id]);
            $message = "<p style='color:green;'>Registration successful! <a href='login.php'>Login here</a></p>";
        } catch (PDOException $e) {
            $message = "<p style='color:red;'>Error: Username or Officer profile already registered.</p>";
        }
    } else {
        $message = "<p style='color:red;'>Please fill all fields.</p>";
    }
}
?>

<!DOCTYPE html>
<html>
<head><title>Register Account</title></head>
<body>
    <h2>Barangay System Account Registration</h2>
    <?= $message ?>
    <form method="POST" action="register.php">
        <label>Select Officer Profile:</label><br>
        <select name="officer_id" required>
            <option value="">-- Choose Officer --</option>
            <?php foreach ($available_officers as $officer): ?>
                <option value="<?= $officer['Officer_ID'] ?>"><?= $officer['Officer_ID'] ?> - <?= $officer['Officer_Name'] ?></option>
            <?php endforeach; ?>
        </select><br><br>

        <label>Username:</label><br>
        <input type="text" name="username" required><br><br>

        <label>Password:</label><br>
        <input type="password" name="password" required><br><br>

        <label>Role:</label><br>
        <select name="role" required>
            <option value="Officer">Officer</option>
            <option value="Admin">Admin</option>
        </select><br><br>

        <button type="submit">Create Account</button>
    </form>
    <p>Already registered? <a href="login.php">Login here</a></p>
</body>
</html>