-- OpenCog Integration Database Schema
-- Additional tables for OpenCog AtomSpace integration and cognitive AI features

-- OpenCog atoms storage (local experimentation)
CREATE TABLE IF NOT EXISTS `oc_opencog_atoms` (
    `atom_id` int(11) NOT NULL AUTO_INCREMENT,
    `type` varchar(50) NOT NULL,
    `name` varchar(255) NOT NULL,
    `attributes` json DEFAULT NULL,
    `truth_value_strength` decimal(5,4) DEFAULT 1.0000,
    `truth_value_confidence` decimal(5,4) DEFAULT 1.0000,
    `attention_sti` int(11) DEFAULT 0,
    `attention_lti` int(11) DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`atom_id`),
    UNIQUE KEY `uk_type_name` (`type`, `name`),
    KEY `idx_type` (`type`),
    KEY `idx_name` (`name`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- OpenCog links storage (local experimentation)
CREATE TABLE IF NOT EXISTS `oc_opencog_links` (
    `link_id` int(11) NOT NULL AUTO_INCREMENT,
    `type` varchar(50) NOT NULL,
    `atom_ids` json NOT NULL,
    `strength` decimal(5,4) DEFAULT 1.0000,
    `confidence` decimal(5,4) DEFAULT 1.0000,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`link_id`),
    KEY `idx_type` (`type`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Customer behavior tracking for AI analysis
CREATE TABLE IF NOT EXISTS `oc_customer_behavior_events` (
    `event_id` int(11) NOT NULL AUTO_INCREMENT,
    `customer_id` int(11) DEFAULT NULL,
    `session_id` varchar(32) DEFAULT NULL,
    `event_type` varchar(50) NOT NULL,
    `event_category` varchar(50) DEFAULT NULL,
    `page_url` varchar(500) DEFAULT NULL,
    `referrer_url` varchar(500) DEFAULT NULL,
    `product_id` int(11) DEFAULT NULL,
    `category_id` int(11) DEFAULT NULL,
    `search_query` varchar(255) DEFAULT NULL,
    `event_data` json DEFAULT NULL,
    `user_agent` varchar(500) DEFAULT NULL,
    `ip_address` varchar(45) DEFAULT NULL,
    `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`event_id`),
    KEY `idx_customer_id` (`customer_id`),
    KEY `idx_session_id` (`session_id`),
    KEY `idx_event_type` (`event_type`),
    KEY `idx_product_id` (`product_id`),
    KEY `idx_timestamp` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Customer segments derived from AI analysis
CREATE TABLE IF NOT EXISTS `oc_customer_segments` (
    `segment_id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `description` text DEFAULT NULL,
    `segment_type` enum('demographic','behavioral','psychographic','geographic') NOT NULL,
    `criteria` json NOT NULL,
    `customer_count` int(11) DEFAULT 0,
    `avg_order_value` decimal(15,4) DEFAULT NULL,
    `avg_lifetime_value` decimal(15,4) DEFAULT NULL,
    `status` enum('active','inactive') DEFAULT 'active',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`segment_id`),
    KEY `idx_name` (`name`),
    KEY `idx_type` (`segment_type`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Customer segment assignments
CREATE TABLE IF NOT EXISTS `oc_customer_segment_assignments` (
    `assignment_id` int(11) NOT NULL AUTO_INCREMENT,
    `customer_id` int(11) NOT NULL,
    `segment_id` int(11) NOT NULL,
    `confidence_score` decimal(5,4) DEFAULT 1.0000,
    `assigned_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `expires_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`assignment_id`),
    UNIQUE KEY `uk_customer_segment` (`customer_id`, `segment_id`),
    KEY `fk_segment_assignment` (`segment_id`),
    KEY `idx_assigned_at` (`assigned_at`),
    CONSTRAINT `fk_segment_assignment` FOREIGN KEY (`segment_id`) REFERENCES `oc_customer_segments` (`segment_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Product similarity matrix for content-based recommendations
CREATE TABLE IF NOT EXISTS `oc_product_similarity` (
    `similarity_id` int(11) NOT NULL AUTO_INCREMENT,
    `product_id_1` int(11) NOT NULL,
    `product_id_2` int(11) NOT NULL,
    `similarity_type` enum('content','collaborative','cognitive','hybrid') NOT NULL,
    `similarity_score` decimal(5,4) NOT NULL,
    `calculation_method` varchar(50) DEFAULT NULL,
    `features_compared` json DEFAULT NULL,
    `calculated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`similarity_id`),
    UNIQUE KEY `uk_products_type` (`product_id_1`, `product_id_2`, `similarity_type`),
    KEY `idx_product_1` (`product_id_1`),
    KEY `idx_product_2` (`product_id_2`),
    KEY `idx_similarity_score` (`similarity_score`),
    KEY `idx_calculated_at` (`calculated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Customer preference profiles
CREATE TABLE IF NOT EXISTS `oc_customer_preferences` (
    `preference_id` int(11) NOT NULL AUTO_INCREMENT,
    `customer_id` int(11) NOT NULL,
    `preference_type` varchar(50) NOT NULL,
    `preference_value` json NOT NULL,
    `confidence_level` decimal(5,4) DEFAULT 0.5000,
    `source` enum('explicit','implicit','inferred') DEFAULT 'inferred',
    `last_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`preference_id`),
    UNIQUE KEY `uk_customer_type` (`customer_id`, `preference_type`),
    KEY `idx_customer_id` (`customer_id`),
    KEY `idx_preference_type` (`preference_type`),
    KEY `idx_confidence_level` (`confidence_level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- A/B testing for recommendations
CREATE TABLE IF NOT EXISTS `oc_recommendation_tests` (
    `test_id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `description` text DEFAULT NULL,
    `algorithm_a` varchar(50) NOT NULL,
    `algorithm_b` varchar(50) NOT NULL,
    `traffic_split` decimal(3,2) DEFAULT 0.50,
    `status` enum('draft','running','paused','completed') DEFAULT 'draft',
    `start_date` date DEFAULT NULL,
    `end_date` date DEFAULT NULL,
    `success_metric` varchar(50) DEFAULT 'click_through_rate',
    `results` json DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`test_id`),
    KEY `idx_name` (`name`),
    KEY `idx_status` (`status`),
    KEY `idx_dates` (`start_date`, `end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Recommendation performance metrics
CREATE TABLE IF NOT EXISTS `oc_recommendation_metrics` (
    `metric_id` int(11) NOT NULL AUTO_INCREMENT,
    `customer_id` int(11) DEFAULT NULL,
    `recommendation_id` int(11) DEFAULT NULL,
    `algorithm_used` varchar(50) NOT NULL,
    `action_type` enum('view','click','purchase','ignore') NOT NULL,
    `product_id` int(11) NOT NULL,
    `confidence_score` decimal(5,4) DEFAULT NULL,
    `context_data` json DEFAULT NULL,
    `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`metric_id`),
    KEY `idx_customer_id` (`customer_id`),
    KEY `idx_recommendation_id` (`recommendation_id`),
    KEY `idx_algorithm` (`algorithm_used`),
    KEY `idx_action_type` (`action_type`),
    KEY `idx_product_id` (`product_id`),
    KEY `idx_timestamp` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cognitive insights generated by OpenCog
CREATE TABLE IF NOT EXISTS `oc_cognitive_insights` (
    `insight_id` int(11) NOT NULL AUTO_INCREMENT,
    `insight_type` varchar(50) NOT NULL,
    `title` varchar(255) NOT NULL,
    `description` text DEFAULT NULL,
    `insight_data` json NOT NULL,
    `confidence_level` decimal(5,4) DEFAULT 0.5000,
    `relevance_score` decimal(5,4) DEFAULT 0.5000,
    `entity_type` varchar(50) DEFAULT NULL,
    `entity_id` int(11) DEFAULT NULL,
    `generated_by` varchar(50) DEFAULT 'opencog',
    `status` enum('new','reviewed','applied','dismissed') DEFAULT 'new',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `reviewed_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`insight_id`),
    KEY `idx_insight_type` (`insight_type`),
    KEY `idx_entity` (`entity_type`, `entity_id`),
    KEY `idx_confidence` (`confidence_level`),
    KEY `idx_status` (`status`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pattern recognition results
CREATE TABLE IF NOT EXISTS `oc_pattern_recognition` (
    `pattern_id` int(11) NOT NULL AUTO_INCREMENT,
    `pattern_type` varchar(50) NOT NULL,
    `pattern_name` varchar(100) NOT NULL,
    `pattern_data` json NOT NULL,
    `frequency` int(11) DEFAULT 1,
    `confidence_score` decimal(5,4) DEFAULT 0.5000,
    `first_detected` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_detected` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `status` enum('active','dormant','archived') DEFAULT 'active',
    PRIMARY KEY (`pattern_id`),
    KEY `idx_pattern_type` (`pattern_type`),
    KEY `idx_pattern_name` (`pattern_name`),
    KEY `idx_frequency` (`frequency`),
    KEY `idx_confidence_score` (`confidence_score`),
    KEY `idx_last_detected` (`last_detected`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Market trend analysis
CREATE TABLE IF NOT EXISTS `oc_market_trends` (
    `trend_id` int(11) NOT NULL AUTO_INCREMENT,
    `trend_name` varchar(100) NOT NULL,
    `trend_type` enum('product','category','seasonal','demographic') NOT NULL,
    `trend_direction` enum('increasing','decreasing','stable','volatile') NOT NULL,
    `trend_strength` decimal(5,4) DEFAULT 0.5000,
    `time_period` varchar(50) NOT NULL,
    `trend_data` json NOT NULL,
    `supporting_metrics` json DEFAULT NULL,
    `detected_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `expires_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`trend_id`),
    KEY `idx_trend_name` (`trend_name`),
    KEY `idx_trend_type` (`trend_type`),
    KEY `idx_trend_direction` (`trend_direction`),
    KEY `idx_trend_strength` (`trend_strength`),
    KEY `idx_detected_at` (`detected_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Real-time recommendation cache
CREATE TABLE IF NOT EXISTS `oc_recommendation_cache` (
    `cache_id` int(11) NOT NULL AUTO_INCREMENT,
    `cache_key` varchar(255) NOT NULL,
    `customer_id` int(11) DEFAULT NULL,
    `product_id` int(11) DEFAULT NULL,
    `algorithm` varchar(50) NOT NULL,
    `recommendations` json NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `expires_at` timestamp NOT NULL,
    PRIMARY KEY (`cache_id`),
    UNIQUE KEY `uk_cache_key` (`cache_key`),
    KEY `idx_customer_id` (`customer_id`),
    KEY `idx_product_id` (`product_id`),
    KEY `idx_algorithm` (`algorithm`),
    KEY `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample customer segments
INSERT IGNORE INTO `oc_customer_segments` (`segment_id`, `name`, `description`, `segment_type`, `criteria`, `customer_count`) VALUES
(1, 'High Value Customers', 'Customers with high lifetime value and frequent purchases', 'behavioral', '{"min_lifetime_value": 1000, "min_orders": 10, "avg_order_value": 100}', 0),
(2, 'New Customers', 'Recently registered customers with limited purchase history', 'behavioral', '{"max_days_since_registration": 30, "max_orders": 2}', 0),
(3, 'At Risk Customers', 'Previously active customers who haven\'t purchased recently', 'behavioral', '{"min_previous_orders": 3, "days_since_last_order": 90}', 0),
(4, 'Bargain Hunters', 'Customers who primarily purchase discounted items', 'behavioral', '{"discount_purchase_ratio": 0.7, "price_sensitivity": "high"}', 0),
(5, 'Premium Shoppers', 'Customers who prefer high-end products', 'behavioral', '{"avg_product_price": 200, "category_preferences": ["luxury", "premium"]}', 0);

-- Insert sample pattern types
INSERT IGNORE INTO `oc_pattern_recognition` (`pattern_type`, `pattern_name`, `pattern_data`, `frequency`, `confidence_score`) VALUES
('seasonal', 'Holiday Shopping Spike', '{"months": [11, 12], "categories": ["gifts", "electronics"], "increase_factor": 2.5}', 12, 0.95),
('behavioral', 'Weekend Shopping Pattern', '{"days": [6, 7], "peak_hours": [10, 11, 14, 15], "categories": ["clothing", "home"]}', 52, 0.88),
('product', 'Complementary Product Pattern', '{"product_pairs": [{"product_a": "laptop", "product_b": "laptop_bag"}, {"product_a": "phone", "product_b": "phone_case"}]}', 100, 0.82);

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS `idx_behavior_events_customer_time` ON `oc_customer_behavior_events` (`customer_id`, `timestamp`);
CREATE INDEX IF NOT EXISTS `idx_similarity_score_desc` ON `oc_product_similarity` (`similarity_score` DESC);
CREATE INDEX IF NOT EXISTS `idx_recommendations_customer_expires` ON `oc_product_recommendations` (`customer_id`, `expires_at`);
CREATE INDEX IF NOT EXISTS `idx_metrics_algorithm_timestamp` ON `oc_recommendation_metrics` (`algorithm_used`, `timestamp`);

-- Add comments to tables for documentation
ALTER TABLE `oc_opencog_atoms` COMMENT = 'Local storage for OpenCog atoms (experimental)';
ALTER TABLE `oc_opencog_links` COMMENT = 'Local storage for OpenCog links between atoms (experimental)';
ALTER TABLE `oc_customer_behavior_events` COMMENT = 'Detailed customer behavior tracking for AI analysis';
ALTER TABLE `oc_customer_segments` COMMENT = 'AI-derived customer segments for targeted marketing';
ALTER TABLE `oc_customer_segment_assignments` COMMENT = 'Assignment of customers to segments with confidence scores';
ALTER TABLE `oc_product_similarity` COMMENT = 'Product similarity matrix for recommendation algorithms';
ALTER TABLE `oc_customer_preferences` COMMENT = 'Learned customer preferences from behavior analysis';
ALTER TABLE `oc_recommendation_tests` COMMENT = 'A/B testing framework for recommendation algorithms';
ALTER TABLE `oc_recommendation_metrics` COMMENT = 'Performance tracking for recommendation algorithms';
ALTER TABLE `oc_cognitive_insights` COMMENT = 'AI-generated insights from OpenCog cognitive analysis';
ALTER TABLE `oc_pattern_recognition` COMMENT = 'Detected patterns in customer and market behavior';
ALTER TABLE `oc_market_trends` COMMENT = 'Market trend analysis and predictions';
ALTER TABLE `oc_recommendation_cache` COMMENT = 'Cache for real-time recommendation results';