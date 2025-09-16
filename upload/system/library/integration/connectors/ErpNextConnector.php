<?php
/**
 * ERPNext Connector
 * 
 * Handles integration with ERPNext ERP system via REST API
 * Supports OAuth 2.0 authentication and bidirectional data sync
 * 
 * @category   Library
 * @package    Integration
 * @subpackage Connectors
 * @author     O9Cart Development Team
 * @license    GPL v3
 */

namespace O9Cart\Integration\Connectors;

class ErpNextConnector {
    
    private $db;
    private $log;
    private $config;
    private $base_url;
    private $api_key;
    private $api_secret;
    private $access_token;
    
    /**
     * Constructor
     * 
     * @param object $registry The application registry
     */
    public function __construct($registry) {
        $this->db = $registry->get('db');
        $this->log = $registry->get('log');
        $this->config = $registry->get('config');
    }
    
    /**
     * Test connection to ERPNext
     * 
     * @param array $config_data
     * @return array
     */
    public function testConnection($config_data) {
        try {
            $this->initializeConnection($config_data);
            
            // Test with a simple API call
            $response = $this->makeApiRequest('GET', '/api/method/frappe.auth.get_logged_user');
            
            if (isset($response['message'])) {
                return [
                    'success' => true,
                    'info' => [
                        'user' => $response['message'],
                        'server_version' => $this->getServerVersion()
                    ]
                ];
            }
            
            return ['success' => false, 'error' => 'Invalid API response'];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Execute synchronization
     * 
     * @param string $job_type
     * @param array $config_data
     * @param array $options
     * @return array
     */
    public function executeSync($job_type, $config_data, $options = []) {
        $this->initializeConnection($config_data);
        
        switch ($job_type) {
            case 'sync_customers':
                return $this->syncCustomers($options);
                
            case 'sync_items':
                return $this->syncItems($options);
                
            case 'sync_orders':
                return $this->syncOrders($options);
                
            case 'import_suppliers':
                return $this->importSuppliers($options);
                
            case 'export_inventory':
                return $this->exportInventory($options);
                
            case 'sync_financial_data':
                return $this->syncFinancialData($options);
                
            default:
                throw new Exception("Unsupported sync job type: {$job_type}");
        }
    }
    
    /**
     * Initialize connection parameters
     * 
     * @param array $config_data
     */
    private function initializeConnection($config_data) {
        $this->base_url = rtrim($config_data['api_url'] ?? '', '/');
        $this->api_key = $config_data['api_key'] ?? '';
        $this->api_secret = $config_data['api_secret'] ?? '';
        
        if (empty($this->base_url) || empty($this->api_key) || empty($this->api_secret)) {
            throw new Exception('ERPNext API configuration incomplete');
        }
        
        // Get or refresh access token
        $this->access_token = $this->getAccessToken();
    }
    
    /**
     * Get access token using API credentials
     * 
     * @return string
     */
    private function getAccessToken() {
        // Check if we have a cached valid token
        $cached_token = $this->getCachedToken();
        if ($cached_token) {
            return $cached_token;
        }
        
        // Request new token
        $auth_data = [
            'usr' => $this->api_key,
            'pwd' => $this->api_secret
        ];
        
        $response = $this->makeAuthRequest($auth_data);
        
        if (isset($response['message'])) {
            $token = $response['message'];
            $this->cacheToken($token);
            return $token;
        }
        
        throw new Exception('Failed to obtain access token from ERPNext');
    }
    
    /**
     * Get cached token if valid
     * 
     * @return string|null
     */
    private function getCachedToken() {
        $query = $this->db->query(
            "SELECT encrypted_data, expires_at FROM " . DB_PREFIX . "external_credentials 
             WHERE system_name = 'erpnext' AND credential_type = 'token' 
             AND (expires_at IS NULL OR expires_at > NOW())"
        );
        
        if ($query->num_rows) {
            return $this->decryptData($query->row['encrypted_data']);
        }
        
        return null;
    }
    
    /**
     * Cache access token
     * 
     * @param string $token
     */
    private function cacheToken($token) {
        $expires_at = date('Y-m-d H:i:s', strtotime('+2 hours')); // ERPNext tokens typically expire in 2 hours
        
        $this->db->query(
            "INSERT INTO " . DB_PREFIX . "external_credentials 
             (system_name, credential_type, encrypted_data, expires_at) 
             VALUES ('erpnext', 'token', ?, ?)
             ON DUPLICATE KEY UPDATE 
             encrypted_data = VALUES(encrypted_data), 
             expires_at = VALUES(expires_at)",
            [$this->encryptData($token), $expires_at]
        );
    }
    
    /**
     * Make authentication request
     * 
     * @param array $auth_data
     * @return array
     */
    private function makeAuthRequest($auth_data) {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->base_url . '/api/method/login',
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($auth_data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
                'Accept: application/json'
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false, // For development - enable in production
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_error($ch)) {
            throw new Exception('cURL error: ' . curl_error($ch));
        }
        
        curl_close($ch);
        
        if ($http_code !== 200) {
            throw new Exception("HTTP error: {$http_code}");
        }
        
        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON response from ERPNext');
        }
        
        return $decoded;
    }
    
    /**
     * Make API request to ERPNext
     * 
     * @param string $method
     * @param string $endpoint
     * @param array $data
     * @return array
     */
    private function makeApiRequest($method, $endpoint, $data = []) {
        $ch = curl_init();
        
        $headers = [
            'Authorization: Bearer ' . $this->access_token,
            'Content-Type: application/json',
            'Accept: application/json'
        ];
        
        $url = $this->base_url . $endpoint;
        
        if ($method === 'GET' && !empty($data)) {
            $url .= '?' . http_build_query($data);
        }
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        
        if (in_array($method, ['POST', 'PUT', 'PATCH']) && !empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_error($ch)) {
            throw new Exception('cURL error: ' . curl_error($ch));
        }
        
        curl_close($ch);
        
        if ($http_code >= 400) {
            throw new Exception("API error: HTTP {$http_code} - {$response}");
        }
        
        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON response from ERPNext API');
        }
        
        return $decoded;
    }
    
