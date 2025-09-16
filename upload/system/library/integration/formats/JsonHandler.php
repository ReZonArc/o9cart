<?php
/**
 * JSON Format Handler
 * 
 * Handles JSON file and string import/export operations
 * 
 * @category   Library
 * @package    Integration
 * @subpackage Formats
 * @author     O9Cart Development Team
 * @license    GPL v3
 */

namespace O9Cart\Integration\Formats;

class JsonHandler {
    
    private $config;
    private $log;
    
    /**
     * Constructor
     * 
     * @param object $registry The application registry
     */
    public function __construct($registry) {
        $this->config = $registry->get('config');
        $this->log = $registry->get('log');
    }
    
    /**
     * Read JSON file and return data array
     * 
     * @param string $file_path
     * @param array $options
     * @return array
     */
    public function readFile($file_path, $options = []) {
        if (!file_exists($file_path)) {
            throw new Exception("JSON file not found: {$file_path}");
        }
        
        $max_size = $options['max_size'] ?? 50 * 1024 * 1024; // 50MB default
        $encoding = $options['encoding'] ?? 'UTF-8';
        $validate_schema = $options['validate_schema'] ?? false;
        $schema = $options['schema'] ?? null;
        
        // Check file size
        $file_size = filesize($file_path);
        if ($file_size > $max_size) {
            throw new Exception("JSON file too large: {$file_size} bytes (max: {$max_size})");
        }
        
        // Read file content
        $content = file_get_contents($file_path);
        if ($content === false) {
            throw new Exception("Unable to read JSON file: {$file_path}");
        }
        
        // Handle encoding
        if ($encoding !== 'UTF-8') {
            $content = mb_convert_encoding($content, 'UTF-8', $encoding);
        }
        
        // Parse JSON
        $data = $this->parseJsonString($content, $options);
        
        // Validate against schema if provided
        if ($validate_schema && $schema) {
            $this->validateAgainstSchema($data, $schema);
        }
        
        $this->log->write("JSON file read successfully: {$file_path} (" . count($data) . " records)");
        
        return $data;
    }
    
    /**
     * Write data array to JSON file
     * 
     * @param array $data
     * @param string $file_path
     * @param array $options
     * @return bool
     */
    public function writeFile($data, $file_path, $options = []) {
        if (empty($data)) {
            throw new Exception("No data to write to JSON file");
        }
        
        $pretty_print = $options['pretty_print'] ?? true;
        $encoding = $options['encoding'] ?? 'UTF-8';
        $backup_existing = $options['backup_existing'] ?? true;
        
        // Ensure directory exists
        $directory = dirname($file_path);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        
        // Backup existing file if requested
        if ($backup_existing && file_exists($file_path)) {
            $backup_path = $file_path . '.backup.' . date('Y-m-d_H-i-s');
            copy($file_path, $backup_path);
        }
        
        // Prepare JSON options
        $json_options = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
        if ($pretty_print) {
            $json_options |= JSON_PRETTY_PRINT;
        }
        
        // Convert to JSON
        $json_content = json_encode($data, $json_options);
        if ($json_content === false) {
            throw new Exception("Failed to encode data to JSON: " . json_last_error_msg());
        }
        
        // Handle encoding
        if ($encoding !== 'UTF-8') {
            $json_content = mb_convert_encoding($json_content, $encoding, 'UTF-8');
        }
        
        // Write to file
        $bytes_written = file_put_contents($file_path, $json_content, LOCK_EX);
        if ($bytes_written === false) {
            throw new Exception("Unable to write JSON file: {$file_path}");
        }
        
        $this->log->write("JSON file written successfully: {$file_path} (" . count($data) . " records, {$bytes_written} bytes)");
        
        return true;
    }
    
    /**
     * Parse JSON string
     * 
     * @param string $json_string
     * @param array $options
     * @return array
     */
    public function parseString($json_string, $options = []) {
        return $this->parseJsonString($json_string, $options);
    }
    
    /**
     * Convert data array to JSON string
     * 
     * @param array $data
     * @param array $options
     * @return string
     */
    public function toString($data, $options = []) {
        $pretty_print = $options['pretty_print'] ?? false;
        
        $json_options = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
        if ($pretty_print) {
            $json_options |= JSON_PRETTY_PRINT;
        }
        
        $json_string = json_encode($data, $json_options);
        if ($json_string === false) {
            throw new Exception("Failed to encode data to JSON: " . json_last_error_msg());
        }
        
        return $json_string;
    }
    
