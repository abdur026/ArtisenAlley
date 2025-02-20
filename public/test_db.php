<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "../config/db.php";

if ($conn) {
    echo "Database connected successfully!";
} else {
    echo "Database connection failed.";
}
?>