# O9Cart Integration Hub Setup Guide

## Overview

This guide provides step-by-step instructions for setting up and configuring the O9Cart Integration Hub, including multi-store synchronization with GnuCash and ERPNext, and the experimental OpenCog intelligence framework.

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Database Setup](#database-setup)
3. [Integration Hub Configuration](#integration-hub-configuration)
4. [GnuCash Integration Setup](#gnucash-integration-setup)
5. [ERPNext Integration Setup](#erpnext-integration-setup)
6. [OpenCog Framework Setup](#opencog-framework-setup)
7. [Webhook Configuration](#webhook-configuration)
8. [Testing and Validation](#testing-and-validation)
9. [Maintenance and Monitoring](#maintenance-and-monitoring)
10. [Troubleshooting](#troubleshooting)

## Prerequisites

### System Requirements

- **PHP**: 8.0+ with extensions: curl, json, xml, zip, openssl, mbstring
- **Database**: MySQL 8.0+ or MariaDB 10.3+
- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **Memory**: 512MB RAM minimum (1GB+ recommended for AI features)
- **Storage**: 1GB disk space minimum
- **Python**: 3.8+ (for OpenCog integration, optional)

### Required PHP Extensions

```bash
# Check required extensions
php -m | grep -E "(curl|json|xml|zip|openssl|mbstring|mysqli|pdo_mysql)"
```

### Optional Dependencies

- **Redis**: For caching and job queues (recommended for production)
- **Elasticsearch**: For advanced search capabilities
- **OpenCog**: For experimental AI features

## Database Setup

### 1. Install Integration Schema

Execute the SQL files to create the required database tables:

```sql
-- Core integration tables
SOURCE docs/database/integration-schema.sql;

-- OpenCog and AI tables
SOURCE docs/database/opencog-schema.sql;
```

### 2. Verify Installation

Check that all tables were created successfully:

```sql
SHOW TABLES LIKE 'oc_integration_%';
SHOW TABLES LIKE 'oc_opencog_%';
SHOW TABLES LIKE 'oc_customer_%';
```

Expected tables:
- `oc_integration_config`
- `oc_sync_jobs`
- `oc_data_mapping`
- `oc_companies`
- `oc_company_mappings`
- `oc_webhooks`
- `oc_webhook_deliveries`
- `oc_external_credentials`
- `oc_opencog_atoms`
- `oc_opencog_links`
- And more...

## Integration Hub Configuration

### 1. Enable Integration Module

Add the integration library path to your O9Cart configuration:

```php
// config.php
define('DIR_INTEGRATION', DIR_SYSTEM . 'library/integration/');
define('DIR_INTELLIGENCE', DIR_SYSTEM . 'library/intelligence/');
```

### 2. Configure Admin Access

Add integration permissions to admin user groups:

```sql
INSERT INTO oc_user_group_permission (user_group_id, permission, value) VALUES
(1, 'integration/hub', 1),
(1, 'integration/sync', 1),
(1, 'intelligence/recommendations', 1);
```

### 3. Set Up Caching (Optional but Recommended)

Configure Redis for improved performance:

```php
// config.php
define('CACHE_DRIVER', 'redis');
define('CACHE_HOSTNAME', 'localhost');
define('CACHE_PORT', 6379);
define('CACHE_PREFIX', 'o9cart_');
```

## GnuCash Integration Setup

### 1. Prepare GnuCash File

Ensure your GnuCash file is accessible:

- **XML Format**: `.gnucash` or `.xml` files
- **SQLite Format**: `.sqlite` or `.db` files

### 2. Configure Integration

1. Access Admin Panel → Extensions → Integration Hub
2. Click "Add New Integration"
3. Fill in the details:

```
Name: GnuCash Integration
Type: Sync
Status: Active
Configuration:
{
    "gnucash_file_path": "/path/to/your/file.gnucash",
    "sync_frequency": "daily",
    "account_mapping": {
        "sales_account": "Income:Sales",
        "receivables_account": "Assets:Accounts Receivable"
    },
    "auto_create_customers": true,
    "sync_transactions": true
}
```

### 3. Test Connection

Click "Test Connection" to verify the integration works properly.

### 4. Configure Sync Jobs

Set up automated synchronization:

- **Import Accounts**: Daily at 2:00 AM
- **Import Transactions**: Every 4 hours
- **Export Orders**: Every hour

## ERPNext Integration Setup

### 1. ERPNext API Setup

In your ERPNext instance:

1. Go to User → API Access
2. Generate API Key and Secret
3. Note the server URL

### 2. Configure Integration

```json
{
    "api_url": "https://your-erpnext-instance.com",
    "api_key": "your_api_key_here",
    "api_secret": "your_api_secret_here",
    "sync_entities": ["customers", "items", "orders"],
    "sync_direction": "both",
    "conflict_resolution": "erpnext_wins",
    "field_mappings": {
        "customer_name": "firstname + ' ' + lastname",
        "email_id": "email",
        "mobile_no": "telephone"
    }
}
```

### 3. Set Up OAuth 2.0 (Recommended)

For enhanced security, configure OAuth 2.0:

1. Create OAuth App in ERPNext
2. Configure redirect URI: `https://your-o9cart-domain.com/admin/integration/erpnext/callback`
3. Update integration config with OAuth credentials

### 4. Test and Validate

1. Test API connection
2. Run initial customer sync
3. Verify data mapping accuracy

## OpenCog Framework Setup (Experimental)

### 1. Install OpenCog (Optional)

For full OpenCog integration:

```bash
# Ubuntu/Debian
sudo apt-get install opencog

# Or build from source
git clone https://github.com/opencog/opencog.git
cd opencog
mkdir build && cd build
cmake ..
make -j4
sudo make install
```

### 2. Configure AtomSpace Connection

```php
// config.php
define('OPENCOG_ATOMSPACE_URL', 'localhost');
define('OPENCOG_ATOMSPACE_PORT', 17001);
define('OPENCOG_ENABLE_LEARNING', true);
```

### 3. Initialize AI Models

Run the model training:

```php
// Via admin interface or command line
$recommendation_engine = new RecommendationEngine($registry);
$recommendation_engine->trainModels([
    'collaborative_filtering' => true,
    'content_based' => true,
    'cognitive_learning' => true
]);
```

### 4. Configure Customer Behavior Tracking

Add tracking code to your frontend:

```javascript
// Track customer behavior
function trackBehavior(eventType, eventData) {
    fetch('/api/track-behavior', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            event_type: eventType,
            event_data: eventData,
            timestamp: new Date().toISOString()
        })
    });
}

// Example usage
trackBehavior('product_view', {product_id: 123});
trackBehavior('add_to_cart', {product_id: 123, quantity: 1});
```

## Webhook Configuration

### 1. Set Up Webhook Endpoints

Configure webhooks for real-time synchronization:

```json
{
    "name": "ERPNext Customer Sync",
    "url": "https://your-erpnext.com/api/method/custom.hooks.customer_updated",
    "method": "POST",
    "events": ["customer.created", "customer.updated"],
    "headers": {
        "Authorization": "Bearer your_token_here",
        "Content-Type": "application/json"
    },
    "secret": "your_webhook_secret",
    "retry_attempts": 3,
    "timeout": 30
}
```

### 2. Webhook Security

Verify webhook signatures in your endpoint:

```php
function verifyWebhookSignature($payload, $signature, $secret) {
    $expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);
    return hash_equals($expected, $signature);
}
```

### 3. Set Up Webhook Queue Processing

Configure cron job for webhook processing:

```bash
# Add to crontab
*/5 * * * * /usr/bin/php /path/to/o9cart/webhook-processor.php
```

## Testing and Validation

### 1. Integration Testing Checklist

- [ ] Database tables created successfully
- [ ] Admin interface accessible
- [ ] GnuCash connection test passes
- [ ] ERPNext API authentication works
- [ ] Webhook delivery successful
- [ ] Data transformation rules working
- [ ] Customer sync bidirectional
- [ ] Product recommendations generating
- [ ] Error logging functional

### 2. Data Validation

Run validation scripts:

```php
// Validate data consistency
$validator = new IntegrationValidator($registry);
$results = $validator->validateAll();

foreach ($results as $test => $result) {
    echo $test . ': ' . ($result['passed'] ? 'PASS' : 'FAIL') . "\n";
    if (!$result['passed']) {
        echo 'Errors: ' . implode(', ', $result['errors']) . "\n";
    }
}
```

### 3. Performance Testing

Monitor system performance:

- Database query optimization
- Memory usage during sync operations
- API response times
- Webhook delivery latency

## Maintenance and Monitoring

### 1. Regular Maintenance Tasks

Set up cron jobs for maintenance:

```bash
# Daily tasks
0 2 * * * /usr/bin/php /path/to/o9cart/maintenance/cleanup-old-logs.php
0 3 * * * /usr/bin/php /path/to/o9cart/maintenance/update-recommendations.php

# Weekly tasks
0 1 * * 0 /usr/bin/php /path/to/o9cart/maintenance/retrain-models.php
0 2 * * 0 /usr/bin/php /path/to/o9cart/maintenance/cleanup-webhook-deliveries.php
```

### 2. Monitoring and Alerts

Monitor key metrics:

- Sync job success rates
- API error rates
- Webhook delivery success
- Database performance
- Customer behavior anomalies

### 3. Log Management

Configure log rotation and monitoring:

```bash
# logrotate configuration
/var/log/o9cart/integration.log {
    daily
    rotate 30
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
}
```

## Troubleshooting

### Common Issues

#### 1. Database Connection Errors

```
Error: Table 'oc_integration_config' doesn't exist
```

**Solution**: Run the database schema installation scripts.

#### 2. API Authentication Failures

```
Error: ERPNext API authentication failed
```

**Solutions**:
- Verify API credentials
- Check server URL format
- Ensure API user has proper permissions
- Check firewall/network connectivity

#### 3. Webhook Delivery Failures

```
Error: Webhook delivery failed with HTTP 500
```

**Solutions**:
- Check endpoint URL accessibility
- Verify webhook signature
- Review server logs on receiving end
- Check network connectivity

#### 4. Memory Issues During Sync

```
Fatal error: Allowed memory size exhausted
```

**Solutions**:
- Increase PHP memory limit
- Process data in smaller batches
- Optimize database queries
- Enable data streaming for large datasets

#### 5. OpenCog Connection Issues

```
Warning: OpenCog AtomSpace connection failed
```

**Solutions**:
- Verify OpenCog installation
- Check service status
- Review network configuration
- Fall back to local AI processing

### Debug Mode

Enable debug logging:

```php
// config.php
define('INTEGRATION_DEBUG', true);
define('LOG_LEVEL', 'DEBUG');
```

### Support and Resources

- **Documentation**: `/docs/` directory
- **API Reference**: `/docs/api-reference.md`
- **Architecture Guide**: `/docs/architecture.md`
- **Community Forum**: Create GitHub issues for support
- **Professional Support**: Contact O9Cart development team

## Security Considerations

### 1. API Security

- Use HTTPS for all API communications
- Implement rate limiting
- Regularly rotate API credentials
- Monitor for suspicious activity

### 2. Database Security

- Use encrypted connections
- Implement proper access controls
- Regular security updates
- Backup encryption

### 3. Webhook Security

- Verify signatures on all incoming webhooks
- Use HTTPS endpoints only
- Implement timeout handling
- Log all webhook activities

## Performance Optimization

### 1. Database Optimization

- Regular index maintenance
- Query optimization
- Connection pooling
- Read replicas for heavy operations

### 2. Caching Strategy

- Implement Redis for session caching
- Cache recommendation results
- API response caching
- Database query result caching

### 3. Async Processing

- Use job queues for heavy operations
- Batch processing for bulk operations
- Background sync for non-critical data
- Webhook queue processing

## Conclusion

The O9Cart Integration Hub provides a powerful foundation for connecting your e-commerce platform with external systems and leveraging AI-powered features. Follow this guide carefully, test thoroughly, and monitor continuously for optimal results.

For advanced configurations and custom development, refer to the technical documentation and consider engaging with the O9Cart development community.