    /**
     * Validate JSON file structure
     * 
     * @param string $file_path
     * @param array $expected_structure
     * @return array Validation results
     */
    public function validateFile($file_path, $expected_structure = []) {
        $results = [
            'valid' => true,
            'errors' => [],
            'warnings' => [],
            'stats' => [
                'file_size' => 0,
                'record_count' => 0,
                'structure' => []
            ]
        ];
        
        try {
            if (!file_exists($file_path)) {
                $results['valid'] = false;
                $results['errors'][] = 'JSON file not found';
                return $results;
            }
            
            $results['stats']['file_size'] = filesize($file_path);
            
            // Read and parse JSON
            $data = $this->readFile($file_path);
            
            if (empty($data)) {
                $results['valid'] = false;
                $results['errors'][] = 'JSON file is empty or contains no data';
                return $results;
            }
            
            $results['stats']['record_count'] = is_array($data) ? count($data) : 1;
            
            // Analyze structure
            $structure = $this->analyzeStructure($data);
            $results['stats']['structure'] = $structure;
            
            // Validate against expected structure if provided
            if (!empty($expected_structure)) {
                $structure_validation = $this->validateStructure($structure, $expected_structure);
                
                if (!$structure_validation['valid']) {
                    $results['valid'] = false;
                    $results['errors'] = array_merge($results['errors'], $structure_validation['errors']);
                }
                
                $results['warnings'] = array_merge($results['warnings'], $structure_validation['warnings']);
            }
            
        } catch (Exception $e) {
            $results['valid'] = false;
            $results['errors'][] = $e->getMessage();
        }
        
        return $results;
    }
    
    /**
     * Convert JSON to other formats
     * 
     * @param string $json_file_path
     * @param string $target_format
     * @param string $output_path
     * @param array $options
     * @return bool
     */
    public function convertTo($json_file_path, $target_format, $output_path, $options = []) {
        $data = $this->readFile($json_file_path);
        
        switch (strtolower($target_format)) {
            case 'csv':
                return $this->convertToCsv($data, $output_path, $options);
                
            case 'xml':
                return $this->convertToXml($data, $output_path, $options);
                
            case 'yaml':
                return $this->convertToYaml($data, $output_path, $options);
                
            default:
                throw new Exception("Unsupported target format: {$target_format}");
        }
    }
    
    /**
     * Merge multiple JSON files
     * 
     * @param array $file_paths
     * @param string $output_path
     * @param array $options
     * @return bool
     */
    public function mergeFiles($file_paths, $output_path, $options = []) {
        $merge_strategy = $options['strategy'] ?? 'append'; // 'append', 'merge_objects', 'deep_merge'
        
        $merged_data = [];
        
        foreach ($file_paths as $file_path) {
            if (!file_exists($file_path)) {
                $this->log->write("Warning: JSON file not found for merge: {$file_path}");
                continue;
            }
            
            $file_data = $this->readFile($file_path);
            
            switch ($merge_strategy) {
                case 'append':
                    if (is_array($file_data) && isset($file_data[0])) {
                        // Array of records
                        $merged_data = array_merge($merged_data, $file_data);
                    } else {
                        // Single object
                        $merged_data[] = $file_data;
                    }
                    break;
                    
                case 'merge_objects':
                    if (is_array($file_data) && !isset($file_data[0])) {
                        // Associative array/object
                        $merged_data = array_merge($merged_data, $file_data);
                    }
                    break;
                    
                case 'deep_merge':
                    $merged_data = $this->deepMergeArrays($merged_data, $file_data);
                    break;
            }
        }
        
        return $this->writeFile($merged_data, $output_path, $options);
    }
    
    /**
     * Split large JSON file into smaller files
     * 
     * @param string $input_path
     * @param string $output_directory
     * @param array $options
     * @return array List of created files
     */
    public function splitFile($input_path, $output_directory, $options = []) {
        $records_per_file = $options['records_per_file'] ?? 1000;
        $file_prefix = $options['file_prefix'] ?? 'split_';
        
        $data = $this->readFile($input_path);
        
        if (!is_array($data) || !isset($data[0])) {
            throw new Exception("JSON file must contain an array of records for splitting");
        }
        
        if (!is_dir($output_directory)) {
            mkdir($output_directory, 0755, true);
        }
        
        $created_files = [];
        $chunks = array_chunk($data, $records_per_file);
        
        foreach ($chunks as $index => $chunk) {
            $output_file = $output_directory . '/' . $file_prefix . str_pad($index + 1, 3, '0', STR_PAD_LEFT) . '.json';
            
            $this->writeFile($chunk, $output_file, $options);
            $created_files[] = $output_file;
        }
        
        $this->log->write("Split JSON file into " . count($created_files) . " files");
        
        return $created_files;
    }
    
