<?php
namespace Opencart\Admin\Controller\Marketplace;

/**
 * Class B2BMarketplaceInstall
 * 
 * Installation and setup for B2B marketplace features
 * 
 * @package Opencart\Admin\Controller\Marketplace
 */
class B2BMarketplaceInstall extends \Opencart\System\Engine\Controller {
    
    /**
     * Install B2B marketplace
     * 
     * @return void
     */
    public function install(): void {
        $this->load->language('marketplace/b2b_marketplace');
        
        $json = [];
        
        if (!$this->user->hasPermission('modify', 'marketplace/b2b_marketplace_install')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            try {
                // Create database tables
                $this->load->model('marketplace/b2b_marketplace');
                $this->model_marketplace_b2b_marketplace->install();
                
                // Add menu entries
                $this->addMenuEntries();
                
                // Create default settings
                $this->createDefaultSettings();
                
                $json['success'] = 'B2B Marketplace installed successfully!';
                
            } catch (Exception $e) {
                $json['error'] = 'Installation failed: ' . $e->getMessage();
            }
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    /**
     * Uninstall B2B marketplace
     * 
     * @return void
     */
    public function uninstall(): void {
        $this->load->language('marketplace/b2b_marketplace');
        
        $json = [];
        
        if (!$this->user->hasPermission('modify', 'marketplace/b2b_marketplace_install')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            try {
                // Remove database tables
                $this->load->model('marketplace/b2b_marketplace');
                $this->model_marketplace_b2b_marketplace->uninstall();
                
                // Remove menu entries
                $this->removeMenuEntries();
                
                // Remove settings
                $this->removeSettings();
                
                $json['success'] = 'B2B Marketplace uninstalled successfully!';
                
            } catch (Exception $e) {
                $json['error'] = 'Uninstallation failed: ' . $e->getMessage();
            }
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    /**
     * Add menu entries
     * 
     * @return void
     */
    private function addMenuEntries(): void {
        $this->load->model('tool/menu');
        
        // Check if menu exists
        $query = $this->db->query("SELECT menu_id FROM `" . DB_PREFIX . "menu` WHERE code = 'b2b_marketplace'");
        
        if (!$query->num_rows) {
            // Add main B2B marketplace menu entry
            $menu_data = [
                'code' => 'b2b_marketplace',
                'type' => 'link',
                'route' => 'marketplace/b2b_marketplace',
                'parent' => 'marketplace',
                'sort_order' => 1,
                'menu_description' => [
                    1 => ['name' => 'B2B Marketplace'] // Language ID 1 for English
                ]
            ];
            
            $this->model_tool_menu->addMenu($menu_data);
        }
    }
    
    /**
     * Remove menu entries
     * 
     * @return void
     */
    private function removeMenuEntries(): void {
        $this->db->query("DELETE FROM `" . DB_PREFIX . "menu` WHERE code = 'b2b_marketplace'");
        $this->db->query("DELETE FROM `" . DB_PREFIX . "menu_description` WHERE menu_id NOT IN (SELECT menu_id FROM `" . DB_PREFIX . "menu`)");
    }
    
    /**
     * Create default settings
     * 
     * @return void
     */
    private function createDefaultSettings(): void {
        $this->load->model('setting/setting');
        
        $default_settings = [
            'b2b_marketplace_enabled' => false,
            'b2b_auto_approval' => false,
            'b2b_min_order_amount' => '0.00',
            'b2b_payment_terms' => ['net30'],
            'b2b_pricing_tiers' => ['bronze', 'silver', 'gold'],
            'multi_store_sync' => false,
            'cross_store_inventory' => false,
            'unified_b2b_accounts' => false,
            'local_sales_enabled' => false,
            'member_linking_enabled' => false,
            'auto_cart_matching' => false
        ];
        
        $this->model_setting_setting->editSetting('b2b_marketplace', $default_settings);
    }
    
    /**
     * Remove settings
     * 
     * @return void
     */
    private function removeSettings(): void {
        $this->load->model('setting/setting');
        $this->model_setting_setting->deleteSetting('b2b_marketplace');
    }
}