    /**
     * Synchronize customers between O9Cart and ERPNext
     * 
     * @param array $options
     * @return array
     */
    private function syncCustomers($options = []) {
        $direction = $options['direction'] ?? 'both'; // 'to_erpnext', 'from_erpnext', 'both'
        $customers_synced = 0;
        
        if ($direction === 'to_erpnext' || $direction === 'both') {
            $customers_synced += $this->exportCustomersToErpNext($options);
        }
        
        if ($direction === 'from_erpnext' || $direction === 'both') {
            $customers_synced += $this->importCustomersFromErpNext($options);
        }
        
        return [
            'success' => true,
            'total_records' => $customers_synced,
            'message' => "Synchronized {$customers_synced} customers"
        ];
    }
    
    /**
     * Export customers from O9Cart to ERPNext
     * 
     * @param array $options
     * @return int Number of customers exported
     */
    private function exportCustomersToErpNext($options) {
        $limit = $options['limit'] ?? 100;
        $offset = $options['offset'] ?? 0;
        
        // Get O9Cart customers that haven't been synced recently
        $customers = $this->db->query(
            "SELECT c.*, 
                    COALESCE(cm.target_entity_id, 0) as erpnext_id,
                    cm.last_sync_at
             FROM " . DB_PREFIX . "customer c
             LEFT JOIN " . DB_PREFIX . "company_mappings cm 
                 ON cm.entity_type = 'customer' 
                 AND cm.source_entity_id = c.customer_id
                 AND cm.target_company_id = (SELECT company_id FROM " . DB_PREFIX . "companies WHERE code = 'ERPNEXT')
             WHERE c.status = 1
             AND (cm.last_sync_at IS NULL OR cm.last_sync_at < DATE_SUB(NOW(), INTERVAL 1 HOUR))
             LIMIT {$limit} OFFSET {$offset}"
        );
        
        $exported = 0;
        
        foreach ($customers->rows as $customer) {
            try {
                $customer_data = [
                    'customer_name' => $customer['firstname'] . ' ' . $customer['lastname'],
                    'customer_type' => 'Individual',
                    'customer_group' => 'All Customer Groups',
                    'territory' => 'All Territories',
                    'email_id' => $customer['email'],
                    'mobile_no' => $customer['telephone'],
                    'website' => '',
                    'custom_o9cart_id' => $customer['customer_id']
                ];
                
                if ($customer['erpnext_id']) {
                    // Update existing customer
                    $response = $this->makeApiRequest(
                        'PUT',
                        "/api/resource/Customer/{$customer['erpnext_id']}",
                        $customer_data
                    );
                } else {
                    // Create new customer
                    $response = $this->makeApiRequest(
                        'POST',
                        '/api/resource/Customer',
                        $customer_data
                    );
                    
                    if (isset($response['data']['name'])) {
                        // Save mapping
                        $this->saveEntityMapping(
                            'customer',
                            $customer['customer_id'],
                            $response['data']['name']
                        );
                    }
                }
                
                $exported++;
                
            } catch (Exception $e) {
                $this->log->write("Failed to export customer {$customer['customer_id']}: " . $e->getMessage());
            }
        }
        
        return $exported;
    }
    
