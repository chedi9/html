<?php
session_start();
require '../db.php';
require '../lang.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare('SELECT * FROM sellers WHERE user_id = ?');
$stmt->execute([$user_id]);
$seller = $stmt->fetch();

if (!$seller) {
    echo 'You are not a seller.';
    exit();
}

$success_msg = '';
$error_msg = '';
$upload_results = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file'];
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if ($file_extension !== 'csv') {
            $error_msg = 'Please upload a CSV file.';
        } else {
            // Read CSV file
            $handle = fopen($file['tmp_name'], 'r');
            if ($handle) {
                $row = 1;
                $success_count = 0;
                $error_count = 0;
                
                // Skip header row
                $headers = fgetcsv($handle);
                
                while (($data = fgetcsv($handle)) !== false) {
                    $row++;
                    
                    try {
                        // Expected CSV columns: name,description,price,stock,category_id,image_url
                        if (count($data) < 4) {
                            $upload_results[] = [
                                'row' => $row,
                                'status' => 'error',
                                'message' => 'Insufficient data columns'
                            ];
                            $error_count++;
                            continue;
                        }
                        
                        $name = trim($data[0]);
                        $description = trim($data[1]);
                        $price = floatval($data[2]);
                        $stock = intval($data[3]);
                        $category_id = isset($data[4]) ? intval($data[4]) : null;
                        $image_url = isset($data[5]) ? trim($data[5]) : '';
                        
                        // Validation
                        if (empty($name) || $price <= 0 || $stock < 0) {
                            $upload_results[] = [
                                'row' => $row,
                                'status' => 'error',
                                'message' => 'Invalid data: name, price, or stock'
                            ];
                            $error_count++;
                            continue;
                        }
                        
                        // Check if category exists
                        if ($category_id) {
                            $cat_stmt = $pdo->prepare('SELECT id FROM categories WHERE id = ?');
                            $cat_stmt->execute([$category_id]);
                            if (!$cat_stmt->fetch()) {
                                $category_id = null; // Use default category if invalid
                            }
                        }
                        
                        // Download image if URL provided
                        $image_filename = '';
                        if (!empty($image_url)) {
                            $image_content = file_get_contents($image_url);
                            if ($image_content !== false) {
                                $image_extension = 'jpg'; // Default to jpg
                                $image_filename = 'bulk_upload_' . $seller['id'] . '_' . time() . '_' . $row . '.' . $image_extension;
                                $image_path = '../uploads/' . $image_filename;
                                
                                if (file_put_contents($image_path, $image_content)) {
                                    // Image saved successfully
                                } else {
                                    $image_filename = ''; // Reset if save failed
                                }
                            }
                        }
                        
                        // Insert product
                        $stmt = $pdo->prepare('
                            INSERT INTO products (name, description, price, stock, category_id, image, seller_id, approved, created_at) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, 0, NOW())
                        ');
                        
                        $stmt->execute([$name, $description, $price, $stock, $category_id, $image_filename, $seller['id']]);
                        
                        $upload_results[] = [
                            'row' => $row,
                            'status' => 'success',
                            'message' => "Product '$name' added successfully"
                        ];
                        $success_count++;
                        
                    } catch (Exception $e) {
                        $upload_results[] = [
                            'row' => $row,
                            'status' => 'error',
                            'message' => 'Database error: ' . $e->getMessage()
                        ];
                        $error_count++;
                    }
                }
                
                fclose($handle);
                
                if ($success_count > 0) {
                    $success_msg = "Successfully uploaded $success_count products. $error_count errors occurred.";
                } else {
                    $error_msg = "No products were uploaded. $error_count errors occurred.";
                }
            } else {
                $error_msg = 'Could not read the CSV file.';
            }
        }
    } else {
        $error_msg = 'File upload error: ' . $file['error'];
    }
}

// Get categories for reference
$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>Bulk Product Upload</title>
    
