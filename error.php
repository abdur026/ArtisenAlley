<?php
session_start();

// Default error settings
$error_code = isset($_GET['code']) ? intval($_GET['code']) : 404;
$error_message = isset($_GET['message']) ? $_GET['message'] : "The page you're looking for doesn't exist.";

// Set appropriate error titles based on error codes
switch ($error_code) {
    case 403:
        $error_title = "Access Denied";
        $error_message = $error_message ?: "You don't have permission to access this resource.";
        break;
    case 500:
        $error_title = "Server Error";
        $error_message = $error_message ?: "Something went wrong on our end. Please try again later.";
        break;
    case 404:
    default:
        $error_title = "Page Not Found";
        $error_message = $error_message ?: "The page you're looking for doesn't exist.";
        $error_code = 404; // Default to 404 for any unspecified error
}

// Set HTTP response code
http_response_code($error_code);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $error_title; ?> | Artisan Alley</title>
    <link rel="stylesheet" href="/src/main.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
        }
        
        .error-container {
            max-width: 600px;
            width: 90%;
            background: white;
            border-radius: 20px;
            padding: 3rem;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        }
        
        .error-code {
            font-size: 6rem;
            font-weight: 700;
            color: #3498db;
            margin: 0;
            line-height: 1;
        }
        
        .error-title {
            font-size: 2rem;
            color: #2c3e50;
            margin: 1rem 0 2rem;
        }
        
        .error-message {
            font-size: 1.1rem;
            color: #7f8c8d;
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        
        .error-icon {
            font-size: 3.5rem;
            color: #3498db;
            margin-bottom: 1.5rem;
        }
        
        .btn {
            display: inline-block;
            background: #3498db;
            color: white;
            text-decoration: none;
            padding: 0.8rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
            margin: 0 10px;
        }
        
        .btn:hover {
            background: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
        }
        
        .btn-home {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin: 0 auto;
            max-width: 200px;
        }
        
        @media (max-width: 600px) {
            .error-container {
                padding: 2rem;
            }
            
            .error-code {
                font-size: 4rem;
            }
            
            .error-title {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <?php if ($error_code === 404): ?>
            <i class="fas fa-map-signs error-icon"></i>
        <?php elseif ($error_code === 403): ?>
            <i class="fas fa-lock error-icon"></i>
        <?php else: ?>
            <i class="fas fa-exclamation-triangle error-icon"></i>
        <?php endif; ?>
        
        <h1 class="error-code"><?php echo $error_code; ?></h1>
        <h2 class="error-title"><?php echo $error_title; ?></h2>
        <p class="error-message"><?php echo $error_message; ?></p>
        
        <a href="/index.php" class="btn btn-home">
            <i class="fas fa-home"></i> Return Home
        </a>
    </div>
</body>
</html> 