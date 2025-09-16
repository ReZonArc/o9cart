<?php
/**
 * O9Cart Integration Hub Configuration
 * 
 * Sample configuration file for integration features
 * Copy to integration-config.php and customize for your environment
 * 
 * @category   Configuration
 * @package    Integration
 * @author     O9Cart Development Team
 * @license    GPL v3
 */

// ================================
// INTEGRATION HUB SETTINGS
// ================================

// Enable/disable integration features
define('INTEGRATION_ENABLED', true);
define('INTEGRATION_DEBUG', false);

// Integration directories
define('DIR_INTEGRATION', DIR_SYSTEM . 'library/integration/');
define('DIR_INTELLIGENCE', DIR_SYSTEM . 'library/intelligence/');

// ================================
// DATABASE SETTINGS
// ================================

// Table prefix for integration tables (usually same as main O9Cart prefix)
define('INTEGRATION_DB_PREFIX', DB_PREFIX);

// ================================
// GNUCASH INTEGRATION
// ================================

// Default GnuCash settings
define('GNUCASH_DEFAULT_FILE_PATH', '/path/to/gnucash/file.gnucash');
define('GNUCASH_BACKUP_DIR', DIR_STORAGE . 'gnucash_backups/');
define('GNUCASH_SYNC_FREQUENCY', 'daily'); // hourly, daily, weekly

// Account mappings for GnuCash sync
$gnucash_account_mapping = [
    'sales_account' => 'Income:Sales',
    'receivables_account' => 'Assets:Accounts Receivable',
    'inventory_account' => 'Assets:Inventory',
    'cogs_account' => 'Expenses:Cost of Goods Sold',
    'shipping_account' => 'Income:Shipping',
    'tax_account' => 'Liabilities:Sales Tax'
];

// ================================
// ERPNEXT INTEGRATION
// ================================

// ERPNext server settings
define('ERPNEXT_API_URL', 'https://your-erpnext-instance.com');
define('ERPNEXT_API_VERSION', 'v1');
define('ERPNEXT_TIMEOUT', 60); // seconds

// ERPNext authentication
define('ERPNEXT_AUTH_METHOD', 'api_key'); // api_key, oauth2
define('ERPNEXT_API_KEY', ''); // Your API key
define('ERPNEXT_API_SECRET', ''); // Your API secret

// OAuth 2.0 settings (if using OAuth)
define('ERPNEXT_CLIENT_ID', '');
define('ERPNEXT_CLIENT_SECRET', '');
define('ERPNEXT_REDIRECT_URI', '');

// Sync settings
define('ERPNEXT_SYNC_BATCH_SIZE', 100);
define('ERPNEXT_SYNC_INTERVAL', 300); // seconds between syncs
define('ERPNEXT_CONFLICT_RESOLUTION', 'erpnext_wins'); // o9cart_wins, erpnext_wins, manual

// Field mappings for ERPNext sync
$erpnext_field_mapping = [
    'customer' => [
        'customer_name' => 'firstname + " " + lastname',
        'email_id' => 'email',
        'mobile_no' => 'telephone',
        'customer_group' => 'All Customer Groups',
        'territory' => 'All Territories'
    ],
    'item' => [
        'item_name' => 'name',
        'item_code' => 'model',
        'description' => 'description',
        'standard_rate' => 'price',
        'item_group' => 'category_name'
    ]
];

// ================================
// OPENCOG AI FRAMEWORK
// ================================

// OpenCog AtomSpace connection
define('OPENCOG_ENABLED', false); // Set to true when OpenCog is available
define('OPENCOG_ATOMSPACE_URL', 'localhost');
define('OPENCOG_ATOMSPACE_PORT', 17001);
define('OPENCOG_TIMEOUT', 30);

// AI learning settings
define('OPENCOG_ENABLE_LEARNING', true);
define('OPENCOG_LEARNING_BATCH_SIZE', 1000);
define('OPENCOG_MODEL_UPDATE_INTERVAL', 3600); // seconds

