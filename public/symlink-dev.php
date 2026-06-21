<?php
// Untuk branch: development
// Server: staging-api.amunisiptn.com

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$target = '/home/u139193965/domains/staging-api.amunisiptn.com/shared/upload';
$link = '/home/u139193965/domains/staging-api.amunisiptn.com/public_html/public/storage';

try {
    if (file_exists($link)) {
        echo "Symlink or file already exists at link destination.<br>";
    } else {
        if (!function_exists('symlink')) throw new Exception("Fungsi symlink() dinonaktifkan (disabled).");
        if (symlink($target, $link)) {
            echo "Symlink in development (staging-api.amunisiptn.com) created successfully!";
        } else {
            echo "Failed to create symlink. Terjadi kesalahan pada level sistem file.";
        }
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo "Error Detail: " . $e->getMessage();
}
?>
