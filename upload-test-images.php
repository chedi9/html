<?php
require_once 'db.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_image'])) {
    $upload_dir = 'uploads/';
    
    // Create uploads directory if it doesn't exist
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $file = $_FILES['test_image'];
    $filename = basename($file['name']);
    $target_path = $upload_dir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        $success_message = "Test image uploaded successfully: $filename";
        
        // Add to database if requested
        if (isset($_POST['add_to_products']) && $_POST['add_to_products'] === 'yes') {
            $stmt = $pdo->prepare("INSERT INTO products (name, description, price, image, category_id) VALUES (?, ?, ?, ?, ?)");
            $product_name = $_POST['product_name'] ?? 'Test Product';
            $description = $_POST['description'] ?? 'Test product for thumbnail testing';
            $price = $_POST['price'] ?? 10.00;
            $category_id = $_POST['category_id'] ?? 1;
            
            $stmt->execute([$product_name, $description, $price, $filename, $category_id]);
            $success_message .= " and added to products table";
        }
    } else {
        $error_message = "Failed to upload image";
    }
}

// Get existing categories for dropdown
$stmt = $pdo->prepare("SELECT id, name FROM categories ORDER BY name");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Test Images - WeBuy</title>
    <link rel="stylesheet" href="css/main.css">
    <style>
        .upload-section {
            margin: 2rem 0;
            padding: 2rem;
            border: 2px solid var(--color-border);
            border-radius: 8px;
            background: var(--color-background);
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
            color: var(--color-text);
        }
        
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid var(--color-border);
            border-radius: 4px;
            background: var(--color-surface);
            color: var(--color-text);
        }
        
        .btn {
            padding: 0.5rem 1rem;
            border: 1px solid var(--color-border);
            border-radius: 4px;
            background: var(--color-primary);
            color: white;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-right: 0.5rem;
        }
        
        .btn:hover {
            background: var(--color-primary-hover);
        }
        
        .message {
            padding: 1rem;
            border-radius: 4px;
            margin: 1rem 0;
        }
        
        .message.success {
            background: #d1fae5;
            border: 1px solid #10b981;
            color: #065f46;
        }
        
        .message.error {
            background: #fee2e2;
            border: 1px solid #ef4444;
            color: #991b1b;
        }
        
        .existing-images {
            margin-top: 2rem;
        }
        
        .image-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .image-item {
            border: 1px solid var(--color-border);
            border-radius: 4px;
            padding: 0.5rem;
            text-align: center;
        }
        
        .image-item img {
            max-width: 100%;
            height: auto;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>📤 Upload Test Images</h1>
            <p>Upload test images to test the thumbnail system</p>
        </header>

        <?php if (isset($success_message)): ?>
            <div class="message success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <section class="upload-section">
            <h2>Upload Test Image</h2>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="test_image">Select Image:</label>
                    <input type="file" id="test_image" name="test_image" accept="image/*" required>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="add_to_products" value="yes">
                        Add to products table for testing
                    </label>
                </div>
                
                <div class="form-group">
                    <label for="product_name">Product Name:</label>
                    <input type="text" id="product_name" name="product_name" value="Test Product">
                </div>
                
                <div class="form-group">
                    <label for="description">Description:</label>
                    <textarea id="description" name="description" rows="3">Test product for thumbnail testing</textarea>
                </div>
                
                <div class="form-group">
                    <label for="price">Price:</label>
                    <input type="number" id="price" name="price" value="10.00" step="0.01" min="0">
                </div>
                
                <div class="form-group">
                    <label for="category_id">Category:</label>
                    <select id="category_id" name="category_id">
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <button type="submit" class="btn">Upload Image</button>
                <a href="test-thumbnails.php" class="btn">Test Thumbnails</a>
                <a href="index.php" class="btn">Back to Homepage</a>
            </form>
        </section>

        <section class="upload-section">
            <h2>Existing Images</h2>
            <div class="existing-images">
                <?php
                $upload_dir = 'uploads/';
                if (is_dir($upload_dir)) {
                    $images = glob($upload_dir . '*.{jpg,jpeg,png,gif}', GLOB_BRACE);
                    if (!empty($images)) {
                        echo '<div class="image-grid">';
                        foreach ($images as $image) {
                            echo '<div class="image-item">';
                            echo '<img src="' . $image . '" alt="Uploaded image" loading="lazy">';
                            echo '<p>' . basename($image) . '</p>';
                            echo '<p>' . round(filesize($image) / 1024, 1) . ' KB</p>';
                            echo '</div>';
                        }
                        echo '</div>';
                    } else {
                        echo '<p>No images found in uploads directory.</p>';
                    }
                } else {
                    echo '<p>Uploads directory does not exist.</p>';
                }
                ?>
            </div>
        </section>
    </div>
</body>
</html> 