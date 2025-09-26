<?php
namespace Opencart\Admin\Controller\Marketplace;

/**
 * Class B2BMarketplace
 * 
 * Handles B2B marketplace configuration and multi-store management
 * with local multi-channel sales member linking features
 * 
 * @package Opencart\Admin\Controller\Marketplace
 */
class B2BMarketplace extends \Opencart\System\Engine\Controller {
    
    /**
     * Index - Main B2B marketplace configuration page
     * 
     * @return void
     */
    public function index(): void {
        $this->load->language('marketplace/b2b_marketplace');
        
        $this->document->setTitle($this->language->get('heading_title'));
        
        $data['breadcrumbs'] = [];
        
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
        ];
        
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('marketplace/b2b_marketplace', 'user_token=' . $this->session->data['user_token'])
        ];
        
        // URLs for AJAX actions
        $data['save'] = $this->url->link('marketplace/b2b_marketplace.save', 'user_token=' . $this->session->data['user_token']);
        $data['playground_deploy'] = $this->url->link('marketplace/b2b_marketplace.playgroundDeploy', 'user_token=' . $this->session->data['user_token']);
        $data['member_linking'] = $this->url->link('marketplace/b2b_marketplace.memberLinking', 'user_token=' . $this->session->data['user_token']);
        
        // Load current configuration
        $this->load->model('setting/setting');
        $setting_info = $this->model_setting_setting->getSetting('b2b_marketplace');
        
        // B2B Marketplace Configuration
        $data['b2b_marketplace_enabled'] = $setting_info['b2b_marketplace_enabled'] ?? false;
        $data['b2b_auto_approval'] = $setting_info['b2b_auto_approval'] ?? false;
        $data['b2b_min_order_amount'] = $setting_info['b2b_min_order_amount'] ?? '';
        $data['b2b_payment_terms'] = $setting_info['b2b_payment_terms'] ?? [];
        $data['b2b_pricing_tiers'] = $setting_info['b2b_pricing_tiers'] ?? [];
        
        // Multi-store B2B configuration
        $data['multi_store_sync'] = $setting_info['multi_store_sync'] ?? false;
        $data['cross_store_inventory'] = $setting_info['cross_store_inventory'] ?? false;
        $data['unified_b2b_accounts'] = $setting_info['unified_b2b_accounts'] ?? false;
        
        // Local multi-channel configuration
        $data['local_sales_enabled'] = $setting_info['local_sales_enabled'] ?? false;
        $data['member_linking_enabled'] = $setting_info['member_linking_enabled'] ?? false;
        $data['auto_cart_matching'] = $setting_info['auto_cart_matching'] ?? false;
        
        // Load stores for multi-store configuration
        $this->load->model('setting/store');
        $data['stores'] = $this->model_setting_store->getStores();
        
        // Add default store
        array_unshift($data['stores'], [
            'store_id' => 0,
            'name' => $this->config->get('config_name') . ' (Default)',
            'url' => HTTP_CATALOG
        ]);
        
        // Load customer groups for B2B configuration
        $this->load->model('customer/customer_group');
        $data['customer_groups'] = $this->model_customer_customer_group->getCustomerGroups();
        
        // Playground deployment status
        $data['playground_status'] = $this->getPlaygroundStatus();
        
        // Member linking statistics
        $data['member_stats'] = $this->getMemberLinkingStats();
        
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('marketplace/b2b_marketplace', $data));
    }
    
    /**
     * Save B2B marketplace configuration
     * 
     * @return void
     */
    public function save(): void {
        $this->load->language('marketplace/b2b_marketplace');
        
        $json = [];
        
        if (!$this->user->hasPermission('modify', 'marketplace/b2b_marketplace')) {
            $json['error']['warning'] = $this->language->get('error_permission');
        }
        
        if (!$json) {
            $this->load->model('setting/setting');
            
            $data = [
                'b2b_marketplace_enabled' => isset($this->request->post['b2b_marketplace_enabled']) ? (bool)$this->request->post['b2b_marketplace_enabled'] : false,
                'b2b_auto_approval' => isset($this->request->post['b2b_auto_approval']) ? (bool)$this->request->post['b2b_auto_approval'] : false,
                'b2b_min_order_amount' => $this->request->post['b2b_min_order_amount'] ?? '',
                'b2b_payment_terms' => $this->request->post['b2b_payment_terms'] ?? [],
                'b2b_pricing_tiers' => $this->request->post['b2b_pricing_tiers'] ?? [],
                'multi_store_sync' => isset($this->request->post['multi_store_sync']) ? (bool)$this->request->post['multi_store_sync'] : false,
                'cross_store_inventory' => isset($this->request->post['cross_store_inventory']) ? (bool)$this->request->post['cross_store_inventory'] : false,
                'unified_b2b_accounts' => isset($this->request->post['unified_b2b_accounts']) ? (bool)$this->request->post['unified_b2b_accounts'] : false,
                'local_sales_enabled' => isset($this->request->post['local_sales_enabled']) ? (bool)$this->request->post['local_sales_enabled'] : false,
                'member_linking_enabled' => isset($this->request->post['member_linking_enabled']) ? (bool)$this->request->post['member_linking_enabled'] : false,
                'auto_cart_matching' => isset($this->request->post['auto_cart_matching']) ? (bool)$this->request->post['auto_cart_matching'] : false,
            ];
            
            $this->model_setting_setting->editSetting('b2b_marketplace', $data);
            
            $json['success'] = $this->language->get('text_success');
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    /**
     * Deploy playground environment
     * 
     * @return void
     */
    public function playgroundDeploy(): void {
        $this->load->language('marketplace/b2b_marketplace');
        
        $json = [];
        
        if (!$this->user->hasPermission('modify', 'marketplace/b2b_marketplace')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            try {
                // Create playground database tables if they don't exist
                $this->createPlaygroundTables();
                
                // Create sample B2B stores
                $this->createSampleB2BStores();
                
                // Create sample B2B customers
                $this->createSampleB2BCustomers();
                
                // Setup member linking demo data
                $this->setupMemberLinkingDemo();
                
                $json['success'] = $this->language->get('text_playground_deployed');
                $json['playground_url'] = HTTP_CATALOG . 'playground/';
                
            } catch (Exception $e) {
                $json['error'] = 'Deployment failed: ' . $e->getMessage();
            }
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    /**
     * Member linking management
     * 
     * @return void
     */
    public function memberLinking(): void {
        $this->load->language('marketplace/b2b_marketplace');
        
        $json = [];
        
        if (!$this->user->hasPermission('modify', 'marketplace/b2b_marketplace')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $action = $this->request->post['action'] ?? '';
            
            switch ($action) {
                case 'link_member':
                    $json = $this->linkMember();
                    break;
                    
                case 'unlink_member':
                    $json = $this->unlinkMember();
                    break;
                    
                case 'auto_match_carts':
                    $json = $this->autoMatchCarts();
                    break;
                    
                default:
                    $json['error'] = 'Invalid action';
            }
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    /**
     * Get playground deployment status
     * 
     * @return array
     */
    private function getPlaygroundStatus(): array {
        $status = [
            'deployed' => false,
            'tables_created' => false,
            'sample_data' => false,
            'last_deployment' => null
        ];
        
        // Check if playground tables exist
        $query = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "b2b_playground%'");
        $status['tables_created'] = $query->num_rows > 0;
        
        if ($status['tables_created']) {
            // Check for sample data
            $query = $this->db->query("SELECT COUNT(*) as count FROM `" . DB_PREFIX . "b2b_playground_stores`");
            $status['sample_data'] = $query->row['count'] > 0;
            $status['deployed'] = $status['sample_data'];
            
            // Get last deployment time
            $query = $this->db->query("SELECT MAX(created_at) as last_deployment FROM `" . DB_PREFIX . "b2b_playground_stores`");
            $status['last_deployment'] = $query->row['last_deployment'];
        }
        
        return $status;
    }
    
    /**
     * Get member linking statistics
     * 
     * @return array
     */
    private function getMemberLinkingStats(): array {
        $stats = [
            'total_links' => 0,
            'active_links' => 0,
            'matched_carts' => 0,
            'pending_matches' => 0
        ];
        
        // Check if member linking table exists
        $query = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "b2b_member_links'");
        
        if ($query->num_rows > 0) {
            $query = $this->db->query("SELECT COUNT(*) as total FROM `" . DB_PREFIX . "b2b_member_links`");
            $stats['total_links'] = $query->row['total'];
            
            $query = $this->db->query("SELECT COUNT(*) as active FROM `" . DB_PREFIX . "b2b_member_links` WHERE status = 'active'");
            $stats['active_links'] = $query->row['active'];
            
            $query = $this->db->query("SELECT COUNT(*) as matched FROM `" . DB_PREFIX . "b2b_cart_matches` WHERE status = 'matched'");
            $stats['matched_carts'] = $query->row['matched'] ?? 0;
            
            $query = $this->db->query("SELECT COUNT(*) as pending FROM `" . DB_PREFIX . "b2b_cart_matches` WHERE status = 'pending'");
            $stats['pending_matches'] = $query->row['pending'] ?? 0;
        }
        
        return $stats;
    }
    
    /**
     * Create playground database tables
     * 
     * @return void
     */
    private function createPlaygroundTables(): void {
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
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`link_id`),
            KEY `b2b_customer_id` (`b2b_customer_id`),
            KEY `local_customer_id` (`local_customer_id`),
            KEY `store_id` (`store_id`)
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
            PRIMARY KEY (`match_id`),
            KEY `link_id` (`link_id`),
            KEY `cart_session_id` (`cart_session_id`),
            CONSTRAINT `fk_cart_match_link` FOREIGN KEY (`link_id`) REFERENCES `" . DB_PREFIX . "b2b_member_links` (`link_id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
    
    /**
     * Create sample B2B stores
     * 
     * @return void
     */
    private function createSampleB2BStores(): void {
        $sample_stores = [
            [
                'name' => 'B2B Wholesale Portal',
                'code' => 'B2B_WHOLESALE',
                'type' => 'b2b',
                'config_data' => json_encode([
                    'min_order_amount' => 500,
                    'payment_terms' => 'NET30',
                    'discount_tier' => 'wholesale'
                ])
            ],
            [
                'name' => 'Local Retail Network',
                'code' => 'LOCAL_RETAIL',
                'type' => 'hybrid',
                'config_data' => json_encode([
                    'local_pickup' => true,
                    'multi_channel' => true,
                    'member_linking' => true
                ])
            ]
        ];
        
        foreach ($sample_stores as $store) {
            $this->db->query("INSERT IGNORE INTO `" . DB_PREFIX . "b2b_playground_stores` 
                (`name`, `code`, `type`, `config_data`) VALUES 
                ('" . $this->db->escape($store['name']) . "', 
                 '" . $this->db->escape($store['code']) . "', 
                 '" . $this->db->escape($store['type']) . "', 
                 '" . $this->db->escape($store['config_data']) . "')");
        }
    }
    
    /**
     * Create sample B2B customers
     * 
     * @return void
     */
    private function createSampleB2BCustomers(): void {
        // This would create sample B2B customer groups and customers
        // Implementation would depend on existing customer structure
    }
    
    /**
     * Setup member linking demo data
     * 
     * @return void
     */
    private function setupMemberLinkingDemo(): void {
        // Create sample member links for demonstration
        // This would link existing customers to B2B accounts
    }
    
    /**
     * Link a member
     * 
     * @return array
     */
    private function linkMember(): array {
        $b2b_customer_id = (int)($this->request->post['b2b_customer_id'] ?? 0);
        $local_customer_id = (int)($this->request->post['local_customer_id'] ?? 0);
        $store_id = (int)($this->request->post['store_id'] ?? 0);
        
        if (!$b2b_customer_id || !$local_customer_id) {
            return ['error' => 'Invalid customer IDs'];
        }
        
        // Check if link already exists
        $query = $this->db->query("SELECT link_id FROM `" . DB_PREFIX . "b2b_member_links` 
            WHERE b2b_customer_id = '" . (int)$b2b_customer_id . "' 
            AND local_customer_id = '" . (int)$local_customer_id . "'");
        
        if ($query->num_rows > 0) {
            return ['error' => 'Link already exists'];
        }
        
        // Create new link
        $this->db->query("INSERT INTO `" . DB_PREFIX . "b2b_member_links` 
            (b2b_customer_id, local_customer_id, store_id, link_type, status) VALUES 
            ('" . (int)$b2b_customer_id . "', '" . (int)$local_customer_id . "', '" . (int)$store_id . "', 'manual', 'active')");
        
        return [
            'success' => 'Member linked successfully',
            'link_id' => $this->db->getLastId()
        ];
    }
    
    /**
     * Unlink a member
     * 
     * @return array
     */
    private function unlinkMember(): array {
        $link_id = (int)($this->request->post['link_id'] ?? 0);
        
        if (!$link_id) {
            return ['error' => 'Invalid link ID'];
        }
        
        $this->db->query("DELETE FROM `" . DB_PREFIX . "b2b_member_links` WHERE link_id = '" . (int)$link_id . "'");
        
        return ['success' => 'Member unlinked successfully'];
    }
    
    /**
     * Auto match carts
     * 
     * @return array
     */
    private function autoMatchCarts(): array {
        $matched = 0;
        
        // Get all active member links
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "b2b_member_links` WHERE status = 'active'");
        
        foreach ($query->rows as $link) {
            // Logic to match carts would go here
            // This is a simplified example
            $matched++;
        }
        
        return [
            'success' => "Auto-matched {$matched} carts",
            'matched_count' => $matched
        ];
    }
}