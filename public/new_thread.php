<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "You must be logged in to create a thread.";
    header('Location: login.php');
    exit;
}

// Get category ID from URL if available
$category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;

// Get categories for the dropdown
$categories_query = "SELECT * FROM forum_categories ORDER BY display_order";
$categories_result = $conn->query($categories_query);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = htmlspecialchars(trim($_POST['title']), ENT_QUOTES, 'UTF-8');
    $content = $_POST['content']; // We'll sanitize this when displaying
    $post_category_id = filter_input(INPUT_POST, 'category_id', FILTER_SANITIZE_NUMBER_INT);
    $user_id = $_SESSION['user_id'];

    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $_SESSION['error'] = "Invalid form submission.";
        header("Location: new_thread.php" . ($category_id ? "?category_id=$category_id" : ""));
        exit;
    }
    
    // Validate inputs
    if (empty($title) || empty($content) || empty($post_category_id)) {
        $_SESSION['error'] = "All fields are required.";
        header("Location: new_thread.php" . ($category_id ? "?category_id=$category_id" : ""));
        exit;
    }
    
    // Create the thread
    $stmt = $conn->prepare("INSERT INTO forum_threads (category_id, user_id, title, content) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiss", $post_category_id, $user_id, $title, $content);
    
    if ($stmt->execute()) {
        $thread_id = $conn->insert_id;
        $_SESSION['success'] = "Thread created successfully!";
        header("Location: thread.php?id=$thread_id");
        exit;
    } else {
        $_SESSION['error'] = "Error creating thread: " . $stmt->error;
        header("Location: new_thread.php" . ($category_id ? "?category_id=$category_id" : ""));
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Thread - Artisan Alley Forum</title>
    <link rel="stylesheet" href="/assets/css/main.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .new-thread-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }

        .new-thread-header {
            background: linear-gradient(135deg, #3498db 0%, #2c3e50 100%);
            border-radius: 20px;
            padding: 2.5rem 2rem;
            color: white;
            margin-bottom: 2rem;
            text-align: center;
        }

        .new-thread-header h1 {
            font-size: 2rem;
            margin-bottom: 1rem;
        }

        .new-thread-header p {
            font-size: 1.1rem;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto;
        }

        .new-thread-form {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #2c3e50;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .form-group textarea {
            min-height: 200px;
            resize: vertical;
        }

        .form-buttons {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: #3498db;
            color: white;
            border: none;
        }

        .btn-primary:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #f1f5f9;
            color: #64748b;
            border: none;
        }

        .btn-secondary:hover {
            background: #e2e8f0;
            color: #334155;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
        }

        .breadcrumb a {
            color: #64748b;
            text-decoration: none;
        }

        .breadcrumb a:hover {
            color: #3498db;
            text-decoration: underline;
        }

        .breadcrumb-separator {
            color: #cbd5e1;
        }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .alert-error {
            background-color: #fee2e2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .formatting-options {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .format-btn {
            padding: 0.4rem 0.6rem;
            background: #f1f5f9;
            border: none;
            border-radius: 4px;
            color: #334155;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .format-btn:hover {
            background: #e2e8f0;
        }

        .required {
            color: #dc2626;
            margin-left: 0.25rem;
        }
    </style>
</head>
<body>
    <?php require_once '../includes/header.php'; ?>
    
    <div class="new-thread-container">
        <div class="breadcrumb">
            <a href="forum.php">Forum</a>
            <span class="breadcrumb-separator"><i class="fas fa-chevron-right"></i></span>
            <span>Create New Thread</span>
        </div>
        
        <div class="new-thread-header">
            <h1>Start a New Discussion</h1>
            <p>Share your ideas, questions, and insights with the Artisan Alley community.</p>
        </div>
        
        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        
        <div class="new-thread-form">
            <form action="new_thread.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="form-group">
                    <label for="category_id">Category<span class="required">*</span></label>
                    <select id="category_id" name="category_id" required>
                        <option value="">-- Select Category --</option>
                        <?php while($category = $categories_result->fetch_assoc()): ?>
                            <option value="<?php echo $category['id']; ?>" <?php echo $category_id == $category['id'] ? 'selected' : ''; ?>>
                                <?php echo $category['name']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="title">Thread Title<span class="required">*</span></label>
                    <input type="text" id="title" name="title" placeholder="Enter a descriptive title" required>
                </div>
                
                <div class="form-group">
                    <label for="content">Content<span class="required">*</span></label>
                    <div class="formatting-options">
                        <button type="button" class="format-btn" onclick="addFormatting('bold')"><i class="fas fa-bold"></i></button>
                        <button type="button" class="format-btn" onclick="addFormatting('italic')"><i class="fas fa-italic"></i></button>
                        <button type="button" class="format-btn" onclick="addFormatting('underline')"><i class="fas fa-underline"></i></button>
                        <button type="button" class="format-btn" onclick="addFormatting('list')"><i class="fas fa-list-ul"></i></button>
                        <button type="button" class="format-btn" onclick="addFormatting('link')"><i class="fas fa-link"></i></button>
                    </div>
                    <textarea id="content" name="content" placeholder="Write your post here..." required></textarea>
                </div>
                
                <div class="form-buttons">
                    <a href="javascript:history.back()" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Create Thread</button>
                </div>
            </form>
        </div>
    </div>
    
    <?php require_once '../includes/footer.php'; ?>
    
    <script>
    function addFormatting(type) {
        const textarea = document.getElementById('content');
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const selectedText = textarea.value.substring(start, end);
        
        let formattedText = '';
        
        switch(type) {
            case 'bold':
                formattedText = `<strong>${selectedText}</strong>`;
                break;
            case 'italic':
                formattedText = `<em>${selectedText}</em>`;
                break;
            case 'underline':
                formattedText = `<u>${selectedText}</u>`;
                break;
            case 'list':
                formattedText = `<ul>\n  <li>${selectedText}</li>\n  <li></li>\n</ul>`;
                break;
            case 'link':
                const url = prompt('Enter URL:', 'https://');
                if (url) {
                    formattedText = `<a href="${url}">${selectedText || url}</a>`;
                } else {
                    return;
                }
                break;
        }
        
        textarea.focus();
        if (typeof textarea.setRangeText === 'function') {
            textarea.setRangeText(formattedText);
        } else {
            // Fallback for older browsers
            textarea.value = textarea.value.substring(0, start) + formattedText + textarea.value.substring(end);
        }
    }
    </script>
</body>
</html> 