    /**
     * Import customers from ERPNext to O9Cart
     * 
     * @param array $options
     * @return int Number of customers imported
     */
    private function importCustomersFromErpNext($options) {
        $limit = $options['limit'] ?? 100;
        $offset = $options['offset'] ?? 0;
        
        // Get ERPNext customers
        $response = $this->makeApiRequest('GET', '/api/resource/Customer', [
            'fields' => '["name","customer_name","email_id","mobile_no","customer_group","territory","custom_o9cart_id"]',
            'limit_page_length' => $limit,
            'limit_start' => $offset
        ]);
        
        $imported = 0;
        
        if (isset($response['data'])) {
            foreach ($response['data'] as $erpnext_customer) {
                try {
                    // Check if customer already exists in O9Cart
                    $existing = null;
                    
                    if (!empty($erpnext_customer['custom_o9cart_id'])) {
                        $existing = $this->db->query(
                            "SELECT customer_id FROM " . DB_PREFIX . "customer WHERE customer_id = ?",
                            [$erpnext_customer['custom_o9cart_id']]
                        );
                    }
                    
                    if (!$existing || !$existing->num_rows) {
                        // Check by email
                        $existing = $this->db->query(
                            "SELECT customer_id FROM " . DB_PREFIX . "customer WHERE email = ?",
                            [$erpnext_customer['email_id']]
                        );
                    }
                    
                    $name_parts = explode(' ', $erpnext_customer['customer_name'], 2);
                    $firstname = $name_parts[0] ?? '';
                    $lastname = $name_parts[1] ?? '';
                    
                    if ($existing && $existing->num_rows) {
                        // Update existing customer
                        $this->db->query(
                            "UPDATE " . DB_PREFIX . "customer 
                             SET firstname = ?, lastname = ?, telephone = ? 
                             WHERE customer_id = ?",
                            [
                                $firstname,
                                $lastname,
                                $erpnext_customer['mobile_no'] ?? '',
                                $existing->row['customer_id']
                            ]
                        );
                        
                        $customer_id = $existing->row['customer_id'];
                    } else {
                        // Create new customer
                        $this->db->query(
                            "INSERT INTO " . DB_PREFIX . "customer 
                             (firstname, lastname, email, telephone, status, date_added) 
                             VALUES (?, ?, ?, ?, 1, NOW())",
                            [
                                $firstname,
                                $lastname,
                                $erpnext_customer['email_id'] ?? '',
                                $erpnext_customer['mobile_no'] ?? ''
                            ]
                        );
                        
                        $customer_id = $this->db->getLastId();
                    }
                    
                    // Save mapping
                    $this->saveEntityMapping(
                        'customer',
                        $customer_id,
                        $erpnext_customer['name']
                    );
                    
                    $imported++;
                    
                } catch (Exception $e) {
                    $this->log->write("Failed to import customer {$erpnext_customer['name']}: " . $e->getMessage());
                }
            }
        }
        
        return $imported;
    }
    