    /**
     * Extract specific fields from JSON data
     * 
     * @param array $data
     * @param array $field_paths
     * @return array
     */
    public function extractFields($data, $field_paths) {
        $extracted = [];
        
        if (isset($data[0]) && is_array($data[0])) {
            // Array of records
            foreach ($data as $record) {
                $extracted_record = [];
                foreach ($field_paths as $alias => $path) {
                    $field_name = is_string($alias) ? $alias : $path;
                    $extracted_record[$field_name] = $this->getValueByPath($record, $path);
                }
                $extracted[] = $extracted_record;
            }
        } else {
            // Single record
            foreach ($field_paths as $alias => $path) {
                $field_name = is_string($alias) ? $alias : $path;
                $extracted[$field_name] = $this->getValueByPath($data, $path);
            }
        }
        
        return $extracted;
    }
    
    /**
     * Filter JSON data based on conditions
     * 
     * @param array $data
     * @param array $conditions
     * @return array
     */
    public function filterData($data, $conditions) {
        if (!isset($data[0]) || !is_array($data[0])) {
            // Single record
            return $this->evaluateConditions($data, $conditions) ? $data : [];
        }
        
        // Array of records
        $filtered = [];
        foreach ($data as $record) {
            if ($this->evaluateConditions($record, $conditions)) {
                $filtered[] = $record;
            }
        }
        
        return $filtered;
    }
    
    /**
     * Parse JSON string with error handling
     * 
     * @param string $json_string
     * @param array $options
     * @return array
     */
    private function parseJsonString($json_string, $options = []) {
        $max_depth = $options['max_depth'] ?? 512;
        $allow_comments = $options['allow_comments'] ?? false;
        
        // Remove comments if allowed (non-standard JSON)
        if ($allow_comments) {
            $json_string = $this->stripJsonComments($json_string);
        }
        
        $data = json_decode($json_string, true, $max_depth);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("JSON parsing error: " . json_last_error_msg());
        }
        
