<?php
/**
 * Integration Hub Admin Controller
 * 
 * Admin interface for managing integrations and data synchronization
 * 
 * @category   Controller
 * @package    Admin
 * @subpackage Integration
 * @author     O9Cart Development Team
 * @license    GPL v3
 */

class ControllerIntegrationHub extends Controller {
    
    private $integration_manager;
    
    public function __construct($registry) {
        parent::__construct($registry);
        
        // Load the integration manager
        $this->load->library('integration/hub/IntegrationManager');
        $this->integration_manager = new \O9Cart\Integration\Hub\IntegrationManager($registry);
    }
    
    /**
     * Main index page - list all integrations
     */
    public function index() {
        $this->load->language('integration/hub');
        
        $this->document->setTitle($this->language->get('heading_title'));
        
        $data['breadcrumbs'] = [];
        
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
        ];
        
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('integration/hub', 'user_token=' . $this->session->data['user_token'])
        ];
        
        $data['add'] = $this->url->link('integration/hub/form', 'user_token=' . $this->session->data['user_token']);
        $data['delete'] = $this->url->link('integration/hub/delete', 'user_token=' . $this->session->data['user_token']);
        
        // Get filters
        $filter_type = $this->request->get['filter_type'] ?? '';
        $filter_status = $this->request->get['filter_status'] ?? '';
        
        $filters = [];
        if ($filter_type) $filters['type'] = $filter_type;
        if ($filter_status) $filters['status'] = $filter_status;
        
        // Get integrations
        $integrations = $this->integration_manager->getIntegrations($filters);
        
        $data['integrations'] = [];
        
        foreach ($integrations as $integration) {
            $data['integrations'][] = [
                'integration_id' => $integration['integration_id'],
                'name' => $integration['name'],
                'type' => ucfirst($integration['type']),
                'status' => ucfirst($integration['status']),
                'created_at' => date('Y-m-d H:i:s', strtotime($integration['created_at'])),
                'updated_at' => date('Y-m-d H:i:s', strtotime($integration['updated_at'])),
                'edit' => $this->url->link('integration/hub/form', 'user_token=' . $this->session->data['user_token'] . '&integration_id=' . $integration['integration_id']),
                'test' => $this->url->link('integration/hub/test', 'user_token=' . $this->session->data['user_token'] . '&integration_id=' . $integration['integration_id']),
                'sync' => $this->url->link('integration/hub/sync', 'user_token=' . $this->session->data['user_token'] . '&integration_id=' . $integration['integration_id'])
            ];
        }
        
        $data['filter_type'] = $filter_type;
        $data['filter_status'] = $filter_status;
        
        $data['types'] = [
            '' => $this->language->get('text_all'),
            'import' => $this->language->get('text_import'),
            'export' => $this->language->get('text_export'),
            'sync' => $this->language->get('text_sync'),
            'webhook' => $this->language->get('text_webhook')
        ];
        
        $data['statuses'] = [
            '' => $this->language->get('text_all'),
            'active' => $this->language->get('text_active'),
            'inactive' => $this->language->get('text_inactive'),
            'error' => $this->language->get('text_error')
        ];
        
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('integration/hub_list', $data));
    }
    
    /**
     * Add/Edit integration form
     */
    public function form() {
        $this->load->language('integration/hub');
        
        $this->document->setTitle($this->language->get('heading_title'));
        
        $data['breadcrumbs'] = [];
        
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
        ];
        
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('integration/hub', 'user_token=' . $this->session->data['user_token'])
        ];
        
        $integration_id = $this->request->get['integration_id'] ?? 0;
        
        if ($integration_id) {
            $data['breadcrumbs'][] = [
                'text' => $this->language->get('text_edit'),
                'href' => $this->url->link('integration/hub/form', 'user_token=' . $this->session->data['user_token'] . '&integration_id=' . $integration_id)
            ];
        } else {
            $data['breadcrumbs'][] = [
                'text' => $this->language->get('text_add'),
                'href' => $this->url->link('integration/hub/form', 'user_token=' . $this->session->data['user_token'])
            ];
        }
        
        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            try {
                if ($integration_id) {
                    $this->integration_manager->updateIntegration($integration_id, $this->request->post);
                    $this->session->data['success'] = $this->language->get('text_success_update');
                } else {
                    $integration_id = $this->integration_manager->createIntegration($this->request->post);
                    $this->session->data['success'] = $this->language->get('text_success_add');
                }
                
                $this->response->redirect($this->url->link('integration/hub', 'user_token=' . $this->session->data['user_token']));
                
            } catch (Exception $e) {
                $data['error_warning'] = $e->getMessage();
            }
        }
        
        $data['action'] = $this->url->link('integration/hub/form', 'user_token=' . $this->session->data['user_token'] . ($integration_id ? '&integration_id=' . $integration_id : ''));
        $data['cancel'] = $this->url->link('integration/hub', 'user_token=' . $this->session->data['user_token']);
        
        // Load integration data if editing
        if ($integration_id) {
            $integration = $this->integration_manager->getIntegration($integration_id);
            if ($integration) {
                $data['name'] = $integration['name'];
                $data['type'] = $integration['type'];
                $data['status'] = $integration['status'];
                $data['config_data'] = $integration['config_data'];
            }
        } else {
            $data['name'] = '';
            $data['type'] = 'sync';
            $data['status'] = 'inactive';
            $data['config_data'] = [];
        }
        
        $data['types'] = [
            'import' => $this->language->get('text_import'),
            'export' => $this->language->get('text_export'),
            'sync' => $this->language->get('text_sync'),
            'webhook' => $this->language->get('text_webhook')
        ];
        
        $data['statuses'] = [
            'active' => $this->language->get('text_active'),
            'inactive' => $this->language->get('text_inactive'),
            'error' => $this->language->get('text_error')
        ];
        
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('integration/hub_form', $data));
    }
    
    /**
     * Delete integration
     */
    public function delete() {
        $this->load->language('integration/hub');
        
        if (isset($this->request->post['selected']) && $this->validateDelete()) {
            foreach ($this->request->post['selected'] as $integration_id) {
                $this->integration_manager->deleteIntegration($integration_id);
            }
            
            $this->session->data['success'] = $this->language->get('text_success_delete');
        }
        
        $this->response->redirect($this->url->link('integration/hub', 'user_token=' . $this->session->data['user_token']));
    }
    
    /**
     * Test integration connection
     */
    public function test() {
        $this->load->language('integration/hub');
        
        $integration_id = $this->request->get['integration_id'] ?? 0;
        
        if ($integration_id) {
            try {
                $result = $this->integration_manager->testIntegration($integration_id);
                
                if ($result['success']) {
                    $this->session->data['success'] = $this->language->get('text_test_success');
                } else {
                    $this->session->data['error'] = $this->language->get('text_test_failed') . ': ' . $result['error'];
                }
                
            } catch (Exception $e) {
                $this->session->data['error'] = $this->language->get('text_test_error') . ': ' . $e->getMessage();
            }
        }
        
        $this->response->redirect($this->url->link('integration/hub', 'user_token=' . $this->session->data['user_token']));
    }
    
    /**
     * Execute sync job
     */
    public function sync() {
        $this->load->language('integration/hub');
        
        $integration_id = $this->request->get['integration_id'] ?? 0;
        $job_type = $this->request->get['job_type'] ?? 'sync';
        
        if ($integration_id) {
            try {
                $job_id = $this->integration_manager->executeSyncJob($integration_id, $job_type);
                $this->session->data['success'] = $this->language->get('text_sync_started') . ' (Job ID: ' . $job_id . ')';
                
            } catch (Exception $e) {
                $this->session->data['error'] = $this->language->get('text_sync_error') . ': ' . $e->getMessage();
            }
        }
        
        $this->response->redirect($this->url->link('integration/hub', 'user_token=' . $this->session->data['user_token']));
    }
    
    /**
     * Get sync job status (AJAX)
     */
    public function getJobStatus() {
        $job_id = $this->request->get['job_id'] ?? 0;
        
        if ($job_id) {
            $status = $this->integration_manager->getSyncJobStatus($job_id);
            
            if ($status) {
                $this->response->addHeader('Content-Type: application/json');
                $this->response->setOutput(json_encode([
                    'success' => true,
                    'data' => $status
                ]));
            } else {
                $this->response->addHeader('Content-Type: application/json');
                $this->response->setOutput(json_encode([
                    'success' => false,
                    'error' => 'Job not found'
                ]));
            }
        } else {
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode([
                'success' => false,
                'error' => 'Job ID required'
            ]));
        }
    }
    
    /**
     * View sync jobs for integration
     */
    public function jobs() {
        $this->load->language('integration/hub');
        
        $integration_id = $this->request->get['integration_id'] ?? 0;
        
        if (!$integration_id) {
            $this->response->redirect($this->url->link('integration/hub', 'user_token=' . $this->session->data['user_token']));
            return;
        }
        
        $integration = $this->integration_manager->getIntegration($integration_id);
        if (!$integration) {
            $this->response->redirect($this->url->link('integration/hub', 'user_token=' . $this->session->data['user_token']));
            return;
        }
        
        $this->document->setTitle($this->language->get('heading_title_jobs'));
        
        $data['breadcrumbs'] = [];
        
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
        ];
        
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('integration/hub', 'user_token=' . $this->session->data['user_token'])
        ];
        
        $data['breadcrumbs'][] = [
            'text' => $integration['name'] . ' - ' . $this->language->get('text_jobs'),
            'href' => $this->url->link('integration/hub/jobs', 'user_token=' . $this->session->data['user_token'] . '&integration_id=' . $integration_id)
        ];
        
        $data['integration_name'] = $integration['name'];
        $data['back'] = $this->url->link('integration/hub', 'user_token=' . $this->session->data['user_token']);
        
        // Get sync jobs
        $jobs = $this->integration_manager->getSyncJobs($integration_id);
        
        $data['jobs'] = [];
        
        foreach ($jobs as $job) {
            $data['jobs'][] = [
                'job_id' => $job['job_id'],
                'job_type' => ucfirst(str_replace('_', ' ', $job['job_type'])),
                'status' => ucfirst($job['status']),
                'progress' => $job['progress'],
                'total_records' => $job['total_records'],
                'started_at' => $job['started_at'] ? date('Y-m-d H:i:s', strtotime($job['started_at'])) : '-',
                'completed_at' => $job['completed_at'] ? date('Y-m-d H:i:s', strtotime($job['completed_at'])) : '-',
                'error_log' => $job['error_log']
            ];
        }
        
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('integration/hub_jobs', $data));
    }
    
    /**
     * Validate delete permission
     */
    protected function validateDelete() {
        if (!$this->user->hasPermission('modify', 'integration/hub')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        
        return !$this->error;
    }
}