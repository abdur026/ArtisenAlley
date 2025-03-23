<?php
// Enable full error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once __DIR__ . '/../config/paths.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: " . url('/index.php'));
    exit;
}

$login_error = '';

// Debug paths - now only shows when debug_paths parameter is explicitly set
if (isset($_GET['debug_paths'])) {
    echo "<pre style='background:#f5f5f5;padding:10px;border:1px solid #ccc;'>";
    echo "===== LOGIN PAGE DEBUG INFO =====\n";
    echo "Server Name: " . htmlspecialchars($_SERVER['SERVER_NAME'] ?? '') . "\n";
    echo "Request URI: " . htmlspecialchars($_SERVER['REQUEST_URI'] ?? '') . "\n";
    echo "Script Name: " . htmlspecialchars($_SERVER['SCRIPT_NAME'] ?? '') . "\n";
    echo "Is UBCO Server: " . (strpos($_SERVER['SERVER_NAME'] ?? '', 'cosc360.ok.ubc.ca') !== false ? 'Yes' : 'No') . "\n";
    echo "BASE_URL: " . (defined('BASE_URL') ? BASE_URL : 'Not defined') . "\n";
    echo "SITE_ROOT: " . (defined('SITE_ROOT') ? SITE_ROOT : 'Not defined') . "\n";
    echo "============================\n";
    echo "</pre>";
}

// Process login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        require_once __DIR__ . '/../config/db.php';
        
        if($conn->connect_error) {
            throw new Exception("Database connection failed: " . $conn->connect_error);
        }
    
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'];

        if (!$email || !$password) {
            $_SESSION['error'] = "All fields are required.";
            header("Location: " . url('/login.php'));
            exit;
        }

        $stmt = $conn->prepare("SELECT id, password, username, first_name, last_name, is_admin FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['is_admin'] = $user['is_admin'] ? true : false;
                
                header("Location: " . url('/index.php'));
                exit;
            } else {
                throw new Exception("Password is incorrect");
            }
        } else {
            throw new Exception("No user found with that email");
        }
    } catch (Exception $e) {
        $login_error = "Login failed: " . $e->getMessage();
        error_log("Login error: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Artisan Alley</title>
    <link rel="stylesheet" href="<?php echo asset_url('src/main.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .login-container {
            max-width: 500px;
            width: 90%;
            margin: 2rem auto;
            padding: 2.5rem;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            transform: translateY(0);
            transition: transform 0.3s ease;
            box-sizing: border-box;
        }

        .login-container:hover {
            transform: translateY(-5px);
        }

        .login-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .login-header h1 {
            color: #2c3e50;
            font-size: 2.5rem;
            margin-bottom: 1rem;
            font-weight: 700;
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -0.5px;
        }

        .login-header p {
            color: #7f8c8d;
            font-size: 1.1rem;
            line-height: 1.6;
        }

        .form-group {
            margin-bottom: 1.8rem;
            position: relative;
            box-sizing: border-box;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.7rem;
            color: #2c3e50;
            font-weight: 600;
            font-size: 0.95rem;
            transition: color 0.3s ease;
        }

        .form-group:focus-within label {
            color: #3498db;
        }

        .form-group input {
            width: 100%;
            padding: 1rem 1rem 1rem 2.5rem;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background-color: #f8fafc;
            box-sizing: border-box;
        }

        .form-group input:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 0 4px rgba(52, 152, 219, 0.1);
            background-color: #fff;
        }

        .form-group i {
            position: absolute;
            left: 12px;
            top: 42px;
            color: #95a5a6;
            transition: all 0.3s ease;
        }

        .form-group:focus-within i {
            color: #3498db;
        }

        .login-btn {
            width: 100%;
            padding: 1.1rem;
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.2);
        }

        .login-btn:hover {
            background: linear-gradient(135deg, #2980b9 0%, #2573a7 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(52, 152, 219, 0.3);
        }

        .login-btn:active {
            transform: translateY(0);
        }

        .register-link {
            text-align: center;
            margin-top: 2rem;
            color: #7f8c8d;
            font-size: 0.95rem;
        }

        .register-link a {
            color: #3498db;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            padding-bottom: 2px;
            border-bottom: 1px solid transparent;
        }

        .register-link a:hover {
            color: #2980b9;
            border-bottom-color: #2980b9;
        }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.5s ease;
        }

        .alert i {
            font-size: 1.2rem;
        }

        .alert-error {
            background-color: #fee2e2;
            border-left: 4px solid #ef4444;
            color: #b91c1c;
        }

        .alert-error i {
            color: #ef4444;
        }

        .alert-success {
            background-color: #dcfce7;
            border-left: 4px solid #22c55e;
            color: #15803d;
        }

        .alert-success i {
            color: #22c55e;
        }

        @keyframes slideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @media (max-width: 600px) {
            .login-container {
                margin: 1rem;
                padding: 1.5rem;
                width: calc(100% - 2rem);
            }
        }
    </style>
    <script src="<?php echo asset_url('src/main.js'); ?>" defer></script>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Welcome Back</h1>
            <p>Please enter your credentials to access your account</p>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($_SESSION['error']); ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($_SESSION['success']); ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if ($login_error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($login_error); ?>
            </div>
        <?php endif; ?>

        <form action="<?php echo url('/login.php'); ?>" method="post">
            <div class="form-group">
                <label for="email">Email Address</label>
                <i class="fas fa-envelope"></i>
                <input type="email" id="email" name="email" placeholder="Enter your email" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <i class="fas fa-lock"></i>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
            </div>
            <button type="submit" class="login-btn">Login</button>
        </form>
        <div class="register-link">
            Don't have an account? <a href="<?php echo url('/register.php'); ?>">Register</a>
        </div>
    </div>
</body>
</html>