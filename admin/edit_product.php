<?php
require_once 'includes/config.php';
checkAdminAuth();
require_once '../includes/db.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header("Location: products.php");
    exit;
}

// Fetch Product
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    die("Product not found.");
}

// Fetch Variants
$stmtV = $pdo->prepare("SELECT * FROM product_variants WHERE product_id = ? ORDER BY id ASC");
$stmtV->execute([$id]);
$variants = $stmtV->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - Xivex Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/admin.css">
    <style>
        /* Modern Form Layout */
        body { background: #f3f4f6; font-family: 'Kanit', sans-serif; }
        
        .page-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 24px;
        }
        .page-header h1 {
            font-family: 'Outfit', sans-serif;
            font-size: 1.8rem; margin: 0; color: #111827; 
        }

        .dashboard-grid {
            display: grid; grid-template-columns: 2fr 1fr; gap: 24px;
            align-items: start;
        }
        
        .modern-card {
            background: #ffffff; border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05), 0 1px 2px rgba(0,0,0,0.03);
            padding: 24px; margin-bottom: 24px;
            border: 1px solid #e5e7eb;
        }
        .modern-card h2 {
            font-size: 1.1rem; color: #111827; margin: 0 0 20px 0;
            padding-bottom: 12px; border-bottom: 1px solid #f3f4f6;
            font-weight: 600;
        }
        
        /* Modern Inputs */
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 0.9rem; font-weight: 500; color: #374151; margin-bottom: 8px; }
        .modern-input, .modern-select, .modern-textarea {
            width: 100%; box-sizing: border-box;
            padding: 10px 14px; border: 1px solid #d1d5db; border-radius: 8px;
            font-family: inherit; font-size: 0.95rem; background: #f9fafb;
            transition: all 0.2s;
        }
        .modern-textarea { resize: vertical; min-height: 120px; }
        .modern-input:focus, .modern-select:focus, .modern-textarea:focus {
            outline: none; border-color: #000; box-shadow: 0 0 0 3px rgba(0,0,0,0.05); background: #fff;
        }
        
        /* Variants */
        .variant-group { 
            background: #f9fafb; border: 1px solid #e5e7eb; 
            padding: 16px; border-radius: 8px; margin-bottom: 12px; 
            display: flex; gap: 12px; align-items: flex-end; 
        }
        .variant-field { flex: 1; }
        .variant-field label { font-size: 0.8rem; color: #6b7280; font-weight: 400; margin-bottom: 4px; }
        
        /* Buttons */
        .btn-modern {
            display: inline-flex; align-items: center; justify-content: center;
            padding: 12px 24px; background: #000; color: #fff; border: none; border-radius: 8px;
            font-family: inherit; font-weight: 600; font-size: 1rem; cursor: pointer;
            transition: all 0.2s; text-decoration: none; width: 100%; gap: 8px;
        }
        .btn-modern:hover { background: #374151; transform: translateY(-1px); }
        .btn-outline { background: #fff; color: #111827; border: 1px solid #d1d5db; }
        .btn-outline:hover { background: #f9fafb; }
        .btn-danger-icon { background: #fee2e2; color: #ef4444; border: 1px solid #fecaca; }
        .btn-danger-icon:hover { background: #fecaca; }

        /* Media */
        .current-img { width: 100%; border-radius: 8px; border: 1px solid #e5e7eb; aspect-ratio: 1/1; object-fit: cover; }
        .custom-file-upload {
            border: 1px dashed #d1d5db; display: flex; align-items: center; justify-content: center;
            padding: 20px; border-radius: 8px; background: #f9fafb; cursor: pointer;
            color: #6b7280; font-size: 0.9rem; transition: 0.2s;
        }
        .custom-file-upload:hover { border-color: #9ca3af; color: #374151; }
        input[type="file"] { display: none; }
        
        /* Toggle */
        .toggle-label { display: flex; align-items: center; cursor: pointer; gap: 10px; font-weight: 500; color: #111827; }
        .toggle-switch { position: relative; width: 44px; height: 24px; background: #d1d5db; border-radius: 24px; transition: 0.3s; }
        .toggle-switch::after {
            content: ''; position: absolute; top: 2px; left: 2px;
            width: 20px; height: 20px; background: white; border-radius: 50%; transition: 0.3s;
        }
        input:checked + .toggle-switch { background: #000; }
        input:checked + .toggle-switch::after { transform: translateX(20px); }

        @media (max-width: 1024px) {
            .dashboard-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<div class="content">
    <div class="page-header">
        <h1><?= __('aep_title') ?>: <span style="font-weight: 400; color:#6b7280;"><?= htmlspecialchars($product['name']) ?></span></h1>
        <a href="products.php" class="btn-modern btn-outline" style="width: auto; padding: 10px 20px;">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin-right: 5px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            กลับสู่หน้ารายการสินค้า
        </a>
    </div>
    
    <?php if (!empty($_GET['error']) && is_string($_GET['error'])): ?>
    <div style="background: #fee2e2; border: 1px solid #fecaca; color: #ef4444; padding: 16px; border-radius: 8px; margin-bottom: 24px; display: flex; align-items: center; gap: 10px; font-weight: 500;">
        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        <?= htmlspecialchars($_GET['error']) ?>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($_GET['success']) && is_string($_GET['success'])): ?>
    <div style="background: #d1fae5; border: 1px solid #a7f3d0; color: #059669; padding: 16px; border-radius: 8px; margin-bottom: 24px; display: flex; align-items: center; gap: 10px; font-weight: 500;">
        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        <?= htmlspecialchars($_GET['success']) ?>
    </div>
    <?php endif; ?>
    
    <form action="update_product_logic.php" method="POST" enctype="multipart/form-data" onsubmit="return checkDuplicateSizes()">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['admin_csrf_token'] ?? '' ?>">
        <input type="hidden" name="id" value="<?= $product['id'] ?>">
        
        <div class="dashboard-grid">
            <!-- Left Column: Primary Details -->
            <div class="grid-left">
                <div class="modern-card">
                    <h2>ข้อมูลพื้นฐานสินค้า</h2>
                    
                    <div class="form-group">
                        <label><?= __('aap_name') ?></label>
                        <input type="text" name="name" value="<?= htmlspecialchars($product['name']) ?>" required class="modern-input">
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label><?= __('aap_category') ?></label>
                            <?php
                                $preset_categories = ['Tops', 'Bottoms', 'Outerwear', 'Accessories'];
                                $current_category = $product['category'] ?? '';
                                $found_category = false;
                                foreach ($preset_categories as $cat) {
                                    if (strcasecmp($current_category, $cat) === 0) {
                                        $found_category = true;
                                        break;
                                    }
                                }
                                if (!$found_category && $current_category !== '') {
                                    $preset_categories[] = $current_category;
                                }
                            ?>
                            <select name="category" class="modern-select">
                                <?php foreach ($preset_categories as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat) ?>" <?= strcasecmp($current_category, $cat) === 0 ? 'selected' : '' ?>><?= htmlspecialchars($cat) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><?= __('aap_base_price') ?></label>
                            <input type="number" name="base_price" value="<?= $product['base_price'] ?>" required min="0" step="0.01" class="modern-input">
                        </div>
                    </div>

                    <div class="form-group">
                        <label><?= __('aap_desc') ?></label>
                        <textarea name="description" required class="modern-textarea"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="modern-card">
                    <h2><?= __('aap_variants') ?> (ขนาดและสต็อก)</h2>
                    <div id="variants-container">
                        <?php foreach ($variants as $v): ?>
                        <div class="variant-group">
                            <input type="hidden" name="existing_variant_ids[]" value="<?= $v['id'] ?>">
                            <div class="variant-field">
                                <label>Size (ขนาด)</label>
                                <input type="text" name="existing_sizes[]" value="<?= htmlspecialchars($v['size']) ?>" required class="modern-input" placeholder="e.g. XL">
                            </div>
                            <div class="variant-field">
                                <label>Price (ราคา)</label>
                                <input type="number" name="existing_prices[]" value="<?= $v['price'] ?>" required min="0" step="0.01" class="modern-input">
                            </div>
                            <div class="variant-field">
                                <label>Stock (จำนวน)</label>
                                <input type="number" name="existing_stocks[]" value="<?= $v['stock'] ?>" required min="0" class="modern-input">
                            </div>
                            <button type="button" class="btn-modern btn-danger-icon" onclick="this.parentElement.remove()" style="width: auto; padding: 10px; margin-bottom: 2px;" title="ลบตัวเลือกนี้">
                                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div id="new-variants-container"></div>
                    
                    <button type="button" class="btn-modern btn-outline" onclick="addVariant()" style="margin-top:10px; width: auto;">
                        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        <?= __('aep_add_new_variant') ?>
                    </button>
                </div>
            </div>

            <!-- Right Column: Media & Settings -->
            <div class="grid-right">
                <div class="modern-card" style="padding-bottom: 30px;">
                    <h2>บันทึกข้อมูล</h2>
                    <button type="submit" class="btn-modern">
                        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        <?= __('aep_btn_save') ?>
                    </button>
                </div>

                <div class="modern-card">
                    <h2>สถานะการแสดงผล</h2>
                    
                    <div class="form-group" style="padding: 10px 0; border-bottom: 1px solid #f3f4f6;">
                        <label class="toggle-label">
                            <input type="checkbox" name="is_visible" value="1" <?= $product['is_visible'] ? 'checked' : '' ?> style="display:none;">
                            <div class="toggle-switch"></div>
                            <span><?= __('aap_visible') ?> (แสดงหน้าร้าน)</span>
                        </label>
                    </div>

                    <div class="form-group" style="margin-top: 20px;">
                        <label><?= __('aap_badge') ?> (ป้ายกำกับ)</label>
                        <input type="text" name="badge" value="<?= htmlspecialchars($product['badge'] ?? '') ?>" placeholder="e.g. New Arrival, Sale" class="modern-input">
                        <span style="font-size:0.8rem; color:#6b7280; display:block; margin-top:5px;">ใส่ข้อความสั้นๆ เพื่อดึงดูดความสนใจ</span>
                    </div>
                </div>

                <div class="modern-card">
                    <h2><?= __('aep_image_replace') ?></h2>
                    
                    <?php if ($product['image']): ?>
                        <div style="margin-bottom: 15px;">
                            <img src="../<?= htmlspecialchars($product['image']) ?>" class="current-img">
                            <div style="font-size:0.8rem; color:#6b7280; text-align:center; margin-top:5px;">รูปปัจจุบัน (Current Image)</div>
                        </div>
                    <?php endif; ?>

                    <label class="custom-file-upload">
                        <input type="file" name="image" accept="image/*" id="imgInp">
                        <div style="display: flex; flex-direction: column; align-items: center; gap: 8px;">
                            <svg width="32" height="32" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                            <span id="fileName">คลิกเพื่ออัปโหลดรูปใหม่ (เฉพาะภาพใหม่)</span>
                        </div>
                    </label>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
document.getElementById('imgInp').onchange = function() {
    const fileNameDisplay = document.getElementById('fileName');
    if (this.files && this.files.length > 0) {
        fileNameDisplay.textContent = 'เตรียมอัปโหลด: ' + this.files[0].name;
        fileNameDisplay.style.color = '#059669';
        fileNameDisplay.style.fontWeight = 'bold';
    } else {
        fileNameDisplay.textContent = 'คลิกเพื่ออัปโหลดรูปใหม่ (เฉพาะภาพใหม่)';
        fileNameDisplay.style.color = 'inherit';
        fileNameDisplay.style.fontWeight = 'normal';
    }
};

function checkDuplicateSizes() {
    const sizeInputs = document.querySelectorAll('input[name="existing_sizes[]"], input[name="new_sizes[]"]');
    const sizes = [];
    for (let input of sizeInputs) {
        const val = input.value.trim().toUpperCase();
        if (val) {
            if (sizes.includes(val)) {
                alert('ไม่สามารถบันทึกได้! เนื่องจากคุณมีตัวเลือกไซส์ "' + val + '" ซ้ำซ้อนกันอยู่ กรุณาตรวจสอบให้แน่ใจว่าแต่ละไซส์มีเพียงตัวเลือกเดียวครับ');
                input.focus();
                return false;
            }
            sizes.push(val);
        }
    }
    return true;
}

function addVariant() {
    const container = document.getElementById('new-variants-container');
    const div = document.createElement('div');
    div.className = 'variant-group';
    div.innerHTML = `
        <div class="variant-field">
            <label>Size (ขนาดใหม่)</label>
            <input type="text" name="new_sizes[]" placeholder="e.g. M" required class="modern-input">
        </div>
        <div class="variant-field">
            <label>Price (ราคา)</label>
            <input type="number" name="new_prices[]" placeholder="Price" required min="0" step="0.01" class="modern-input">
        </div>
        <div class="variant-field">
            <label>Stock (จำนวนเริ่ม)</label>
            <input type="number" name="new_stocks[]" value="100" placeholder="Stock" min="0" class="modern-input">
        </div>
        <button type="button" class="btn-modern btn-danger-icon" onclick="this.parentElement.remove()" style="width: auto; padding: 10px; margin-bottom: 2px;" title="ลบตัวเลือกนี้">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
        </button>
    `;
    container.appendChild(div);
}
</script>

</body>
</html>
