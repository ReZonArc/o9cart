<?php
/**
 * Recommendation Engine
 * 
 * AI-powered product recommendation system using collaborative and content-based filtering
 * Integrates with OpenCog for advanced cognitive recommendations
 * 
 * @category   Library
 * @package    Intelligence
 * @subpackage Recommendation
 * @author     O9Cart Development Team
 * @license    GPL v3
 */

namespace O9Cart\Intelligence\Recommendation;

use O9Cart\Intelligence\OpenCog\AtomSpaceConnector;

class RecommendationEngine {
    
    private $db;
    private $log;
    private $config;
    private $cache;
    private $atomspace;
    
    /**
     * Constructor
     * 
     * @param object $registry The application registry
     */
    public function __construct($registry) {
        $this->db = $registry->get('db');
        $this->log = $registry->get('log');
        $this->config = $registry->get('config');
        $this->cache = $registry->get('cache');
        
        // Initialize OpenCog connector for advanced recommendations
        $this->atomspace = new AtomSpaceConnector($registry);
    }
    
    /**
     * Get product recommendations for a customer
     * 
     * @param int $customer_id
     * @param array $options
     * @return array
     */
    public function getRecommendationsForCustomer($customer_id, $options = []) {
        $limit = $options['limit'] ?? 10;
        $algorithm = $options['algorithm'] ?? 'hybrid';
        $exclude_purchased = $options['exclude_purchased'] ?? true;
        
        $recommendations = [];
        
        try {
            switch ($algorithm) {
                case 'collaborative':
                    $recommendations = $this->getCollaborativeRecommendations($customer_id, $limit, $exclude_purchased);
                    break;
                    
                case 'content_based':
                    $recommendations = $this->getContentBasedRecommendations($customer_id, $limit, $exclude_purchased);
                    break;
                    
                case 'cognitive':
                    $recommendations = $this->getCognitiveRecommendations($customer_id, $limit, $exclude_purchased);
                    break;
                    
                case 'hybrid':
                default:
                    $recommendations = $this->getHybridRecommendations($customer_id, $limit, $exclude_purchased);
                    break;
            }
            
            // Store recommendations for future analysis
            $this->storeRecommendations($customer_id, $recommendations, $algorithm);
            
            $this->log->write("Generated {$algorithm} recommendations for customer {$customer_id}");
            
        } catch (Exception $e) {
            $this->log->write("Recommendation generation failed: " . $e->getMessage());
        }
        
        return $recommendations;
    }
    
    /**
     * Get trending product recommendations
     * 
     * @param array $options
     * @return array
     */
    public function getTrendingRecommendations($options = []) {
        $limit = $options['limit'] ?? 10;
        $time_period = $options['time_period'] ?? 7; // days
        
        $cache_key = "trending_products_{$time_period}_{$limit}";
        $trending = $this->cache->get($cache_key);
        
        if (!$trending) {
            $trending = $this->calculateTrendingProducts($time_period, $limit);
            $this->cache->set($cache_key, $trending, 3600); // Cache for 1 hour
        }
        
        return $trending;
    }
    
    /**
     * Get product recommendations based on current product
     * 
     * @param int $product_id
     * @param array $options
     * @return array
     */
    public function getRelatedProducts($product_id, $options = []) {
        $limit = $options['limit'] ?? 5;
        $algorithm = $options['algorithm'] ?? 'similarity';
        
        switch ($algorithm) {
            case 'similarity':
                return $this->getSimilarProducts($product_id, $limit);
                
            case 'frequently_bought':
                return $this->getFrequentlyBoughtTogether($product_id, $limit);
                
            case 'alternative':
                return $this->getAlternativeProducts($product_id, $limit);
                
            default:
                return $this->getSimilarProducts($product_id, $limit);
        }
    }
    
