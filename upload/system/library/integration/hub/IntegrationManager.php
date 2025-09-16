<?php
/**
 * Integration Manager
 * 
 * Core service for managing external integrations and data synchronization
 * 
 * @category   Library
 * @package    Integration
 * @subpackage Hub
 * @author     O9Cart Development Team
 * @license    GPL v3
 */

namespace O9Cart\Integration\Hub;

class IntegrationManager {
    
    private $db;
    private $config;
    private $log;
    private $cache;
    
    /**
     * Constructor
     * 
     * @param object $registry The application registry
     */
    public function __construct($registry) {
        $this->db = $registry->get('db');
        $this->config = $registry->get('config');
        $this->log = $registry->get('log');
        $this->cache = $registry->get('cache');
    }
    
    /**
     * Get all configured integrations
     * 
     * @param array $filters Optional filters
     * @return array List of integrations
     */
    public function getIntegrations($filters = []) {
        $sql = "SELECT * FROM " . DB_PREFIX . "integration_config WHERE 1=1";
        $params = [];
        
        if (!empty($filters['type'])) {
            $sql .= " AND type = ?";
            $params[] = $filters['type'];
        }
        
        if (!empty($filters['status'])) {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
        }
        
        $sql .= " ORDER BY name ASC";
        
        $query = $this->db->query($sql, $params);
        
        $integrations = [];
        foreach ($query->rows as $row) {
            $row['config_data'] = json_decode($row['config_data'], true);
            $integrations[] = $row;
        }
        
        return $integrations;
    }
    
    /**
     * Get integration by ID
     * 
     * @param int $integration_id
     * @return array|false
     */
    public function getIntegration($integration_id) {
        $query = $this->db->query(
            "SELECT * FROM " . DB_PREFIX . "integration_config WHERE integration_id = ?",
            [$integration_id]
        );
        
        if ($query->num_rows) {
            $integration = $query->row;
            $integration['config_data'] = json_decode($integration['config_data'], true);
            return $integration;
        }
        
        return false;
    }
    
    /**
     * Create new integration
     * 
     * @param array $data Integration configuration data
     * @return int Integration ID
     */
    public function createIntegration($data) {
        $this->validateIntegrationData($data);
        
        $sql = "INSERT INTO " . DB_PREFIX . "integration_config 
                (name, type, status, config_data) 
                VALUES (?, ?, ?, ?)";
        
        $params = [
            $data['name'],
            $data['type'],
            $data['status'] ?? 'inactive',
            json_encode($data['config_data'] ?? [])
        ];
        
        $this->db->query($sql, $params);
        $integration_id = $this->db->getLastId();
        
        $this->log->write("Integration created: " . $data['name'] . " (ID: {$integration_id})");
        
        // Clear cache
        $this->cache->delete('integrations.*');
        
        return $integration_id;
    }
    
    /**
     * Update integration
     * 
     * @param int $integration_id
     * @param array $data
     * @return bool
     */
    public function updateIntegration($integration_id, $data) {
        $this->validateIntegrationData($data);
        
        $sql = "UPDATE " . DB_PREFIX . "integration_config 
                SET name = ?, type = ?, status = ?, config_data = ?, updated_at = NOW() 
                WHERE integration_id = ?";
        
        $params = [
            $data['name'],
            $data['type'],
            $data['status'] ?? 'inactive',
            json_encode($data['config_data'] ?? []),
            $integration_id
        ];
        
        $this->db->query($sql, $params);
        
        $this->log->write("Integration updated: ID {$integration_id}");
        
        // Clear cache
        $this->cache->delete('integrations.*');
        
        return true;
    }
    
    /**
     * Delete integration
     * 
     * @param int $integration_id
     * @return bool
     */
    public function deleteIntegration($integration_id) {
        // Delete related sync jobs
        $this->db->query(
            "DELETE FROM " . DB_PREFIX . "sync_jobs WHERE integration_id = ?",
            [$integration_id]
        );
        
        // Delete data mappings
        $this->db->query(
            "DELETE FROM " . DB_PREFIX . "data_mapping WHERE integration_id = ?",
            [$integration_id]
        );
        
        // Delete integration
        $this->db->query(
            "DELETE FROM " . DB_PREFIX . "integration_config WHERE integration_id = ?",
            [$integration_id]
        );
        
        $this->log->write("Integration deleted: ID {$integration_id}");
        
        // Clear cache
        $this->cache->delete('integrations.*');
        
        return true;
    }
    
