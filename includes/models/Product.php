<?php
/**
 * Product Model - Handles all database operations for products
 */
class Product {
    private static $table = 'products';

    /**
     * Fetch all visible products with optional category filtering
     */
    public static function getAll($category = null, $orderBy = 'id DESC') {
        global $pdo;
        
        $whereClause = "WHERE is_visible = 1";
        $params = [];

        if ($category) {
            $whereClause .= " AND category = ?";
            $params[] = $category;
        }

        $stmt = $pdo->prepare("SELECT * FROM " . self::$table . " $whereClause ORDER BY $orderBy");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Fetch a single visible product by ID
     */
    public static function find($id) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM " . self::$table . " WHERE id = ? AND is_visible = 1");
        $stmt->execute([(int)$id]);
        return $stmt->fetch();
    }

    /**
     * Fetch all variants for a specific product
     */
    public static function getVariants($productId) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM product_variants WHERE product_id = ? ORDER BY price ASC");
        $stmt->execute([(int)$productId]);
        return $stmt->fetchAll();
    }

    /**
     * Fetch the newest products (Featured)
     */
    public static function getFeatured($limit = 4) {
        return self::getAll(null, "created_at DESC LIMIT " . (int)$limit);
    }
}
?>
