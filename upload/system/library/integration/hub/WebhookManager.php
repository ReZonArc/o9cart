<?php
/**
 * Webhook Manager
 * 
 * Manages webhook configurations and delivery for real-time data synchronization
 * 
 * @category   Library
 * @package    Integration
 * @subpackage Hub
 * @author     O9Cart Development Team
 * @license    GPL v3
 */

namespace O9Cart\Integration\Hub;

class WebhookManager {
    
    private $db;
    private $log;
    private $config;
    private $queue;
    
    /**
     * Constructor
     * 
     * @param object $registry The application registry
     */
    public function __construct($registry) {
        $this->db = $registry->get('db');
        $this->log = $registry->get('log');
        $this->config = $registry->get('config');
        
        // Initialize queue system (Redis or database-based)
        $this->initializeQueue();
    }
    
    /**
     * Create webhook configuration
     * 
     * @param array $webhook_data
     * @return int Webhook ID
     */
    public function createWebhook($webhook_data) {
        $this->validateWebhookData($webhook_data);
        
        $sql = "INSERT INTO " . DB_PREFIX . "webhooks 
                (integration_id, name, url, method, headers, events, secret, status, retry_attempts, timeout) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $webhook_data['integration_id'],
            $webhook_data['name'],
            $webhook_data['url'],
            $webhook_data['method'] ?? 'POST',
            json_encode($webhook_data['headers'] ?? []),
            json_encode($webhook_data['events']),
            $webhook_data['secret'] ?? $this->generateSecret(),
            $webhook_data['status'] ?? 'active',
            $webhook_data['retry_attempts'] ?? 3,
            $webhook_data['timeout'] ?? 30
        ];
        
        $this->db->query($sql, $params);
        $webhook_id = $this->db->getLastId();
        
        $this->log->write("Webhook created: {$webhook_data['name']} (ID: {$webhook_id})");
        
