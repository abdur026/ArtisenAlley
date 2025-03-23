<?php

// Function to generate breadcrumb HTML
function generate_breadcrumbs($items) {
    if (empty($items)) {
        return '';
    }
    
    $html = '<nav class="breadcrumb-nav" aria-label="breadcrumb">';
    $html .= '<ol class="breadcrumb">';
    
    $count = count($items);
    foreach ($items as $index => $item) {
        $isLast = ($index === $count - 1);
        $class = $isLast ? 'breadcrumb-item active" aria-current="page' : 'breadcrumb-item';
        
        if (!$isLast && isset($item['url'])) {
            $html .= '<li class="' . $class . '"><a href="' . htmlspecialchars($item['url']) . '">' . htmlspecialchars($item['name']) . '</a></li>';
        } else {
            $html .= '<li class="' . $class . '">' . htmlspecialchars($item['name']) . '</li>';
        }
    }
    
    $html .= '</ol>';
    $html .= '</nav>';
    
    return $html;
}
?>

<style>
.breadcrumb-nav {
    margin-bottom: 1.5rem;
    font-size: 0.9rem;
}

.breadcrumb {
    display: flex;
    flex-wrap: wrap;
    padding: 0.75rem 1rem;
    margin-bottom: 1rem;
    list-style: none;
    background-color: rgba(240, 240, 240, 0.5);
    border-radius: 0.25rem;
}

.breadcrumb-item + .breadcrumb-item {
    padding-left: 0.5rem;
}

.breadcrumb-item + .breadcrumb-item::before {
    display: inline-block;
    padding-right: 0.5rem;
    color: #6c757d;
    content: "/";
}

.breadcrumb-item a {
    color: #3498db;
    text-decoration: none;
}

.breadcrumb-item a:hover {
    text-decoration: underline;
}

.breadcrumb-item.active {
    color: #6c757d;
}
</style> 