# Agoraspace Installation and Setup Guide

## Overview

This guide provides step-by-step instructions for installing and configuring the Agoraspace community marketplace with OpenCog AtomSpace integration in O9Cart.

## Prerequisites

### System Requirements

**Minimum Requirements:**
- PHP 8.0+ with extensions: curl, json, xml, zip, openssl, mysqli
- MySQL 8.0+ or MariaDB 10.3+
- Web server (Apache 2.4+ or Nginx 1.18+)
- 4GB RAM minimum (8GB+ recommended for production)
- 20GB free disk space

**Recommended for Production:**
- 16GB+ RAM for optimal OpenCog performance
- SSD storage for database
- Redis for caching and session storage
- SSL certificate for HTTPS

### OpenCog Requirements

**OpenCog AtomSpace Server:**
- Ubuntu 20.04+ or CentOS 8+ (recommended)
- OpenCog framework installed
- AtomSpace network server running
- Python 3.8+ with OpenCog Python bindings

## Installation Steps

### Step 1: Install OpenCog Framework

#### On Ubuntu/Debian:

```bash
# Install dependencies
sudo apt-get update
sudo apt-get install build-essential cmake cxxtest libboost-all-dev
sudo apt-get install libcogutil-dev libatomspace-dev opencog-dev

# Install OpenCog (if not already installed)
git clone https://github.com/opencog/opencog.git
cd opencog
mkdir build && cd build
cmake ..
make -j$(nproc)
sudo make install
```

#### On CentOS/RHEL:

```bash
# Install dependencies
sudo yum groupinstall "Development Tools"
sudo yum install cmake boost-devel

# Build OpenCog from source (follow OpenCog documentation)
```

### Step 2: Configure OpenCog AtomSpace Server

Create OpenCog server configuration file `/etc/opencog/atomspace-server.conf`:

```scheme
; OpenCog AtomSpace Server Configuration for Agoraspace
(use-modules (opencog))
(use-modules (opencog cogserver))

; Server configuration
(start-cogserver "0.0.0.0" 17001)

; Enable required modules
(load-modules 
    "opencog/nlp/types/nlp-types.scm"
    "opencog/spacetime/spacetime-types.scm"
    "opencog/dynamics/attention/attention-types.scm"
)

; Agoraspace-specific atom types
(define-public MEMBER_NODE (TypeNode "MemberNode"))
(define-public SKILL_NODE (TypeNode "SkillNode"))
(define-public COLLABORATION_NODE (TypeNode "CollaborationNode"))
(define-public REPUTATION_LINK (TypeNode "ReputationLink"))
(define-public KNOWLEDGE_LINK (TypeNode "KnowledgeLink"))
(define-public MARKET_SIGNAL_NODE (TypeNode "MarketSignalNode"))
(define-public AGI_AGENT_NODE (TypeNode "AgiAgentNode"))

; Enable pattern mining
(load-modules "opencog/learning/pattern-index/pattern-index.scm")

; Start the server
(system "echo 'OpenCog AtomSpace Server started for Agoraspace'")
```

Start the OpenCog server:

```bash
# Create systemd service file
sudo tee /etc/systemd/system/opencog-atomspace.service > /dev/null <<EOF
[Unit]
Description=OpenCog AtomSpace Server for Agoraspace
After=network.target

[Service]
Type=simple
User=opencog
Group=opencog
WorkingDirectory=/opt/opencog
ExecStart=/usr/local/bin/guile -l /etc/opencog/atomspace-server.conf
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
EOF

# Create opencog user and start service
sudo useradd -r -s /bin/false opencog
sudo systemctl daemon-reload
sudo systemctl enable opencog-atomspace
sudo systemctl start opencog-atomspace
```

### Step 3: Configure O9Cart for Agoraspace

#### 3.1 Copy Configuration Files

```bash
# Navigate to O9Cart directory
cd /path/to/o9cart

# Copy agoraspace configuration
cp config/agoraspace-config.sample.php config/agoraspace-config.php

# Edit configuration
nano config/agoraspace-config.php
```

