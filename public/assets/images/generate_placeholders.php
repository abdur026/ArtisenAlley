<?php
// Function to create a placeholder image
function createPlaceholderImage($width, $height, $text, $filename) {
    $image = imagecreatetruecolor($width, $height);
    
    // Colors
    $bgColor = imagecolorallocate($image, 240, 240, 240);
    $textColor = imagecolorallocate($image, 120, 120, 120);
    
    // Fill background
    imagefilledrectangle($image, 0, 0, $width, $height, $bgColor);
    
    // Add text
    $fontSize = 20;
    $font = 5; // Built-in font
    $textWidth = imagefontwidth($font) * strlen($text);
    $textHeight = imagefontheight($font);
    $x = ($width - $textWidth) / 2;
    $y = ($height - $textHeight) / 2;
    
    imagestring($image, $font, $x, $y, $text, $textColor);
    
    // Save image
    imagejpeg($image, $filename, 90);
    imagedestroy($image);
}

// Create product images
$products = [
    'necklace1.jpg' => 'Necklace',
    'vase1.jpg' => 'Vase',
    'scarf1.jpg' => 'Scarf',
    'bowl1.jpg' => 'Bowl'
];

foreach ($products as $filename => $text) {
    createPlaceholderImage(400, 400, $text, __DIR__ . '/' . $filename);
}

// Create category images
$categories = [
    'jewelry.jpg' => 'Jewelry',
    'pottery.jpg' => 'Pottery',
    'textile.jpg' => 'Textile',
    'wood.jpg' => 'Wood',
    'art.jpg' => 'Art',
    'home-decor.jpg' => 'Home Decor'
];

$categoriesDir = __DIR__ . '/categories';
if (!file_exists($categoriesDir)) {
    mkdir($categoriesDir, 0777, true);
}

foreach ($categories as $filename => $text) {
    createPlaceholderImage(400, 300, $text, $categoriesDir . '/' . $filename);
}

echo "Placeholder images generated successfully!\n"; 