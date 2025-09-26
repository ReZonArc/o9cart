<?php
namespace Opencart\Admin\Controller\Marketplace;

/**
 * Agoraspace Community Marketplace Controller
 * 
 * Administrative interface for managing OpenCog-powered community marketplace
 * 
 * @category   Controller
 * @package    Marketplace
 * @subpackage Agoraspace
 * @author     O9Cart Development Team
 * @license    GPL v3
 */
class Agoraspace extends \Opencart\System\Engine\Controller {
    
    /**
     * Main agoraspace dashboard
     */
    public function index(): void {
        $this->load->language('marketplace/agoraspace');
        
        $this->document->setTitle($this->language->get('heading_title'));
        
        $data['breadcrumbs'] = [];
        
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
        ];
        
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('marketplace/agoraspace', 'user_token=' . $this->session->data['user_token'])
        ];
        
        // Check if OpenCog integration is enabled
        $data['opencog_enabled'] = $this->config->get('opencog_enabled');
        $data['atomspace_status'] = $this->checkAtomSpaceStatus();
        
        // Get community statistics
        $data['community_stats'] = $this->getCommunityStatistics();
        
        // Get recent activities
        $data['recent_activities'] = $this->getRecentActivities();
        
        // Get market signals
        $data['market_signals'] = $this->getActiveMarketSignals();
        
        // Get AGI agent status
        $data['agi_agents'] = $this->getAGIAgentStatus();
        
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('marketplace/agoraspace', $data));
    }
    
    /**
     * Community members management
     */
    public function members(): void {
        $this->load->language('marketplace/agoraspace');
        
        if (isset($this->request->get['filter_reputation_min'])) {
            $filter_reputation_min = (float)$this->request->get['filter_reputation_min'];
        } else {
            $filter_reputation_min = 0.0;
        }
        
        if (isset($this->request->get['filter_role'])) {
            $filter_role = (string)$this->request->get['filter_role'];
        } else {
            $filter_role = '';
        }
        
        if (isset($this->request->get['filter_skill'])) {
            $filter_skill = (string)$this->request->get['filter_skill'];
        } else {
            $filter_skill = '';
        }
        
        if (isset($this->request->get['page'])) {
            $page = (int)$this->request->get['page'];
        } else {
            $page = 1;
        }
        
        $limit = 20;
        $start = ($page - 1) * $limit;
        
        // Get filtered members
        $filter_data = [
            'filter_reputation_min' => $filter_reputation_min,
            'filter_role' => $filter_role,
            'filter_skill' => $filter_skill,
            'start' => $start,
            'limit' => $limit
        ];
        
        $data['members'] = $this->getMembers($filter_data);
        $data['total_members'] = $this->getTotalMembers($filter_data);
        
        // Pagination
        $pagination = new \Opencart\System\Library\Pagination();
        $pagination->total = $data['total_members'];
        $pagination->page = $page;
        $pagination->limit = $limit;
        $pagination->url = $this->url->link('marketplace/agoraspace/members', 'user_token=' . $this->session->data['user_token'] . '&page={page}');
        
        $data['pagination'] = $pagination->render();
        $data['results'] = sprintf($this->language->get('text_pagination'), ($data['total_members']) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($data['total_members'] - $limit)) ? $data['total_members'] : ((($page - 1) * $limit) + $limit), $data['total_members'], ceil($data['total_members'] / $limit));
        
        $data['filter_reputation_min'] = $filter_reputation_min;
        $data['filter_role'] = $filter_role;
        $data['filter_skill'] = $filter_skill;
        
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');  
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('marketplace/agoraspace_members', $data));
    }
    
    /**
     * Collaborations management
     */
    public function collaborations(): void {
        $this->load->language('marketplace/agoraspace');
        
        if (isset($this->request->get['filter_status'])) {
            $filter_status = (string)$this->request->get['filter_status'];
        } else {
            $filter_status = '';
        }
        
        if (isset($this->request->get['filter_type'])) {
            $filter_type = (string)$this->request->get['filter_type'];
        } else {
            $filter_type = '';
        }
        
        if (isset($this->request->get['page'])) {
            $page = (int)$this->request->get['page'];
        } else {
            $page = 1;
        }
        
        $limit = 20;
        $start = ($page - 1) * $limit;
        
        $filter_data = [
            'filter_status' => $filter_status,
            'filter_type' => $filter_type,
            'start' => $start,
            'limit' => $limit
        ];
        
        $data['collaborations'] = $this->getCollaborations($filter_data);
        $data['total_collaborations'] = $this->getTotalCollaborations($filter_data);
        
        // Pagination
        $pagination = new \Opencart\System\Library\Pagination();
        $pagination->total = $data['total_collaborations'];
        $pagination->page = $page;
        $pagination->limit = $limit;
        $pagination->url = $this->url->link('marketplace/agoraspace/collaborations', 'user_token=' . $this->session->data['user_token'] . '&page={page}');
        
        $data['pagination'] = $pagination->render();
        
        $data['filter_status'] = $filter_status;
        $data['filter_type'] = $filter_type;
        
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('marketplace/agoraspace_collaborations', $data));
    }
    
    /**
     * Skills ecosystem management
     */
    public function skills(): void {
        $this->load->language('marketplace/agoraspace');
        
        if (isset($this->request->get['filter_category'])) {
            $filter_category = (string)$this->request->get['filter_category'];
        } else {
            $filter_category = '';
        }
        
        if (isset($this->request->get['sort'])) {
            $sort = (string)$this->request->get['sort'];
        } else {
            $sort = 'demand_level';
        }
        
        if (isset($this->request->get['order'])) {
            $order = (string)$this->request->get['order'];
        } else {
            $order = 'DESC';
        }
        
        if (isset($this->request->get['page'])) {
            $page = (int)$this->request->get['page'];
        } else {
            $page = 1;
        }
        
        $limit = 20;
        $start = ($page - 1) * $limit;
        
        $filter_data = [
            'filter_category' => $filter_category,
            'sort' => $sort,
            'order' => $order,
            'start' => $start,
            'limit' => $limit
        ];
        
        $data['skills'] = $this->getSkills($filter_data);
        $data['total_skills'] = $this->getTotalSkills($filter_data);
        
        // Get skill categories for filter
        $data['categories'] = $this->getSkillCategories();
        
        // Pagination
        $pagination = new \Opencart\System\Library\Pagination();
        $pagination->total = $data['total_skills'];
        $pagination->page = $page;
        $pagination->limit = $limit;
        $pagination->url = $this->url->link('marketplace/agoraspace/skills', 'user_token=' . $this->session->data['user_token'] . '&page={page}');
        
        $data['pagination'] = $pagination->render();
        
        $data['filter_category'] = $filter_category;
        $data['sort'] = $sort;
        $data['order'] = $order;
        
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('marketplace/agoraspace_skills', $data));
    }
    
    /**
     * AGI agents management
     */
    public function agents(): void {
        $this->load->language('marketplace/agoraspace');
        
        $data['agents'] = $this->getAGIAgents();
        $data['atomspace_status'] = $this->checkAtomSpaceStatus();
        
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('marketplace/agoraspace_agents', $data));
    }
    
    /**
     * Market signals and intelligence
     */
    public function signals(): void {
        $this->load->language('marketplace/agoraspace');
        
        if (isset($this->request->get['filter_type'])) {
            $filter_type = (string)$this->request->get['filter_type'];
        } else {
            $filter_type = '';
        }
        
        if (isset($this->request->get['filter_confidence_min'])) {
            $filter_confidence_min = (float)$this->request->get['filter_confidence_min'];
        } else {
            $filter_confidence_min = 0.5;
        }
        
        $filter_data = [
            'filter_type' => $filter_type,
            'filter_confidence_min' => $filter_confidence_min,
            'limit' => 50
        ];
        
        $data['market_signals'] = $this->getMarketSignals($filter_data);
        $data['signal_types'] = $this->getSignalTypes();
        
        $data['filter_type'] = $filter_type;
        $data['filter_confidence_min'] = $filter_confidence_min;
        
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('marketplace/agoraspace_signals', $data));
    }
    
    /**
     * OpenCog integration settings
     */
    public function settings(): void {
        $this->load->language('marketplace/agoraspace');
        
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->load->model('setting/setting');
            
            $this->model_setting_setting->editSetting('agoraspace', $this->request->post);
            
            $this->session->data['success'] = $this->language->get('text_success');
            
            $this->response->redirect($this->url->link('marketplace/agoraspace/settings', 'user_token=' . $this->session->data['user_token']));
        }
        
        $data['breadcrumbs'] = [];
        
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
        ];
        
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('marketplace/agoraspace', 'user_token=' . $this->session->data['user_token'])
        ];
        
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_settings'),
            'href' => $this->url->link('marketplace/agoraspace/settings', 'user_token=' . $this->session->data['user_token'])
        ];
        
        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }
        
        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }
        
        // Form settings
        $data['action'] = $this->url->link('marketplace/agoraspace/settings', 'user_token=' . $this->session->data['user_token']);
        $data['cancel'] = $this->url->link('marketplace/agoraspace', 'user_token=' . $this->session->data['user_token']);
        
        // Configuration values
        if (isset($this->request->post['agoraspace_enabled'])) {
            $data['agoraspace_enabled'] = $this->request->post['agoraspace_enabled'];
        } else {
            $data['agoraspace_enabled'] = $this->config->get('agoraspace_enabled');
        }
        
        if (isset($this->request->post['agoraspace_opencog_url'])) {
            $data['agoraspace_opencog_url'] = $this->request->post['agoraspace_opencog_url'];
        } else {
            $data['agoraspace_opencog_url'] = $this->config->get('agoraspace_opencog_url') ?: 'localhost';
        }
        
        if (isset($this->request->post['agoraspace_opencog_port'])) {
            $data['agoraspace_opencog_port'] = $this->request->post['agoraspace_opencog_port'];
        } else {
            $data['agoraspace_opencog_port'] = $this->config->get('agoraspace_opencog_port') ?: '17001';
        }
        
        if (isset($this->request->post['agoraspace_agi_enabled'])) {
            $data['agoraspace_agi_enabled'] = $this->request->post['agoraspace_agi_enabled'];
        } else {
            $data['agoraspace_agi_enabled'] = $this->config->get('agoraspace_agi_enabled');
        }
        
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('marketplace/agoraspace_settings', $data));
    }
    
    /**
     * Test OpenCog AtomSpace connection
     */
    public function testConnection(): void {
        $json = [];
        
        try {
            // Try to initialize the AtomSpace orchestrator
            $registry = new \Opencart\System\Engine\Registry();
            $registry->set('db', $this->db);
            $registry->set('log', $this->log);
            $registry->set('config', $this->config);
            
            $orchestrator = new \O9Cart\Intelligence\OpenCog\AgoraspaceOrchestrator($registry);
            
            if ($orchestrator->initializeAgoraspace()) {
                $json['success'] = 'OpenCog AtomSpace connection successful!';
                $json['status'] = 'connected';
            } else {
                $json['error'] = 'Failed to connect to OpenCog AtomSpace';
                $json['status'] = 'disconnected';
            }
            
        } catch (Exception $e) {
            $json['error'] = 'Connection test failed: ' . $e->getMessage();
            $json['status'] = 'error';
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    /**
     * Initialize agoraspace database tables
     */
    public function initializeDatabase(): void {
        $json = [];
        
        try {
            $sql_file = DIR_APPLICATION . '../docs/database/agoraspace-schema.sql';
            
            if (file_exists($sql_file)) {
                $sql_content = file_get_contents($sql_file);
                $queries = explode(';', $sql_content);
                
                $executed = 0;
                foreach ($queries as $query) {
                    $query = trim($query);
                    if (!empty($query) && !preg_match('/^--/', $query)) {
                        $this->db->query($query);
                        $executed++;
                    }
                }
                
                $json['success'] = "Database initialized successfully. Executed {$executed} queries.";
                
            } else {
                $json['error'] = 'Database schema file not found.';
            }
            
        } catch (Exception $e) {
            $json['error'] = 'Database initialization failed: ' . $e->getMessage();
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    /**
     * Check AtomSpace server status
     */
    private function checkAtomSpaceStatus(): array {
        try {
            $registry = new \Opencart\System\Engine\Registry();
            $registry->set('db', $this->db);
            $registry->set('log', $this->log);
            $registry->set('config', $this->config);
            
            $connector = new \O9Cart\Intelligence\OpenCog\AtomSpaceConnector($registry);
            
            if ($connector->connect()) {
                return [
                    'status' => 'connected',
                    'message' => 'AtomSpace server is accessible',
                    'url' => $this->config->get('agoraspace_opencog_url') ?: 'localhost',
                    'port' => $this->config->get('agoraspace_opencog_port') ?: '17001'
                ];
            } else {
                return [
                    'status' => 'disconnected',
                    'message' => 'AtomSpace server is not accessible',
                    'url' => $this->config->get('agoraspace_opencog_url') ?: 'localhost',
                    'port' => $this->config->get('agoraspace_opencog_port') ?: '17001'
                ];
            }
            
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Error checking AtomSpace status: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get community statistics
     */
    private function getCommunityStatistics(): array {
        $stats = [];
        
        try {
            // Total members
            $query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "agoraspace_members WHERE status = 'active'");
            $stats['total_members'] = $query->row['total'] ?? 0;
            
            // Active collaborations
            $query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "agoraspace_collaborations WHERE status IN ('active', 'recruiting')");
            $stats['active_collaborations'] = $query->row['total'] ?? 0;
            
            // Total skills
            $query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "agoraspace_skills");
            $stats['total_skills'] = $query->row['total'] ?? 0;
            
            // Market signals
            $query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "agoraspace_market_signals WHERE status = 'active'");
            $stats['active_signals'] = $query->row['total'] ?? 0;
            
        } catch (Exception $e) {
            // Return empty stats if tables don't exist yet
            $stats = [
                'total_members' => 0,
                'active_collaborations' => 0,
                'total_skills' => 0,
                'active_signals' => 0
            ];
        }
        
        return $stats;
    }
    
    /**
     * Get recent community activities
     */
    private function getRecentActivities(): array {
        $activities = [];
        
        try {
            $query = $this->db->query("
                SELECT 'member_joined' as activity_type, username as title, join_date as created_at
                FROM " . DB_PREFIX . "agoraspace_members 
                WHERE status = 'active'
                ORDER BY join_date DESC 
                LIMIT 5
            ");
            
            foreach ($query->rows as $row) {
                $activities[] = [
                    'type' => $row['activity_type'],
                    'title' => $row['title'],
                    'created_at' => $row['created_at']
                ];
            }
            
        } catch (Exception $e) {
            // Return empty activities if tables don't exist yet
        }
        
        return $activities;
    }
    
    /**
     * Get active market signals
     */
    private function getActiveMarketSignals(): array {
        $signals = [];
        
        try {
            $query = $this->db->query("
                SELECT signal_type, entity_name, signal_strength, confidence_level, created_at
                FROM " . DB_PREFIX . "agoraspace_market_signals 
                WHERE status = 'active' 
                ORDER BY signal_strength DESC, created_at DESC 
                LIMIT 10
            ");
            
            $signals = $query->rows;
            
        } catch (Exception $e) {
            // Return empty signals if tables don't exist yet
        }
        
        return $signals;
    }
    
    /**
     * Get AGI agent status
     */
    private function getAGIAgentStatus(): array {
        $agents = [];
        
        try {
            $query = $this->db->query("
                SELECT name, agent_type, specialization, status, trust_score, total_interactions
                FROM " . DB_PREFIX . "agoraspace_agi_agents 
                ORDER BY name
            ");
            
            $agents = $query->rows;
            
        } catch (Exception $e) {
            // Return empty agents if tables don't exist yet
        }
        
        return $agents;
    }
    
    /**
     * Get members with filters
     */
    private function getMembers($filter_data): array {
        $members = [];
        
        try {
            $sql = "SELECT * FROM " . DB_PREFIX . "agoraspace_members WHERE 1=1";
            
            if ($filter_data['filter_reputation_min'] > 0) {
                $sql .= " AND reputation_score >= " . (float)$filter_data['filter_reputation_min'];
            }
            
            if (!empty($filter_data['filter_role'])) {
                $sql .= " AND community_role = '" . $this->db->escape($filter_data['filter_role']) . "'";
            }
            
            $sql .= " ORDER BY reputation_score DESC";
            
            if (isset($filter_data['start']) && isset($filter_data['limit'])) {
                $sql .= " LIMIT " . (int)$filter_data['start'] . "," . (int)$filter_data['limit'];
            }
            
            $query = $this->db->query($sql);
            $members = $query->rows;
            
        } catch (Exception $e) {
            // Return empty members if tables don't exist yet
        }
        
        return $members;
    }
    
    /**
     * Get total members count
     */
    private function getTotalMembers($filter_data): int {
        try {
            $sql = "SELECT COUNT(*) as total FROM " . DB_PREFIX . "agoraspace_members WHERE 1=1";
            
            if ($filter_data['filter_reputation_min'] > 0) {
                $sql .= " AND reputation_score >= " . (float)$filter_data['filter_reputation_min'];
            }
            
            if (!empty($filter_data['filter_role'])) {
                $sql .= " AND community_role = '" . $this->db->escape($filter_data['filter_role']) . "'";
            }
            
            $query = $this->db->query($sql);
            return (int)$query->row['total'];
            
        } catch (Exception $e) {
            return 0;
        }
    }
    
    /**
     * Get collaborations with filters
     */
    private function getCollaborations($filter_data): array {
        $collaborations = [];
        
        try {
            $sql = "SELECT c.*, m.username as initiator_username 
                   FROM " . DB_PREFIX . "agoraspace_collaborations c
                   LEFT JOIN " . DB_PREFIX . "agoraspace_members m ON c.initiator_member_id = m.member_id
                   WHERE 1=1";
            
            if (!empty($filter_data['filter_status'])) {
                $sql .= " AND c.status = '" . $this->db->escape($filter_data['filter_status']) . "'";
            }
            
            if (!empty($filter_data['filter_type'])) {
                $sql .= " AND c.project_type = '" . $this->db->escape($filter_data['filter_type']) . "'";
            }
            
            $sql .= " ORDER BY c.created_at DESC";
            
            if (isset($filter_data['start']) && isset($filter_data['limit'])) {
                $sql .= " LIMIT " . (int)$filter_data['start'] . "," . (int)$filter_data['limit'];
            }
            
            $query = $this->db->query($sql);
            $collaborations = $query->rows;
            
        } catch (Exception $e) {
            // Return empty collaborations if tables don't exist yet
        }
        
        return $collaborations;
    }
    
    /**
     * Get total collaborations count
     */
    private function getTotalCollaborations($filter_data): int {
        try {
            $sql = "SELECT COUNT(*) as total FROM " . DB_PREFIX . "agoraspace_collaborations WHERE 1=1";
            
            if (!empty($filter_data['filter_status'])) {
                $sql .= " AND status = '" . $this->db->escape($filter_data['filter_status']) . "'";
            }
            
            if (!empty($filter_data['filter_type'])) {
                $sql .= " AND project_type = '" . $this->db->escape($filter_data['filter_type']) . "'";
            }
            
            $query = $this->db->query($sql);
            return (int)$query->row['total'];
            
        } catch (Exception $e) {
            return 0;
        }
    }
    
    /**
     * Get skills with filters
     */
    private function getSkills($filter_data): array {
        $skills = [];
        
        try {
            $sql = "SELECT s.*, COUNT(ms.member_id) as practitioner_count, AVG(ms.proficiency_level) as avg_proficiency
                   FROM " . DB_PREFIX . "agoraspace_skills s
                   LEFT JOIN " . DB_PREFIX . "agoraspace_member_skills ms ON s.skill_id = ms.skill_id
                   WHERE 1=1";
            
            if (!empty($filter_data['filter_category'])) {
                $sql .= " AND s.category = '" . $this->db->escape($filter_data['filter_category']) . "'";
            }
            
            $sql .= " GROUP BY s.skill_id";
            
            $allowed_sorts = ['name', 'category', 'demand_level', 'supply_level', 'practitioner_count'];
            if (in_array($filter_data['sort'], $allowed_sorts)) {
                $sql .= " ORDER BY " . $filter_data['sort'];
                if ($filter_data['order'] == 'DESC') {
                    $sql .= " DESC";
                }
            }
            
            if (isset($filter_data['start']) && isset($filter_data['limit'])) {
                $sql .= " LIMIT " . (int)$filter_data['start'] . "," . (int)$filter_data['limit'];
            }
            
            $query = $this->db->query($sql);
            $skills = $query->rows;
            
        } catch (Exception $e) {
            // Return empty skills if tables don't exist yet
        }
        
        return $skills;
    }
    
    /**
     * Get total skills count
     */
    private function getTotalSkills($filter_data): int {
        try {
            $sql = "SELECT COUNT(*) as total FROM " . DB_PREFIX . "agoraspace_skills WHERE 1=1";
            
            if (!empty($filter_data['filter_category'])) {
                $sql .= " AND category = '" . $this->db->escape($filter_data['filter_category']) . "'";
            }
            
            $query = $this->db->query($sql);
            return (int)$query->row['total'];
            
        } catch (Exception $e) {
            return 0;
        }
    }
    
    /**
     * Get skill categories
     */
    private function getSkillCategories(): array {
        try {
            $query = $this->db->query("SELECT DISTINCT category FROM " . DB_PREFIX . "agoraspace_skills ORDER BY category");
            return array_column($query->rows, 'category');
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Get AGI agents
     */
    private function getAGIAgents(): array {
        try {
            $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "agoraspace_agi_agents ORDER BY name");
            return $query->rows;
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Get market signals with filters
     */
    private function getMarketSignals($filter_data): array {
        $signals = [];
        
        try {
            $sql = "SELECT * FROM " . DB_PREFIX . "agoraspace_market_signals WHERE status = 'active'";
            
            if (!empty($filter_data['filter_type'])) {
                $sql .= " AND signal_type = '" . $this->db->escape($filter_data['filter_type']) . "'";
            }
            
            if ($filter_data['filter_confidence_min'] > 0) {
                $sql .= " AND confidence_level >= " . (float)$filter_data['filter_confidence_min'];
            }
            
            $sql .= " ORDER BY signal_strength DESC, created_at DESC";
            
            if (isset($filter_data['limit'])) {
                $sql .= " LIMIT " . (int)$filter_data['limit'];
            }
            
            $query = $this->db->query($sql);
            $signals = $query->rows;
            
        } catch (Exception $e) {
            // Return empty signals if tables don't exist yet
        }
        
        return $signals;
    }
    
    /**
     * Get signal types
     */
    private function getSignalTypes(): array {
        return [
            'demand_spike' => 'Demand Spike',
            'supply_shortage' => 'Supply Shortage',
            'price_anomaly' => 'Price Anomaly',
            'trend_emergence' => 'Trend Emergence',
            'opportunity' => 'Opportunity',
            'skill_gap' => 'Skill Gap',
            'collaboration_potential' => 'Collaboration Potential'
        ];
    }
    
    /**
     * Validate permissions
     */
    private function validate(): bool {
        if (!$this->user->hasPermission('modify', 'marketplace/agoraspace')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        
        return !$this->error;
    }
}