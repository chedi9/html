<?php
function listTopLevel($dir) {
    echo "📁 Root Directory Contents:\n";
    foreach (scandir($dir) as $item) {
        if ($item !== '.' && $item !== '..') {
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            echo $item . (is_dir($path) ? "/\n" : "\n");
        }
    }
}

function listSubdirContents($dirName, $root) {
    $fullPath = $root . DIRECTORY_SEPARATOR . $dirName;
    if (!is_dir($fullPath)) {
        echo "\n⚠️ Subdirectory '$dirName' not found.\n";
        return;
    }

    echo "\n📂 Contents of '$dirName':\n";
    foreach (scandir($fullPath) as $item) {
        if ($item !== '.' && $item !== '..') {
            echo "  " . $item . "\n";
        }
    }
}

// Use current directory as root
$root = __DIR__;

// List root contents
listTopLevel($root);

// List contents of specific subdirectories
$subdirs = ['client', 'admin', 'archive', 'lang'];
foreach ($subdirs as $subdir) {
    listSubdirContents($subdir, $root);
}
?>