// Recommendation engine settings
define('RECOMMENDATION_DEFAULT_ALGORITHM', 'hybrid'); // collaborative, content_based, cognitive, hybrid
define('RECOMMENDATION_CACHE_TTL', 1800); // seconds
define('RECOMMENDATION_MIN_CONFIDENCE', 0.3);
define('RECOMMENDATION_MAX_ITEMS', 20);

// Customer behavior tracking
define('BEHAVIOR_TRACKING_ENABLED', true);
define('BEHAVIOR_TRACKING_ANONYMIZE', true);
define('BEHAVIOR_TRACKING_RETENTION_DAYS', 365);

// ================================
// WEBHOOK SETTINGS
// ================================

// Webhook processing
define('WEBHOOK_ENABLED', true);
define('WEBHOOK_MAX_RETRIES', 3);
define('WEBHOOK_TIMEOUT', 30);
define('WEBHOOK_QUEUE_ENABLED', true);

// Webhook security
define('WEBHOOK_SIGNATURE_ALGORITHM', 'sha256');
define('WEBHOOK_VERIFY_SSL', true);

// Queue settings
define('WEBHOOK_QUEUE_DRIVER', 'database'); // database, redis
define('WEBHOOK_QUEUE_BATCH_SIZE', 10);
define('WEBHOOK_QUEUE_PROCESS_INTERVAL', 60); // seconds

// ================================
// DATA TRANSFORMATION
// ================================

// Data processing limits
define('DATA_PROCESSING_MEMORY_LIMIT', '512M');
define('DATA_PROCESSING_TIME_LIMIT', 300); // seconds
define('DATA_PROCESSING_BATCH_SIZE', 500);

// File handling
define('FILE_UPLOAD_MAX_SIZE', 50 * 1024 * 1024); // 50MB
define('FILE_PROCESSING_TEMP_DIR', sys_get_temp_dir() . '/o9cart_integration/');

// Supported formats
$supported_import_formats = ['csv', 'json', 'xml', 'xlsx'];
$supported_export_formats = ['csv', 'json', 'xml', 'xlsx', 'yaml'];

// ================================
// CACHING SETTINGS
// ================================

// Cache drivers: file, redis, memcached
define('INTEGRATION_CACHE_DRIVER', 'file');
define('INTEGRATION_CACHE_TTL', 3600); // seconds

// Redis settings (if using Redis)
define('INTEGRATION_REDIS_HOST', 'localhost');
define('INTEGRATION_REDIS_PORT', 6379);
define('INTEGRATION_REDIS_PASSWORD', '');
define('INTEGRATION_REDIS_DATABASE', 1);

// ================================
// LOGGING SETTINGS
// ================================

// Log levels: DEBUG, INFO, WARNING, ERROR
define('INTEGRATION_LOG_LEVEL', 'INFO');
define('INTEGRATION_LOG_FILE', DIR_LOGS . 'integration.log');
define('INTEGRATION_LOG_MAX_SIZE', 10 * 1024 * 1024); // 10MB
define('INTEGRATION_LOG_ROTATION', true);

// ================================
// SECURITY SETTINGS
// ================================

// Encryption settings
define('INTEGRATION_ENCRYPTION_METHOD', 'AES-256-CBC');
define('INTEGRATION_ENCRYPTION_KEY', ''); // Set a secure random key

// API rate limiting
define('API_RATE_LIMIT_ENABLED', true);
define('API_RATE_LIMIT_REQUESTS', 1000); // requests per hour
define('API_RATE_LIMIT_WINDOW', 3600); // seconds

// IP whitelist for API access (optional)
$integration_ip_whitelist = [
    // '192.168.1.0/24',
    // '10.0.0.0/8'
];

// ================================
// MONITORING SETTINGS
// ================================

// Health check settings
define('HEALTH_CHECK_ENABLED', true);
define('HEALTH_CHECK_ENDPOINT', '/integration/health');

// Performance monitoring
define('PERFORMANCE_MONITORING_ENABLED', true);
define('PERFORMANCE_SLOW_QUERY_THRESHOLD', 1000); // milliseconds
define('PERFORMANCE_MEMORY_USAGE_ALERT', 80); // percentage

// ================================
// COMPANY/MULTI-STORE SETTINGS
// ================================