    /**
     * Train recommendation models with historical data
     * 
     * @param array $options
     * @return bool
     */
    public function trainModels($options = []) {
        $this->log->write("Starting recommendation model training...");
        
        try {
            // Train collaborative filtering model
            $this->trainCollaborativeFilteringModel();
            
            // Train content-based model
            $this->trainContentBasedModel();
            
            // Train OpenCog cognitive model
            $this->trainCognitiveModel();
            
            // Update model metadata
            $this->updateModelMetadata();
            
            $this->log->write("Recommendation model training completed successfully");
            return true;
            
        } catch (Exception $e) {
            $this->log->write("Model training failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get collaborative filtering recommendations
     * 
     * @param int $customer_id
     * @param int $limit
     * @param bool $exclude_purchased
     * @return array
     */
    private function getCollaborativeRecommendations($customer_id, $limit, $exclude_purchased) {
        // Find similar customers based on purchase history
        $similar_customers = $this->findSimilarCustomers($customer_id);
        
        if (empty($similar_customers)) {
            return $this->getFallbackRecommendations($limit);
        }
        
        // Get products purchased by similar customers
        $similar_customer_ids = array_column($similar_customers, 'customer_id');
        $placeholders = str_repeat('?,', count($similar_customer_ids) - 1) . '?';
        
        $sql = "SELECT p.product_id, p.name, p.price, p.image, 
                       COUNT(*) as purchase_count,
                       AVG(similarity_score) as avg_similarity
                FROM " . DB_PREFIX . "order_product op
                JOIN " . DB_PREFIX . "product p ON p.product_id = op.product_id
                JOIN (
                    SELECT customer_id, similarity_score 
                    FROM temp_similar_customers 
                    WHERE target_customer_id = ?
                ) sc ON sc.customer_id = op.customer_id
                WHERE op.customer_id IN ({$placeholders})
                AND p.status = 1";
        
        $params = array_merge([$customer_id], $similar_customer_ids);
        
        if ($exclude_purchased) {
            $sql .= " AND p.product_id NOT IN (
                        SELECT DISTINCT product_id 
                        FROM " . DB_PREFIX . "order_product op2
                        JOIN " . DB_PREFIX . "order o ON o.order_id = op2.order_id
                        WHERE o.customer_id = ?
                      )";
            $params[] = $customer_id;
        }
        
        $sql .= " GROUP BY p.product_id
                  ORDER BY (purchase_count * avg_similarity) DESC
                  LIMIT ?";
        $params[] = $limit;
        
        $query = $this->db->query($sql, $params);
        
        $recommendations = [];
        foreach ($query->rows as $row) {
            $recommendations[] = [
                'product_id' => $row['product_id'],
                'name' => $row['name'],
                'price' => $row['price'],
                'image' => $row['image'],
                'confidence_score' => min(0.9, $row['avg_similarity'] * ($row['purchase_count'] / 10)),
                'recommendation_type' => 'collaborative'
            ];
        }
        
        return $recommendations;
    }
    
    /**
     * Get content-based recommendations
     * 
     * @param int $customer_id
     * @param int $limit
     * @param bool $exclude_purchased
     * @return array
     */
    private function getContentBasedRecommendations($customer_id, $limit, $exclude_purchased) {
        // Get customer's purchase history to understand preferences
        $customer_preferences = $this->analyzeCustomerPreferences($customer_id);
        
        if (empty($customer_preferences)) {
            return $this->getFallbackRecommendations($limit);
        }
        
        // Find products matching customer preferences
        $sql = "SELECT p.product_id, p.name, p.price, p.image, p.model,
                       pc.name as category_name,
                       SUM(CASE 
                           WHEN pc.category_id IN (" . implode(',', array_keys($customer_preferences['categories'])) . ") 
                           THEN 1 ELSE 0 
                       END) as category_match_score
                FROM " . DB_PREFIX . "product p
                LEFT JOIN " . DB_PREFIX . "product_to_category ptc ON ptc.product_id = p.product_id
                LEFT JOIN " . DB_PREFIX . "category pc ON pc.category_id = ptc.category_id
                WHERE p.status = 1";
        
        if ($exclude_purchased) {
            $sql .= " AND p.product_id NOT IN (
                        SELECT DISTINCT product_id 
                        FROM " . DB_PREFIX . "order_product op
                        JOIN " . DB_PREFIX . "order o ON o.order_id = op.order_id
                        WHERE o.customer_id = ?
                      )";
        }
        
        $sql .= " GROUP BY p.product_id
                  HAVING category_match_score > 0
                  ORDER BY category_match_score DESC
                  LIMIT ?";
        
        $params = $exclude_purchased ? [$customer_id, $limit] : [$limit];
        $query = $this->db->query($sql, $params);
        
        $recommendations = [];
        foreach ($query->rows as $row) {
            $recommendations[] = [
                'product_id' => $row['product_id'],
                'name' => $row['name'],
                'price' => $row['price'],
                'image' => $row['image'],
                'confidence_score' => min(0.85, $row['category_match_score'] / count($customer_preferences['categories'])),
                'recommendation_type' => 'content_based'
            ];
        }
        
        return $recommendations;
    }
    
    /**
     * Get cognitive recommendations using OpenCog
     * 
     * @param int $customer_id
     * @param int $limit
     * @param bool $exclude_purchased
     * @return array
     */
    private function getCognitiveRecommendations($customer_id, $limit, $exclude_purchased) {
        if (!$this->atomspace->connect()) {
            // Fallback to collaborative filtering if OpenCog is not available
            return $this->getCollaborativeRecommendations($customer_id, $limit, $exclude_purchased);
        }
        
        try {
            // Use OpenCog for advanced inference
            $result = $this->atomspace->executeCognitiveProcedure('recommendation_inference', [
                'customer_id' => $customer_id,
                'limit' => $limit,
                'exclude_purchased' => $exclude_purchased
            ]);
            
            if ($result['success'] && !empty($result['recommendations'])) {
                return array_map(function($rec) {
                    $rec['recommendation_type'] = 'cognitive';
                    return $rec;
                }, $result['recommendations']);
            }
            
        } catch (Exception $e) {
            $this->log->write("Cognitive recommendation failed: " . $e->getMessage());
        }
        
        // Fallback to hybrid approach
        return $this->getHybridRecommendations($customer_id, $limit, $exclude_purchased);
    }
    
    /**
     * Get hybrid recommendations combining multiple algorithms
     * 
     * @param int $customer_id
     * @param int $limit
     * @param bool $exclude_purchased
     * @return array
     */
    private function getHybridRecommendations($customer_id, $limit, $exclude_purchased) {
        $recommendations = [];
        
        // Get recommendations from different algorithms
        $collaborative = $this->getCollaborativeRecommendations($customer_id, ceil($limit * 0.6), $exclude_purchased);
        $content_based = $this->getContentBasedRecommendations($customer_id, ceil($limit * 0.4), $exclude_purchased);
        
        // Merge and deduplicate
        $all_recommendations = array_merge($collaborative, $content_based);
        $unique_recommendations = [];
        $seen_products = [];
        
        foreach ($all_recommendations as $rec) {
            if (!in_array($rec['product_id'], $seen_products)) {
                $unique_recommendations[] = $rec;
                $seen_products[] = $rec['product_id'];
            }
        }
        
        // Sort by confidence score and limit results
        usort($unique_recommendations, function($a, $b) {
            return $b['confidence_score'] <=> $a['confidence_score'];
        });
        
        return array_slice($unique_recommendations, 0, $limit);
    }
    
    /**
     * Find similar customers based on purchase behavior
     * 
     * @param int $customer_id
     * @return array
     */
    private function findSimilarCustomers($customer_id) {
        // Simple similarity based on common products purchased
        $sql = "SELECT c2.customer_id, 
                       COUNT(DISTINCT op1.product_id) as common_products,
                       COUNT(DISTINCT op2.product_id) as total_products_c2,
                       (COUNT(DISTINCT op1.product_id) / COUNT(DISTINCT op2.product_id)) as similarity_score
                FROM " . DB_PREFIX . "order_product op1
                JOIN " . DB_PREFIX . "order o1 ON o1.order_id = op1.order_id
                JOIN " . DB_PREFIX . "order_product op2 ON op2.product_id = op1.product_id
                JOIN " . DB_PREFIX . "order o2 ON o2.order_id = op2.order_id
                JOIN " . DB_PREFIX . "customer c2 ON c2.customer_id = o2.customer_id
                WHERE o1.customer_id = ? AND o2.customer_id != ?
                GROUP BY c2.customer_id
                HAVING common_products >= 2 AND similarity_score >= 0.1
                ORDER BY similarity_score DESC
                LIMIT 20";
        
        $query = $this->db->query($sql, [$customer_id, $customer_id]);
        
        return $query->rows;
    }
    
    /**
     * Analyze customer preferences from purchase history
     * 
     * @param int $customer_id
     * @return array
     */
    private function analyzeCustomerPreferences($customer_id) {
        $preferences = [
            'categories' => [],
            'price_range' => [],
            'brands' => []
        ];
        
        // Analyze category preferences
        $category_query = $this->db->query(
            "SELECT pc.category_id, pc.name, COUNT(*) as purchase_count
             FROM " . DB_PREFIX . "order_product op
             JOIN " . DB_PREFIX . "order o ON o.order_id = op.order_id
             JOIN " . DB_PREFIX . "product_to_category ptc ON ptc.product_id = op.product_id
             JOIN " . DB_PREFIX . "category pc ON pc.category_id = ptc.category_id
             WHERE o.customer_id = ?
             GROUP BY pc.category_id
             ORDER BY purchase_count DESC",
            [$customer_id]
        );
        
        foreach ($category_query->rows as $category) {
            $preferences['categories'][$category['category_id']] = [
                'name' => $category['name'],
                'weight' => $category['purchase_count']
            ];
        }
        
        return $preferences;
    }
    
    /**
     * Calculate trending products based on recent activity
     * 
     * @param int $time_period Days to look back
     * @param int $limit Number of products to return
     * @return array
     */
    private function calculateTrendingProducts($time_period, $limit) {
        $sql = "SELECT p.product_id, p.name, p.price, p.image,
                       COUNT(op.product_id) as recent_sales,
                       AVG(op.price) as avg_sale_price,
                       (COUNT(op.product_id) * 0.7 + COUNT(DISTINCT o.customer_id) * 0.3) as trend_score
                FROM " . DB_PREFIX . "product p
                JOIN " . DB_PREFIX . "order_product op ON op.product_id = p.product_id
                JOIN " . DB_PREFIX . "order o ON o.order_id = op.order_id
                WHERE o.date_added >= DATE_SUB(NOW(), INTERVAL ? DAY)
                AND p.status = 1
                GROUP BY p.product_id
                HAVING recent_sales >= 2
                ORDER BY trend_score DESC
                LIMIT ?";
        
        $query = $this->db->query($sql, [$time_period, $limit]);
        
        $trending = [];
        foreach ($query->rows as $row) {
            $trending[] = [
                'product_id' => $row['product_id'],
                'name' => $row['name'],
                'price' => $row['price'],
                'image' => $row['image'],
                'trend_score' => round($row['trend_score'], 2),
                'recent_sales' => $row['recent_sales'],
                'recommendation_type' => 'trending'
            ];
        }
        
        return $trending;
    }
    
    /**
     * Get similar products based on attributes
     * 
     * @param int $product_id
     * @param int $limit
     * @return array
     */
    private function getSimilarProducts($product_id, $limit) {
        // Get product categories
        $categories = $this->db->query(
            "SELECT category_id FROM " . DB_PREFIX . "product_to_category WHERE product_id = ?",
            [$product_id]
        );
        
        if (!$categories->num_rows) {
            return [];
        }
        
        $category_ids = array_column($categories->rows, 'category_id');
        $placeholders = str_repeat('?,', count($category_ids) - 1) . '?';
        
        $sql = "SELECT p.product_id, p.name, p.price, p.image,
                       COUNT(ptc.category_id) as common_categories
                FROM " . DB_PREFIX . "product p
                JOIN " . DB_PREFIX . "product_to_category ptc ON ptc.product_id = p.product_id
                WHERE ptc.category_id IN ({$placeholders})
                AND p.product_id != ?
                AND p.status = 1
                GROUP BY p.product_id
                ORDER BY common_categories DESC
                LIMIT ?";
        
        $params = array_merge($category_ids, [$product_id, $limit]);
        $query = $this->db->query($sql, $params);
        
        $similar = [];
        foreach ($query->rows as $row) {
            $similar[] = [
                'product_id' => $row['product_id'],
                'name' => $row['name'],
                'price' => $row['price'],
                'image' => $row['image'],
                'similarity_score' => $row['common_categories'] / count($category_ids),
                'recommendation_type' => 'similar'
            ];
        }
        
        return $similar;
    }
    
    /**
     * Get products frequently bought together
     * 
     * @param int $product_id
     * @param int $limit
     * @return array
     */
    private function getFrequentlyBoughtTogether($product_id, $limit) {
        $sql = "SELECT p.product_id, p.name, p.price, p.image,
                       COUNT(*) as cooccurrence_count
                FROM " . DB_PREFIX . "order_product op1
                JOIN " . DB_PREFIX . "order_product op2 ON op1.order_id = op2.order_id
                JOIN " . DB_PREFIX . "product p ON p.product_id = op2.product_id
                WHERE op1.product_id = ? AND op2.product_id != ?
                AND p.status = 1
                GROUP BY p.product_id
                ORDER BY cooccurrence_count DESC
                LIMIT ?";
        
        $query = $this->db->query($sql, [$product_id, $product_id, $limit]);
        
        $frequently_bought = [];
        foreach ($query->rows as $row) {
            $frequently_bought[] = [
                'product_id' => $row['product_id'],
                'name' => $row['name'],
                'price' => $row['price'],
                'image' => $row['image'],
                'cooccurrence_count' => $row['cooccurrence_count'],
                'recommendation_type' => 'frequently_bought'
            ];
        }
        
        return $frequently_bought;
    }
    
    /**
     * Store recommendations for analysis
     * 
     * @param int $customer_id
     * @param array $recommendations
     * @param string $algorithm
     */
    private function storeRecommendations($customer_id, $recommendations, $algorithm) {
        foreach ($recommendations as $rec) {
            $this->db->query(
                "INSERT INTO " . DB_PREFIX . "product_recommendations 
                 (customer_id, product_id, recommended_product_id, recommendation_type, confidence_score, expires_at) 
                 VALUES (?, NULL, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR))
                 ON DUPLICATE KEY UPDATE 
                 confidence_score = VALUES(confidence_score), 
                 expires_at = VALUES(expires_at)",
                [
                    $customer_id,
                    $rec['product_id'],
                    $algorithm,
                    $rec['confidence_score'] ?? 0.5
                ]
            );
        }
    }
    
