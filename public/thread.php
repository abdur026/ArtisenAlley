<?php
session_start();
require_once '../config/db.php';

// Get thread ID from URL
$thread_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$thread_id) {
    header('Location: forum.php');
    exit;
}

// Update thread view count
$update_views = $conn->prepare("UPDATE forum_threads SET views = views + 1 WHERE id = ?");
$update_views->bind_param('i', $thread_id);
$update_views->execute();

// Get thread info with category and author
$thread_query = "SELECT t.*, c.name as category_name, c.id as category_id, u.name as author_name, u.profile_image 
                FROM forum_threads t 
                JOIN forum_categories c ON t.category_id = c.id 
                JOIN users u ON t.user_id = u.id 
                WHERE t.id = ?";
$stmt = $conn->prepare($thread_query);
$stmt->bind_param('i', $thread_id);
$stmt->execute();
$thread_result = $stmt->get_result();

if ($thread_result->num_rows === 0) {
    header('Location: forum.php');
    exit;
}

$thread = $thread_result->fetch_assoc();

// Handle new reply submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reply') {
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['error'] = "You must be logged in to reply.";
        header("Location: login.php");
        exit;
    }
    
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $_SESSION['error'] = "Invalid form submission.";
        header("Location: thread.php?id=$thread_id");
        exit;
    }
    
    $content = $_POST['content']; // We'll sanitize this when displaying
    $user_id = $_SESSION['user_id'];
    
    // Validate inputs
    if (empty($content)) {
        $_SESSION['error'] = "Reply content cannot be empty.";
        header("Location: thread.php?id=$thread_id");
        exit;
    }
    
    // Check if thread is locked
    if ($thread['is_locked']) {
        $_SESSION['error'] = "This thread is locked and cannot be replied to.";
        header("Location: thread.php?id=$thread_id");
        exit;
    }
    
    // Insert reply
    $stmt = $conn->prepare("INSERT INTO forum_replies (thread_id, user_id, content) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $thread_id, $user_id, $content);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Reply posted successfully!";
        
        // Check if this is an AJAX request
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            // Get the new reply with user info
            $reply_id = $conn->insert_id;
            $reply_query = "SELECT r.*, u.name as author_name, u.profile_image 
                          FROM forum_replies r 
                          JOIN users u ON r.user_id = u.id 
                          WHERE r.id = ?";
            $stmt = $conn->prepare($reply_query);
            $stmt->bind_param('i', $reply_id);
            $stmt->execute();
            $reply_result = $stmt->get_result();
            $reply = $reply_result->fetch_assoc();
            
            // Return JSON response
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'reply' => [
                    'id' => $reply['id'],
                    'content' => $reply['content'],
                    'created_at' => $reply['created_at'],
                    'author_name' => $reply['author_name'],
                    'profile_image' => $reply['profile_image']
                ]
            ]);
            exit;
        }
        
        header("Location: thread.php?id=$thread_id#reply-$conn->insert_id");
        exit;
    } else {
        $_SESSION['error'] = "Error posting reply: " . $stmt->error;
        header("Location: thread.php?id=$thread_id");
        exit;
    }
}

// Get replies
$replies_query = "SELECT r.*, u.name as author_name, u.profile_image, u.role as user_role 
                FROM forum_replies r 
                JOIN users u ON r.user_id = u.id 
                WHERE r.thread_id = ? 
                ORDER BY r.created_at ASC";
