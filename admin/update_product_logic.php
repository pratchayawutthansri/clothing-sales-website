<?php
require_once 'includes/config.php';
checkAdminAuth();
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: products.php");
    exit;
}

// CSRF Check
if (!isset($_POST['csrf_token']) || !isset($_SESSION['admin_csrf_token']) || !hash_equals($_SESSION['admin_csrf_token'], $_POST['csrf_token'])) {
    die("Security Error: Invalid CSRF Token");
}

$id = (int)$_POST['id'];
$name = trim($_POST['name']);
$category = trim($_POST['category']);
$description = trim($_POST['description']);
$base_price = (float)$_POST['base_price'];
$badge = $_POST['badge'] ?? null;
$is_visible = isset($_POST['is_visible']) ? 1 : 0;

if (empty($name) || $base_price <= 0) {
    header("Location: edit_product.php?id=$id&error=" . urlencode("Name and price are required (price must be > 0)"));
    exit;
}

try {
    $pdo->beginTransaction();
    $fileToDelete = null;

    // 1. Update Product Info
    $sql = "UPDATE products SET name = ?, category = ?, description = ?, base_price = ?, badge = ?, is_visible = ? WHERE id = ?";
    $params = [$name, $category, $description, $base_price, $badge, $is_visible, $id];
    
    // 2. Handle Image Upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($_FILES['image']['tmp_name']);
        
        if (!in_array($ext, $allowed) || !in_array($mimeType, $allowedMimes)) {
            throw new Exception("Invalid image type (Allowed: JPG, PNG, WebP)");
        }
        if ($_FILES['image']['size'] > $maxSize) {
            throw new Exception("Image is too large (Maximum: 5MB)");
        }
        
        // Get old image to delete
        $stmtOld = $pdo->prepare("SELECT image FROM products WHERE id = ?");
        $stmtOld->execute([$id]);
        $oldImg = $stmtOld->fetchColumn();
        
        // Upload new
        $newName = uniqid() . '.' . $ext;
        $dest = '../images/' . $newName;
        $dbPath = 'images/' . $newName; // Relative to web root
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
            // Track old file for deletion after commit
            if ($oldImg && file_exists("../" . $oldImg) && $oldImg !== 'images/placeholder.jpg') {
                $fileToDelete = "../" . $oldImg;
            }
            
            // Update DB with new image
            $sqlUpdateImg = "UPDATE products SET image = ? WHERE id = ?";
            $stmtUpdateImg = $pdo->prepare($sqlUpdateImg);
            $stmtUpdateImg->execute([$dbPath, $id]);
        } else {
            throw new Exception("เกิดข้อผิดพลาด: ไม่สามารถบันทึกไฟล์ภาพในระบบได้ (Permission/Disk Error)");
        }
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // 3. Update Existing Variants and Delete Removed Ones
    $submittedVariantIds = [];
    if (isset($_POST['existing_variant_ids'])) {
        $ids = $_POST['existing_variant_ids'];
        $sizes = $_POST['existing_sizes'];
        $prices = $_POST['existing_prices'];
        $stocks = $_POST['existing_stocks'];
        
        $stmtUpdateVar = $pdo->prepare("UPDATE product_variants SET size = ?, price = ?, stock = ? WHERE id = ? AND product_id = ?");
        
        for ($i = 0; $i < count($ids); $i++) {
            $currentId = (int)$ids[$i];
            $submittedVariantIds[] = $currentId;
            $stmtUpdateVar->execute([
                strtoupper(isset($sizes[$i]) ? $sizes[$i] : ''),
                (float)($prices[$i] ?? 0),
                max(0, (int)($stocks[$i] ?? 0)),
                $currentId,
                $id
            ]);
        }
    }

    // Delete variants that were removed from the UI
    if (empty($submittedVariantIds)) {
        $stmtDeleteVar = $pdo->prepare("DELETE FROM product_variants WHERE product_id = ?");
        $stmtDeleteVar->execute([$id]);
    } else {
        $placeholders = implode(',', array_fill(0, count($submittedVariantIds), '?'));
        $stmtDeleteVar = $pdo->prepare("DELETE FROM product_variants WHERE product_id = ? AND id NOT IN ($placeholders)");
        $paramsDelete = array_merge([$id], $submittedVariantIds);
        $stmtDeleteVar->execute($paramsDelete);
    }

    // 4. Insert New Variants
    if (isset($_POST['new_sizes'])) {
        $newSizes = $_POST['new_sizes'];
        $newPrices = $_POST['new_prices'];
        $newStocks = $_POST['new_stocks'];
        
        $stmtInsertVar = $pdo->prepare("INSERT INTO product_variants (product_id, size, price, stock) VALUES (?, ?, ?, ?)");
        
        for ($i = 0; $i < count($newSizes); $i++) {
            if (!empty($newSizes[$i])) {
                $vPrice = (float)($newPrices[$i] ?? 0);
                $vStock = max(0, (int)($newStocks[$i] ?? 0));
                if ($vPrice <= 0) {
                    throw new Exception("ราคาของ Variant ไซส์ '{$newSizes[$i]}' ห้ามน้อยกว่าหรือเท่ากับ 0");
                }
                
                $stmtInsertVar->execute([
                    $id,
                    strtoupper($newSizes[$i]),
                    $vPrice,
                    $vStock
                ]);
            }
        }
    }

    $pdo->commit();
    
    // Execute pending file deletion
    if ($fileToDelete && file_exists($fileToDelete)) {
        unlink($fileToDelete);
    }
    
    header("Location: products.php?success=Product updated successfully");
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    header("Location: edit_product.php?id=$id&error=" . urlencode($e->getMessage()));
    exit;
}
?>