    /**
     * Test integration connection
     * 
     * @param int $integration_id
     * @return array Test results
     */
    public function testIntegration($integration_id) {
        $integration = $this->getIntegration($integration_id);
        if (!$integration) {
            return ['success' => false, 'error' => 'Integration not found'];
        }
        
        try {
            $connector = $this->getConnector($integration['type']);
            $result = $connector->testConnection($integration['config_data']);
            
            $this->log->write("Integration test: {$integration['name']} - " . 
                             ($result['success'] ? 'SUCCESS' : 'FAILED'));
            
            return $result;
            
        } catch (Exception $e) {
            $this->log->write("Integration test error: {$integration['name']} - " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Execute sync job
     * 
     * @param int $integration_id
     * @param string $job_type
     * @param array $options
     * @return int Job ID
     */
    public function executeSyncJob($integration_id, $job_type, $options = []) {
        $integration = $this->getIntegration($integration_id);
        if (!$integration || $integration['status'] !== 'active') {
            throw new Exception('Integration not found or inactive');
        }
        
        // Create sync job record
        $job_id = $this->createSyncJob($integration_id, $job_type);
        
        try {
            $this->updateSyncJobStatus($job_id, 'running');
            
            $connector = $this->getConnector($integration['type']);
            $result = $connector->executeSync($job_type, $integration['config_data'], $options);
            
            $this->updateSyncJobStatus($job_id, 'completed', [
                'progress' => 100,
                'total_records' => $result['total_records'] ?? 0
            ]);
            
            $this->log->write("Sync job completed: {$integration['name']} - {$job_type}");
            
        } catch (Exception $e) {
            $this->updateSyncJobStatus($job_id, 'failed', [
                'error_log' => $e->getMessage()
            ]);
            
            $this->log->write("Sync job failed: {$integration['name']} - {$job_type} - " . $e->getMessage());
            throw $e;
        }
        
        return $job_id;
    }
    
    /**
     * Get sync job status
     * 
     * @param int $job_id
     * @return array|false
     */
    public function getSyncJobStatus($job_id) {
        $query = $this->db->query(
            "SELECT * FROM " . DB_PREFIX . "sync_jobs WHERE job_id = ?",
            [$job_id]
        );
        
        return $query->num_rows ? $query->row : false;
    }
    
    /**
     * Get sync jobs for integration
     * 
     * @param int $integration_id
     * @param int $limit
     * @return array
     */
    public function getSyncJobs($integration_id, $limit = 50) {
        $query = $this->db->query(
            "SELECT * FROM " . DB_PREFIX . "sync_jobs 
             WHERE integration_id = ? 
             ORDER BY started_at DESC 
             LIMIT ?",
            [$integration_id, $limit]
        );
        
        return $query->rows;
    }
    
    /**
     * Validate integration data
     * 
     * @param array $data
     * @throws Exception
     */
    private function validateIntegrationData($data) {
        if (empty($data['name'])) {
            throw new Exception('Integration name is required');
        }
        
        if (empty($data['type'])) {
            throw new Exception('Integration type is required');
        }
        
        $valid_types = ['import', 'export', 'sync', 'webhook'];
        if (!in_array($data['type'], $valid_types)) {
            throw new Exception('Invalid integration type');
        }
        
        $valid_statuses = ['active', 'inactive', 'error'];
        if (isset($data['status']) && !in_array($data['status'], $valid_statuses)) {
            throw new Exception('Invalid integration status');
        }
    }
    
    /**
     * Get connector instance
     * 
     * @param string $type
     * @return object
     */
    private function getConnector($type) {
        $connector_class = 'O9Cart\\Integration\\Connectors\\' . ucfirst($type) . 'Connector';
        
        if (!class_exists($connector_class)) {
            throw new Exception("Connector not found for type: {$type}");
        }
        
        return new $connector_class($this->registry);
    }
    
    /**
     * Create sync job record
     * 
     * @param int $integration_id
     * @param string $job_type
     * @return int Job ID
     */
    private function createSyncJob($integration_id, $job_type) {
        $this->db->query(
            "INSERT INTO " . DB_PREFIX . "sync_jobs 
             (integration_id, job_type, status, started_at) 
             VALUES (?, ?, 'pending', NOW())",
            [$integration_id, $job_type]
        );
        
        return $this->db->getLastId();
    }
    
    /**
     * Update sync job status
     * 
     * @param int $job_id
     * @param string $status
     * @param array $updates
     */
    private function updateSyncJobStatus($job_id, $status, $updates = []) {
        $set_clause = "status = ?";
        $params = [$status];
        
        if (isset($updates['progress'])) {
            $set_clause .= ", progress = ?";
            $params[] = $updates['progress'];
        }
        
        if (isset($updates['total_records'])) {
            $set_clause .= ", total_records = ?";
            $params[] = $updates['total_records'];
        }
        
        if (isset($updates['error_log'])) {
            $set_clause .= ", error_log = ?";
            $params[] = $updates['error_log'];
        }
        
        if ($status === 'completed' || $status === 'failed') {
            $set_clause .= ", completed_at = NOW()";
        }
        
        $params[] = $job_id;
        
        $this->db->query(
            "UPDATE " . DB_PREFIX . "sync_jobs SET {$set_clause} WHERE job_id = ?",
            $params
        );
    }
}