<?php
$target = '/home/u139193965/domains/staging-api.amunisiptn.com/public_html/shared/upload';
$link = '/home/u139193965/domains/staging-api.amunisiptn.com/public_html/public/storage';

if (file_exists($link)) {
    echo "Symlink or file already exists at link destination.<br>";
} else {
    if (symlink($target, $link)) {
        echo "Symlink created successfully!";
    } else {
        echo "Failed to create symlink. Ensure the target exists and permissions are correct.";
    }
}
?>
