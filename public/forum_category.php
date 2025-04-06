<?php
session_start();
require_once '../config/db.php';

// Get category ID from URL
$category_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$category_id) {
    header('Location: forum.php');
    exit;
}

// Get category info
$category_query = "SELECT * FROM forum_categories WHERE id = ?";
$stmt = $conn->prepare($category_query);
$stmt->bind_param('i', $category_id);
$stmt->execute();
$category_result = $stmt->get_result();

if ($category_result->num_rows === 0) {
    header('Location: forum.php');
    exit;
}

$category = $category_result->fetch_assoc();

// Get threads in this category
$threads_query = "SELECT t.*, u.name as author_name, u.profile_image,
                 (SELECT COUNT(*) FROM forum_replies WHERE thread_id = t.id) as reply_count,
                 (SELECT MAX(created_at) FROM forum_replies WHERE thread_id = t.id) as latest_reply
                 FROM forum_threads t
                 JOIN users u ON t.user_id = u.id
                 WHERE t.category_id = ?
                 ORDER BY t.is_sticky DESC, t.created_at DESC";
$stmt = $conn->prepare($threads_query);
$stmt->bind_param('i', $category_id);
$stmt->execute();
$threads_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $category['name']; ?> - Artisan Alley Forum</title>
    <link rel="stylesheet" href="/assets/css/main.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .category-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }

        .category-header {
            background: linear-gradient(135deg, #3498db 0%, #2c3e50 100%);
            border-radius: 20px;
            padding: 2.5rem 2rem;
            color: white;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }

        .category-header-content {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .category-icon {
            width: 70px;
            height: 70px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(5px);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
        }

        .category-info {
            flex: 1;
        }

        .category-name {
            font-size: 2rem;
            margin: 0 0 0.5rem;
        }

        .category-description {
            font-size: 1.1rem;
            opacity: 0.9;
            margin: 0;
        }

        .category-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .create-thread-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: #3498db;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 1rem;
        }

        .create-thread-btn:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }

        .thread-filters {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .filter-btn {
            background: #f1f5f9;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            color: #64748b;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .filter-btn:hover {
            background: #e2e8f0;
            color: #334155;
        }

        .threads-list {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .thread-item {
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 1.5rem;
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
            transition: all 0.3s ease;
        }

        .thread-item:hover {
            background: #f8f9fa;
        }

        .thread-item:last-child {
            border-bottom: none;
        }

        .thread-author {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 100px;
        }

        .author-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 0.5rem;
        }

        .author-name {
            text-align: center;
            font-size: 0.9rem;
            font-weight: 500;
            color: #2c3e50;
        }

        .thread-content {
            flex: 1;
        }

        .thread-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin: 0 0 0.5rem;
        }

        .thread-title a {
            color: #2c3e50;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .thread-title a:hover {
            color: #3498db;
        }

        .thread-meta {
            display: flex;
            align-items: center;
            gap: 1rem;
            font-size: 0.85rem;
            color: #64748b;
            margin-bottom: 0.75rem;
        }

        .thread-preview {
            color: #64748b;
            font-size: 0.95rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .thread-stats {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
            padding: 0 1rem;
            width: 100px;
        }

        .stats-item {
            text-align: center;
            font-size: 0.9rem;
            color: #64748b;
        }

        .stats-count {
            display: block;
            font-weight: 600;
            font-size: 1.2rem;
            color: #2c3e50;
            margin-bottom: 0.25rem;
        }

        .sticky-thread {
            background: #f0f9ff;
        }

        .sticky-tag {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            background: #3498db;
            color: white;
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-weight: 500;
            margin-right: 0.5rem;
        }

        .locked-tag {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            background: #e74c3c;
            color: white;
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-weight: 500;
            margin-right: 0.5rem;
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

        .empty-state {
            padding: 3rem 2rem;
            text-align: center;
            color: #64748b;
        }

        .empty-state i {
            font-size: 3rem;
            color: #e2e8f0;
            margin-bottom: 1rem;
        }

        .empty-state p {
            margin: 0 0 1.5rem;
        }
    </style>
</head>
<body>
    <?php require_once '../includes/header.php'; ?>
    
    <div class="category-container">
        <div class="breadcrumb">
            <a href="forum.php">Forum</a>
            <span class="breadcrumb-separator"><i class="fas fa-chevron-right"></i></span>
            <span><?php echo $category['name']; ?></span>
        </div>
        
        <div class="category-header">
            <div class="category-header-content">
                <div class="category-icon">
                    <i class="fas <?php echo $category['icon']; ?>"></i>
                </div>
                <div class="category-info">
                    <h1 class="category-name"><?php echo $category['name']; ?></h1>
                    <p class="category-description"><?php echo $category['description']; ?></p>
                </div>
            </div>
        </div>
        
        <div class="category-actions">
            <?php if(isset($_SESSION['user_id'])): ?>
                <a href="new_thread.php?category_id=<?php echo $category_id; ?>" class="create-thread-btn">
                    <i class="fas fa-plus-circle"></i> Create New Thread
                </a>
            <?php else: ?>
                <a href="login.php" class="create-thread-btn">
                    <i class="fas fa-sign-in-alt"></i> Login to Create Thread
                </a>
            <?php endif; ?>
            
            <div class="thread-filters">
                <button class="filter-btn active"><i class="fas fa-clock"></i> Latest</button>
                <button class="filter-btn"><i class="fas fa-fire"></i> Popular</button>
            </div>
        </div>
        
        <div class="threads-list">
            <?php if($threads_result->num_rows > 0): ?>
                <?php while($thread = $threads_result->fetch_assoc()): ?>
                    <div class="thread-item <?php echo $thread['is_sticky'] ? 'sticky-thread' : ''; ?>">
                        <div class="thread-author">
                            <img src="<?php echo $thread['profile_image'] ? '/uploads/' . $thread['profile_image'] : '/assets/images/default-avatar.png'; ?>" alt="<?php echo $thread['author_name']; ?>" class="author-avatar">
                            <span class="author-name"><?php echo $thread['author_name']; ?></span>
                        </div>
                        
                        <div class="thread-content">
                            <h3 class="thread-title">
                                <?php if($thread['is_sticky']): ?>
                                    <span class="sticky-tag"><i class="fas fa-thumbtack"></i> Sticky</span>
                                <?php endif; ?>
                                <?php if($thread['is_locked']): ?>
                                    <span class="locked-tag"><i class="fas fa-lock"></i> Locked</span>
                                <?php endif; ?>
                                <a href="thread.php?id=<?php echo $thread['id']; ?>">
                                    <?php echo $thread['title']; ?>
                                </a>
                            </h3>
                            <div class="thread-meta">
                                <span><i class="fas fa-clock"></i> <?php echo date('M j, Y g:i a', strtotime($thread['created_at'])); ?></span>
                                <span><i class="fas fa-comment"></i> <?php echo $thread['reply_count']; ?> replies</span>
                                <?php if($thread['reply_count'] > 0 && !empty($thread['latest_reply'])): ?>
                                    <span><i class="fas fa-history"></i> Last reply: <?php echo date('M j, Y g:i a', strtotime($thread['latest_reply'])); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="thread-preview">
                                <?php echo substr(strip_tags($thread['content']), 0, 200) . (strlen($thread['content']) > 200 ? '...' : ''); ?>
                            </div>
                        </div>
                        
                        <div class="thread-stats">
                            <div class="stats-item">
                                <span class="stats-count"><?php echo $thread['views']; ?></span>
                                Views
                            </div>
                            <div class="stats-item">
                                <span class="stats-count"><?php echo $thread['reply_count']; ?></span>
                                Replies
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-comment-slash"></i>
                    <p>No threads in this category yet. Be the first to start a discussion!</p>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <a href="new_thread.php?category_id=<?php echo $category_id; ?>" class="create-thread-btn">
                            <i class="fas fa-plus-circle"></i> Create Thread
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php require_once '../includes/footer.php'; ?>
</body>
</html> 