<?php
/**
 * Agoraspace Community Marketplace Configuration
 * 
 * Configuration file for OpenCog AtomSpace-powered community marketplace
 * Copy to agoraspace-config.php and customize for your environment
 * 
 * @category   Configuration
 * @package    Agoraspace
 * @author     O9Cart Development Team
 * @license    GPL v3
 */

// ================================
// AGORASPACE CORE SETTINGS
// ================================

// Enable/disable agoraspace functionality
define('AGORASPACE_ENABLED', false); // Set to true when ready to use

// Agoraspace directories
define('DIR_AGORASPACE', DIR_SYSTEM . 'library/intelligence/opencog/');
define('DIR_AGORASPACE_TEMPLATES', DIR_APPLICATION . 'view/template/agoraspace/');

// ================================
// OPENCOG ATOMSPACE SETTINGS
// ================================

// OpenCog AtomSpace server connection
define('AGORASPACE_OPENCOG_URL', 'localhost');
define('AGORASPACE_OPENCOG_PORT', 17001);
define('AGORASPACE_OPENCOG_TIMEOUT', 30);
define('AGORASPACE_OPENCOG_RETRY_ATTEMPTS', 3);
define('AGORASPACE_OPENCOG_RETRY_DELAY', 5); // seconds

// AtomSpace authentication (if required)
define('AGORASPACE_OPENCOG_AUTH_ENABLED', false);
define('AGORASPACE_OPENCOG_USERNAME', '');
define('AGORASPACE_OPENCOG_PASSWORD', '');
define('AGORASPACE_OPENCOG_API_KEY', '');

// ================================
// AGI SERVICES SETTINGS
// ================================

// Enable autonomous AGI services
define('AGORASPACE_AGI_ENABLED', true);
define('AGORASPACE_AGI_DEBUG', false);

// Ambient services configuration
define('AGORASPACE_AMBIENT_SERVICES', [
    'MarketTrendAnalyzer' => true,
    'CollaborationMatchmaker' => true,
    'SkillGapIdentifier' => true,
    'CommunityModerator' => false, // Requires careful configuration
    'KnowledgeEvolutionTracker' => true,
    'EmergentBehaviorDetector' => true
]);

// AGI learning and adaptation settings
define('AGORASPACE_LEARNING_ENABLED', true);
define('AGORASPACE_LEARNING_BATCH_SIZE', 100);
define('AGORASPACE_LEARNING_INTERVAL', 3600); // seconds (1 hour)
define('AGORASPACE_PATTERN_MINING_ENABLED', true);
define('AGORASPACE_COGNITIVE_REASONING_ENABLED', true);

// ================================
// COMMUNITY MARKETPLACE SETTINGS
// ================================

// Member registration and profiles
define('AGORASPACE_OPEN_REGISTRATION', true);
define('AGORASPACE_REQUIRE_SKILL_VERIFICATION', false);
define('AGORASPACE_MIN_REPUTATION_THRESHOLD', 0.1);
define('AGORASPACE_DEFAULT_REPUTATION_SCORE', 0.5);

// Collaboration settings
define('AGORASPACE_MAX_COLLABORATION_SIZE', 10);
define('AGORASPACE_MIN_COLLABORATION_SIZE', 2);
define('AGORASPACE_AUTO_MATCHMAKING_ENABLED', true);
define('AGORASPACE_COLLABORATION_SUCCESS_THRESHOLD', 0.6);

// Skill ecosystem settings
define('AGORASPACE_SKILL_DEMAND_CALCULATION_INTERVAL', 86400); // daily
define('AGORASPACE_SKILL_ENDORSEMENT_WEIGHT', 0.3);
define('AGORASPACE_SKILL_VERIFICATION_BONUS', 0.2);

// ================================
// REPUTATION AND TRUST SYSTEM
// ================================

// Reputation calculation parameters
define('AGORASPACE_REPUTATION_DECAY_RATE', 0.95); // 5% decay per month
define('AGORASPACE_REPUTATION_BOOST_FACTOR', 1.1);
define('AGORASPACE_TRUST_NETWORK_DEPTH', 3); // degrees of separation
define('AGORASPACE_MIN_TRUST_SCORE', 0.0);
define('AGORASPACE_MAX_TRUST_SCORE', 1.0);

// Reputation sources and weights
define('AGORASPACE_REPUTATION_WEIGHTS', [
    'peer_feedback' => 0.4,
    'collaboration_success' => 0.3,
    'skill_verification' => 0.2,
    'community_contribution' => 0.1
]);

// ================================
// MARKET INTELLIGENCE SETTINGS
// ================================

// Market signal detection
define('AGORASPACE_SIGNAL_DETECTION_ENABLED', true);
define('AGORASPACE_SIGNAL_CONFIDENCE_THRESHOLD', 0.7);
define('AGORASPACE_SIGNAL_STRENGTH_THRESHOLD', 0.5);
define('AGORASPACE_SIGNAL_EXPIRY_TIME', 168); // hours (1 week)

// Market signal types to monitor
define('AGORASPACE_MONITORED_SIGNALS', [
    'demand_spike' => true,
    'supply_shortage' => true,
    'price_anomaly' => true,
    'trend_emergence' => true,
    'skill_gap' => true,
    'collaboration_potential' => true,
    'knowledge_gap' => true
]);

// Opportunity detection settings
define('AGORASPACE_OPPORTUNITY_DETECTION_INTERVAL', 3600); // hourly
define('AGORASPACE_OPPORTUNITY_MIN_VALUE', 100); // minimum estimated value
define('AGORASPACE_OPPORTUNITY_NOTIFICATION_ENABLED', true);

// ================================
// KNOWLEDGE MANAGEMENT
// ================================

