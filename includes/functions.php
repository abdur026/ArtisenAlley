<?php
require_once __DIR__ . '/../config/config.php';

function url($path) {
    return BASE_URL . $path;
}

function asset($path) {
    return BASE_URL . '/assets' . $path;
}

function image($path) {
    return BASE_URL . '/assets/images' . $path;
}
?> 