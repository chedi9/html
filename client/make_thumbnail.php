<?php
// Usage: make_thumbnail('uploads/original.jpg', 'uploads/thumb_original.jpg', 300, 300);
function make_thumbnail($src, $dest, $max_width, $max_height) {
    $info = getimagesize($src);
    if (!$info) return false;
    $type = $info[2];
    switch ($type) {
        case IMAGETYPE_JPEG: $img = imagecreatefromjpeg($src); break;
        case IMAGETYPE_PNG:  $img = imagecreatefrompng($src); break;
        case IMAGETYPE_GIF:  $img = imagecreatefromgif($src); break;
        default: return false;
    }
    $src_w = imagesx($img);
    $src_h = imagesy($img);
    $src_ratio = $src_w / $src_h;
    $thumb_ratio = $max_width / $max_height;
    if ($src_ratio > $thumb_ratio) {
        // Source is wider
        $new_h = $max_height;
        $new_w = (int)($max_height * $src_ratio);
    } else {
        // Source is taller or equal
        $new_w = $max_width;
        $new_h = (int)($max_width / $src_ratio);
    }
    $tmp = imagecreatetruecolor($max_width, $max_height);
    // Fill with white for PNG/GIF transparency
    $white = imagecolorallocate($tmp, 255, 255, 255);
    imagefill($tmp, 0, 0, $white);
    // Center crop
    imagecopyresampled(
        $tmp, $img,
        0 - ($new_w - $max_width) / 2, // Center X
        0 - ($new_h - $max_height) / 2, // Center Y
        0, 0,
        $new_w, $new_h,
        $src_w, $src_h
    );
    $result = imagejpeg($tmp, $dest, 85); // 85% quality
    imagedestroy($img);
    imagedestroy($tmp);
    return $result;
} 