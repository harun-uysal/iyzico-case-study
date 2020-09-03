<?php
class ControllerExtensionModuleIyzicoCase extends Controller {
 
    public function index()
    {
        $this->load->model('setting/setting');

        $data['insert_tag'] = "#product";
        $data['insert_type'] = "append";

        $data['button'] = $this->config->get('module_iyzico_case_button');
		
        return $this->load->view('extension/module/iyzico_case', $data);
    }

    public function createOrder()
    {	
        
        $this->load->language('extension/module/iyzico_case');

        $errors = array();
		
        if (isset($this->request->post['product_id'])) 
        {

            $product_id = (int)$this->request->post['product_id'];
            $this->load->model('catalog/product');

			$product_info = $this->model_catalog_product->getProduct($product_id);

            if ($product_info)
            {

                if (isset($this->request->post['quantity']))
                {
					$quantity = (int)$this->request->post['quantity'];
                }
                else
                {
					$quantity = 1;
				}

                if (isset($this->request->post['option']))
                {
					$option = array_filter($this->request->post['option']);
                }
                else
                {
					$option = array();
				}

				$product_options = $this->model_catalog_product->getProductOptions($this->request->post['product_id']);

                foreach ($product_options as $product_option)
                {
                    if ($product_option['required'] && empty($option[$product_option['product_option_id']]))
                    {
                        $errors[] = sprintf($this->language->get('error_required'), $product_option['name']);
					}
				}
                
            }

        }

        if($errors)
        {
            $data['error'] = $errors;
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($data));
            return;
        }

        $product_price = $this->tax->calculate($product_info['price'], $product_info['tax_class_id'], $this->config->get('config_tax'));
        
        $product_price = number_format($product_price, "2", '.', '');
        $total_price = number_format($product_price * $quantity, "2", '.', '');
        
        $product_name = $product_info['name'];
        $product_category = $this->getCategoryName($product_id);

        
        require_once DIR_SYSTEM . 'library/iyzipay/IyzipayBootstrap.php';

        $api_key = $this->config->get('module_iyzico_case_api_key');
        $secret_key = $this->config->get('module_iyzico_case_secret_key');

        IyzipayBootstrap::init();

        $options = new \Iyzipay\Options();
        $options->setApiKey($api_key);
        $options->setSecretKey($secret_key);
        $options->setBaseUrl("https://sandbox-api.iyzipay.com");
                
        $request = new \Iyzipay\Request\CreatePayWithIyzicoInitializeRequest();
        $request->setLocale(\Iyzipay\Model\Locale::TR);
        $request->setPrice($total_price);
        $request->setPaidPrice($total_price);
        $request->setCurrency(\Iyzipay\Model\Currency::TL);
        $request->setCallbackUrl($this->url->link('extension/module/iyzico_case/getCallBack', '', true));
        
        $buyer = new \Iyzipay\Model\Buyer();
        $buyer->setId("0");
        $buyer->setName("John");
        $buyer->setSurname("Doe");
        $buyer->setEmail("email@email.com");
        $buyer->setIdentityNumber("74300864791");
        $buyer->setRegistrationAddress("Nidakule Göztepe, Merdivenköy Mah. Bora Sok. No:1");
        $buyer->setIp($_SERVER['REMOTE_ADDR']);
        $buyer->setCity("Istanbul");
        $buyer->setCountry("Turkey");
        $buyer->setZipCode("34732");
        $request->setBuyer($buyer);

        $shippingAddress = new \Iyzipay\Model\Address();
        $shippingAddress->setContactName("Jane Doe");
        $shippingAddress->setCity("Istanbul");
        $shippingAddress->setCountry("Turkey");
        $shippingAddress->setAddress("Nidakule Göztepe, Merdivenköy Mah. Bora Sok. No:1");
        $shippingAddress->setZipCode("34742");
        $request->setShippingAddress($shippingAddress);