// Knowledge contribution settings
define('AGORASPACE_KNOWLEDGE_SHARING_ENABLED', true);
define('AGORASPACE_KNOWLEDGE_QUALITY_THRESHOLD', 0.6);
define('AGORASPACE_KNOWLEDGE_VERIFICATION_REQUIRED', false);
define('AGORASPACE_KNOWLEDGE_EVOLUTION_TRACKING', true);

// Content moderation
define('AGORASPACE_AUTO_MODERATION_ENABLED', false); // Use with caution
define('AGORASPACE_CONTENT_QUALITY_THRESHOLD', 0.7);
define('AGORASPACE_SPAM_DETECTION_ENABLED', true);

// ================================
// GOVERNANCE AND MODERATION
// ================================

// Community governance settings
define('AGORASPACE_DEMOCRATIC_GOVERNANCE', true);
define('AGORASPACE_VOTING_THRESHOLD', 0.6); // 60% consensus required
define('AGORASPACE_MODERATOR_RATIO', 0.05); // 5% of community can be moderators

// Dispute resolution
define('AGORASPACE_AUTOMATED_DISPUTE_RESOLUTION', false);
define('AGORASPACE_DISPUTE_ESCALATION_THRESHOLD', 72); // hours
define('AGORASPACE_MEDIATION_TIMEOUT', 168); // hours

// ================================
// PERFORMANCE AND SCALING
// ================================

// Caching settings
define('AGORASPACE_CACHE_ENABLED', true);
define('AGORASPACE_CACHE_TTL', 3600); // 1 hour
define('AGORASPACE_CACHE_PREFIX', 'agoraspace_');

// Rate limiting
define('AGORASPACE_API_RATE_LIMIT', 100); // requests per minute
define('AGORASPACE_ATOMSPACE_QUERY_LIMIT', 50); // concurrent queries
define('AGORASPACE_BATCH_PROCESSING_SIZE', 1000);

// Performance monitoring
define('AGORASPACE_PERFORMANCE_MONITORING', true);
define('AGORASPACE_SLOW_QUERY_THRESHOLD', 5.0); // seconds
define('AGORASPACE_MEMORY_LIMIT_WARNING', 512); // MB

// ================================
// SECURITY SETTINGS
// ================================

// Access control
define('AGORASPACE_ADMIN_ROLE_REQUIRED', true);
define('AGORASPACE_API_AUTHENTICATION_REQUIRED', true);
define('AGORASPACE_RATE_LIMITING_ENABLED', true);

// Data protection
define('AGORASPACE_ENCRYPT_SENSITIVE_DATA', true);
define('AGORASPACE_DATA_RETENTION_PERIOD', 2555); // days (7 years)
define('AGORASPACE_ANONYMIZE_OLD_DATA', true);

// Privacy settings
define('AGORASPACE_DEFAULT_PRIVACY_LEVEL', 'community'); // public, community, private
define('AGORASPACE_ALLOW_PROFILE_VISIBILITY_CONTROL', true);
define('AGORASPACE_ANONYMIZE_PUBLIC_DATA', false);

// ================================
// LOGGING AND MONITORING
// ================================

// Logging configuration
define('AGORASPACE_LOGGING_ENABLED', true);
define('AGORASPACE_LOG_LEVEL', 'INFO'); // DEBUG, INFO, WARN, ERROR
define('AGORASPACE_LOG_RETENTION_DAYS', 30);
define('AGORASPACE_LOG_ATOMSPACE_QUERIES', false); // Can be verbose

// Monitoring and alerts
define('AGORASPACE_MONITORING_ENABLED', true);
define('AGORASPACE_HEALTH_CHECK_INTERVAL', 300); // seconds
define('AGORASPACE_ALERT_EMAIL', 'admin@yourstore.com');
define('AGORASPACE_ALERT_WEBHOOK_URL', ''); // Optional Slack/Discord webhook

// Metrics collection
define('AGORASPACE_METRICS_COLLECTION_ENABLED', true);
define('AGORASPACE_METRICS_RETENTION_DAYS', 365);
define('AGORASPACE_ANONYMIZE_METRICS', true);

// ================================
// EXPERIMENTAL FEATURES
// ================================

// Emergent behavior detection
define('AGORASPACE_EMERGENT_BEHAVIOR_DETECTION', false); // Experimental
define('AGORASPACE_BEHAVIORAL_PATTERN_MINING', false);   // Experimental
define('AGORASPACE_PREDICTIVE_MODELING', false);         // Experimental

// Advanced AGI features
define('AGORASPACE_AUTONOMOUS_AGENTS', false);           // Experimental
define('AGORASPACE_SELF_MODIFYING_CODE', false);         // Very experimental
define('AGORASPACE_COGNITIVE_ARCHITECTURES', false);     // Research phase

// Blockchain integration (future)
define('AGORASPACE_BLOCKCHAIN_ENABLED', false);          // Future feature
define('AGORASPACE_SMART_CONTRACTS', false);             // Future feature
define('AGORASPACE_DECENTRALIZED_GOVERNANCE', false);    // Future feature

// ================================
// DEVELOPMENT AND TESTING
// ================================

// Development mode settings
define('AGORASPACE_DEVELOPMENT_MODE', false);
define('AGORASPACE_MOCK_ATOMSPACE', false); // Use mock instead of real AtomSpace
define('AGORASPACE_GENERATE_TEST_DATA', false);
define('AGORASPACE_DISABLE_EXTERNAL_APIS', false);

// Testing configuration
define('AGORASPACE_TESTING_ENABLED', false);
define('AGORASPACE_UNIT_TESTS_ENABLED', true);
define('AGORASPACE_INTEGRATION_TESTS_ENABLED', true);
define('AGORASPACE_LOAD_TESTING_ENABLED', false);

?>