<?php
class ControllerExtensionModuleIyzicoCase extends Controller {

    private $button_shapes   = array("Yuvarlak"=>'pill',"Dikdörtgen"=>'rect');
    private $button_colors   = array("Mavi"=>'blue',"Turuncu"=>'orange',"Sarı"=>'yellow',"Siyah"=>'black');
    private $button_taglines = array("Yazı"=>'text',"Logo"=>'logo');

    public function install()
    {
        $this->load->model('setting/setting');
        $this->model_setting_setting->editSetting('module_iyzico_case', ['module_iyzico_case_status'=>0]);

        $query = $this->db->query("SELECT DISTINCT layout_id FROM " . DB_PREFIX . "layout_route WHERE route = 'product/product'");
		
		$layouts = $query->rows;
		
        foreach ($layouts as $layout)
        {
			$this->db->query("INSERT INTO " . DB_PREFIX . "layout_module SET layout_id = '" . (int)$layout['layout_id'] . "', code = 'iyzico_case', position = 'content_top', sort_order = '0'");
        }
        
    }

    public function uninstall()
    {
        $this->load->model('setting/setting');
        $this->model_setting_setting->editSetting('module_iyzico_case', ['module_iyzico_case_status'=>0]);
    }

    protected function validate()
    {
        if (!$this->user->hasPermission('modify', 'extension/module/iyzico_case'))
        {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }

    public function index()
    {

        $this->load->language('extension/module/iyzico_case');
        $this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate())
        {
         
            $this->model_setting_setting->editSetting('module_iyzico_case', $this->request->post);

			$this->session->data['success'] = $this->language->get('success_save');
            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));

        }

        if (isset($this->error['warning']))
        {
            $data['error_warning'] = $this->error['warning'];
        }
        else
        {
            $data['error_warning'] = '';
        }

        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
        );
 
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/module/iyzico_case', 'user_token=' . $this->session->data['user_token'], true)
        );
    
        $data['action'] = $this->url->link('extension/module/iyzico_case', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

        $data['text_enabled'] = $this->language->get('text_enabled');
        $data['text_disabled'] = $this->language->get('text_disabled');

        $data['button_shapes'] = $this->button_shapes;
        $data['button_colors'] = $this->button_colors;
        $data['button_taglines'] = $this->button_taglines;
    
        if (isset($this->request->post['status']))
        {
            $data['status'] = $this->request->post['status'];
        }
        else
        {
            $data['status'] = $this->config->get('module_iyzico_case_status');
        }

        if (isset($this->request->post['api_key']))
        {
            $data['api_key'] = $this->request->post['api_key'];
        }
        else
        {
            $data['api_key'] = $this->config->get('module_iyzico_case_api_key');
        }

        if (isset($this->request->post['secret_key']))
        {
            $data['secret_key'] = $this->request->post['secret_key'];
        }
        else
        {
            $data['secret_key'] = $this->config->get('module_iyzico_case_secret_key');
        }

        if (isset($this->request->post['button']))
        {
            $data['button'] = $this->request->post['button'];
        }
        else
        {
            $data['button'] = $this->config->get('module_iyzico_case_button');
        }


        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        $this->response->setOutput($this->load->view('extension/module/iyzico_case', $data));
    
    }

}
