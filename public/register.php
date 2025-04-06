<?php
session_start();
require_once '../includes/utils/csrf.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'], 'register_form')) {
        $_SESSION['error'] = "Invalid form submission. Please try again.";
        header("Location: register.php");
        exit;
    }

    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    if (!$name || !$email || !$password) {
        $_SESSION['error'] = "All fields are required.";
        header("Location: register.php");
        exit;
    }

    // Check if profile image was uploaded
    $profile_image = null;
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $file_type = $_FILES['profile_image']['type'];
        
        if (!in_array($file_type, $allowed_types)) {
            $_SESSION['error'] = "Invalid file type. Please upload a JPEG, PNG, GIF, or WEBP image.";
            header("Location: register.php");
            exit;
        }
        
        $file_size = $_FILES['profile_image']['size'];
        if ($file_size > 5242880) { // 5MB
            $_SESSION['error'] = "File is too large. Maximum file size is 5MB.";
            header("Location: register.php");
            exit;
        }
        
        // Create uploads directory if it doesn't exist
        $upload_dir = '../uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Generate unique filename
        $file_extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
        $filename = 'profile_' . time() . '_' . uniqid() . '.' . $file_extension;
        $upload_path = $upload_dir . $filename;
        
        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
            $profile_image = $filename;
        } else {
            $_SESSION['error'] = "Failed to upload image. Please try again.";
            header("Location: register.php");
            exit;
        }
    } else {
        $_SESSION['error'] = "Profile image is required. Please upload an image.";
        header("Location: register.php");
        exit;
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    require_once '../config/db.php';
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, profile_image) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $email, $hashedPassword, $profile_image);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Registration successful! Please log in.";
        header("Location: login.php");
    } else {
        $_SESSION['error'] = "Error: " . $stmt->error;
        header("Location: register.php");
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Artisan Alley</title>
    <link rel="stylesheet" href="/src/main.css">
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

        .register-container {
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

        .register-container:hover {
            transform: translateY(-5px);
        }

        .register-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .register-header h1 {
            color: #2c3e50;
            font-size: 2.5rem;
            margin-bottom: 1rem;
            font-weight: 700;
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -0.5px;
        }

        .register-header p {
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

        .form-group input[type="file"] {
            padding: 0.8rem 1rem;
            padding-left: 2.5rem;
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

        .register-btn {
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

        .register-btn:hover {
            background: linear-gradient(135deg, #2980b9 0%, #2573a7 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(52, 152, 219, 0.3);
        }

        .register-btn:active {
            transform: translateY(0);
        }

        .login-link {
            text-align: center;
            margin-top: 2rem;
            color: #7f8c8d;
            font-size: 0.95rem;
        }

        .login-link a {
            color: #3498db;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            padding-bottom: 2px;
            border-bottom: 1px solid transparent;
        }

        .login-link a:hover {
            color: #2980b9;
            border-bottom-color: #2980b9;
            text-decoration: none;
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
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .alert-success {
            background-color: #dcfce7;
            color: #16a34a;
            border: 1px solid #bbf7d0;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 600px) {
            .register-container {
                margin: 1rem;
                padding: 1.5rem;
                width: calc(100% - 2rem);
                max-width: none;
            }

            .register-header h1 {
                font-size: 2rem;
            }

            .form-group input {
                font-size: 16px; /* Prevents zoom on mobile */
            }
        }

        /* Password strength indicator */
        .password-strength {
            height: 4px;
            background: #e0e0e0;
            border-radius: 2px;
            margin-top: 8px;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .password-strength-bar {
            height: 100%;
            width: 0;
            transition: all 0.3s ease;
            border-radius: 2px;
        }

        .strength-weak { width: 33.33%; background: #e74c3c; }
        .strength-medium { width: 66.66%; background: #f1c40f; }
        .strength-strong { width: 100%; background: #2ecc71; }

        /* Image preview */
        .image-preview {
            width: 100%;
            max-width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #e0e0e0;
            margin: 10px auto;
            display: block;
        }

        .image-preview-container {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .file-input-container {
            position: relative;
        }

        .custom-file-upload {
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            display: inline-block;
            padding: 1rem 1rem 1rem 2.5rem;
            cursor: pointer;
            width: 100%;
            background-color: #f8fafc;
            box-sizing: border-box;
            font-size: 1rem;
            text-align: left;
            transition: all 0.3s ease;
            color: #95a5a6;
        }

        .custom-file-upload:hover {
            border-color: #3498db;
        }

        .input-file {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }
    </style>
    <script src="../src/main.js" defer></script>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <h1>Create Account</h1>
            <p>Join our community of artisans and art lovers</p>
        </div>

        <?php
        if (isset($_SESSION['error'])) {
            echo "<div class='alert alert-error'><i class='fas fa-exclamation-circle'></i> " . $_SESSION['error'] . "</div>";
            unset($_SESSION['error']);
        }
        if (isset($_SESSION['success'])) {
            echo "<div class='alert alert-success'><i class='fas fa-check-circle'></i> " . $_SESSION['success'] . "</div>";
            unset($_SESSION['success']);
        }
        ?>

        <form action="register.php" method="POST" enctype="multipart/form-data" onsubmit="return validateRegistrationForm();">
            <?php echo csrf_token_field('register_form'); ?>
            
            <div class="image-preview-container">
                <img id="preview-image" src="/assets/images/default-avatar.png" alt="Profile Preview" class="image-preview">
            </div>
            
            <div class="form-group">
                <label for="profile_image">Profile Image</label>
                <div class="file-input-container">
                    <label for="profile_image" class="custom-file-upload">
                        <i class="fas fa-camera"></i>
                        <span id="file-name">Choose a profile picture</span>
                    </label>
                    <input type="file" id="profile_image" name="profile_image" class="input-file" accept="image/*" required onchange="previewImage(this)">
                </div>
            </div>

            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" required placeholder="Enter your full name">
                <i class="fas fa-user"></i>
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required placeholder="Enter your email">
                <i class="fas fa-envelope"></i>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required placeholder="Choose a strong password" onkeyup="checkPasswordStrength(this.value)">
                <i class="fas fa-lock"></i>
                <div class="password-strength">
                    <div class="password-strength-bar"></div>
                </div>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required placeholder="Confirm your password">
                <i class="fas fa-lock"></i>
            </div>

            <button type="submit" class="register-btn">Create Account</button>
        </form>

        <div class="login-link">
            <p>Already have an account? <a href="login.php">Sign in here</a></p>
        </div>
    </div>
    <script>
        function checkPasswordStrength(password) {
            const strengthBar = document.querySelector('.password-strength-bar');
            const hasLower = /[a-z]/.test(password);
            const hasUpper = /[A-Z]/.test(password);
            const hasNumber = /\d/.test(password);
            const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(password);
            const length = password.length;

            let strength = 0;
            if (length > 7) strength++;
            if (hasLower && hasUpper) strength++;
            if (hasNumber) strength++;
            if (hasSpecial) strength++;

            strengthBar.className = 'password-strength-bar';
            if (strength >= 4) {
                strengthBar.classList.add('strength-strong');
            } else if (strength >= 2) {
                strengthBar.classList.add('strength-medium');
            } else if (strength >= 1) {
                strengthBar.classList.add('strength-weak');
            }
        }

        function previewImage(input) {
            const preview = document.getElementById('preview-image');
            const fileNameSpan = document.getElementById('file-name');
            
            if (input.files && input.files[0]) {
                const fileName = input.files[0].name;
                fileNameSpan.textContent = fileName.length > 25 ? fileName.substring(0, 22) + '...' : fileName;
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                };
                reader.readAsDataURL(input.files[0]);
            } else {
                preview.src = '/assets/images/default-avatar.png';
                fileNameSpan.textContent = 'Choose a profile picture';
            }
        }

        function validateRegistrationForm() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const profileImage = document.getElementById('profile_image');
            
            if (password !== confirmPassword) {
                alert('Passwords do not match!');
                return false;
            }
            
            if (!profileImage.files || !profileImage.files[0]) {
                alert('Please select a profile image!');
                return false;
            }
            
            return true;
        }
    </script>
</body>
</html>