#### 3.2 Update Main O9Cart Configuration

Add to your main `config.php`:

```php
// Include Agoraspace configuration
if (file_exists(DIR_CONFIG . 'agoraspace-config.php')) {
    include_once(DIR_CONFIG . 'agoraspace-config.php');
}
```

#### 3.3 Configure Database Connection

Ensure your database user has sufficient privileges:

```sql
-- Grant permissions for agoraspace tables
GRANT ALL PRIVILEGES ON your_database.oc_agoraspace_* TO 'your_user'@'localhost';
FLUSH PRIVILEGES;
```

### Step 4: Initialize Agoraspace Database

#### Option 1: Using Admin Interface

1. Log in to O9Cart admin panel
2. Navigate to **Marketplace > Agoraspace**
3. Click **"Initialize Database"** button
4. Wait for confirmation message

#### Option 2: Using Command Line

```bash
# Navigate to O9Cart directory
cd /path/to/o9cart

# Run database initialization
mysql -u your_user -p your_database < docs/database/agoraspace-schema.sql

# Verify tables were created
mysql -u your_user -p your_database -e "SHOW TABLES LIKE 'oc_agoraspace_%';"
```

### Step 5: Configure Web Server

#### Apache Configuration

Add to your Apache virtual host or `.htaccess`:

```apache
# Enable required modules
LoadModule rewrite_module modules/mod_rewrite.so
LoadModule headers_module modules/mod_headers.so

# Agoraspace specific settings
<Location "/agoraspace">
    # Enable WebSocket proxy for real-time features (future)
    ProxyPass "ws://localhost:8080/ws/"
    ProxyPassReverse "ws://localhost:8080/ws/"
    
    # Security headers
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection "1; mode=block"
</Location>

# PHP settings for Agoraspace
php_value memory_limit 512M
php_value max_execution_time 300
php_value max_input_vars 3000
```

#### Nginx Configuration

Add to your Nginx server block:

```nginx
# Agoraspace location block
location /agoraspace {
    try_files $uri $uri/ /index.php?$query_string;
    
    # Security headers
    add_header X-Content-Type-Options nosniff;
    add_header X-Frame-Options DENY;
    add_header X-XSS-Protection "1; mode=block";
}

# WebSocket support for real-time features (future)
location /ws {
    proxy_pass http://localhost:8080;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "upgrade";
    proxy_set_header Host $host;
}

# PHP-FPM settings for Agoraspace
location ~ \.php$ {
    fastcgi_param PHP_VALUE "memory_limit=512M
                           max_execution_time=300
                           max_input_vars=3000";
    # ... other fastcgi settings
}
```

### Step 6: Set Up Caching (Recommended)

#### Redis Installation and Configuration

```bash
# Install Redis
sudo apt-get install redis-server  # Ubuntu/Debian
# or
sudo yum install redis            # CentOS/RHEL

# Configure Redis for Agoraspace
sudo tee -a /etc/redis/redis.conf > /dev/null <<EOF

# Agoraspace-specific Redis settings
maxmemory 256mb
maxmemory-policy allkeys-lru

# Persistence for important data
save 900 1
save 300 10
save 60 10000

# Security
requirepass your_redis_password
EOF

# Restart Redis
sudo systemctl restart redis
```

#### Configure O9Cart for Redis

Add to your `config/agoraspace-config.php`:

```php
// Redis configuration
define('AGORASPACE_REDIS_ENABLED', true);
define('AGORASPACE_REDIS_HOST', '127.0.0.1');
define('AGORASPACE_REDIS_PORT', 6379);
define('AGORASPACE_REDIS_PASSWORD', 'your_redis_password');
define('AGORASPACE_REDIS_DATABASE', 1);
```

### Step 7: Configure Permissions and Security

#### File Permissions

