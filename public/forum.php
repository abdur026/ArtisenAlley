<?php
session_start();
require_once '../config/db.php';

// Get categories with thread counts
$categories_query = "SELECT c.*, 
                    (SELECT COUNT(*) FROM forum_threads WHERE category_id = c.id) as thread_count,
                    (SELECT COUNT(*) FROM forum_replies r 
                     JOIN forum_threads t ON r.thread_id = t.id 
                     WHERE t.category_id = c.id) as reply_count
                    FROM forum_categories c
                    ORDER BY c.display_order";
$categories_result = $conn->query($categories_query);

// Get recent threads
$recent_threads_query = "SELECT t.*, c.name as category_name, u.name as author_name, u.profile_image,
                        (SELECT COUNT(*) FROM forum_replies WHERE thread_id = t.id) as reply_count
                        FROM forum_threads t
                        JOIN forum_categories c ON t.category_id = c.id
                        JOIN users u ON t.user_id = u.id
                        ORDER BY t.created_at DESC
                        LIMIT 5";
$recent_threads_result = $conn->query($recent_threads_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Community Forum - Artisan Alley</title>
    <link rel="stylesheet" href="/assets/css/main.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .forum-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }

        .forum-header {
            background: linear-gradient(135deg, #3498db 0%, #2c3e50 100%);
            border-radius: 20px;
            padding: 3rem 2rem;
            color: white;
            margin-bottom: 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .forum-header h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            position: relative;
        }

        .forum-header p {
            font-size: 1.1rem;
            max-width: 700px;
            margin: 0 auto 1.5rem;
            position: relative;
        }

        .forum-action {
            display: inline-block;
            background: white;
            color: #3498db;
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 1rem;
        }

        .forum-action:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            background: #f8f9fa;
        }

        .forum-sections {
            display: grid;
            grid-template-columns: 7fr 3fr;
            gap: 2rem;
        }

        .categories-section {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .section-title {
            padding: 1.5rem;
            background: #f8f9fa;
            border-bottom: 1px solid #eee;
            font-size: 1.2rem;
            font-weight: 600;
            color: #2c3e50;
        }

        .category-list {
            padding: 0;
            margin: 0;
            list-style: none;
        }

        .category-item {
            border-bottom: 1px solid #eee;
            transition: all 0.3s ease;
        }

        .category-item:last-child {
            border-bottom: none;
        }

        .category-item:hover {
            background: #f8f9fa;
        }

        .category-link {
            display: flex;
            padding: 1.5rem;
            text-decoration: none;
            color: inherit;
        }

        .category-icon {
            width: 50px;
            height: 50px;
            background: #f1f5f9;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #3498db;
            font-size: 1.5rem;
            margin-right: 1.5rem;
        }

        .category-info {
            flex: 1;
        }

        .category-name {
            font-weight: 600;
            font-size: 1.1rem;
            margin: 0 0 0.5rem;
            color: #2c3e50;
        }

        .category-description {
            color: #64748b;
            margin: 0;
            font-size: 0.95rem;
        }

        .category-stats {
            text-align: center;
            padding: 0 1rem;
            color: #64748b;
            font-size: 0.9rem;
        }

        .stats-count {
            font-weight: 600;
            font-size: 1.1rem;
            color: #2c3e50;
            display: block;
            margin-bottom: 0.25rem;
        }

        .recent-section {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .recent-threads {
            padding: 0;
            margin: 0;
            list-style: none;
        }

        .thread-item {
            border-bottom: 1px solid #eee;
            padding: 1.25rem;
            transition: all 0.3s ease;
        }

        .thread-item:last-child {
            border-bottom: none;
        }

        .thread-item:hover {
            background: #f8f9fa;
        }

        .thread-title {
            font-weight: 600;
            margin: 0 0 0.5rem;
            font-size: 1rem;
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
            font-size: 0.85rem;
            color: #64748b;
        }

        .thread-author {
            display: flex;
            align-items: center;
            margin-right: 1rem;
        }

        .author-avatar {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 0.5rem;
        }

        .thread-category {
            background: #f1f5f9;
            color: #3498db;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            margin-right: 1rem;
            font-weight: 500;
            font-size: 0.8rem;
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
    
    <div class="forum-container">
        <div class="forum-header">
            <h1>Artisan Alley Community Forum</h1>
            <p>Join our community of artisans and craft enthusiasts to share ideas, get inspiration, and connect with like-minded creators.</p>
            <?php if(isset($_SESSION['user_id'])): ?>
                <a href="new_thread.php" class="forum-action"><i class="fas fa-plus-circle"></i> Create New Thread</a>
            <?php else: ?>
                <a href="login.php" class="forum-action"><i class="fas fa-sign-in-alt"></i> Login to Participate</a>
            <?php endif; ?>
        </div>
        
        <div class="forum-sections">
            <div class="categories-section">
                <div class="section-title">Forum Categories</div>
                <?php if($categories_result->num_rows > 0): ?>
                    <ul class="category-list">
                        <?php while($category = $categories_result->fetch_assoc()): ?>
                            <li class="category-item">
                                <a href="forum_category.php?id=<?php echo $category['id']; ?>" class="category-link">
                                    <div class="category-icon">
                                        <i class="fas <?php echo $category['icon']; ?>"></i>
                                    </div>
                                    <div class="category-info">
                                        <h3 class="category-name"><?php echo $category['name']; ?></h3>
                                        <p class="category-description"><?php echo $category['description']; ?></p>
                                    </div>
                                    <div class="category-stats">
                                        <span class="stats-count"><?php echo $category['thread_count']; ?></span>
                                        Threads
                                    </div>
                                    <div class="category-stats">
                                        <span class="stats-count"><?php echo $category['reply_count']; ?></span>
                                        Replies
                                    </div>
                                </a>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-comments-slash"></i>
                        <p>No forum categories found. Check back later!</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="recent-section">
                <div class="section-title">Recent Discussions</div>
                <?php if($recent_threads_result->num_rows > 0): ?>
                    <ul class="recent-threads">
                        <?php while($thread = $recent_threads_result->fetch_assoc()): ?>
                            <li class="thread-item">
                                <h3 class="thread-title">
                                    <a href="thread.php?id=<?php echo $thread['id']; ?>">
                                        <?php echo $thread['title']; ?>
                                    </a>
                                </h3>
                                <div class="thread-meta">
                                    <div class="thread-author">
                                        <img src="<?php echo $thread['profile_image'] ? '/uploads/' . $thread['profile_image'] : '/assets/images/default-avatar.png'; ?>" alt="<?php echo $thread['author_name']; ?>" class="author-avatar">
                                        <span><?php echo $thread['author_name']; ?></span>
                                    </div>
                                    <span class="thread-category"><?php echo $thread['category_name']; ?></span>
                                    <span><i class="fas fa-comment"></i> <?php echo $thread['reply_count']; ?> replies</span>
                                </div>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-comment-slash"></i>
                        <p>No threads yet. Be the first to start a discussion!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php require_once '../includes/footer.php'; ?>
</body>
</html> 