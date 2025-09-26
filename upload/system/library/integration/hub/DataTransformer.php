<?php
/**
 * Data Transformer
 * 
 * Handles data mapping and transformation between different formats and systems
 * 
 * @category   Library
 * @package    Integration
 * @subpackage Hub
 * @author     O9Cart Development Team
 * @license    GPL v3
 */

namespace O9Cart\Integration\Hub;

class DataTransformer {
    
    private $db;
    private $log;
    private $mapping_rules = [];
    
    /**
     * Constructor
     * 
     * @param object $registry The application registry
     */
    public function __construct($registry) {
        $this->db = $registry->get('db');
        $this->log = $registry->get('log');
    }
    
    /**
     * Load mapping rules for integration
     * 
     * @param int $integration_id
     */
    public function loadMappingRules($integration_id) {
        $query = $this->db->query(
            "SELECT * FROM " . DB_PREFIX . "data_mapping WHERE integration_id = ?",
            [$integration_id]
        );
        
        $this->mapping_rules = [];
        foreach ($query->rows as $row) {
            $this->mapping_rules[$row['source_field']] = [
                'target_field' => $row['target_field'],
                'transformation_rule' => json_decode($row['transformation_rule'], true)
            ];
        }
    }
    
    /**
     * Transform data array using mapping rules
     * 
     * @param array $source_data
     * @param int $integration_id
     * @return array Transformed data
     */
    public function transformData($source_data, $integration_id) {
        $this->loadMappingRules($integration_id);
        
        $transformed_data = [];
        
        foreach ($source_data as $source_key => $source_value) {
            if (isset($this->mapping_rules[$source_key])) {
                $mapping = $this->mapping_rules[$source_key];
                $target_key = $mapping['target_field'];
                $transformation_rule = $mapping['transformation_rule'];
                
                $transformed_value = $this->applyTransformation($source_value, $transformation_rule);
                $transformed_data[$target_key] = $transformed_value;
            } else {
                // If no mapping rule, keep original key and value
                $transformed_data[$source_key] = $source_value;
            }
        }
        
        return $transformed_data;
    }
    
    /**
     * Apply transformation rule to a value
     * 
     * @param mixed $value
     * @param array $rule
     * @return mixed Transformed value
     */
    private function applyTransformation($value, $rule) {
        if (empty($rule) || !is_array($rule)) {
            return $value;
        }
        
        switch ($rule['type']) {
            case 'cast':
                return $this->castValue($value, $rule['target_type']);
                
            case 'format':
                return $this->formatValue($value, $rule['format']);
                
            case 'lookup':
                return $this->lookupValue($value, $rule['lookup_table']);
                
            case 'calculate':
                return $this->calculateValue($value, $rule['formula']);
                
            case 'concatenate':
                return $this->concatenateValue($value, $rule['parts'], $rule['separator'] ?? '');
                
            case 'extract':
                return $this->extractValue($value, $rule['pattern']);
                
            case 'default':
                return !empty($value) ? $value : $rule['default_value'];
                
            case 'conditional':
                return $this->applyConditionalRule($value, $rule['conditions']);
                
            default:
                $this->log->write("Unknown transformation rule type: " . $rule['type']);
                return $value;
        }
    }
    
    /**
     * Cast value to specific type
     * 
     * @param mixed $value
     * @param string $type
     * @return mixed
     */
    private function castValue($value, $type) {
        switch ($type) {
            case 'string':
                return (string) $value;
            case 'int':
            case 'integer':
                return (int) $value;
            case 'float':
            case 'double':
                return (float) $value;
            case 'bool':
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'array':
                return is_array($value) ? $value : [$value];
            case 'json':
                return is_string($value) ? json_decode($value, true) : $value;
            default:
                return $value;
        }
    }
    
    /**
     * Format value using sprintf-style formatting
     * 
     * @param mixed $value
     * @param string $format
     * @return string
     */
    private function formatValue($value, $format) {
        try {
            return sprintf($format, $value);
        } catch (Exception $e) {
            $this->log->write("Value formatting error: " . $e->getMessage());
            return $value;
        }
    }
    
    /**
     * Lookup value in a table
     * 
     * @param mixed $value
     * @param array $lookup_table
     * @return mixed
     */
    private function lookupValue($value, $lookup_table) {
        return isset($lookup_table[$value]) ? $lookup_table[$value] : $value;
    }
    
    /**
     * Calculate value using simple formula
     * 
     * @param mixed $value
     * @param string $formula
     * @return mixed
     */
    private function calculateValue($value, $formula) {
        // Simple formula evaluation (for security, only allow basic math operations)
        $formula = str_replace('{value}', $value, $formula);
        
        // Allow only numbers, operators, and parentheses
        if (preg_match('/^[0-9+\-*\/\.\(\)\s]+$/', $formula)) {
            try {
                return eval("return {$formula};");
            } catch (Exception $e) {
                $this->log->write("Formula calculation error: " . $e->getMessage());
                return $value;
            }
        }
        
        return $value;
    }
    
