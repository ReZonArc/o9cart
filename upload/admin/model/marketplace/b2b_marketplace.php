<?php
namespace Opencart\Admin\Model\Marketplace;

/**
 * Class B2BMarketplace
 * 
 * Model for handling B2B marketplace data operations
 * 
 * @package Opencart\Admin\Model\Marketplace
 */
class B2BMarketplace extends \Opencart\System\Engine\Model {
    
    /**
     * Install B2B marketplace tables
     * 
     * @return void
     */
    public function install(): void {
        // B2B marketplace configuration table
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "b2b_marketplace_config` (
            `config_id` int(11) NOT NULL AUTO_INCREMENT,
            `store_id` int(11) NOT NULL DEFAULT '0',
            `key` varchar(64) NOT NULL,
            `value` text NOT NULL,
            `serialized` tinyint(1) NOT NULL DEFAULT '0',
            PRIMARY KEY (`config_id`),
            KEY `store_id` (`store_id`),
            KEY `key` (`key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        // B2B customer groups extension
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "b2b_customer_group_extended` (
            `customer_group_id` int(11) NOT NULL,
            `b2b_enabled` tinyint(1) NOT NULL DEFAULT '0',
            `min_order_amount` decimal(15,4) NOT NULL DEFAULT '0.0000',
            `payment_terms` varchar(50) DEFAULT NULL,
            `pricing_tier` varchar(50) DEFAULT NULL,
            `auto_approval` tinyint(1) NOT NULL DEFAULT '0',
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`customer_group_id`),
            CONSTRAINT `fk_b2b_customer_group` FOREIGN KEY (`customer_group_id`) REFERENCES `" . DB_PREFIX . "customer_group` (`customer_group_id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        // B2B store configuration
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "b2b_store_config` (
            `store_id` int(11) NOT NULL,
            `b2b_enabled` tinyint(1) NOT NULL DEFAULT '0',
            `b2b_auto_approval` tinyint(1) NOT NULL DEFAULT '0',
            `multi_store_sync` tinyint(1) NOT NULL DEFAULT '0',
            `cross_store_inventory` tinyint(1) NOT NULL DEFAULT '0',
            `unified_accounts` tinyint(1) NOT NULL DEFAULT '0',
            `local_sales_enabled` tinyint(1) NOT NULL DEFAULT '0',
            `member_linking_enabled` tinyint(1) NOT NULL DEFAULT '0',
            `auto_cart_matching` tinyint(1) NOT NULL DEFAULT '0',
            `config_data` json,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`store_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        // B2B playground stores table
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "b2b_playground_stores` (
            `playground_store_id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(100) NOT NULL,
            `code` varchar(20) NOT NULL,
            `type` enum('b2b','b2c','hybrid') DEFAULT 'b2b',
            `status` tinyint(1) DEFAULT '1',
            `config_data` json,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`playground_store_id`),
            UNIQUE KEY `code` (`code`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        // B2B member links table
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "b2b_member_links` (
            `link_id` int(11) NOT NULL AUTO_INCREMENT,
            `b2b_customer_id` int(11) NOT NULL,
            `local_customer_id` int(11) NOT NULL,
            `store_id` int(11) DEFAULT '0',
            `link_type` enum('manual','auto','api') DEFAULT 'manual',
            `status` enum('active','inactive','pending') DEFAULT 'pending',
            `metadata` json,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`link_id`),
            KEY `b2b_customer_id` (`b2b_customer_id`),
            KEY `local_customer_id` (`local_customer_id`),
            KEY `store_id` (`store_id`),
            UNIQUE KEY `unique_link` (`b2b_customer_id`, `local_customer_id`, `store_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        // B2B cart matches table
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "b2b_cart_matches` (
            `match_id` int(11) NOT NULL AUTO_INCREMENT,
            `link_id` int(11) NOT NULL,
            `cart_session_id` varchar(32) NOT NULL,
            `matched_cart_id` varchar(32),
            `match_score` decimal(3,2) DEFAULT '0.00',
            `status` enum('pending','matched','failed') DEFAULT 'pending',
            `match_data` json,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`match_id`),
            KEY `link_id` (`link_id`),
            KEY `cart_session_id` (`cart_session_id`),
            CONSTRAINT `fk_cart_match_link` FOREIGN KEY (`link_id`) REFERENCES `" . DB_PREFIX . "b2b_member_links` (`link_id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        // B2B sales channels table
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "b2b_sales_channels` (
            `channel_id` int(11) NOT NULL AUTO_INCREMENT,
            `store_id` int(11) NOT NULL DEFAULT '0',
            `name` varchar(100) NOT NULL,
            `type` enum('online','local','hybrid','api') DEFAULT 'online',
            `status` tinyint(1) NOT NULL DEFAULT '1',
            `config_data` json,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`channel_id`),
            KEY `store_id` (`store_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
    
    /**
     * Get member linking statistics
     * 
     * @return array
     */
    public function getMemberLinkingStats(): array {
        $stats = [
            'total_links' => 0,
            'active_links' => 0,
            'pending_links' => 0,
            'matched_carts' => 0,
            'pending_matches' => 0
        ];
        
        // Check if tables exist first
        $tables_exist = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "b2b_member_links'")->num_rows > 0;
        
        if ($tables_exist) {
            $query = $this->db->query("SELECT COUNT(*) as total FROM `" . DB_PREFIX . "b2b_member_links`");
            $stats['total_links'] = $query->row['total'];
            
            $query = $this->db->query("SELECT COUNT(*) as active FROM `" . DB_PREFIX . "b2b_member_links` WHERE status = 'active'");
            $stats['active_links'] = $query->row['active'];
            
            $query = $this->db->query("SELECT COUNT(*) as pending FROM `" . DB_PREFIX . "b2b_member_links` WHERE status = 'pending'");
            $stats['pending_links'] = $query->row['pending'];
            
            // Cart matches statistics
            $cart_table_exists = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "b2b_cart_matches'")->num_rows > 0;
            
            if ($cart_table_exists) {
                $query = $this->db->query("SELECT COUNT(*) as matched FROM `" . DB_PREFIX . "b2b_cart_matches` WHERE status = 'matched'");
                $stats['matched_carts'] = $query->row['matched'];
                
                $query = $this->db->query("SELECT COUNT(*) as pending FROM `" . DB_PREFIX . "b2b_cart_matches` WHERE status = 'pending'");
                $stats['pending_matches'] = $query->row['pending'];
            }
        }
        
        return $stats;
    }
    
    /**
     * Get playground deployment status
     * 
     * @return array
     */
    public function getPlaygroundStatus(): array {
        $status = [
            'deployed' => false,
            'tables_created' => false,
            'sample_data' => false,
            'last_deployment' => null
        ];
        
        // Check if playground tables exist
        $query = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "b2b_playground_stores'");
        $status['tables_created'] = $query->num_rows > 0;
        
        if ($status['tables_created']) {
            // Check for sample data
            $query = $this->db->query("SELECT COUNT(*) as count FROM `" . DB_PREFIX . "b2b_playground_stores`");
            $status['sample_data'] = $query->row['count'] > 0;
            $status['deployed'] = $status['sample_data'];
            
            if ($status['sample_data']) {
                // Get last deployment time
                $query = $this->db->query("SELECT MAX(created_at) as last_deployment FROM `" . DB_PREFIX . "b2b_playground_stores`");
                $status['last_deployment'] = $query->row['last_deployment'];
            }
        }
        
        return $status;
    }
    
    /**
     * Auto match carts based on member links
     * 
     * @return int Number of matches created
     */
    public function autoMatchCarts(): int {
        $matched = 0;
        
        // Get all active member links
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "b2b_member_links` WHERE status = 'active'");
        
        foreach ($query->rows as $link) {
            // This is a simplified example - in a real implementation,
            // you would match carts based on products, timing, location, etc.
            
            // For demo purposes, we'll create a mock match
            $match_data = [
                'link_id' => $link['link_id'],
                'cart_session_id' => md5('demo_' . $link['link_id'] . '_' . time()),
                'matched_cart_id' => md5('matched_' . $link['link_id'] . '_' . time()),
                'match_score' => rand(80, 99) / 100,
                'status' => 'matched',
                'match_data' => [
                    'match_method' => 'auto',
                    'confidence' => 'high',
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            ];
            
            $this->addCartMatch($match_data);
            $matched++;
        }
        
        return $matched;
    }
    
    /**
     * Add cart match
     * 
     * @param array $data
     * @return int
     */
    public function addCartMatch(array $data): int {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "b2b_cart_matches` SET 
            `link_id` = '" . (int)$data['link_id'] . "',
            `cart_session_id` = '" . $this->db->escape($data['cart_session_id']) . "',
            `matched_cart_id` = '" . $this->db->escape($data['matched_cart_id'] ?? '') . "',
            `match_score` = '" . (float)($data['match_score'] ?? 0) . "',
            `status` = '" . $this->db->escape($data['status'] ?? 'pending') . "',
            `match_data` = '" . $this->db->escape(json_encode($data['match_data'] ?? [])) . "'");
        
        return $this->db->getLastId();
    }
}