        $billingAddress = new \Iyzipay\Model\Address();
        $billingAddress->setContactName("Jane Doe");
        $billingAddress->setCity("Istanbul");
        $billingAddress->setCountry("Turkey");
        $billingAddress->setAddress("Nidakule Göztepe, Merdivenköy Mah. Bora Sok. No:1");
        $billingAddress->setZipCode("34742");
        $request->setBillingAddress($billingAddress);
        
        $basketItems = array();
        $firstBasketItem = new \Iyzipay\Model\BasketItem();
        $firstBasketItem->setId($product_id);
        $firstBasketItem->setName($product_name);
        $firstBasketItem->setCategory1($product_category);
        $firstBasketItem->setItemType(\Iyzipay\Model\BasketItemType::PHYSICAL);
        $firstBasketItem->setPrice($total_price);
        $basketItems[0] = $firstBasketItem;
        $request->setBasketItems($basketItems);


        $payWithIyzicoInitialize = \Iyzipay\Model\PayWithIyzicoInitialize::create($request, $options);

        $payWithIyzicoStatus = $payWithIyzicoInitialize->getStatus();

        if($payWithIyzicoStatus == "failure")
        {
            $payWithIyzicoErrorMessage = $payWithIyzicoInitialize->getErrorMessage();

            $data['error'] = "Ödeme isteği sırasında hata oluştu. - " . $payWithIyzicoErrorMessage;
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($data));
            return;
        }

        $payment_url = $payWithIyzicoInitialize->getPayWithIyzicoPageUrl();

        $payment_token = $payWithIyzicoInitialize->getToken();

        $data['url'] = $payment_url;

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($data));

    }

    public function getCallBack()
    {

        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');

        if(!isset($this->request->post['token']) || empty($this->request->post['token']))
        {
            $data['payment_result'] = "Geçersiz işlem anahtarı.";
            return $this->response->setOutput($this->load->view('extension/module/iyzico_case_result', $data));        
        }

        require_once DIR_SYSTEM . 'library/iyzipay/IyzipayBootstrap.php';

        $api_key = $this->config->get('module_iyzico_case_api_key');
        $secret_key = $this->config->get('module_iyzico_case_secret_key');

        IyzipayBootstrap::init();

        $options = new \Iyzipay\Options();
        $options->setApiKey($api_key);
        $options->setSecretKey($secret_key);
        $options->setBaseUrl("https://sandbox-api.iyzipay.com");

        $request = new \Iyzipay\Request\RetrievePayWithIyzicoRequest();
        $request->setLocale(\Iyzipay\Model\Locale::TR);
        $request->setToken($this->request->post['token']);
        $payWithIyzico = \Iyzipay\Model\PayWithIyzico::retrieve($request, $options);
        
        $payment_status = $payWithIyzico->getPaymentStatus();

        if($payment_status == "SUCCESS")
        {
            $payment_total = $payWithIyzico->getPaidPrice();
            $payment_currency = $payWithIyzico->getCurrency();
            $data['payment_result'] = "Ödemeniz alındı.";
            $data['payment_total'] = $payment_total;
            $data['payment_currency'] = $payment_currency;
        }
        else
        {
            $error_message = $payWithIyzico->getErrorMessage();
            $data['payment_result'] = "Ödeme sırasında hata oluştu. - ".$error_message;
        }
    
        return $this->response->setOutput($this->load->view('extension/module/iyzico_case_result', $data));

    }

    public function getCategoryName($product_id)
    {
        $product_id = $this->db->escape($product_id);

		$query = $this->db->query("SELECT category_id FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . $product_id . "' LIMIT 1");

        if(count($query->rows))
        {

			$category_id = $this->db->escape($query->rows[0]['category_id']);

			$category 	 = $this->db->query("SELECT name FROM " . DB_PREFIX . "category_description WHERE category_id = '" . $category_id . "' LIMIT 1");

            if($category->rows[0]['name'])
            {
				$category_name = $category->rows[0]['name'];
            }
            else
            {
				$category_name = 'NO CATEGORIES';
			}

        }
        else
        {
			$category_name = 'NO CATEGORIES';
		}
		
		return $category_name;
    }

}