```bash
# Set proper permissions for O9Cart
sudo chown -R www-data:www-data /path/to/o9cart/
sudo find /path/to/o9cart/ -type d -exec chmod 755 {} \;
sudo find /path/to/o9cart/ -type f -exec chmod 644 {} \;

# Make specific directories writable
sudo chmod -R 777 /path/to/o9cart/upload/system/storage/
sudo chmod -R 777 /path/to/o9cart/upload/system/storage/logs/
sudo chmod -R 777 /path/to/o9cart/upload/image/
```

#### Security Configuration

```php
// Add to config/agoraspace-config.php
define('AGORASPACE_ADMIN_ROLE_REQUIRED', true);
define('AGORASPACE_API_AUTHENTICATION_REQUIRED', true);
define('AGORASPACE_RATE_LIMITING_ENABLED', true);
define('AGORASPACE_ENCRYPT_SENSITIVE_DATA', true);
```

### Step 8: Test Installation

#### 8.1 Test OpenCog Connection

```bash
# Test OpenCog server
telnet localhost 17001

# Should connect and show OpenCog prompt
# Type 'help' to see available commands
# Type 'quit' to exit
```

#### 8.2 Test O9Cart Integration

1. Log in to O9Cart admin panel
2. Navigate to **Marketplace > Agoraspace**
3. Click **"Test Connection"** button
4. Verify "Connected" status is displayed

#### 8.3 Create Test Data

```sql
-- Insert test community member
INSERT INTO oc_agoraspace_members (user_id, username, reputation_score, community_role) 
VALUES (1, 'testuser', 0.75, 'member');

-- Insert test skill
INSERT INTO oc_agoraspace_skills (name, category, demand_level, supply_level) 
VALUES ('OpenCog Development', 'AI/ML', 0.9, 0.2);

-- Verify data
SELECT * FROM oc_agoraspace_members;
SELECT * FROM oc_agoraspace_skills;
```

## Configuration Options

### Basic Configuration

Edit `config/agoraspace-config.php`:

```php
// Enable Agoraspace
define('AGORASPACE_ENABLED', true);

// OpenCog connection
define('AGORASPACE_OPENCOG_URL', 'localhost');
define('AGORASPACE_OPENCOG_PORT', 17001);

// Enable AGI services
define('AGORASPACE_AGI_ENABLED', true);
```

### Advanced Configuration

```php
// Community settings
define('AGORASPACE_OPEN_REGISTRATION', true);
define('AGORASPACE_AUTO_MATCHMAKING_ENABLED', true);
define('AGORASPACE_MAX_COLLABORATION_SIZE', 10);

// Performance settings
define('AGORASPACE_CACHE_ENABLED', true);
define('AGORASPACE_CACHE_TTL', 3600);
define('AGORASPACE_API_RATE_LIMIT', 100);
```

## Monitoring and Maintenance

### Health Checks

Create monitoring script `/usr/local/bin/agoraspace-health-check.sh`:

```bash
#!/bin/bash

# Check OpenCog server
if ! nc -z localhost 17001; then
    echo "ERROR: OpenCog AtomSpace server is not responding"
    exit 1
fi

# Check database tables
TABLES=$(mysql -u your_user -pyour_password your_database -e "SHOW TABLES LIKE 'oc_agoraspace_%';" | wc -l)
if [ $TABLES -lt 8 ]; then
    echo "ERROR: Agoraspace database tables are missing"
    exit 1
fi

# Check Redis (if enabled)
if ! redis-cli -a your_redis_password ping | grep -q PONG; then
    echo "WARNING: Redis cache is not responding"
fi

echo "OK: All Agoraspace services are healthy"
```

### Log Monitoring

Monitor important log files:

```bash
# O9Cart error logs
tail -f /var/log/apache2/error.log | grep -i agoraspace

# OpenCog logs
tail -f /var/log/opencog/atomspace-server.log

# System resource usage
htop
iostat -x 1
```

### Database Maintenance