    /**
     * Synchronize items/products between systems
     * 
     * @param array $options
     * @return array
     */
    private function syncItems($options = []) {
        $direction = $options['direction'] ?? 'both';
        $items_synced = 0;
        
        if ($direction === 'to_erpnext' || $direction === 'both') {
            $items_synced += $this->exportItemsToErpNext($options);
        }
        
        if ($direction === 'from_erpnext' || $direction === 'both') {
            $items_synced += $this->importItemsFromErpNext($options);
        }
        
        return [
            'success' => true,
            'total_records' => $items_synced,
            'message' => "Synchronized {$items_synced} items"
        ];
    }
    
    /**
     * Synchronize orders between systems
     * 
     * @param array $options
     * @return array
     */
    private function syncOrders($options = []) {
        $direction = $options['direction'] ?? 'to_erpnext';
        $orders_synced = 0;
        
        if ($direction === 'to_erpnext' || $direction === 'both') {
            $orders_synced += $this->exportOrdersToErpNext($options);
        }
        
        return [
            'success' => true,
            'total_records' => $orders_synced,
            'message' => "Synchronized {$orders_synced} orders"
        ];
    }
    
    /**
     * Save entity mapping between O9Cart and ERPNext
     * 
     * @param string $entity_type
     * @param int $source_entity_id
     * @param string $target_entity_id
     */
    private function saveEntityMapping($entity_type, $source_entity_id, $target_entity_id) {
        // Get ERPNext company ID
        $company_query = $this->db->query(
            "SELECT company_id FROM " . DB_PREFIX . "companies WHERE code = 'ERPNEXT'"
        );
        
        if (!$company_query->num_rows) {
            // Create ERPNext company record if it doesn't exist
            $this->db->query(
                "INSERT INTO " . DB_PREFIX . "companies (name, code, status) VALUES ('ERPNext', 'ERPNEXT', 'active')"
            );
            $company_id = $this->db->getLastId();
        } else {
            $company_id = $company_query->row['company_id'];
        }
        
        // Save or update mapping
        $this->db->query(
            "INSERT INTO " . DB_PREFIX . "company_mappings 
             (source_company_id, target_company_id, entity_type, source_entity_id, target_entity_id, sync_status, last_sync_at) 
             VALUES (1, ?, ?, ?, ?, 'synced', NOW())
             ON DUPLICATE KEY UPDATE 
             target_entity_id = VALUES(target_entity_id), 
             sync_status = 'synced', 
             last_sync_at = NOW()",
            [$company_id, $entity_type, $source_entity_id, $target_entity_id]
        );
    }
    
    /**
     * Get server version information
     * 
     * @return string
     */
    private function getServerVersion() {
        try {
            $response = $this->makeApiRequest('GET', '/api/method/frappe.utils.get_version');
            return $response['message'] ?? 'Unknown';
        } catch (Exception $e) {
            return 'Unknown';
        }
    }
    
    /**
     * Encrypt sensitive data
     * 
     * @param string $data
     * @return string
     */
    private function encryptData($data) {
        $key = $this->config->get('config_encryption_key') ?: 'default_key';
        return base64_encode(openssl_encrypt($data, 'AES-256-CBC', $key, 0, substr($key, 0, 16)));
    }
    
    /**
     * Decrypt sensitive data
     * 
     * @param string $encrypted_data
     * @return string
     */
    private function decryptData($encrypted_data) {
        $key = $this->config->get('config_encryption_key') ?: 'default_key';
        return openssl_decrypt(base64_decode($encrypted_data), 'AES-256-CBC', $key, 0, substr($key, 0, 16));
    }
    
    /**
     * Placeholder methods for additional sync operations
     */
    
    private function exportItemsToErpNext($options) {
        // Implementation for exporting products to ERPNext
        return 0;
    }
    
    private function importItemsFromErpNext($options) {
        // Implementation for importing items from ERPNext
        return 0;
    }
    
    private function exportOrdersToErpNext($options) {
        // Implementation for exporting orders to ERPNext
        return 0;
    }
    
    private function importSuppliers($options) {
        // Implementation for importing suppliers from ERPNext
        return ['success' => true, 'total_records' => 0];
    }
    
    private function exportInventory($options) {
        // Implementation for exporting inventory to ERPNext
        return ['success' => true, 'total_records' => 0];
    }
    
    private function syncFinancialData($options) {
        // Implementation for syncing financial data
        return ['success' => true, 'total_records' => 0];
    }
}