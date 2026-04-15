<?php
require_once 'includes/config.php';
checkAdminAuth();
require_once '../includes/db.php'; // Use main DB connection

// Fetch Products
$stmt = $pdo->query("SELECT * FROM products ORDER BY id DESC");
$products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products Management - Xivex Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/admin.css">
    <style>
        /* Modern SaaS Dashboard Layout */
        body { background: #f3f4f6; font-family: 'Kanit', sans-serif; }
        
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .page-header h1 { font-family: 'Outfit', sans-serif; font-size: 1.8rem; margin: 0; color: #111827; }
        
        .btn-modern { 
            display: inline-flex; align-items: center; justify-content: center; 
            padding: 10px 20px; background: #000; color: #fff; border: none; 
            border-radius: 8px; font-weight: 600; font-size: 0.95rem; text-decoration: none; 
            transition: 0.2s; gap: 8px; cursor: pointer;
        }
        .btn-modern:hover { background: #374151; transform: translateY(-1px); }

        .modern-table-card { 
            background: white; border-radius: 12px; 
            box-shadow: 0 1px 3px rgba(0,0,0,0.05), 0 1px 2px rgba(0,0,0,0.03); 
            overflow: hidden; border: 1px solid #e5e7eb; 
        }
        .modern-table { width: 100%; border-collapse: separate; border-spacing: 0; }
        .modern-table th { 
            background: #f9fafb; font-weight: 600; color: #4b5563; font-size: 0.85rem; 
            text-transform: uppercase; letter-spacing: 0.05em; padding: 16px 24px; 
            text-align: left; border-bottom: 1px solid #e5e7eb; 
        }
        .modern-table td { 
            padding: 16px 24px; vertical-align: middle; border-bottom: 1px solid #f3f4f6; 
            transition: background 0.2s; 
        }
        .modern-table tbody tr:hover td { background: #f9fafb; }
        .modern-table tbody tr:last-child td { border-bottom: none; }

        .product-thumb { width: 48px; height: 48px; border-radius: 8px; object-fit: cover; border: 1px solid #e5e7eb; display: block; }

        .primary-text { font-weight: 600; color: #111827; margin-bottom: 2px; }
        .secondary-text { font-size: 0.85rem; color: #6b7280; font-family: 'Outfit', sans-serif;}
        
        .badge-category { display: inline-block; padding: 4px 10px; background: #f3f4f6; color: #4b5563; border-radius: 20px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; border: 1px solid #e5e7eb; }

        .action-flex { display: flex; gap: 8px; justify-content: flex-end; }
        .btn-action { padding: 8px 12px; border-radius: 6px; font-weight: 600; font-size: 0.85rem; cursor: pointer; transition: 0.2s; display: inline-flex; align-items: center; gap: 5px; text-decoration: none; }
        
        .btn-edit { background: #fef3c7; color: #d97706; border: none; }
        .btn-edit:hover { background: #fde68a; }
        
        .btn-danger { background: #fee2e2; color: #ef4444; border: none; }
        .btn-danger:hover { background: #fecaca; }

        .alert-success { background: #dcfce7; color: #166534; padding: 16px 24px; border-radius: 8px; margin-bottom: 24px; font-weight: 500; display: flex; align-items: center; gap: 10px; border: 1px solid #bbf7d0;}
        .alert-error { background: #fee2e2; color: #991b1b; padding: 16px 24px; border-radius: 8px; margin-bottom: 24px; font-weight: 500; display: flex; align-items: center; gap: 10px; border: 1px solid #fecaca;}
    </style>
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<div class="content">
    
    <div class="page-header">
        <h1><?= __('ap_title') ?></h1>
        <a href="add_product.php" class="btn-modern">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            <?= __('ap_add_new') ?>
        </a>
    </div>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert-error">
            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <?= htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['success'])): ?>
        <div class="alert-success">
            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <?= __('ap_msg_success') ?>
        </div>
    <?php endif; ?>

    <div class="modern-table-card">
        <table class="modern-table">
            <thead>
                <tr>
                    <th style="width: 80px;"><?= __('ap_th_image') ?></th>
                    <th><?= __('ap_th_name') ?></th>
                    <th><?= __('ap_th_category') ?></th>
                    <th><?= __('ap_th_price') ?></th>
                    <th style="text-align: right;"><?= __('ap_th_action') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="5" style="padding: 40px; text-align: center; color: #6b7280; font-size: 1.1rem;">
                           <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width:48px; height:48px; margin-bottom:10px; opacity:0.5;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg><br>
                           ยังไม่มีสินค้าในระบบ
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($products as $product): ?>
                    <tr>
                        <td>
                            <img src="../<?= htmlspecialchars($product['image']) ?>" class="product-thumb" alt="Product Image">
                        </td>
                        <td>
                            <div class="primary-text"><?= htmlspecialchars($product['name']) ?></div>
                            <div class="secondary-text">#PRD-<?= str_pad($product['id'], 4, '0', STR_PAD_LEFT) ?></div>
                        </td>
                        <td>
                            <span class="badge-category"><?= htmlspecialchars($product['category']) ?></span>
                        </td>
                        <td class="primary-text">฿<?= number_format($product['base_price'], 0) ?></td>
                        <td>
                            <div class="action-flex">
                                <a href="edit_product.php?id=<?= $product['id'] ?>" class="btn-action btn-edit" title="<?= __('ap_btn_edit') ?>">
                                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                    แก้ไข
                                </a>
                                <form action="delete_product.php" method="POST" onsubmit="return confirm('<?= __('ap_msg_delete_confirm') ?>')">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['admin_csrf_token'] ?? '' ?>">
                                    <input type="hidden" name="id" value="<?= $product['id'] ?>">
                                    <button type="submit" class="btn-action btn-danger" title="<?= __('ap_btn_delete') ?>">
                                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        ลบ
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
