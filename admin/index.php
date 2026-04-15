<?php
require_once 'includes/config.php';
checkAdminAuth();
require_once '../includes/db.php';

// --- KPI Stats Logic ---

// 1. Total Revenue (Paid, Shipped, Completed)
$stmtRevenue = $pdo->query("SELECT SUM(total_price) FROM orders WHERE status IN ('paid', 'shipped', 'completed')");
$totalRevenue = $stmtRevenue->fetchColumn() ?: 0;

// 2. Pending Orders (Waiting for Admin Check)
$stmtPending = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'");
$pendingOrders = $stmtPending->fetchColumn();

// 3. To Ship (Paid but not shipped)
$stmtToShip = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'paid'");
$toShipCount = $stmtToShip->fetchColumn();

// 4. Low Stock Items (Stock < 10)
$stmtLowStock = $pdo->query("SELECT COUNT(*) FROM product_variants WHERE stock < 10");
$lowStockCount = $stmtLowStock->fetchColumn();

// 4. Monthly Sales (Current Month)
$currentMonth = date('Y-m');
$stmtMonthly = $pdo->prepare("SELECT SUM(total_price) FROM orders WHERE status IN ('paid', 'shipped', 'completed') AND DATE_FORMAT(order_date, '%Y-%m') = ?");
$stmtMonthly->execute([$currentMonth]);
$monthlySales = $stmtMonthly->fetchColumn() ?: 0;

// 5. Chart Data (Last 6 Months) - Single query
$months = [];
$sales = [];
$startMonth = date('Y-m', strtotime("-5 months"));

