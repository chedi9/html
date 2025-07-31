<?php
require_once 'db.php';
require_once 'includes/thumbnail_helper.php';

// Get sample products and categories
$stmt = $pdo->prepare("SELECT id, name, image FROM products WHERE image IS NOT NULL AND image != '' LIMIT 4");
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT id, name, image, icon FROM categories WHERE (image IS NOT NULL AND image != '') OR (icon IS NOT NULL AND icon != '') LIMIT 2");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ensure thumbnails directory exists
if (!is_dir('uploads/thumbnails/')) {
    mkdir('uploads/thumbnails/', 0755, true);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thumbnail Test - WeBuy</title>
</head>
<body style="font-family: Arial, sans-serif; margin: 20px; background: #f0f0f0;">
    <div style="max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px;">
        <h1 style="text-align: center; color: #333;">🖼️ Thumbnail System Test</h1>
        
        <!-- Statistics -->
        <div style="margin: 20px 0; padding: 15px; border: 2px solid #ddd; border-radius: 8px; background: #f9f9f9;">
            <h2 style="margin-top: 0; color: #444;">📊 System Statistics</h2>
            <?php
            $total_products = count($products);
            $total_categories = count($categories);
            $total_images = $total_products + $total_categories;
            
            // Count existing thumbnails
            $thumb_count = 0;
            if (is_dir('uploads/thumbnails/')) {
                $thumb_files = glob('uploads/thumbnails/*.jpg');
                $thumb_count = count($thumb_files);
            }
            ?>
            <p><strong>Products:</strong> <?php echo $total_products; ?></p>
            <p><strong>Categories:</strong> <?php echo $total_categories; ?></p>
            <p><strong>Total Images:</strong> <?php echo $total_images; ?></p>
            <p><strong>Thumbnails:</strong> <?php echo $thumb_count; ?></p>
            
            <div style="text-align: center; margin: 20px 0;">
                <button onclick="generateThumbnails()" style="padding: 10px 20px; margin: 5px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">🔄 Generate Thumbnails</button>
                <button onclick="cleanupThumbnails()" style="padding: 10px 20px; margin: 5px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer;">🧹 Cleanup</button>
                <a href="index.php" style="padding: 10px 20px; margin: 5px; background: #28a745; color: white; text-decoration: none; border-radius: 4px; display: inline-block;">🏠 Home</a>
            </div>
        </div>

        <!-- Product Images -->
        <div style="margin: 20px 0; padding: 15px; border: 2px solid #ddd; border-radius: 8px; background: #f9f9f9;">
            <h2 style="margin-top: 0; color: #444;">📦 Product Images</h2>
            <?php foreach ($products as $product) { ?>
                <div style="margin: 20px 0; padding: 15px; border: 1px solid #ccc; border-radius: 8px; background: white;">
                    <h3 style="margin: 0 0 15px 0; color: #333;"><?php echo htmlspecialchars($product['name']); ?></h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div style="text-align: center; padding: 10px; border: 1px solid #eee; border-radius: 6px; background: #f9f9f9;">
                            <h4 style="margin: 0 0 10px 0; color: #666;">Original</h4>
                            <?php 
                            $original_path = 'uploads/' . $product['image'];
                            $original_exists = file_exists($original_path);
                            $original_size = $original_exists ? filesize($original_path) : 0;
                            ?>
                            <?php if ($original_exists) { ?>
                                <img src="<?php echo $original_path; ?>" alt="Original" style="max-width: 100%; height: auto; border-radius: 4px; border: 1px solid #ddd; max-height: 200px;">
                                <p style="margin-top: 10px; font-size: 12px; color: #666;">Size: <?php echo round($original_size / 1024, 1); ?> KB</p>
                            <?php } else { ?>
                                <div style="color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 4px;">Image not found: <?php echo $original_path; ?></div>
                            <?php } ?>
                        </div>
                        
                        <div style="text-align: center; padding: 10px; border: 1px solid #eee; border-radius: 6px; background: #f9f9f9;">
                            <h4 style="margin: 0 0 10px 0; color: #666;">Thumbnail</h4>
                            <?php 
                            if ($original_exists) {
                                // Generate thumbnail if it doesn't exist
                                $thumb_path = get_thumbnail_path($original_path, 'small');
                                if (!file_exists($thumb_path)) {
                                    ensure_thumbnail_exists($original_path, 'small');
                                    $thumb_path = get_thumbnail_path($original_path, 'small');
                                }
                                
                                $thumb_exists = file_exists($thumb_path);
                                $thumb_size = $thumb_exists ? filesize($thumb_path) : 0;
                                
                                if ($thumb_exists) { ?>
                                    <img src="<?php echo $thumb_path; ?>" alt="Thumbnail" style="max-width: 100%; height: auto; border-radius: 4px; border: 1px solid #ddd; max-height: 200px;">
                                    <p style="margin-top: 10px; font-size: 12px; color: #666;">
                                        Size: <?php echo round($thumb_size / 1024, 1); ?> KB
                                        <?php if ($original_size > 0) { ?>
                                            <br>Savings: <?php echo round((($original_size - $thumb_size) / $original_size) * 100, 1); ?>%
                                        <?php } ?>
                                    </p>
                                <?php } else { ?>
                                    <div style="color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 4px;">Thumbnail not generated</div>
                                <?php }
                            } else { ?>
                                <div style="color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 4px;">No original image</div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>

        <!-- Category Images -->
        <div style="margin: 20px 0; padding: 15px; border: 2px solid #ddd; border-radius: 8px; background: #f9f9f9;">
            <h2 style="margin-top: 0; color: #444;">📂 Category Images</h2>
            <?php foreach ($categories as $category) { ?>
                <div style="margin: 20px 0; padding: 15px; border: 1px solid #ccc; border-radius: 8px; background: white;">
                    <h3 style="margin: 0 0 15px 0; color: #333;"><?php echo htmlspecialchars($category['name']); ?></h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div style="text-align: center; padding: 10px; border: 1px solid #eee; border-radius: 6px; background: #f9f9f9;">
                            <h4 style="margin: 0 0 10px 0; color: #666;">Original</h4>
                            <?php 
                            $original_path = 'uploads/' . ($category['image'] ?: $category['icon']);
                            $original_exists = file_exists($original_path);
                            $original_size = $original_exists ? filesize($original_path) : 0;
                            ?>
                            <?php if ($original_exists) { ?>
                                <img src="<?php echo $original_path; ?>" alt="Original" style="max-width: 100%; height: auto; border-radius: 4px; border: 1px solid #ddd; max-height: 200px;">
                                <p style="margin-top: 10px; font-size: 12px; color: #666;">Size: <?php echo round($original_size / 1024, 1); ?> KB</p>
                            <?php } else { ?>
                                <div style="color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 4px;">Image not found: <?php echo $original_path; ?></div>
                            <?php } ?>
                        </div>
                        
                        <div style="text-align: center; padding: 10px; border: 1px solid #eee; border-radius: 6px; background: #f9f9f9;">
                            <h4 style="margin: 0 0 10px 0; color: #666;">Thumbnail</h4>
                            <?php 
                            if ($original_exists) {
                                // Generate thumbnail if it doesn't exist
                                $thumb_path = get_thumbnail_path($original_path, 'medium');
                                if (!file_exists($thumb_path)) {
                                    ensure_thumbnail_exists($original_path, 'medium');
                                    $thumb_path = get_thumbnail_path($original_path, 'medium');
                                }
                                
                                $thumb_exists = file_exists($thumb_path);
                                $thumb_size = $thumb_exists ? filesize($thumb_path) : 0;
                                
                                if ($thumb_exists) { ?>
                                    <img src="<?php echo $thumb_path; ?>" alt="Thumbnail" style="max-width: 100%; height: auto; border-radius: 4px; border: 1px solid #ddd; max-height: 200px;">
                                    <p style="margin-top: 10px; font-size: 12px; color: #666;">
                                        Size: <?php echo round($thumb_size / 1024, 1); ?> KB
                                        <?php if ($original_size > 0) { ?>
                                            <br>Savings: <?php echo round((($original_size - $thumb_size) / $original_size) * 100, 1); ?>%
                                        <?php } ?>
                                    </p>
                                <?php } else { ?>
                                    <div style="color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 4px;">Thumbnail not generated</div>
                                <?php }
                            } else { ?>
                                <div style="color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 4px;">No original image</div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>

        <!-- Size Comparison -->
        <div style="margin: 20px 0; padding: 15px; border: 2px solid #ddd; border-radius: 8px; background: #f9f9f9;">
            <h2 style="margin-top: 0; color: #444;">📏 Size Comparison</h2>
            <?php if (!empty($products)) { ?>
                <?php $test_product = $products[0]; ?>
                <div style="margin: 20px 0; padding: 15px; border: 1px solid #ccc; border-radius: 8px; background: white;">
                    <h3 style="margin: 0 0 15px 0; color: #333;"><?php echo htmlspecialchars($test_product['name']); ?> - Different Sizes</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
                        <?php 
                        $original_path = 'uploads/' . $test_product['image'];
                        $sizes = ['small' => 150, 'medium' => 300, 'large' => 500];
                        
                        foreach ($sizes as $size_name => $size_value) {
                            if (file_exists($original_path)) {
                                $thumb_path = get_thumbnail_path($original_path, $size_name);
                                if (!file_exists($thumb_path)) {
                                    ensure_thumbnail_exists($original_path, $size_name);
                                    $thumb_path = get_thumbnail_path($original_path, $size_name);
                                }
                                $thumb_exists = file_exists($thumb_path);
                                $thumb_size = $thumb_exists ? filesize($thumb_path) : 0;
                                echo '<div style="text-align: center; padding: 10px; border: 1px solid #eee; border-radius: 6px; background: #f9f9f9;">';
                                echo '<h4 style="margin: 0 0 10px 0; color: #666;">' . ucfirst($size_name) . ' (' . $size_value . 'px)</h4>';
                                if ($thumb_exists) {
                                    echo '<img src="' . $thumb_path . '" alt="' . $size_name . '" style="max-width: 100%; height: auto; border-radius: 4px; border: 1px solid #ddd; max-height: 200px;">';
                                    echo '<p style="margin-top: 10px; font-size: 12px; color: #666;">Size: ' . round($thumb_size / 1024, 1) . ' KB</p>';
                                } else {
                                    echo '<div style="color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 4px;">Not generated</div>';
                                }
                                echo '</div>';
                            }
                        }
                        ?>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>

    <script>
        function generateThumbnails() {
            const btn = event.target;
            btn.disabled = true;
            btn.textContent = '🔄 Generating...';
            
            fetch('generate_thumbnails.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({action: 'generate_all'})
            })
            .then(response => response.json())
            .then(data => {
                alert(`Generated ${data.generated} thumbnails successfully!`);
                location.reload();
            })
            .catch(error => {
                alert('Error: ' + error.message);
            })
            .finally(() => {
                btn.disabled = false;
                btn.textContent = '🔄 Generate Thumbnails';
            });
        }
        
        function cleanupThumbnails() {
            const btn = event.target;
            btn.disabled = true;
            btn.textContent = '🧹 Cleaning...';
            
            fetch('generate_thumbnails.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({action: 'cleanup'})
            })
            .then(response => response.json())
            .then(data => {
                alert(`Cleaned up ${data.cleaned} orphaned thumbnails!`);
                location.reload();
            })
            .catch(error => {
                alert('Error: ' + error.message);
            })
            .finally(() => {
                btn.disabled = false;
                btn.textContent = '🧹 Cleanup';
            });
        }
    </script>
</body>
</html> 