        return $data;
    }
    
    /**
     * Analyze JSON structure
     * 
     * @param mixed $data
     * @return array
     */
    private function analyzeStructure($data) {
        if (is_array($data)) {
            if (empty($data)) {
                return ['type' => 'empty_array'];
            }
            
            if (isset($data[0])) {
                // Indexed array
                $sample_record = $data[0];
                return [
                    'type' => 'array_of_records',
                    'count' => count($data),
                    'sample_fields' => is_array($sample_record) ? array_keys($sample_record) : ['scalar_values']
                ];
            } else {
                // Associative array
                return [
                    'type' => 'object',
                    'fields' => array_keys($data)
                ];
            }
        } else {
            return [
                'type' => 'scalar',
                'data_type' => gettype($data)
            ];
        }
    }
    
    /**
     * Validate structure against expected format
     * 
     * @param array $actual_structure
     * @param array $expected_structure
     * @return array
     */
    private function validateStructure($actual_structure, $expected_structure) {
        $result = [
            'valid' => true,
            'errors' => [],
            'warnings' => []
        ];
        
        // Check type
        if (isset($expected_structure['type']) && $actual_structure['type'] !== $expected_structure['type']) {
            $result['valid'] = false;
            $result['errors'][] = "Expected type '{$expected_structure['type']}', got '{$actual_structure['type']}'";
        }
        
        // Check required fields
        if (isset($expected_structure['required_fields']) && isset($actual_structure['sample_fields'])) {
            $missing_fields = array_diff($expected_structure['required_fields'], $actual_structure['sample_fields']);
            if (!empty($missing_fields)) {
                $result['valid'] = false;
                $result['errors'][] = "Missing required fields: " . implode(', ', $missing_fields);
            }
        }
        
        return $result;
    }
    
    /**
     * Get value by dot notation path
     * 
     * @param array $data
     * @param string $path
     * @return mixed
     */
    private function getValueByPath($data, $path) {
        $keys = explode('.', $path);
        $value = $data;
        
        foreach ($keys as $key) {
            if (is_array($value) && isset($value[$key])) {
                $value = $value[$key];
            } else {
                return null;
            }
        }
        
        return $value;
    }
    
    /**
     * Evaluate filter conditions
     * 
     * @param array $record
     * @param array $conditions
     * @return bool
     */
    private function evaluateConditions($record, $conditions) {
        foreach ($conditions as $field => $condition) {
            $value = $this->getValueByPath($record, $field);
            
            if (is_array($condition)) {
                $operator = $condition['operator'] ?? '=';
                $compare_value = $condition['value'];
                
                switch ($operator) {
                    case '=':
                    case '==':
                        if ($value != $compare_value) return false;
                        break;
                    case '!=':
                        if ($value == $compare_value) return false;
                        break;
                    case '>':
                        if ($value <= $compare_value) return false;
                        break;
                    case '>=':
                        if ($value < $compare_value) return false;
                        break;
                    case '<':
                        if ($value >= $compare_value) return false;
                        break;
                    case '<=':
                        if ($value > $compare_value) return false;
                        break;
                    case 'in':
                        if (!in_array($value, (array) $compare_value)) return false;
                        break;
                    case 'contains':
                        if (strpos($value, $compare_value) === false) return false;
                        break;
                }
            } else {
                // Simple equality check
                if ($value != $condition) return false;
            }
        }
        
        return true;
    }
    
    /**
     * Deep merge arrays recursively
     * 
     * @param array $array1
     * @param array $array2
     * @return array
     */
    private function deepMergeArrays($array1, $array2) {
        foreach ($array2 as $key => $value) {
            if (is_array($value) && isset($array1[$key]) && is_array($array1[$key])) {
                $array1[$key] = $this->deepMergeArrays($array1[$key], $value);
            } else {
                $array1[$key] = $value;
            }
        }
        
        return $array1;
    }
    
    /**
     * Strip JSON comments (non-standard)
     * 
     * @param string $json_string
     * @return string
     */
    private function stripJsonComments($json_string) {
        // Remove single-line comments
        $json_string = preg_replace('/\/\/.*$/m', '', $json_string);
        
        // Remove multi-line comments
        $json_string = preg_replace('/\/\*.*?\*\//s', '', $json_string);
        
        return $json_string;
    }
    
    /**
     * Validate against JSON schema
     * 
     * @param array $data
     * @param array $schema
     * @throws Exception
     */
    private function validateAgainstSchema($data, $schema) {
        // Basic schema validation (in production, use a proper JSON Schema validator)
        if (isset($schema['type'])) {
            $expected_type = $schema['type'];
            $actual_type = is_array($data) ? 'array' : gettype($data);
            
            if ($expected_type !== $actual_type) {
                throw new Exception("Schema validation failed: expected {$expected_type}, got {$actual_type}");
            }
        }
    }
    
    /**
     * Convert to CSV format
     * 
     * @param array $data
     * @param string $output_path
     * @param array $options
     * @return bool
     */
    private function convertToCsv($data, $output_path, $options) {
        // This would use the CsvHandler class
        $csv_handler = new CsvHandler($this->registry);
        return $csv_handler->writeFile($data, $output_path, $options);
    }
    
    /**
     * Convert to XML format
     * 
     * @param array $data
     * @param string $output_path
     * @param array $options
     * @return bool
     */
    private function convertToXml($data, $output_path, $options) {
        // Basic XML conversion
        $xml = new SimpleXMLElement('<root/>');
        $this->arrayToXml($data, $xml);
        
        return file_put_contents($output_path, $xml->asXML()) !== false;
    }
    
    /**
     * Convert array to XML
     * 
     * @param array $data
     * @param SimpleXMLElement $xml
     */
    private function arrayToXml($data, $xml) {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if (is_numeric($key)) {
                    $key = 'item';
                }
                $subnode = $xml->addChild($key);
                $this->arrayToXml($value, $subnode);
            } else {
                $xml->addChild($key, htmlspecialchars($value));
            }
        }
    }
    
    /**
     * Convert to YAML format
     * 
     * @param array $data
     * @param string $output_path
     * @param array $options
     * @return bool
     */
    private function convertToYaml($data, $output_path, $options) {
        // Basic YAML conversion (in production, use a proper YAML library)
        $yaml_content = $this->arrayToYaml($data);
        return file_put_contents($output_path, $yaml_content) !== false;
    }
    
    /**
     * Convert array to YAML
     * 
     * @param array $data
     * @param int $indent
     * @return string
     */
    private function arrayToYaml($data, $indent = 0) {
        $yaml = '';
        $prefix = str_repeat('  ', $indent);
        
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $yaml .= $prefix . $key . ":\n";
                $yaml .= $this->arrayToYaml($value, $indent + 1);
            } else {
                $yaml .= $prefix . $key . ': ' . $value . "\n";
            }
        }
        
        return $yaml;
    }
}