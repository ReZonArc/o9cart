<?php
/**
 * CSV Format Handler
 * 
 * Handles CSV file import/export operations
 * 
 * @category   Library
 * @package    Integration
 * @subpackage Formats
 * @author     O9Cart Development Team
 * @license    GPL v3
 */

namespace O9Cart\Integration\Formats;

class CsvHandler {
    
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
     * Read CSV file and return data array
     * 
     * @param string $file_path
     * @param array $options
     * @return array
     */
    public function readFile($file_path, $options = []) {
        if (!file_exists($file_path)) {
            throw new Exception("CSV file not found: {$file_path}");
        }
        
        $delimiter = $options['delimiter'] ?? ',';
        $enclosure = $options['enclosure'] ?? '"';
        $escape = $options['escape'] ?? '\\';
        $has_header = $options['has_header'] ?? true;
        $encoding = $options['encoding'] ?? 'UTF-8';
        
        $data = [];
        $header = [];
        $row_count = 0;
        
        // Handle different encodings
        if ($encoding !== 'UTF-8') {
            $content = file_get_contents($file_path);
            $content = mb_convert_encoding($content, 'UTF-8', $encoding);
            $temp_file = tempnam(sys_get_temp_dir(), 'csv_converted_');
            file_put_contents($temp_file, $content);
            $file_path = $temp_file;
        }
        
        $handle = fopen($file_path, 'r');
        if (!$handle) {
            throw new Exception("Unable to open CSV file: {$file_path}");
        }
        
        try {
            while (($row = fgetcsv($handle, 0, $delimiter, $enclosure, $escape)) !== false) {
                $row_count++;
                
                if ($row_count === 1 && $has_header) {
                    $header = $row;
                    continue;
                }
                
                if ($has_header && !empty($header)) {
                    $row_data = [];
                    foreach ($row as $index => $value) {
                        $column_name = isset($header[$index]) ? $header[$index] : "column_{$index}";
                        $row_data[$column_name] = $this->cleanValue($value);
                    }
                    $data[] = $row_data;
                } else {
                    $data[] = array_map([$this, 'cleanValue'], $row);
                }
            }
        } finally {
            fclose($handle);
            
            // Clean up temporary file if created
            if (isset($temp_file) && file_exists($temp_file)) {
                unlink($temp_file);
            }
        }
        
        $this->log->write("CSV file read successfully: {$file_path} ({$row_count} rows)");
        
        return $data;
    }
    
    /**
     * Write data array to CSV file
     * 
     * @param array $data
     * @param string $file_path
     * @param array $options
     * @return bool
     */
    public function writeFile($data, $file_path, $options = []) {
        if (empty($data)) {
            throw new Exception("No data to write to CSV file");
        }
        
        $delimiter = $options['delimiter'] ?? ',';
        $enclosure = $options['enclosure'] ?? '"';
        $escape = $options['escape'] ?? '\\';
        $include_header = $options['include_header'] ?? true;
        $encoding = $options['encoding'] ?? 'UTF-8';
        
        // Ensure directory exists
        $directory = dirname($file_path);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        
        $handle = fopen($file_path, 'w');
        if (!$handle) {
            throw new Exception("Unable to create CSV file: {$file_path}");
        }
        
        try {
            $header_written = false;
            
            foreach ($data as $row) {
                // Write header row if needed
                if ($include_header && !$header_written && is_array($row)) {
                    $header = array_keys($row);
                    fputcsv($handle, $header, $delimiter, $enclosure, $escape);
                    $header_written = true;
                }
                
                // Write data row
                if (is_array($row)) {
                    $values = array_values($row);
                } else {
                    $values = [$row];
                }
                
                fputcsv($handle, $values, $delimiter, $enclosure, $escape);
            }
        } finally {
            fclose($handle);
        }
        
        // Handle encoding conversion if needed
        if ($encoding !== 'UTF-8') {
            $content = file_get_contents($file_path);
            $content = mb_convert_encoding($content, $encoding, 'UTF-8');
            file_put_contents($file_path, $content);
        }
        
        $this->log->write("CSV file written successfully: {$file_path} (" . count($data) . " rows)");
        
        return true;
    }
    
    /**
     * Parse CSV from string
     * 
     * @param string $csv_string
     * @param array $options
     * @return array
     */
    public function parseString($csv_string, $options = []) {
        $temp_file = tempnam(sys_get_temp_dir(), 'csv_string_');
        file_put_contents($temp_file, $csv_string);
        
        try {
            return $this->readFile($temp_file, $options);
        } finally {
            unlink($temp_file);
        }
    }
    
    /**
     * Convert data array to CSV string
     * 
     * @param array $data
     * @param array $options
     * @return string
     */
    public function toString($data, $options = []) {
        $temp_file = tempnam(sys_get_temp_dir(), 'csv_string_');
        
        try {
            $this->writeFile($data, $temp_file, $options);
            return file_get_contents($temp_file);
        } finally {
            unlink($temp_file);
        }
    }
    