        return $webhook_id;
    }
    
    /**
     * Update webhook configuration
     * 
     * @param int $webhook_id
     * @param array $webhook_data
     * @return bool
     */
    public function updateWebhook($webhook_id, $webhook_data) {
        $this->validateWebhookData($webhook_data);
        
        $sql = "UPDATE " . DB_PREFIX . "webhooks 
                SET name = ?, url = ?, method = ?, headers = ?, events = ?, 
                    secret = ?, status = ?, retry_attempts = ?, timeout = ?, updated_at = NOW() 
                WHERE webhook_id = ?";
        
        $params = [
            $webhook_data['name'],
            $webhook_data['url'],
            $webhook_data['method'] ?? 'POST',
            json_encode($webhook_data['headers'] ?? []),
            json_encode($webhook_data['events']),
            $webhook_data['secret'] ?? $this->generateSecret(),
            $webhook_data['status'] ?? 'active',
            $webhook_data['retry_attempts'] ?? 3,
            $webhook_data['timeout'] ?? 30,
            $webhook_id
        ];
        
        $this->db->query($sql, $params);
        
        $this->log->write("Webhook updated: ID {$webhook_id}");
        
        return true;
    }
    
    /**
     * Delete webhook
     * 
     * @param int $webhook_id
     * @return bool
     */
    public function deleteWebhook($webhook_id) {
        // Delete delivery history
        $this->db->query(
            "DELETE FROM " . DB_PREFIX . "webhook_deliveries WHERE webhook_id = ?",
            [$webhook_id]
        );
        
        // Delete webhook
        $this->db->query(
            "DELETE FROM " . DB_PREFIX . "webhooks WHERE webhook_id = ?",
            [$webhook_id]
        );
        
        $this->log->write("Webhook deleted: ID {$webhook_id}");
        
        return true;
    }
    
    /**
     * Get webhook by ID
     * 
     * @param int $webhook_id
     * @return array|false
     */
    public function getWebhook($webhook_id) {
        $query = $this->db->query(
            "SELECT * FROM " . DB_PREFIX . "webhooks WHERE webhook_id = ?",
            [$webhook_id]
        );
        
        if ($query->num_rows) {
            $webhook = $query->row;
            $webhook['headers'] = json_decode($webhook['headers'], true);
            $webhook['events'] = json_decode($webhook['events'], true);
            return $webhook;
        }
        
        return false;
    }
    
    /**
     * Get webhooks for integration
     * 
     * @param int $integration_id
     * @param string $status
     * @return array
     */
    public function getWebhooks($integration_id, $status = null) {
        $sql = "SELECT * FROM " . DB_PREFIX . "webhooks WHERE integration_id = ?";
        $params = [$integration_id];
        
        if ($status) {
            $sql .= " AND status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        $query = $this->db->query($sql, $params);
        
        $webhooks = [];
        foreach ($query->rows as $row) {
            $row['headers'] = json_decode($row['headers'], true);
            $row['events'] = json_decode($row['events'], true);
            $webhooks[] = $row;
        }
        
        return $webhooks;
    }
    
    /**
     * Trigger webhook for specific event
     * 
     * @param string $event_type
     * @param array $payload
     * @param int $integration_id Optional - trigger only for specific integration
     */
    public function triggerEvent($event_type, $payload, $integration_id = null) {
        $sql = "SELECT * FROM " . DB_PREFIX . "webhooks WHERE status = 'active'";
        $params = [];
        
        if ($integration_id) {
            $sql .= " AND integration_id = ?";
            $params[] = $integration_id;
        }
        
        $query = $this->db->query($sql, $params);
        
        foreach ($query->rows as $webhook) {
            $events = json_decode($webhook['events'], true);
            
            // Check if this webhook is configured for this event
            if (in_array($event_type, $events) || in_array('*', $events)) {
                $this->scheduleWebhookDelivery($webhook['webhook_id'], $event_type, $payload);
            }
        }
    }
    
    /**
     * Schedule webhook delivery
     * 
     * @param int $webhook_id
     * @param string $event_type
     * @param array $payload
     * @return int Delivery ID
     */
    public function scheduleWebhookDelivery($webhook_id, $event_type, $payload) {
        // Add metadata to payload
        $enhanced_payload = [
            'event' => $event_type,
            'timestamp' => date('c'),
            'data' => $payload
        ];
        
        $sql = "INSERT INTO " . DB_PREFIX . "webhook_deliveries 
                (webhook_id, event_type, payload, attempt_count) 
                VALUES (?, ?, ?, 0)";
        
        $this->db->query($sql, [
            $webhook_id,
            $event_type,
            json_encode($enhanced_payload)
        ]);
        
        $delivery_id = $this->db->getLastId();
        
        // Add to queue for immediate processing
        $this->queueWebhookDelivery($delivery_id);
        
        return $delivery_id;
    }
    
    /**
     * Process webhook delivery
     * 
     * @param int $delivery_id
     * @return bool
     */
    public function processWebhookDelivery($delivery_id) {
        // Get delivery record
        $delivery_query = $this->db->query(
            "SELECT wd.*, w.* FROM " . DB_PREFIX . "webhook_deliveries wd
             JOIN " . DB_PREFIX . "webhooks w ON w.webhook_id = wd.webhook_id
             WHERE wd.delivery_id = ?",
            [$delivery_id]
        );
        
        if (!$delivery_query->num_rows) {
            $this->log->write("Webhook delivery not found: {$delivery_id}");
            return false;
        }
        
        $delivery = $delivery_query->row;
        
        try {
            // Increment attempt count
            $this->db->query(
                "UPDATE " . DB_PREFIX . "webhook_deliveries 
                 SET attempt_count = attempt_count + 1 
                 WHERE delivery_id = ?",
                [$delivery_id]
            );
            
            $payload = json_decode($delivery['payload'], true);
            $headers = json_decode($delivery['headers'], true);
            
            // Prepare request
            $request_headers = $this->prepareHeaders($headers, $delivery['secret'], $payload);
            
            // Make HTTP request
            $response = $this->makeHttpRequest(
                $delivery['method'],
                $delivery['url'],
                $payload,
                $request_headers,
                $delivery['timeout']
            );
            
            // Update delivery record with success
            $this->db->query(
                "UPDATE " . DB_PREFIX . "webhook_deliveries 
                 SET response_status = ?, response_body = ?, delivered_at = NOW() 
                 WHERE delivery_id = ?",
                [$response['status'], $response['body'], $delivery_id]
            );
            
            if ($response['status'] >= 200 && $response['status'] < 300) {
                $this->log->write("Webhook delivered successfully: {$delivery['url']} (Delivery ID: {$delivery_id})");
                return true;
            } else {
                throw new Exception("HTTP {$response['status']}: {$response['body']}");
            }
            
        } catch (Exception $e) {
            $error_message = $e->getMessage();
            
            // Update delivery record with error
            $this->db->query(
                "UPDATE " . DB_PREFIX . "webhook_deliveries 
                 SET response_status = 0, response_body = ? 
                 WHERE delivery_id = ?",
                [$error_message, $delivery_id]
            );
            
            $this->log->write("Webhook delivery failed: {$delivery['url']} - {$error_message}");
            
            // Schedule retry if within retry limit
            if ($delivery['attempt_count'] < $delivery['retry_attempts']) {
                $this->scheduleRetry($delivery_id, $delivery['attempt_count'] + 1);
            }
            
            return false;
        }
    }
    
    /**
     * Schedule webhook delivery retry
     * 
     * @param int $delivery_id
     * @param int $attempt_count
     */
    private function scheduleRetry($delivery_id, $attempt_count) {
        // Exponential backoff: 2^attempt minutes
        $delay_minutes = pow(2, $attempt_count);
        $next_retry = date('Y-m-d H:i:s', strtotime("+{$delay_minutes} minutes"));
        
        $this->db->query(
            "UPDATE " . DB_PREFIX . "webhook_deliveries 
             SET next_retry_at = ? 
             WHERE delivery_id = ?",
            [$next_retry, $delivery_id]
        );
        
        // Add to queue for later processing
        $this->queueWebhookDelivery($delivery_id, $delay_minutes * 60);
    }
    
    /**
     * Process webhook delivery queue
     * 
     * @param int $limit Maximum number of deliveries to process
     */
    public function processDeliveryQueue($limit = 10) {
        // Get pending deliveries that are due for retry
        $deliveries = $this->db->query(
            "SELECT delivery_id FROM " . DB_PREFIX . "webhook_deliveries 
             WHERE delivered_at IS NULL 
             AND (next_retry_at IS NULL OR next_retry_at <= NOW())
             ORDER BY created_at ASC 
             LIMIT ?",
            [$limit]
        );
        
        foreach ($deliveries->rows as $delivery) {
            $this->processWebhookDelivery($delivery['delivery_id']);
        }
    }
    
    /**
     * Get webhook delivery history
     * 
     * @param int $webhook_id
     * @param int $limit
     * @return array
     */
    public function getDeliveryHistory($webhook_id, $limit = 50) {
        $query = $this->db->query(
            "SELECT * FROM " . DB_PREFIX . "webhook_deliveries 
             WHERE webhook_id = ? 
             ORDER BY created_at DESC 
             LIMIT ?",
            [$webhook_id, $limit]
        );
        
        $deliveries = [];
        foreach ($query->rows as $row) {
            $row['payload'] = json_decode($row['payload'], true);
            $deliveries[] = $row;
        }
        
        return $deliveries;
    }
    
    /**
     * Validate webhook configuration data
     * 
     * @param array $webhook_data
     * @throws Exception
     */
    private function validateWebhookData($webhook_data) {
        if (empty($webhook_data['name'])) {
            throw new Exception('Webhook name is required');
        }
        
        if (empty($webhook_data['url'])) {
            throw new Exception('Webhook URL is required');
        }
        
        if (!filter_var($webhook_data['url'], FILTER_VALIDATE_URL)) {
            throw new Exception('Invalid webhook URL format');
        }
        
        if (empty($webhook_data['events']) || !is_array($webhook_data['events'])) {
            throw new Exception('Webhook events must be specified as array');
        }
        
        $valid_methods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];
        $method = $webhook_data['method'] ?? 'POST';
        if (!in_array($method, $valid_methods)) {
            throw new Exception('Invalid HTTP method');
        }
    }
    
    /**
     * Generate secure webhook secret
     * 
     * @return string
     */
    private function generateSecret() {
        return bin2hex(random_bytes(32));
    }
    
    /**
     * Prepare HTTP headers for webhook request
     * 
     * @param array $custom_headers
     * @param string $secret
     * @param array $payload
     * @return array
     */
    private function prepareHeaders($custom_headers, $secret, $payload) {
        $headers = [
            'Content-Type: application/json',
            'User-Agent: O9Cart-Webhook/1.0'
        ];
        
        // Add custom headers
        foreach ($custom_headers as $name => $value) {
            $headers[] = "{$name}: {$value}";
        }
        
        // Add signature header for security
        if ($secret) {
            $signature = hash_hmac('sha256', json_encode($payload), $secret);
            $headers[] = "X-O9Cart-Signature: sha256={$signature}";
        }
        
        return $headers;
    }
    
    /**
     * Make HTTP request for webhook delivery
     * 
     * @param string $method
     * @param string $url
     * @param array $payload
     * @param array $headers
     * @param int $timeout
     * @return array
     */
    private function makeHttpRequest($method, $url, $payload, $headers, $timeout) {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3
        ]);
        
        if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        }
        
        $response_body = curl_exec($ch);
        $response_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            throw new Exception("cURL error: {$error}");
        }
        
        return [
            'status' => $response_status,
            'body' => $response_body
        ];
    }
    
    /**
     * Initialize queue system
     */
    private function initializeQueue() {
        // In a production environment, you would use Redis or a proper message queue
        // For now, we'll use a simple database-based approach
        $this->queue = new DatabaseQueue($this->db);
    }
    
    /**
     * Queue webhook delivery for processing
     * 
     * @param int $delivery_id
     * @param int $delay_seconds
     */
    private function queueWebhookDelivery($delivery_id, $delay_seconds = 0) {
        $process_at = $delay_seconds > 0 ? date('Y-m-d H:i:s', time() + $delay_seconds) : date('Y-m-d H:i:s');
        
        // For now, just log the queuing action
        // In production, this would add to Redis queue or similar
        $this->log->write("Queued webhook delivery {$delivery_id} for processing at {$process_at}");
    }
    
    /**
     * Clean up old webhook deliveries
     * 
     * @param int $days_old
     * @return int Number of records deleted
     */
    public function cleanupOldDeliveries($days_old = 30) {
        $this->db->query(
            "DELETE FROM " . DB_PREFIX . "webhook_deliveries 
             WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)",
            [$days_old]
        );
        
        $deleted_count = $this->db->countAffected();
        $this->log->write("Cleaned up {$deleted_count} old webhook deliveries");
        
        return $deleted_count;
    }
}

/**
 * Simple database-based queue implementation
 * In production, use Redis or proper message queue system
 */
class DatabaseQueue {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    // Queue implementation methods would go here
}