```sql
-- Optimize agoraspace tables monthly
OPTIMIZE TABLE oc_agoraspace_members;
OPTIMIZE TABLE oc_agoraspace_collaborations;
OPTIMIZE TABLE oc_agoraspace_market_signals;

-- Clean up old market signals
DELETE FROM oc_agoraspace_market_signals 
WHERE status = 'expired' AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);

-- Update reputation scores (run weekly)
UPDATE oc_agoraspace_members 
SET reputation_score = reputation_score * 0.999 
WHERE last_activity < DATE_SUB(NOW(), INTERVAL 7 DAY);
```

## Troubleshooting

### Common Issues

#### 1. OpenCog Connection Failed

**Symptoms:** "AtomSpace server is not accessible" error

**Solutions:**
- Check if OpenCog server is running: `sudo systemctl status opencog-atomspace`
- Verify port 17001 is open: `netstat -tlnp | grep 17001`
- Check firewall rules: `sudo ufw status`
- Verify configuration in `/etc/opencog/atomspace-server.conf`

#### 2. Database Tables Not Found

**Symptoms:** "Table 'oc_agoraspace_members' doesn't exist" error

**Solutions:**
- Run database initialization: Navigate to admin panel and click "Initialize Database"
- Check database permissions: `SHOW GRANTS FOR 'your_user'@'localhost';`
- Manually run schema: `mysql -u your_user -p your_database < docs/database/agoraspace-schema.sql`

#### 3. High Memory Usage

**Symptoms:** Server running out of memory, PHP fatal errors

**Solutions:**
- Increase PHP memory limit: `php_value memory_limit 1024M`
- Optimize OpenCog server configuration
- Enable Redis caching
- Monitor with: `free -h` and `ps aux --sort=-%mem`

#### 4. Slow Performance

**Symptoms:** Slow page loads, timeouts

**Solutions:**
- Enable caching: Set `AGORASPACE_CACHE_ENABLED` to `true`
- Optimize database: Run `OPTIMIZE TABLE` on all agoraspace tables
- Check slow query log
- Consider increasing server resources

### Debug Mode

Enable debug mode for detailed logging:

```php
// In config/agoraspace-config.php
define('AGORASPACE_DEVELOPMENT_MODE', true);
define('AGORASPACE_AGI_DEBUG', true);
define('AGORASPACE_LOG_LEVEL', 'DEBUG');
```

Check debug logs:
```bash
tail -f upload/system/storage/logs/agoraspace.log
```

## Security Considerations

### Production Security Checklist

- [ ] Change default passwords for all services
- [ ] Enable HTTPS with valid SSL certificate
- [ ] Configure firewall to restrict OpenCog server access
- [ ] Enable rate limiting for API endpoints
- [ ] Regular security updates for all components
- [ ] Monitor logs for suspicious activity
- [ ] Use strong authentication for admin access
- [ ] Backup encryption keys securely

### Backup Strategy

```bash
#!/bin/bash
# Backup script for Agoraspace

# Database backup
mysqldump -u your_user -pyour_password your_database > /backup/agoraspace_$(date +%Y%m%d).sql

# OpenCog AtomSpace backup (if persisted)
cp -r /var/lib/opencog/atomspace /backup/atomspace_$(date +%Y%m%d)/

# Configuration backup
tar -czf /backup/agoraspace_config_$(date +%Y%m%d).tar.gz \
    config/agoraspace-config.php \
    /etc/opencog/atomspace-server.conf
```

## Support and Resources

### Documentation
- [OpenCog Documentation](https://wiki.opencog.org/)
- [AtomSpace Tutorial](https://wiki.opencog.org/w/AtomSpace)
- [O9Cart Developer Guide](docs/developer-guide.md)

### Community
- [OpenCog Forum](https://groups.google.com/forum/#!forum/opencog)
- [O9Cart Community](https://community.opencart.com/)

### Professional Support
For professional installation, configuration, and support services, contact the O9Cart development team.

## Conclusion

You have successfully installed and configured Agoraspace community marketplace with OpenCog AtomSpace integration. The system is now ready for community members to participate in collaborative intelligence networks and benefit from ambient AGI services.

Remember to monitor system performance, keep all components updated, and engage with the community to maximize the benefits of this cognitive marketplace platform.