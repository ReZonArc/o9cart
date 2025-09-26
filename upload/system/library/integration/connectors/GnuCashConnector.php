<?php
/**
 * GnuCash Connector
 * 
 * Handles integration with GnuCash accounting software
 * Supports both XML file format and SQLite database format
 * 
 * @category   Library
 * @package    Integration
 * @subpackage Connectors
 * @author     O9Cart Development Team
 * @license    GPL v3
 */

namespace O9Cart\Integration\Connectors;

class GnuCashConnector {
    
    private $db;
    private $log;
    private $config;
    
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
     * Test connection to GnuCash
     * 
     * @param array $config_data
     * @return array
     */
    public function testConnection($config_data) {
        try {
            $file_path = $config_data['gnucash_file_path'] ?? '';
            
            if (empty($file_path)) {
                return ['success' => false, 'error' => 'GnuCash file path not specified'];
            }
            
            if (!file_exists($file_path)) {
                return ['success' => false, 'error' => 'GnuCash file not found: ' . $file_path];
            }
            
            // Determine file type and test accordingly
            $file_extension = pathinfo($file_path, PATHINFO_EXTENSION);
            
            if ($file_extension === 'gnucash' || $file_extension === 'xml') {
                return $this->testXmlFile($file_path);
            } elseif ($file_extension === 'sqlite' || $file_extension === 'db') {
                return $this->testSqliteFile($file_path);
            } else {
                return ['success' => false, 'error' => 'Unsupported file format: ' . $file_extension];
            }
            
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
        $file_path = $config_data['gnucash_file_path'] ?? '';
        $file_extension = pathinfo($file_path, PATHINFO_EXTENSION);
        
        switch ($job_type) {
            case 'import_accounts':
                return $this->importAccounts($file_path, $file_extension, $config_data);
                
            case 'import_transactions':
                return $this->importTransactions($file_path, $file_extension, $config_data, $options);
                
            case 'export_transactions':
                return $this->exportTransactions($file_path, $file_extension, $config_data, $options);
                
            case 'sync_customers':
                return $this->syncCustomers($file_path, $file_extension, $config_data);
                
            default:
                throw new Exception("Unsupported sync job type: {$job_type}");
        }
    }
    
    /**
     * Test XML file format
     * 
     * @param string $file_path
     * @return array
     */
    private function testXmlFile($file_path) {
        try {
            $xml = simplexml_load_file($file_path);
            
            if ($xml === false) {
                return ['success' => false, 'error' => 'Invalid XML file format'];
            }
            
            // Check for GnuCash namespace
            $namespaces = $xml->getNamespaces(true);
            if (!isset($namespaces['gnc'])) {
                return ['success' => false, 'error' => 'Not a valid GnuCash XML file'];
            }
            
            return [
                'success' => true,
                'info' => [
                    'format' => 'XML',
                    'version' => (string) $xml->version,
                    'namespaces' => array_keys($namespaces)
                ]
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'XML parsing error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Test SQLite file format
     * 
     * @param string $file_path
     * @return array
     */
    private function testSqliteFile($file_path) {
        try {
            $sqlite = new PDO("sqlite:{$file_path}");
            $sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Check for GnuCash tables
            $tables = $sqlite->query("SELECT name FROM sqlite_master WHERE type='table'");
            $table_names = $tables->fetchAll(PDO::FETCH_COLUMN);
            
            $required_tables = ['accounts', 'transactions', 'splits'];
            $missing_tables = array_diff($required_tables, $table_names);
            
            if (!empty($missing_tables)) {
                return [
                    'success' => false, 
                    'error' => 'Missing required tables: ' . implode(', ', $missing_tables)
                ];
            }
            
            return [
                'success' => true,
                'info' => [
                    'format' => 'SQLite',
                    'tables' => $table_names
                ]
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'SQLite error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Import chart of accounts from GnuCash
     * 
     * @param string $file_path
     * @param string $file_extension
     * @param array $config_data
     * @return array
     */
    private function importAccounts($file_path, $file_extension, $config_data) {
        if ($file_extension === 'gnucash' || $file_extension === 'xml') {
            return $this->importAccountsFromXml($file_path, $config_data);
        } else {
            return $this->importAccountsFromSqlite($file_path, $config_data);
        }
    }
    
    /**
     * Import accounts from XML file
     * 
     * @param string $file_path
     * @param array $config_data
     * @return array
     */
    private function importAccountsFromXml($file_path, $config_data) {
        $xml = simplexml_load_file($file_path);
        $namespaces = $xml->getNamespaces(true);
        
        $accounts_imported = 0;
        $accounts_updated = 0;
        
        // Parse accounts from XML
        foreach ($xml->xpath('//gnc:account') as $account) {
            $account->registerXPathNamespace('gnc', $namespaces['gnc']);
            $account->registerXPathNamespace('act', $namespaces['act']);
            
            $account_data = [
                'guid' => (string) $account->{'act:id'},
                'name' => (string) $account->{'act:name'},
                'type' => (string) $account->{'act:type'},
                'description' => (string) $account->{'act:description'},
                'code' => (string) $account->{'act:code'},
                'parent_guid' => null
            ];
            
            // Get parent account if exists
            $parent = $account->xpath('act:parent');
            if (!empty($parent)) {
                $account_data['parent_guid'] = (string) $parent[0];
            }
            
            // Check if account already exists
            $existing = $this->db->query(
                "SELECT account_id FROM " . DB_PREFIX . "gnucash_accounts WHERE guid = ?",
                [$account_data['guid']]
            );
            
            if ($existing->num_rows) {
                // Update existing account
                $this->db->query(
                    "UPDATE " . DB_PREFIX . "gnucash_accounts 
                     SET name = ?, type = ?, description = ?, code = ?, parent_guid = ?, updated_at = NOW() 
                     WHERE guid = ?",
                    [
                        $account_data['name'],
                        $account_data['type'],
                        $account_data['description'],
                        $account_data['code'],
                        $account_data['parent_guid'],
                        $account_data['guid']
                    ]
                );
                $accounts_updated++;
            } else {
                // Insert new account
                $this->db->query(
                    "INSERT INTO " . DB_PREFIX . "gnucash_accounts 
                     (guid, name, type, description, code, parent_guid) 
                     VALUES (?, ?, ?, ?, ?, ?)",
                    [
                        $account_data['guid'],
                        $account_data['name'],
                        $account_data['type'],
                        $account_data['description'],
                        $account_data['code'],
                        $account_data['parent_guid']
                    ]
                );
                $accounts_imported++;
            }
        }
        
        $this->log->write("GnuCash accounts import completed: {$accounts_imported} new, {$accounts_updated} updated");
        
        return [
            'success' => true,
            'total_records' => $accounts_imported + $accounts_updated,
            'imported' => $accounts_imported,
            'updated' => $accounts_updated
        ];
    }
    
    /**
     * Import accounts from SQLite database
     * 
     * @param string $file_path
     * @param array $config_data
     * @return array
     */
    private function importAccountsFromSqlite($file_path, $config_data) {
        $sqlite = new PDO("sqlite:{$file_path}");
        $sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $accounts = $sqlite->query("SELECT * FROM accounts");
        
        $accounts_imported = 0;
        $accounts_updated = 0;
        
        foreach ($accounts as $account) {
            // Check if account already exists
            $existing = $this->db->query(
                "SELECT account_id FROM " . DB_PREFIX . "gnucash_accounts WHERE guid = ?",
                [$account['guid']]
            );
            
            if ($existing->num_rows) {
                // Update existing account
                $this->db->query(
                    "UPDATE " . DB_PREFIX . "gnucash_accounts 
                     SET name = ?, account_type = ?, description = ?, code = ?, parent_guid = ?, updated_at = NOW() 
                     WHERE guid = ?",
                    [
                        $account['name'],
                        $account['account_type'],
                        $account['description'],
                        $account['code'],
                        $account['parent_guid'],
                        $account['guid']
                    ]
                );
                $accounts_updated++;
            } else {
                // Insert new account
                $this->db->query(
                    "INSERT INTO " . DB_PREFIX . "gnucash_accounts 
                     (guid, name, account_type, description, code, parent_guid) 
                     VALUES (?, ?, ?, ?, ?, ?)",
                    [
                        $account['guid'],
                        $account['name'],
                        $account['account_type'],
                        $account['description'],
                        $account['code'],
                        $account['parent_guid']
                    ]
                );
                $accounts_imported++;
            }
        }
        
        $this->log->write("GnuCash accounts import from SQLite completed: {$accounts_imported} new, {$accounts_updated} updated");
        
        return [
            'success' => true,
            'total_records' => $accounts_imported + $accounts_updated,
            'imported' => $accounts_imported,
            'updated' => $accounts_updated
        ];
    }
    
    /**
     * Import transactions from GnuCash
     * 
     * @param string $file_path
     * @param string $file_extension
     * @param array $config_data
     * @param array $options
     * @return array
     */
    private function importTransactions($file_path, $file_extension, $config_data, $options) {
        $date_from = $options['date_from'] ?? null;
        $date_to = $options['date_to'] ?? null;
        
        if ($file_extension === 'gnucash' || $file_extension === 'xml') {
            return $this->importTransactionsFromXml($file_path, $config_data, $date_from, $date_to);
        } else {
            return $this->importTransactionsFromSqlite($file_path, $config_data, $date_from, $date_to);
        }
    }
    
    /**
     * Import transactions from XML file
     * 
     * @param string $file_path
     * @param array $config_data
     * @param string|null $date_from
     * @param string|null $date_to
     * @return array
     */
    private function importTransactionsFromXml($file_path, $config_data, $date_from = null, $date_to = null) {
        $xml = simplexml_load_file($file_path);
        $namespaces = $xml->getNamespaces(true);
        
        $transactions_imported = 0;
        $splits_imported = 0;
        
        foreach ($xml->xpath('//gnc:transaction') as $transaction) {
            $transaction->registerXPathNamespace('gnc', $namespaces['gnc']);
            $transaction->registerXPathNamespace('trn', $namespaces['trn']);
            $transaction->registerXPathNamespace('ts', $namespaces['ts']);
            
            $transaction_data = [
                'guid' => (string) $transaction->{'trn:id'},
                'description' => (string) $transaction->{'trn:description'},
                'posted_date' => $this->parseGnuCashDate((string) $transaction->{'trn:date-posted'}->{'ts:date'}),
                'entered_date' => $this->parseGnuCashDate((string) $transaction->{'trn:date-entered'}->{'ts:date'})
            ];
            
            // Filter by date range if specified
            if ($date_from && $transaction_data['posted_date'] < $date_from) continue;
            if ($date_to && $transaction_data['posted_date'] > $date_to) continue;
            
            // Insert transaction if it doesn't exist
            $existing = $this->db->query(
                "SELECT transaction_id FROM " . DB_PREFIX . "gnucash_transactions WHERE guid = ?",
                [$transaction_data['guid']]
            );
            
            if (!$existing->num_rows) {
                $this->db->query(
                    "INSERT INTO " . DB_PREFIX . "gnucash_transactions 
                     (guid, description, posted_date, entered_date) 
                     VALUES (?, ?, ?, ?)",
                    [
                        $transaction_data['guid'],
                        $transaction_data['description'],
                        $transaction_data['posted_date'],
                        $transaction_data['entered_date']
                    ]
                );
                
                $transaction_id = $this->db->getLastId();
                $transactions_imported++;
                
                // Import splits for this transaction
                foreach ($transaction->xpath('trn:splits/trn:split') as $split) {
                    $split->registerXPathNamespace('split', $namespaces['split']);
                    
                    $split_data = [
                        'guid' => (string) $split->{'split:id'},
                        'account_guid' => (string) $split->{'split:account'},
                        'value' => $this->parseGnuCashAmount((string) $split->{'split:value'}),
                        'quantity' => $this->parseGnuCashAmount((string) $split->{'split:quantity'})
                    ];
                    
                    $this->db->query(
                        "INSERT INTO " . DB_PREFIX . "gnucash_splits 
                         (transaction_id, guid, account_guid, value_amount, quantity) 
                         VALUES (?, ?, ?, ?, ?)",
                        [
                            $transaction_id,
                            $split_data['guid'],
                            $split_data['account_guid'],
                            $split_data['value'],
                            $split_data['quantity']
                        ]
                    );
                    
                    $splits_imported++;
                }
            }
        }
        
        $this->log->write("GnuCash transactions import completed: {$transactions_imported} transactions, {$splits_imported} splits");
        
        return [
            'success' => true,
            'total_records' => $transactions_imported,
            'transactions' => $transactions_imported,
            'splits' => $splits_imported
        ];
    }
    
    /**
     * Export transactions to GnuCash
     * 
     * @param string $file_path
     * @param string $file_extension
     * @param array $config_data
     * @param array $options
     * @return array
     */
    private function exportTransactions($file_path, $file_extension, $config_data, $options) {
        // Implementation for exporting O9Cart transactions to GnuCash
        // This would involve creating GnuCash XML or inserting into SQLite
        
        $date_from = $options['date_from'] ?? date('Y-m-01');
        $date_to = $options['date_to'] ?? date('Y-m-t');
        
        // Get O9Cart orders for the specified period
        $orders = $this->db->query(
            "SELECT * FROM " . DB_PREFIX . "order 
             WHERE date_added >= ? AND date_added <= ?",
            [$date_from, $date_to]
        );
        
        $exported_count = 0;
        
        foreach ($orders->rows as $order) {
            // Convert O9Cart order to GnuCash transaction format
            $transaction_data = $this->convertOrderToGnuCashTransaction($order);
            
            if ($file_extension === 'sqlite' || $file_extension === 'db') {
                $this->exportTransactionToSqlite($file_path, $transaction_data);
            } else {
                // For XML export, we would need to maintain the XML structure
                $this->exportTransactionToXml($file_path, $transaction_data);
            }
            
            $exported_count++;
        }
        
        $this->log->write("Exported {$exported_count} transactions to GnuCash");
        
        return [
            'success' => true,
            'total_records' => $exported_count
        ];
    }
    
    /**
     * Sync customers between O9Cart and GnuCash
     * 
     * @param string $file_path
     * @param string $file_extension
     * @param array $config_data
     * @return array
     */
    private function syncCustomers($file_path, $file_extension, $config_data) {
        // Implementation for customer synchronization
        // This is a placeholder for the actual implementation
        
        return [
            'success' => true,
            'total_records' => 0,
            'message' => 'Customer sync not yet implemented'
        ];
    }
    
    /**
     * Parse GnuCash date format
     * 
     * @param string $gnucash_date
     * @return string MySQL date format
     */
    private function parseGnuCashDate($gnucash_date) {
        // GnuCash uses format like "2023-12-31 00:00:00 -0500"
        $timestamp = strtotime($gnucash_date);
        return date('Y-m-d H:i:s', $timestamp);
    }
    
    /**
     * Parse GnuCash amount format
     * 
     * @param string $gnucash_amount
     * @return float
     */
    private function parseGnuCashAmount($gnucash_amount) {
        // GnuCash uses fractional format like "12345/100"
        if (strpos($gnucash_amount, '/') !== false) {
            list($numerator, $denominator) = explode('/', $gnucash_amount);
            return (float) $numerator / (float) $denominator;
        }
        
        return (float) $gnucash_amount;
    }
    
    /**
     * Convert O9Cart order to GnuCash transaction format
     * 
     * @param array $order
     * @return array
     */
    private function convertOrderToGnuCashTransaction($order) {
        // This is a simplified conversion
        // In practice, you would need to map O9Cart fields to GnuCash structure
        
        return [
            'guid' => 'oc-order-' . $order['order_id'],
            'description' => 'Order #' . $order['order_id'] . ' - ' . $order['firstname'] . ' ' . $order['lastname'],
            'posted_date' => $order['date_added'],
            'splits' => [
                [
                    'account_guid' => 'sales-account-guid', // Would come from configuration
                    'value' => -$order['total'], // Credit to sales account
                    'quantity' => -$order['total']
                ],
                [
                    'account_guid' => 'receivables-account-guid', // Would come from configuration
                    'value' => $order['total'], // Debit to receivables
                    'quantity' => $order['total']
                ]
            ]
        ];
    }
    
    /**
     * Export transaction to SQLite database
     * 
     * @param string $file_path
     * @param array $transaction_data
     */
    private function exportTransactionToSqlite($file_path, $transaction_data) {
        $sqlite = new PDO("sqlite:{$file_path}");
        $sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Insert transaction
        $stmt = $sqlite->prepare(
            "INSERT OR IGNORE INTO transactions (guid, description, post_date, enter_date) 
             VALUES (?, ?, ?, ?)"
        );
        
        $stmt->execute([
            $transaction_data['guid'],
            $transaction_data['description'],
            $transaction_data['posted_date'],
            date('Y-m-d H:i:s')
        ]);
        
        // Insert splits
        foreach ($transaction_data['splits'] as $split) {
            $split_stmt = $sqlite->prepare(
                "INSERT OR IGNORE INTO splits (guid, tx_guid, account_guid, value_num, value_denom, quantity_num, quantity_denom) 
                 VALUES (?, ?, ?, ?, 100, ?, 100)"
            );
            
            $split_stmt->execute([
                $transaction_data['guid'] . '-split-' . uniqid(),
                $transaction_data['guid'],
                $split['account_guid'],
                $split['value'] * 100, // Convert to cents
                $split['quantity'] * 100 // Convert to cents
            ]);
        }
    }
    
    /**
     * Export transaction to XML file
     * 
     * @param string $file_path
     * @param array $transaction_data
     */
    private function exportTransactionToXml($file_path, $transaction_data) {
        // This would require complex XML manipulation
        // For now, just log that XML export is not fully implemented
        $this->log->write("XML export for transaction {$transaction_data['guid']} - implementation needed");
    }
}