// Multi-company support
define('MULTI_COMPANY_ENABLED', true);
define('DEFAULT_COMPANY_CODE', 'MAIN');

// Company configurations
$company_configs = [
    'MAIN' => [
        'name' => 'Main Store',
        'currency' => 'USD',
        'timezone' => 'America/New_York',
        'locale' => 'en_US'
    ],
    'EU' => [
        'name' => 'European Branch',
        'currency' => 'EUR',
        'timezone' => 'Europe/London',
        'locale' => 'en_GB'
    ]
];

// ================================
// FEATURE FLAGS
// ================================

// Enable/disable specific features
$integration_features = [
    'data_sync' => true,
    'webhooks' => true,
    'ai_recommendations' => true,
    'customer_analytics' => true,
    'pattern_recognition' => true,
    'trend_analysis' => true,
    'automated_insights' => false, // Experimental
    'predictive_analytics' => false, // Experimental
    'cognitive_processing' => false // Experimental - requires OpenCog
];

// ================================
// MAINTENANCE SETTINGS
// ================================

// Cleanup settings
define('CLEANUP_OLD_LOGS_DAYS', 30);
define('CLEANUP_OLD_WEBHOOKS_DAYS', 7);
define('CLEANUP_OLD_SYNC_JOBS_DAYS', 14);
define('CLEANUP_OLD_ANALYTICS_DAYS', 90);

// Model retraining intervals
define('MODEL_RETRAIN_INTERVAL', 86400); // seconds (daily)
define('MODEL_RETRAIN_MIN_DATA_POINTS', 100);

// ================================
// NOTIFICATION SETTINGS
// ================================

// Email notifications for critical events
define('NOTIFICATION_EMAIL_ENABLED', false);
define('NOTIFICATION_EMAIL_TO', 'admin@yourstore.com');
define('NOTIFICATION_EMAIL_FROM', 'noreply@yourstore.com');

// Slack notifications (optional)
define('NOTIFICATION_SLACK_ENABLED', false);
define('NOTIFICATION_SLACK_WEBHOOK_URL', '');
define('NOTIFICATION_SLACK_CHANNEL', '#integrations');

// ================================
// CUSTOM EXTENSIONS
// ================================

// Custom connector directories
$custom_connector_paths = [
    DIR_INTEGRATION . 'custom/connectors/',
    DIR_APPLICATION . 'extensions/integration/connectors/'
];

// Custom format handlers
$custom_format_handlers = [
    'custom_csv' => 'CustomCsvHandler',
    'proprietary_xml' => 'ProprietaryXmlHandler'
];

// ================================
// DEVELOPMENT/DEBUG SETTINGS
// ================================

if (INTEGRATION_DEBUG) {
    // Enable verbose logging in debug mode
    define('INTEGRATION_LOG_LEVEL', 'DEBUG');
    
    // Disable caching in debug mode
    define('INTEGRATION_CACHE_TTL', 0);
    
    // Enable query logging
    define('INTEGRATION_LOG_QUERIES', true);
    
    // Reduce batch sizes for easier debugging
    define('DATA_PROCESSING_BATCH_SIZE', 10);
}

// ================================
// VALIDATION AND SETUP
// ================================

// Validate required directories exist
$required_dirs = [
    DIR_INTEGRATION,
    DIR_INTELLIGENCE,
    FILE_PROCESSING_TEMP_DIR,
    dirname(INTEGRATION_LOG_FILE)
];

foreach ($required_dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Validate required extensions
$required_extensions = ['curl', 'json', 'xml', 'zip', 'openssl', 'mbstring'];
foreach ($required_extensions as $ext) {
    if (!extension_loaded($ext)) {
        trigger_error("Required PHP extension not loaded: {$ext}", E_USER_WARNING);
    }
}

// Set memory limit for integration operations
if (DATA_PROCESSING_MEMORY_LIMIT) {
    ini_set('memory_limit', DATA_PROCESSING_MEMORY_LIMIT);
}

// Set time limit for long-running operations
if (DATA_PROCESSING_TIME_LIMIT) {
    set_time_limit(DATA_PROCESSING_TIME_LIMIT);
}