$stmtChart = $pdo->prepare("
    SELECT DATE_FORMAT(order_date, '%Y-%m') AS month, SUM(total_price) AS total
    FROM orders
    WHERE status IN ('paid', 'shipped', 'completed')
      AND DATE_FORMAT(order_date, '%Y-%m') >= ?
    GROUP BY month
    ORDER BY month ASC
");
$stmtChart->execute([$startMonth]);
$chartData = [];
while ($row = $stmtChart->fetch()) {
    $chartData[$row['month']] = (float)$row['total'];
}

// Fill in all 6 months (including months with zero sales)
for ($i = 5; $i >= 0; $i--) {
    $m = date('Y-m', strtotime("-$i months"));
    $label = date('M Y', strtotime("-$i months"));
    $months[] = $label;
    $sales[] = $chartData[$m] ?? 0;
}

// 6. Sales by Category (for Doughnut chart)
$stmtCat = $pdo->query("
    SELECT p.category, SUM(oi.subtotal) AS total
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.status IN ('paid', 'shipped', 'completed')
    GROUP BY p.category
    ORDER BY total DESC
");
$catData = $stmtCat->fetchAll(PDO::FETCH_ASSOC);
$catLabels = array_column($catData, 'category');
$catValues = array_map('floatval', array_column($catData, 'total'));

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xivex Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/admin.css">
    <style>
        /* ─── Dashboard Premium ─── */
        body { background: #f3f4f6; font-family: 'Kanit', sans-serif; }

        .dash-title {
            font-family: 'Outfit', sans-serif;
            font-size: 1.8rem;
            font-weight: 700;
            color: #111827;
            margin-bottom: 24px;
        }

        /* Stat Cards Grid */
        .dash-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        /* Minimalist SaaS Card */
        .dcard {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            transition: all 0.2s ease-in-out;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 120px;
        }
        .dcard:hover {
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05), 0 4px 6px -2px rgba(0,0,0,0.025);
            transform: translateY(-2px);
            border-color: #d1d5db;
        }

        /* Card Top Row */
        .dcard-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        .dcard-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .dcard-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Icon Colors */
        .icon-revenue { background: #dcfce7; color: #16a34a; }
        .icon-monthly { background: #eff6ff; color: #2563eb; }
        .icon-pending { background: #fef9c3; color: #ca8a04; }
        .icon-toship  { background: #f3e8ff; color: #9333ea; }
        .icon-lowstock{ background: #fee2e2; color: #dc2626; }

        .dcard-icon svg { width: 18px; height: 18px; }

        /* Card Bottom */
        .dcard-bottom { margin-top: 16px; }
        .dcard-value {
            font-family: 'Outfit', sans-serif;
            font-size: 2rem;
            font-weight: 700;
            color: #111827;
            line-height: 1;
        }
        .dcard-sub {
            font-size: 0.8rem;
            color: #9ca3af;
            margin-top: 6px;
            font-weight: 500;
        }

        /* ─── Chart Section ─── */
        .chart-box {
            background: #fff;
            padding: 24px;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            margin-bottom: 40px;
        }
        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            border-bottom: 1px solid #f3f4f6;
            padding-bottom: 16px;
        }
        .chart-header h2 {
            margin: 0;
            font-family: 'Outfit', sans-serif;
            font-size: 1.1rem;
            font-weight: 600;
            color: #111827;
        }
        .chart-badge {
            font-size: 0.75rem;
            background: #f3f4f6;
            color: #4b5563;
            padding: 4px 10px;
            border-radius: 20px;
            font-weight: 600;
        }
    </style>
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<div class="content">
    <h1 class="dash-title"><?= __('dash_main') ?></h1>
    
    <div class="dash-grid">
        <!-- Total Revenue -->
        <div class="dcard">
            <div class="dcard-top">
                <div class="dcard-label"><?= __('dash_total_rev') ?></div>
                <div class="dcard-icon icon-revenue">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                </div>
            </div>
            <div class="dcard-bottom">
                <div class="dcard-value">฿<?= number_format($totalRevenue, 0) ?></div>
                <div class="dcard-sub"><?= __('dash_all_time') ?></div>
            </div>
        </div>

        <!-- Monthly Revenue -->
        <div class="dcard">
            <div class="dcard-top">
                <div class="dcard-label"><?= __('dash_monthly_rev') ?></div>
                <div class="dcard-icon icon-monthly">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline><polyline points="17 6 23 6 23 12"></polyline></svg>
                </div>
            </div>
            <div class="dcard-bottom">
                <div class="dcard-value">฿<?= number_format($monthlySales, 0) ?></div>
                <div class="dcard-sub"><?= date('F Y') ?></div>
            </div>
        </div>

        <!-- Pending -->
        <div class="dcard">
            <div class="dcard-top">
                <div class="dcard-label"><?= __('dash_pending') ?></div>
                <div class="dcard-icon icon-pending">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                </div>
            </div>
            <div class="dcard-bottom">
                <div class="dcard-value" style="color: #ca8a04;"><?= $pendingOrders ?></div>
                <div class="dcard-sub"><?= __('dash_orders_verify') ?></div>
            </div>
        </div>

        <!-- To Ship -->
        <div class="dcard">
            <div class="dcard-top">
                <div class="dcard-label"><?= __('dash_to_ship') ?></div>
                <div class="dcard-icon icon-toship">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
                </div>
            </div>
            <div class="dcard-bottom">
                <div class="dcard-value" style="color: #9333ea;"><?= $toShipCount ?></div>
                <div class="dcard-sub"><?= __('dash_paid_waiting') ?></div>
            </div>
        </div>

        <!-- Low Stock -->
        <div class="dcard">
            <div class="dcard-top">
                <div class="dcard-label"><?= __('dash_low_stock') ?></div>
                <div class="dcard-icon icon-lowstock">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                </div>
            </div>
            <div class="dcard-bottom">
                <div class="dcard-value" style="color: #dc2626;"><?= $lowStockCount ?></div>
                <div class="dcard-sub"><?= __('dash_less_than_10') ?></div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 24px; margin-bottom: 40px;">
        
        <!-- Bar Chart: Monthly Sales -->
        <div class="chart-box">
            <div class="chart-header">
                <h2><?= __('dash_chart_title') ?></h2>
                <span class="chart-badge"><?= __('dash_chart_badge') ?></span>
            </div>
            <div style="height: 300px;">
                <canvas id="salesChart"></canvas>
            </div>
        </div>

        <!-- Doughnut Chart: Sales by Category -->
        <div class="chart-box">
            <div class="chart-header">
                <h2><?= __('dash_cat_title') ?></h2>
            </div>
            <div style="height: 300px; display: flex; align-items: center; justify-content: center;">
                <?php if (empty($catData)): ?>
                    <div style="display:flex; flex-direction:column; align-items:center; color:#9ca3af;">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width:48px;height:48px;margin-bottom:10px;opacity:0.5"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                        <span><?= __('dash_no_sales') ?></span>
                    </div>
                <?php else: ?>
                    <canvas id="catChart"></canvas>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    // Global Chart Config
    Chart.defaults.font.family = "'Kanit', sans-serif";
    Chart.defaults.color = '#6b7280';

    // ─── Bar Chart: Monthly Revenue ───
    const ctx1 = document.getElementById('salesChart').getContext('2d');
    new Chart(ctx1, {
        type: 'bar',
        data: {
            labels: <?= json_encode($months) ?>,
            datasets: [{
                label: '<?= __('dash_revenue_label') ?>',
                data: <?= json_encode($sales) ?>,
                backgroundColor: '#111827',
                hoverBackgroundColor: '#374151',
                borderRadius: 4,
                borderSkipped: false,
                barPercentage: 0.5,
                categoryPercentage: 0.6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: '#f3f4f6', drawBorder: false },
                    border: { display: false },
                    ticks: {
                        padding: 10,
                        callback: function(value) {
                            if (value >= 1000) return '฿' + (value / 1000).toFixed(0) + 'K';
                            return '฿' + value;
                        }
                    }
                },
                x: {
                    grid: { display: false },
                    border: { display: false },
                    ticks: { padding: 10 }
                }
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#111827',
                    titleFont: { family: "'Outfit', sans-serif", size: 13, weight: '600' },
                    bodyFont: { family: "'Kanit', sans-serif", size: 13 },
                    padding: 12,
                    cornerRadius: 8,
                    displayColors: false,
                    callbacks: {
                        label: function(ctx) { return '฿' + ctx.parsed.y.toLocaleString(); }
                    }
                }
            }
        }
    });

    // ─── Doughnut Chart: Sales by Category ───
    <?php if (!empty($catData)): ?>
    const ctx2 = document.getElementById('catChart').getContext('2d');
    const catColors = [
        '#111827', '#4b5563', '#9ca3af', '#d1d5db', '#1f2937',
        '#374151', '#6b7280', '#e5e7eb', '#000000', '#f3f4f6'
    ];
    new Chart(ctx2, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($catLabels) ?>,
            datasets: [{
                data: <?= json_encode($catValues) ?>,
                backgroundColor: catColors.slice(0, <?= count($catLabels) ?>),
                hoverOffset: 4,
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        padding: 20,
                        boxWidth: 10,
                        boxHeight: 10,
                        usePointStyle: true,
                        pointStyle: 'circle'
                    }
                },
                tooltip: {
                    backgroundColor: '#111827',
                    titleFont: { family: "'Outfit', sans-serif", size: 13, weight: '600' },
                    bodyFont: { family: "'Kanit', sans-serif", size: 13 },
                    padding: 12,
                    cornerRadius: 8,
                    callbacks: {
                        label: function(ctx) {
                            const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                            const pct = ((ctx.parsed / total) * 100).toFixed(1);
                            return ' ฿' + ctx.parsed.toLocaleString() + ' (' + pct + '%)';
                        }
                    }
                }
            }
        }
    });
    <?php endif; ?>
    </script>
    
</div>

</body>
</html>
