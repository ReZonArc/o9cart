-- O9Cart Integration Hub Database Schema
-- This file contains the database schema for the Integration Hub features

-- Integration configurations table
CREATE TABLE IF NOT EXISTS `oc_integration_config` (
    `integration_id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `type` enum('import','export','sync','webhook') NOT NULL,
    `status` enum('active','inactive','error') DEFAULT 'inactive',
    `config_data` json DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`integration_id`),
    KEY `idx_type_status` (`type`, `status`),
    KEY `idx_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data sync jobs table
CREATE TABLE IF NOT EXISTS `oc_sync_jobs` (
    `job_id` int(11) NOT NULL AUTO_INCREMENT,
    `integration_id` int(11) NOT NULL,
    `job_type` varchar(50) NOT NULL,
    `status` enum('pending','running','completed','failed') DEFAULT 'pending',
    `progress` int(11) DEFAULT 0,
    `total_records` int(11) DEFAULT 0,
    `error_log` text,
    `started_at` timestamp NULL DEFAULT NULL,
    `completed_at` timestamp NULL DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`job_id`),
    KEY `fk_sync_jobs_integration` (`integration_id`),
    KEY `idx_status_started` (`status`, `started_at`),
    KEY `idx_job_type` (`job_type`),
    CONSTRAINT `fk_sync_jobs_integration` FOREIGN KEY (`integration_id`) REFERENCES `oc_integration_config` (`integration_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data mapping configurations table
CREATE TABLE IF NOT EXISTS `oc_data_mapping` (
    `mapping_id` int(11) NOT NULL AUTO_INCREMENT,
    `integration_id` int(11) NOT NULL,
    `source_field` varchar(100) NOT NULL,
    `target_field` varchar(100) NOT NULL,
    `transformation_rule` json DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`mapping_id`),
    UNIQUE KEY `uk_integration_source` (`integration_id`, `source_field`),
    KEY `fk_data_mapping_integration` (`integration_id`),
    KEY `idx_source_field` (`source_field`),
    KEY `idx_target_field` (`target_field`),
    CONSTRAINT `fk_data_mapping_integration` FOREIGN KEY (`integration_id`) REFERENCES `oc_integration_config` (`integration_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Company management table for multi-store sync
CREATE TABLE IF NOT EXISTS `oc_companies` (
    `company_id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `code` varchar(20) NOT NULL,
    `currency_code` varchar(3) DEFAULT 'USD',
    `timezone` varchar(50) DEFAULT 'UTC',
    `config_data` json DEFAULT NULL,
    `status` enum('active','inactive') DEFAULT 'active',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`company_id`),
    UNIQUE KEY `uk_company_code` (`code`),
    KEY `idx_name` (`name`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cross-company data mapping table
CREATE TABLE IF NOT EXISTS `oc_company_mappings` (
    `mapping_id` int(11) NOT NULL AUTO_INCREMENT,
    `source_company_id` int(11) NOT NULL,
    `target_company_id` int(11) NOT NULL,
    `entity_type` varchar(50) NOT NULL,
    `source_entity_id` int(11) NOT NULL,
    `target_entity_id` int(11) NOT NULL,
    `sync_status` enum('synced','pending','conflict') DEFAULT 'pending',
    `last_sync_at` timestamp NULL DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`mapping_id`),
    UNIQUE KEY `uk_company_entity_mapping` (`source_company_id`, `target_company_id`, `entity_type`, `source_entity_id`),
    KEY `fk_source_company` (`source_company_id`),
    KEY `fk_target_company` (`target_company_id`),
    KEY `idx_entity_type` (`entity_type`),
    KEY `idx_sync_status` (`sync_status`),
    CONSTRAINT `fk_source_company` FOREIGN KEY (`source_company_id`) REFERENCES `oc_companies` (`company_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_target_company` FOREIGN KEY (`target_company_id`) REFERENCES `oc_companies` (`company_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Webhook configurations table
CREATE TABLE IF NOT EXISTS `oc_webhooks` (
    `webhook_id` int(11) NOT NULL AUTO_INCREMENT,
    `integration_id` int(11) NOT NULL,
    `name` varchar(100) NOT NULL,
    `url` varchar(500) NOT NULL,
    `method` enum('GET','POST','PUT','PATCH','DELETE') DEFAULT 'POST',
    `headers` json DEFAULT NULL,
    `events` json NOT NULL,
    `secret` varchar(255) DEFAULT NULL,
    `status` enum('active','inactive') DEFAULT 'active',
    `retry_attempts` int(11) DEFAULT 3,
    `timeout` int(11) DEFAULT 30,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`webhook_id`),
    KEY `fk_webhooks_integration` (`integration_id`),
    KEY `idx_status` (`status`),
    CONSTRAINT `fk_webhooks_integration` FOREIGN KEY (`integration_id`) REFERENCES `oc_integration_config` (`integration_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Webhook delivery log table
CREATE TABLE IF NOT EXISTS `oc_webhook_deliveries` (
    `delivery_id` int(11) NOT NULL AUTO_INCREMENT,
    `webhook_id` int(11) NOT NULL,
    `event_type` varchar(50) NOT NULL,
    `payload` json NOT NULL,
    `response_status` int(11) DEFAULT NULL,
    `response_body` text,
    `attempt_count` int(11) DEFAULT 1,
    `delivered_at` timestamp NULL DEFAULT NULL,
    `next_retry_at` timestamp NULL DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`delivery_id`),
    KEY `fk_deliveries_webhook` (`webhook_id`),
    KEY `idx_event_type` (`event_type`),
    KEY `idx_next_retry` (`next_retry_at`),
    KEY `idx_created_at` (`created_at`),
    CONSTRAINT `fk_deliveries_webhook` FOREIGN KEY (`webhook_id`) REFERENCES `oc_webhooks` (`webhook_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- External system credentials table (encrypted)
CREATE TABLE IF NOT EXISTS `oc_external_credentials` (
    `credential_id` int(11) NOT NULL AUTO_INCREMENT,
    `integration_id` int(11) NOT NULL,
    `system_name` varchar(100) NOT NULL,
    `credential_type` enum('api_key','oauth2','basic_auth','token') NOT NULL,
    `encrypted_data` text NOT NULL,
    `expires_at` timestamp NULL DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`credential_id`),
    KEY `fk_credentials_integration` (`integration_id`),
    KEY `idx_system_name` (`system_name`),
    KEY `idx_expires_at` (`expires_at`),
    CONSTRAINT `fk_credentials_integration` FOREIGN KEY (`integration_id`) REFERENCES `oc_integration_config` (`integration_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- AI/ML model configurations for OpenCog integration
CREATE TABLE IF NOT EXISTS `oc_ai_models` (
    `model_id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `type` enum('recommendation','prediction','classification','clustering') NOT NULL,
    `algorithm` varchar(50) NOT NULL,
    `config_data` json DEFAULT NULL,
    `training_data_path` varchar(500) DEFAULT NULL,
    `model_file_path` varchar(500) DEFAULT NULL,
    `accuracy_score` decimal(5,4) DEFAULT NULL,
    `status` enum('training','trained','deployed','deprecated') DEFAULT 'training',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`model_id`),
    KEY `idx_type_status` (`type`, `status`),
    KEY `idx_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Customer behavior analytics table
CREATE TABLE IF NOT EXISTS `oc_customer_analytics` (
    `analytics_id` int(11) NOT NULL AUTO_INCREMENT,
    `customer_id` int(11) NOT NULL,
    `session_id` varchar(32) DEFAULT NULL,
    `event_type` varchar(50) NOT NULL,
    `event_data` json DEFAULT NULL,
    `page_url` varchar(500) DEFAULT NULL,
    `user_agent` varchar(500) DEFAULT NULL,
    `ip_address` varchar(45) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`analytics_id`),
    KEY `idx_customer_id` (`customer_id`),
    KEY `idx_session_id` (`session_id`),
    KEY `idx_event_type` (`event_type`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Product recommendations table
CREATE TABLE IF NOT EXISTS `oc_product_recommendations` (
    `recommendation_id` int(11) NOT NULL AUTO_INCREMENT,
    `customer_id` int(11) DEFAULT NULL,
    `product_id` int(11) NOT NULL,
    `recommended_product_id` int(11) NOT NULL,
    `recommendation_type` enum('collaborative','content_based','hybrid','trending') NOT NULL,
    `confidence_score` decimal(5,4) DEFAULT NULL,
    `context_data` json DEFAULT NULL,
    `expires_at` timestamp NULL DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`recommendation_id`),
    KEY `idx_customer_product` (`customer_id`, `product_id`),
    KEY `idx_recommended_product` (`recommended_product_id`),
    KEY `idx_type_score` (`recommendation_type`, `confidence_score`),
    KEY `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inventory predictions table
CREATE TABLE IF NOT EXISTS `oc_inventory_predictions` (
    `prediction_id` int(11) NOT NULL AUTO_INCREMENT,
    `product_id` int(11) NOT NULL,
    `prediction_type` enum('demand_forecast','reorder_point','seasonal_trend') NOT NULL,
    `prediction_value` decimal(15,4) NOT NULL,
    `confidence_level` decimal(5,4) DEFAULT NULL,
    `prediction_date` date NOT NULL,
    `factors` json DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`prediction_id`),
    KEY `idx_product_type` (`product_id`, `prediction_type`),
    KEY `idx_prediction_date` (`prediction_date`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample data for testing
INSERT IGNORE INTO `oc_companies` (`company_id`, `name`, `code`, `currency_code`, `timezone`, `status`) VALUES
(1, 'Main Store', 'MAIN', 'USD', 'America/New_York', 'active'),
(2, 'European Branch', 'EU', 'EUR', 'Europe/London', 'active'),
(3, 'Asian Branch', 'ASIA', 'JPY', 'Asia/Tokyo', 'active');

-- Insert sample integration configurations
INSERT IGNORE INTO `oc_integration_config` (`integration_id`, `name`, `type`, `status`, `config_data`) VALUES
(1, 'GnuCash Integration', 'sync', 'inactive', '{"gnucash_file_path": "/path/to/gnucash/file.gnucash", "sync_frequency": "daily", "account_mapping": {}}'),
(2, 'ERPNext Integration', 'sync', 'inactive', '{"api_url": "https://your-erpnext.com/api", "api_key": "", "api_secret": "", "sync_entities": ["customers", "items", "orders"]}'),
(3, 'CSV Product Import', 'import', 'active', '{"file_path": "/import/products/", "format": "csv", "delimiter": ",", "has_header": true}');

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS `idx_sync_jobs_integration_status` ON `oc_sync_jobs` (`integration_id`, `status`);
CREATE INDEX IF NOT EXISTS `idx_data_mapping_integration_source` ON `oc_data_mapping` (`integration_id`, `source_field`);
CREATE INDEX IF NOT EXISTS `idx_webhook_deliveries_status_retry` ON `oc_webhook_deliveries` (`response_status`, `next_retry_at`);
CREATE INDEX IF NOT EXISTS `idx_customer_analytics_customer_event` ON `oc_customer_analytics` (`customer_id`, `event_type`);
CREATE INDEX IF NOT EXISTS `idx_recommendations_customer_type` ON `oc_product_recommendations` (`customer_id`, `recommendation_type`);

-- Add comments to tables for documentation
ALTER TABLE `oc_integration_config` COMMENT = 'Stores configuration for external system integrations';
ALTER TABLE `oc_sync_jobs` COMMENT = 'Tracks data synchronization jobs and their status';
ALTER TABLE `oc_data_mapping` COMMENT = 'Defines field mappings and transformations between systems';
ALTER TABLE `oc_companies` COMMENT = 'Multi-company configuration for cross-system synchronization';
ALTER TABLE `oc_company_mappings` COMMENT = 'Maps entities between different companies/systems';
ALTER TABLE `oc_webhooks` COMMENT = 'Webhook configurations for real-time data sync';
ALTER TABLE `oc_webhook_deliveries` COMMENT = 'Log of webhook delivery attempts and results';
ALTER TABLE `oc_external_credentials` COMMENT = 'Encrypted storage for external system credentials';
ALTER TABLE `oc_ai_models` COMMENT = 'AI/ML model configurations for OpenCog integration';
ALTER TABLE `oc_customer_analytics` COMMENT = 'Customer behavior data for AI analysis';
ALTER TABLE `oc_product_recommendations` COMMENT = 'AI-generated product recommendations';
ALTER TABLE `oc_inventory_predictions` COMMENT = 'AI-powered inventory predictions and forecasts';