    /**
     * Validate CSV file structure
     * 
     * @param string $file_path
     * @param array $expected_columns
     * @return array Validation results
     */
    public function validateFile($file_path, $expected_columns = []) {
        $results = [
            'valid' => true,
            'errors' => [],
            'warnings' => [],
            'stats' => [
                'total_rows' => 0,
                'data_rows' => 0,
                'columns' => 0
            ]
        ];
        
        try {
            // Read first few rows to validate structure
            $sample_data = $this->readFile($file_path, ['max_rows' => 10]);
            
            if (empty($sample_data)) {
                $results['valid'] = false;
                $results['errors'][] = 'CSV file is empty';
                return $results;
            }
            
            $results['stats']['data_rows'] = count($sample_data);
            $results['stats']['columns'] = count($sample_data[0]);
            
            // Check expected columns if provided
            if (!empty($expected_columns)) {
                $actual_columns = array_keys($sample_data[0]);
                $missing_columns = array_diff($expected_columns, $actual_columns);
                $extra_columns = array_diff($actual_columns, $expected_columns);
                
                if (!empty($missing_columns)) {
                    $results['valid'] = false;
                    $results['errors'][] = 'Missing required columns: ' . implode(', ', $missing_columns);
                }
                
                if (!empty($extra_columns)) {
                    $results['warnings'][] = 'Unexpected columns found: ' . implode(', ', $extra_columns);
                }
            }
            
            // Check for consistent column count
            $column_count = count($sample_data[0]);
            foreach ($sample_data as $index => $row) {
                if (count($row) !== $column_count) {
                    $results['warnings'][] = "Row " . ($index + 1) . " has inconsistent column count";
                }
            }
            
        } catch (Exception $e) {
            $results['valid'] = false;
            $results['errors'][] = $e->getMessage();
        }
        
        return $results;
    }
    
    /**
     * Auto-detect CSV format options
     * 
     * @param string $file_path
     * @return array Detected options
     */
    public function detectFormat($file_path) {
        if (!file_exists($file_path)) {
            throw new Exception("CSV file not found: {$file_path}");
        }
        
        $sample = file_get_contents($file_path, false, null, 0, 1024); // Read first 1KB
        
        $options = [
            'delimiter' => ',',
            'enclosure' => '"',
            'has_header' => true,
            'encoding' => 'UTF-8'
        ];
        
        // Detect delimiter
        $delimiters = [',', ';', '\t', '|'];
        $delimiter_counts = [];
        
        foreach ($delimiters as $delimiter) {
            $delimiter_counts[$delimiter] = substr_count($sample, $delimiter);
        }
        
        $options['delimiter'] = array_search(max($delimiter_counts), $delimiter_counts);
        
        // Detect encoding
        $encoding = mb_detect_encoding($sample, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
        if ($encoding) {
            $options['encoding'] = $encoding;
        }
        
        // Detect if first row is header
        $lines = explode("\n", $sample);
        if (count($lines) >= 2) {
            $first_row = str_getcsv($lines[0], $options['delimiter']);
            $second_row = str_getcsv($lines[1], $options['delimiter']);
            
            // If first row contains non-numeric values and second row has numeric values,
            // assume first row is header
            $first_numeric = 0;
            $second_numeric = 0;
            
            foreach ($first_row as $value) {
                if (is_numeric(trim($value))) $first_numeric++;
            }
            
            foreach ($second_row as $value) {
                if (is_numeric(trim($value))) $second_numeric++;
            }
            
            $options['has_header'] = $first_numeric < $second_numeric;
        }
        
        return $options;
    }
    
    /**
     * Convert CSV to array of objects
     * 
     * @param string $file_path
     * @param array $options
     * @return array
     */
    public function toObjects($file_path, $options = []) {
        $data = $this->readFile($file_path, $options);
        $objects = [];
        
        foreach ($data as $row) {
            $objects[] = (object) $row;
        }
        
        return $objects;
    }
    
    /**
     * Clean and normalize cell value
     * 
     * @param string $value
     * @return string
     */
    private function cleanValue($value) {
        // Trim whitespace
        $value = trim($value);
        
        // Convert empty strings to null
        if ($value === '') {
            return null;
        }
        
        // Handle special values
        if (in_array(strtolower($value), ['null', 'nil', 'none'])) {
            return null;
        }
        
        // Auto-convert numeric values
        if (is_numeric($value)) {
            return strpos($value, '.') !== false ? (float) $value : (int) $value;
        }
        
        // Handle boolean values
        $lower_value = strtolower($value);
        if (in_array($lower_value, ['true', 'yes', '1', 'on'])) {
            return true;
        }
        if (in_array($lower_value, ['false', 'no', '0', 'off'])) {
            return false;
        }
        
        return $value;
    }
}