</head>
<body>
    <div class="bulk-upload-container">
        <div class="bulk-upload-header">
            <h1>📦 Bulk Product Upload</h1>
            <p>Upload multiple products at once using a CSV file</p>
        </div>
        
        <div class="bulk-upload-content">
            <a href="seller_dashboard.php" class="back-btn">← Back to Dashboard</a>
            
            <?php if ($success_msg): ?>
                <div>
                    <?php echo htmlspecialchars($success_msg); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error_msg): ?>
                <div>
                    <?php echo htmlspecialchars($error_msg); ?>
                </div>
            <?php endif; ?>
            
            <div class="upload-section">
                <h2>📋 CSV Template</h2>
                <div class="csv-template">
                    <h3>Required CSV Format</h3>
                    <p>Your CSV file should have the following columns (in order):</p>
                    <table>
                        <thead>
                            <tr>
                                <th>Column</th>
                                <th>Required</th>
                                <th>Description</th>
                                <th>Example</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>name</td>
                                <td>✅ Yes</td>
                                <td>Product name</td>
                                <td>iPhone 13 Pro</td>
                            </tr>
                            <tr>
                                <td>description</td>
                                <td>✅ Yes</td>
                                <td>Product description</td>
                                <td>Latest iPhone with amazing camera</td>
                            </tr>
                            <tr>
                                <td>price</td>
                                <td>✅ Yes</td>
                                <td>Product price (numbers only)</td>
                                <td>999.99</td>
                            </tr>
                            <tr>
                                <td>stock</td>
                                <td>✅ Yes</td>
                                <td>Available stock quantity</td>
                                <td>50</td>
                            </tr>
                            <tr>
                                <td>category_id</td>
                                <td>❌ No</td>
                                <td>Category ID (see reference below)</td>
                                <td>1</td>
                            </tr>
                            <tr>
                                <td>image_url</td>
                                <td>❌ No</td>
                                <td>URL to product image</td>
                                <td>https://example.com/image.jpg</td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <p>
                        <strong>Download Template:</strong> 
                        <a href="download_template.php">Download CSV Template</a>
                    </p>
                </div>
            </div>
            
            <div class="categories-reference">
                <h3>📂 Category Reference</h3>
                <p>Use these category IDs in your CSV file:</p>
                <div class="categories-grid">
                    <?php foreach ($categories as $category): ?>
                        <div class="category-item">
                            <strong>ID <?php echo $category['id']; ?>:</strong> <?php echo htmlspecialchars($category['name']); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="upload-section">
                <h2>📤 Upload CSV File</h2>
                <form method="post" enctype="multipart/form-data">
                    <div class="file-upload-area">
                        <label for="csv_file" class="file-upload-label">
                            📁 Click to select CSV file or drag and drop
                        </label>
                        <input type="file" id="csv_file" name="csv_file" accept=".csv" required>
                        <p>Maximum file size: 5MB</p>
                    </div>
                    
                    <button type="submit" class="upload-btn">🚀 Upload Products</button>
                </form>
            </div>
            
            <?php if (!empty($upload_results)): ?>
                <div class="results-section">
                    <h2>📊 Upload Results</h2>
                    <?php foreach ($upload_results as $result): ?>
                        <div class="result-item <?php echo $result['status']; ?>">
                            <strong>Row <?php echo $result['row']; ?>:</strong> <?php echo htmlspecialchars($result['message']); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // File upload preview
        document.getElementById('csv_file').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const label = document.querySelector('.file-upload-label');
                label.textContent = `📁 Selected: ${file.name}`;
            }
        });
        
        // Drag and drop functionality
        const uploadArea = document.querySelector('.file-upload-area');
        const fileInput = document.getElementById('csv_file');
        
        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            uploadArea.style.borderColor = '#1A237E';
        });
        
        uploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            uploadArea.style.borderColor = '#00BFAE';
        });
        
        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            uploadArea.style.borderColor = '#00BFAE';
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                const label = document.querySelector('.file-upload-label');
                label.textContent = `📁 Selected: ${files[0].name}`;
            }
        });
    </script>
</body>
</html> 