$stmt = $conn->prepare($replies_query);
$stmt->bind_param('i', $thread_id);
$stmt->execute();
$replies_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $thread['title']; ?> - Artisan Alley Forum</title>
    <link rel="stylesheet" href="/assets/css/main.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .thread-container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 0 1.5rem;
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

        .thread-header {
            background: linear-gradient(135deg, #3498db 0%, #2c3e50 100%);
            border-radius: 20px;
            padding: 2rem;
            color: white;
            margin-bottom: 2rem;
        }

        .thread-title {
            font-size: 1.8rem;
            margin: 0 0 1rem;
            font-weight: 700;
        }

        .thread-meta {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            font-size: 0.95rem;
            opacity: 0.9;
        }

        .thread-status {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .status-tag {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.3rem 0.6rem;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .sticky-tag {
            background: rgba(52, 152, 219, 0.3);
        }

        .locked-tag {
            background: rgba(231, 76, 60, 0.3);
        }

        .post {
            background: white;
            border-radius: 15px;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            display: flex;
        }

        .post-sidebar {
            width: 200px;
            padding: 1.5rem;
            border-right: 1px solid #eee;
            background: #f8f9fa;
            border-radius: 15px 0 0 15px;
            text-align: center;
        }

        .post-author {
            margin-bottom: 1rem;
        }

        .author-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 0.75rem;
            border: 3px solid #fff;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .author-name {
            font-weight: 600;
            font-size: 1.1rem;
            color: #2c3e50;
            margin: 0 0 0.25rem;
        }

        .author-role {
            display: inline-block;
            background: #3498db;
            color: white;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .author-role.admin {
            background: #e74c3c;
        }

        .post-stats {
            font-size: 0.9rem;
            color: #64748b;
            margin-top: 1rem;
        }

        .post-main {
            flex: 1;
            padding: 1.5rem;
        }

        .post-content {
            color: #334155;
            line-height: 1.6;
            margin-bottom: 1rem;
        }

        .post-content p {
            margin: 0 0 1rem;
        }

        .post-content img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
        }

        .post-content a {
            color: #3498db;
            text-decoration: none;
        }

        .post-content a:hover {
            text-decoration: underline;
        }

        .post-date {
            text-align: right;
            font-size: 0.9rem;
            color: #94a3b8;
        }

        .reply-form-container {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-top: 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .reply-form-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin: 0 0 1rem;
            color: #2c3e50;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group textarea {
            width: 100%;
            min-height: 150px;
            padding: 0.75rem 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            resize: vertical;
        }

        .form-group textarea:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .form-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
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

        .locked-message {
            background: #fee2e2;
            color: #b91c1c;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin: 2rem 0;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.75rem;
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

        .alert-success {
            background-color: #dcfce7;
            color: #16a34a;
            border: 1px solid #bbf7d0;
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
    </style>
</head>
<body>
    <?php require_once '../includes/header.php'; ?>
    
    <div class="thread-container">
        <!-- Breadcrumb navigation -->
        <div class="breadcrumb">
            <a href="forum.php">Forum</a>
            <span class="breadcrumb-separator"><i class="fas fa-chevron-right"></i></span>
            <a href="forum_category.php?id=<?php echo $thread['category_id']; ?>"><?php echo $thread['category_name']; ?></a>
            <span class="breadcrumb-separator"><i class="fas fa-chevron-right"></i></span>
            <span><?php echo $thread['title']; ?></span>
        </div>
        
        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        
        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        
        <!-- Thread header -->
        <div class="thread-header">
            <h1 class="thread-title"><?php echo $thread['title']; ?></h1>
            <div class="thread-meta">
                <span><i class="fas fa-clock"></i> Posted on <?php echo date('M j, Y g:i a', strtotime($thread['created_at'])); ?></span>
                <span><i class="fas fa-eye"></i> <?php echo $thread['views']; ?> views</span>
                <span><i class="fas fa-comment"></i> <?php echo $replies_result->num_rows; ?> replies</span>
            </div>
            <div class="thread-status">
                <?php if($thread['is_sticky']): ?>
                    <div class="status-tag sticky-tag">
                        <i class="fas fa-thumbtack"></i> Sticky
                    </div>
                <?php endif; ?>
                <?php if($thread['is_locked']): ?>
                    <div class="status-tag locked-tag">
                        <i class="fas fa-lock"></i> Locked
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Original post -->
        <div class="post" id="post-<?php echo $thread['id']; ?>">
            <div class="post-sidebar">
                <div class="post-author">
                    <img src="<?php echo $thread['profile_image'] ? '/uploads/' . $thread['profile_image'] : '/assets/images/default-avatar.png'; ?>" alt="<?php echo $thread['author_name']; ?>" class="author-avatar">
                    <h3 class="author-name"><?php echo $thread['author_name']; ?></h3>
                    <span class="author-role <?php echo $thread['user_role'] === 'admin' ? 'admin' : ''; ?>">
                        <?php echo ucfirst($thread['user_role']); ?>
                    </span>
                </div>
                <div class="post-stats">
                    <div>Member since <?php echo date('M Y', strtotime($thread['created_at'])); ?></div>
                </div>
            </div>
            <div class="post-main">
                <div class="post-content">
                    <?php echo $thread['content']; ?>
                </div>
                <div class="post-date">
                    <?php echo date('F j, Y g:i a', strtotime($thread['created_at'])); ?>
                </div>
            </div>
        </div>
        
        <!-- Replies -->
        <?php if($replies_result->num_rows > 0): ?>
            <?php while($reply = $replies_result->fetch_assoc()): ?>
                <div class="post" id="reply-<?php echo $reply['id']; ?>">
                    <div class="post-sidebar">
                        <div class="post-author">
                            <img src="<?php echo $reply['profile_image'] ? '/uploads/' . $reply['profile_image'] : '/assets/images/default-avatar.png'; ?>" alt="<?php echo $reply['author_name']; ?>" class="author-avatar">
                            <h3 class="author-name"><?php echo $reply['author_name']; ?></h3>
                            <span class="author-role <?php echo $reply['user_role'] === 'admin' ? 'admin' : ''; ?>">
                                <?php echo ucfirst($reply['user_role']); ?>
                            </span>
                        </div>
                        <div class="post-stats">
                            <div>Member since <?php echo date('M Y', strtotime($reply['created_at'])); ?></div>
                        </div>
                    </div>
                    <div class="post-main">
                        <div class="post-content">
                            <?php echo $reply['content']; ?>
                        </div>
                        <div class="post-date">
                            <?php echo date('F j, Y g:i a', strtotime($reply['created_at'])); ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>
        
        <!-- Reply form or locked message -->
        <?php if($thread['is_locked']): ?>
            <div class="locked-message">
                <i class="fas fa-lock"></i> This thread is locked. No new replies can be posted.
            </div>
        <?php elseif(isset($_SESSION['user_id'])): ?>
            <div class="reply-form-container" id="reply-form-container">
                <h2 class="reply-form-title">Post a Reply</h2>
                <form id="reply-form" action="thread.php?id=<?php echo $thread_id; ?>" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="reply">
                    
                    <div class="form-group">
                        <div class="formatting-options">
                            <button type="button" class="format-btn" onclick="addFormatting('bold')"><i class="fas fa-bold"></i></button>
                            <button type="button" class="format-btn" onclick="addFormatting('italic')"><i class="fas fa-italic"></i></button>
                            <button type="button" class="format-btn" onclick="addFormatting('underline')"><i class="fas fa-underline"></i></button>
                            <button type="button" class="format-btn" onclick="addFormatting('list')"><i class="fas fa-list-ul"></i></button>
                            <button type="button" class="format-btn" onclick="addFormatting('link')"><i class="fas fa-link"></i></button>
                        </div>
                        <textarea id="content" name="content" placeholder="Write your reply here..." required></textarea>
                    </div>
                    
                    <div class="form-buttons">
                        <button type="submit" class="btn btn-primary">Post Reply</button>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <div class="reply-form-container">
                <h2 class="reply-form-title">Join the Discussion</h2>
                <p>You need to be logged in to post a reply.</p>
                <div class="form-buttons">
                    <a href="login.php" class="btn btn-primary">Login to Reply</a>
                    <a href="register.php" class="btn btn-secondary">Create an Account</a>
                </div>
            </div>
        <?php endif; ?>
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
            textarea.value = textarea.value.substring(0, start) + formattedText + textarea.value.substring(end);
        }
    }
    
    // Submit reply form via AJAX
    document.getElementById('reply-form')?.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        fetch('thread.php?id=<?php echo $thread_id; ?>', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Create new reply element
                const reply = data.reply;
                const newReply = document.createElement('div');
                newReply.className = 'post';
                newReply.id = `reply-${reply.id}`;
                
                newReply.innerHTML = `
                    <div class="post-sidebar">
                        <div class="post-author">
                            <img src="${reply.profile_image ? '/uploads/' + reply.profile_image : '/assets/images/default-avatar.png'}" 
                                alt="${reply.author_name}" class="author-avatar">
                            <h3 class="author-name">${reply.author_name}</h3>
                            <span class="author-role"><?php echo $_SESSION['user_role']; ?></span>
                        </div>
                        <div class="post-stats">
                            <div>Member since ${new Date(reply.created_at).toLocaleDateString('en-US', {month: 'short', year: 'numeric'})}</div>
                        </div>
                    </div>
                    <div class="post-main">
                        <div class="post-content">
                            ${reply.content}
                        </div>
                        <div class="post-date">
                            ${new Date(reply.created_at).toLocaleDateString('en-US', {
                                year: 'numeric',
                                month: 'long',
                                day: 'numeric',
                                hour: 'numeric',
                                minute: 'numeric',
                                hour12: true
                            })}
                        </div>
                    </div>
                `;
                
                // Add reply to page
                document.getElementById('reply-form-container').before(newReply);
                
                // Clear form
                document.getElementById('content').value = '';
                
                // Scroll to new reply
                newReply.scrollIntoView({ behavior: 'smooth' });
                
                // Update reply count
                const replyCountElement = document.querySelector('.thread-meta i.fa-comment');
                if (replyCountElement) {
                    const text = replyCountElement.nextSibling;
                    const count = parseInt(text.textContent.match(/\d+/)[0]) + 1;
                    text.textContent = ` ${count} replies`;
                }
            } else {
                alert('Error posting reply: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    });
    </script>
</body>
</html> 