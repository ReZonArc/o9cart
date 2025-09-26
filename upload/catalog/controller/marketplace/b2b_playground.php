<?php
namespace Opencart\Catalog\Controller\Marketplace;

/**
 * Class B2BPlayground
 * 
 * Frontend playground interface for testing B2B marketplace features
 * 
 * @package Opencart\Catalog\Controller\Marketplace
 */
class B2BPlayground extends \Opencart\System\Engine\Controller {
    
    /**
     * Index - Main playground page
     * 
     * @return void
     */
    public function index(): void {
        $this->load->language('marketplace/b2b_playground');
        
        $this->document->setTitle($this->language->get('heading_title'));
        $this->document->setDescription($this->language->get('meta_description'));
        
        $data['breadcrumbs'] = [];
        
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home')
        ];
        
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('marketplace/b2b_playground')
        ];
        
        // B2B playground features demonstration
        $data['b2b_features'] = [
            [
                'title' => 'B2B Wholesale Portal',
                'description' => 'Experience bulk ordering with tiered pricing',
                'url' => $this->url->link('marketplace/b2b_playground.wholesale'),
                'icon' => 'fa-building'
            ],
            [
                'title' => 'Multi-Store Sync',
                'description' => 'See how inventory syncs across multiple stores',
                'url' => $this->url->link('marketplace/b2b_playground.multistore'),
                'icon' => 'fa-sync'
            ],
            [
                'title' => 'Local Sales Integration',
                'description' => 'Connect online orders with local pickup',
                'url' => $this->url->link('marketplace/b2b_playground.localsales'),
                'icon' => 'fa-map-marker-alt'
            ],
            [
                'title' => 'Member Linking Demo',
                'description' => 'See cart matching between B2B and local customers',
                'url' => $this->url->link('marketplace/b2b_playground.memberlinking'),
                'icon' => 'fa-link'
            ]
        ];
        
        // Demo statistics
        $data['demo_stats'] = [
            ['label' => 'B2B Stores', 'value' => '3', 'icon' => 'fa-store'],
            ['label' => 'Active Links', 'value' => '15', 'icon' => 'fa-link'],
            ['label' => 'Cart Matches', 'value' => '42', 'icon' => 'fa-shopping-cart'],
            ['label' => 'Sync Operations', 'value' => '128', 'icon' => 'fa-sync']
        ];
        
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');
        
        $this->response->setOutput($this->load->view('marketplace/b2b_playground', $data));
    }
}