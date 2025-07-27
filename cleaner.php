<?php
// Get all .sql files in the current directory
$sqlFiles = glob('*.sql');

$deleted = [];
$failed = [];

foreach ($sqlFiles as $file) {
    if (unlink($file)) {
        $deleted[] = $file;
    } else {
        $failed[] = $file;
    }
}

echo "Deleted .sql files:\n";
foreach ($deleted as $file) {
    echo " - $file\n";
}

if (!empty($failed)) {
    echo "\nFailed to delete:\n";
    foreach ($failed as $file) {
        echo " - $file\n";
    }
}
?>