    /**
     * Get fallback recommendations when personalized ones aren't available
     * 
     * @param int $limit
     * @return array
     */
    private function getFallbackRecommendations($limit) {
        return $this->getTrendingRecommendations(['limit' => $limit]);
    }
    
    /**
     * Training methods (placeholder implementations)
     */
    private function trainCollaborativeFilteringModel() {
        $this->log->write("Training collaborative filtering model...");
        // Implementation would involve matrix factorization or similar techniques
    }
    
    private function trainContentBasedModel() {
        $this->log->write("Training content-based model...");
        // Implementation would involve feature extraction and similarity calculations
    }
    
    private function trainCognitiveModel() {
        $this->log->write("Training cognitive model with OpenCog...");
        
        if ($this->atomspace->connect()) {
            // Learn from customer behavior data
            $behavior_data = $this->getCustomerBehaviorData();
            $this->atomspace->learn('customer_behavior', $behavior_data);
            
            // Learn product similarities
            $similarity_data = $this->getProductSimilarityData();
            $this->atomspace->learn('product_similarity', $similarity_data);
        }
    }
    
    private function updateModelMetadata() {
        $this->db->query(
            "INSERT INTO " . DB_PREFIX . "ai_models 
             (name, type, algorithm, status, created_at) 
             VALUES ('recommendation_engine', 'recommendation', 'hybrid', 'trained', NOW())
             ON DUPLICATE KEY UPDATE 
             status = 'trained', updated_at = NOW()"
        );
    }
    
    private function getCustomerBehaviorData() {
        // Get recent customer behavior for training
        return [];
    }
    
    private function getProductSimilarityData() {
        // Calculate product similarities for training
        return [];
    }
    
    private function getAlternativeProducts($product_id, $limit) {
        // Find alternative products (different brands, similar function)
        return $this->getSimilarProducts($product_id, $limit);
    }
}