    /**
     * Concatenate value with other parts
     * 
     * @param mixed $value
     * @param array $parts
     * @param string $separator
     * @return string
     */
    private function concatenateValue($value, $parts, $separator = '') {
        $result_parts = [$value];
        
        foreach ($parts as $part) {
            if (is_array($part) && isset($part['type']) && $part['type'] === 'field') {
                // This would need access to other fields in the data
                // For now, just add the literal value
                $result_parts[] = $part['value'] ?? '';
            } else {
                $result_parts[] = $part;
            }
        }
        
        return implode($separator, $result_parts);
    }
    
    /**
     * Extract value using regex pattern
     * 
     * @param mixed $value
     * @param string $pattern
     * @return mixed
     */
    private function extractValue($value, $pattern) {
        if (is_string($value) && preg_match($pattern, $value, $matches)) {
            return isset($matches[1]) ? $matches[1] : $matches[0];
        }
        
        return $value;
    }
    
    /**
     * Apply conditional rule
     * 
     * @param mixed $value
     * @param array $conditions
     * @return mixed
     */
    private function applyConditionalRule($value, $conditions) {
        foreach ($conditions as $condition) {
            if ($this->evaluateCondition($value, $condition['condition'])) {
                return $condition['result'];
            }
        }
        
        // Return original value if no conditions match
        return $value;
    }
    
    /**
     * Evaluate a condition
     * 
     * @param mixed $value
     * @param array $condition
     * @return bool
     */
    private function evaluateCondition($value, $condition) {
        $operator = $condition['operator'];
        $compare_value = $condition['value'];
        
        switch ($operator) {
            case '==':
            case 'equals':
                return $value == $compare_value;
            case '!=':
            case 'not_equals':
                return $value != $compare_value;
            case '>':
            case 'greater_than':
                return $value > $compare_value;
            case '>=':
            case 'greater_equal':
                return $value >= $compare_value;
            case '<':
            case 'less_than':
                return $value < $compare_value;
            case '<=':
            case 'less_equal':
                return $value <= $compare_value;
            case 'contains':
                return strpos($value, $compare_value) !== false;
            case 'starts_with':
                return strpos($value, $compare_value) === 0;
            case 'ends_with':
                return substr($value, -strlen($compare_value)) === $compare_value;
            case 'matches':
                return preg_match($compare_value, $value);
            case 'in':
                return in_array($value, (array) $compare_value);
            case 'not_in':
                return !in_array($value, (array) $compare_value);
            case 'empty':
                return empty($value);
            case 'not_empty':
                return !empty($value);
            default:
                return false;
        }
    }
    
    /**
     * Save mapping rule
     * 
     * @param int $integration_id
     * @param string $source_field
     * @param string $target_field
     * @param array $transformation_rule
     * @return bool
     */
    public function saveMappingRule($integration_id, $source_field, $target_field, $transformation_rule = []) {
        // Check if mapping already exists
        $query = $this->db->query(
            "SELECT mapping_id FROM " . DB_PREFIX . "data_mapping 
             WHERE integration_id = ? AND source_field = ?",
            [$integration_id, $source_field]
        );
        
        if ($query->num_rows) {
            // Update existing mapping
            $this->db->query(
                "UPDATE " . DB_PREFIX . "data_mapping 
                 SET target_field = ?, transformation_rule = ? 
                 WHERE integration_id = ? AND source_field = ?",
                [
                    $target_field,
                    json_encode($transformation_rule),
                    $integration_id,
                    $source_field
                ]
            );
        } else {
            // Create new mapping
            $this->db->query(
                "INSERT INTO " . DB_PREFIX . "data_mapping 
                 (integration_id, source_field, target_field, transformation_rule) 
                 VALUES (?, ?, ?, ?)",
                [
                    $integration_id,
                    $source_field,
                    $target_field,
                    json_encode($transformation_rule)
                ]
            );
        }
        
        return true;
    }
    
    /**
     * Delete mapping rule
     * 
     * @param int $integration_id
     * @param string $source_field
     * @return bool
     */
    public function deleteMappingRule($integration_id, $source_field) {
        $this->db->query(
            "DELETE FROM " . DB_PREFIX . "data_mapping 
             WHERE integration_id = ? AND source_field = ?",
            [$integration_id, $source_field]
        );
        
        return true;
    }
    
    /**
     * Get all mapping rules for integration
     * 
     * @param int $integration_id
     * @return array
     */
    public function getMappingRules($integration_id) {
        $query = $this->db->query(
            "SELECT * FROM " . DB_PREFIX . "data_mapping WHERE integration_id = ?",
            [$integration_id]
        );
        
        $mappings = [];
        foreach ($query->rows as $row) {
            $mappings[] = [
                'mapping_id' => $row['mapping_id'],
                'source_field' => $row['source_field'],
                'target_field' => $row['target_field'],
                'transformation_rule' => json_decode($row['transformation_rule'], true)
            ];
        }
        
        return $mappings;
    }
}