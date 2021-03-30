<?php
/**
 * Novalnet payment module
 *
 * This module is used for real time processing of Novalnet transaction of customers.
 *
 * This free contribution made by request.
 * If you have found this script useful a small
 * recommendation as well as a comment on merchant form
 * would be greatly appreciated.
 *
 * @author    Novalnet AG
 * @copyright Copyright by Novalnet
 * @license   https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 *
 * Script: LightspeedController.php
 */

namespace Lightspeed\Controller;

use Zend\Mail;
use Lightspeed\Model\Lightspeed;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Lightspeed\Model\LightspeedTable;
use Lightspeed\Helper\NovalnetHelper;
use Lightspeed\Helper\WebshopappApiClient;
use Zend\I18n\Translator\Translator;
use Zend\Stdlib\ParametersInterface;
use Zend\Http\Client;

class LightspeedController extends AbstractActionController
{
    private $table;
    private $helper;
    public $api_key = 'b6bf86a4938e57c09d37c09fb7b6ed0e';
    public $api_secret = '46c570387c3f3f59beef08c394755e36';

    /**
     * Initialize the object
     *
     * @param  object $table
     * @return none
     */
    public function __construct(LightspeedTable $table)
    {
      $this->table = $table;
      $this->helper = new NovalnetHelper($this->table);
    }

    /**
     * Redirect to novalnet
     *
     * @param  none
     * @return none
     */
    public function indexAction()
    {
        header('Location:https://admin.novalnet.de/');
        exit;
    }

    /**
     * Respond with the payment methiod to be displayed
     *
     * @param  none
     * @return none
     */
    public function paymentmethodsAction()
    {
        $params = array();

        $params = json_decode(file_get_contents('php://input'), true);
        $lang = (!empty($params['shop']['language']) && $params['shop']['language'] == 'de') ? 'de_DE' : 'en_US';
        $translator = new Translator();
        $translator->addTranslationFile('phparray', dirname(__DIR__). '/lang/'.$lang.'.php', '', $lang);
        if((isset($params['shop']['id']) && !empty($params['shop']['id'])) && (!empty($this->getRequest()->getServer()->get('HTTP_USER_AGENT')) && $this->getRequest()->getServer()->get('HTTP_USER_AGENT') == 'WebshopappApi') && !empty($this->getRequest()->getServer()->get('HTTP_X_SHOP_ID'))) {
            $table_data = array(
            'shop_id' => $params['shop']['id'],
            'request_domain' => $this->getRequest()->getServer()->get('HTTP_USER_AGENT'),
            'request_ip' => $this->getRequest()->getServer()->get('REMOTE_ADDR')
            );
            $this->table->insert($table_data, 'novalnet_lightspeed_request_log');
            $config_check = $this->helper->validateConfigParam($params['shop']['id']);
            if(!$config_check){
                echo '';
                exit;
            }
            $address = (!empty($params['billing_address']['address2'])) ? $params['billing_address']['address1'].','.$params['billing_address']['address2'] : $params['billing_address']['address1'];
            $payment_gateways_list = json_decode($this->table->fetch_value(['shop_id' => $params['shop']['id'], 'config_path' => 'payment_gateways_list'], 'novalnet_lightspeed_merchant_configuration', ['value']));
            $shop_id = $params['shop']['id'];
            
            $guarantee_invoice_enabled = $this->table->fetch_value(['shop_id' => $shop_id, 'config_path' => 'novalnet_invoice_enable_guarantee'], 'novalnet_lightspeed_merchant_configuration', ['value']);

            $guarantee_sepa_enabled = $this->table->fetch_value(['shop_id' => $shop_id, 'config_path' => 'novalnet_sepa_enable_guarantee'], 'novalnet_lightspeed_merchant_configuration', ['value']);
			
            $guarantee_invoice_force_enabled = $this->table->fetch_value(['shop_id' => $shop_id, 'config_path' => 'novalnet_invoice_enable_force_guarantee'], 'novalnet_lightspeed_merchant_configuration', ['value']);
            
            $guarantee_sepa_force_enabled = $this->table->fetch_value(['shop_id' => $shop_id, 'config_path' => 'novalnet_sepa_enable_force_guarantee'], 'novalnet_lightspeed_merchant_configuration', ['value']);

            $guarantee_invoice_minimum_amount = $this->table->fetch_value(['shop_id' => $shop_id, 'config_path' => 'novalnet_invoice_guarantee_minimum_amount'], 'novalnet_lightspeed_merchant_configuration', ['value']);
            $min_invoice_amount = !empty($guarantee_invoice_minimum_amount) ? $guarantee_invoice_minimum_amount : 999;

            $guarantee_sepa_minimum_amount = $this->table->fetch_value(['shop_id' => $shop_id, 'config_path' => 'novalnet_sepa_guarantee_minimum_amount'], 'novalnet_lightspeed_merchant_configuration', ['value']);
            $min_sepa_amount = !empty($guarantee_sepa_minimum_amount) ? $guarantee_sepa_minimum_amount : 999;

            $order_amount = $params['quote']['price_incl']*100;

            $country = strtoupper($params['billing_address']['country']);

            $currency = strtoupper($params['shop']['currency']);

            $billing_address = $params['billing_address'];

            $shipping_address = $params['shipping_address'];

            $billing_shipping_diff = array_diff($billing_address, $shipping_address);
            $force_guarantee_sepa = $force_guarantee_invoice = 0;

            if($guarantee_sepa_enabled && ($order_amount < $min_sepa_amount || $currency != 'EUR' || !in_array($country, array('DE', 'AT', 'CH')) || !empty($billing_shipping_diff)) && in_array('novalnet_sepa', $payment_gateways_list))  {
                if(($guarantee_sepa_force_enabled) == 1) {
                    $force_guarantee_sepa = 1;
                } else {
                    $key = array_search('novalnet_sepa', $payment_gateways_list);
                    unset($payment_gateways_list[$key]);
                }

            }
            if($guarantee_invoice_enabled && ($order_amount < $min_invoice_amount || $currency != 'EUR' || !in_array($country, array('DE', 'AT', 'CH')) || !empty($billing_shipping_diff))  && in_array('novalnet_invoice', $payment_gateways_list))  {
                if(($guarantee_invoice_force_enabled) == 1) {
                    $force_guarantee_invoice = 1;
                } else {
                    $key = array_search('novalnet_invoice', $payment_gateways_list);
                    unset($payment_gateways_list[$key]);
                }
            }

            $i =1;
            foreach($payment_gateways_list as $key => $value ){
                $paymentdetails []= $this->helper->getPaymentDetails($value, $translator, $lang);
            }
            foreach($paymentdetails as $key => $value ){

                $enabled_payments[$i] = [
                                    "id" => $i,
                                    "title" => $value['payment_name'],
                                    "icon" => "https://lightspeed.novalnet.de/img/" . $value['logo'] . ".png",
                                    "data" => [
                                        "address" => $address,
                                        "house_no" => (!empty($params['billing_address']['number'])) ? $params['billing_address']['number'] : '',
                                        "city" => $params['billing_address']['city'],
                                        "zipcode" => $params['billing_address']['zipcode'],
                                        "country" => $params['billing_address']['country'],
                                        "key" => $value['key'],
                                        "payment_type" => $value['payment_type'],
                                    ]
                                ];
                if($value['payment_type'] == 'INVOICE_START') {
                    $enabled_payments[$i]['data']['invoice_type'] = $value['invoice_type'];
                    if($value['invoice_type'] == 'INVOICE') {
                        $enabled_payments[$i]['data']['force_guarantee_invoice'] = $force_guarantee_invoice;
                    }
                }
                if($value['payment_type'] == 'DIRECT_DEBIT_SEPA') {
                    $enabled_payments[$i]['data']['force_guarantee_sepa'] = $force_guarantee_sepa;
                }
                $i++;
            }
            $data = [ "payment_methods" =>  $enabled_payments ];
            $this->helper->sendResponse($data);
        } else {
            $text = urlencode('Unauthorized access');
            return $this->redirect()->toRoute('backend', ['action' => 'denied','signature' => $text]);
        }
    }

    /**
     * Respond with payment url
     * Respond with order status
     *
     * @param  none
     * @return none
     */
    public function paymentAction()
    {
        $id = (int) $this->params()->fromRoute('id', 0);
        if(!empty($id)){
            $shop_id = $this->getRequest()->getServer()->get('HTTP_X_SHOP_ID');
            $data = $this->table->get_status($id, $shop_id, 'novalnet_lightspeed_transaction_detail');
            if(empty($data)){
                $status = array("status" => "cancelled");
                $this->helper->sendResponse($status);
            }
        } else {
            $lang = 'en_US';
            $translator = new Translator();
            $translator->addTranslationFile('phparray', dirname(__DIR__). '/lang/'.$lang.'.php', '', $lang);
            if(!empty($this->getRequest()->getServer()->get('HTTP_USER_AGENT')) && $this->getRequest()->getServer()->get('HTTP_USER_AGENT') == 'WebshopappApi' && !empty($this->getRequest()->getServer()->get('HTTP_X_SHOP_ID'))) {
                $json_post = json_decode(file_get_contents('php://input'), true);
                if(empty($json_post)) {
                    $this->helper->sendResponse('');
                }
                $table_data = array(
                    'shop_id' => $json_post['shop']['id'],
                    'request_domain' => $this->getRequest()->getServer()->get('HTTP_USER_AGENT'),
                    'request_ip' => $this->getRequest()->getServer()->get('REMOTE_ADDR')
                );
                $this->table->insert($table_data, 'novalnet_lightspeed_request_log');
                $shop_param = $this->table->fetchAll('novalnet_lightspeed_installed_shop', $json_post['shop']['id']);
                $userSecret = md5($shop_param['token'].$this->api_secret);
                $api = new WebshopappApiClient($shop_param['cluster'], $this->api_key, $userSecret, $shop_param['lang']);
                $shopurl = $api->shop->get();
                $data = $this->helper->buildNovalnetParams($json_post, $shopurl['mainDomain'], $this->getRequest()->getServer('REMOTE_ADDR'), $this->getRequest()->getServer('SERVER_ADDR'));
                if(!empty($data)){
                    $gateway = $this->table->fetch_value(['shop_id' => $json_post['shop']['id'], 'config_path' => 'gateway'], 'novalnet_lightspeed_merchant_configuration', ['value']);
                    $gateway_timeout = !empty($gateway)? $gateway : 240;

                    $client = new Client('https://paygate.novalnet.de/paygate.jsp', [
                        'maxredirects' => 0,
                        'timeout' => $gateway_timeout
                    ]);
                    $client->setMethod('POST');
                    $client->setParameterPost( $data );
                    $response = $client->send()->getBody();
                    parse_str($response, $response);
                }
                $url = array("payment_url" => $response['url']);
                $this->helper->sendResponse($url);
            } else {
				$text = urlencode('Unauthorized access');
				return $this->redirect()->toRoute('backend', ['action' => 'denied','signature' => $text]);
            }
        }
    }

    /**
     * Handle the response of the payment
     *
     * @param  none
     * @return none
     */
    public function successAction()
    {
        $data = (array)$this->getRequest()->getPost();
        if (!empty($data) && isset($data['status']) && (isset($data['inputval3']) || isset($data['shop_id']))) {
            $shop_id = (($data['inputval3']) ? $data['inputval3'] : ($data['shop_id'] ? $data['shop_id'] : ''));
            $order_id = (($data['inputval2']) ? $data['inputval2'] : ($data['order_id'] ? $data['order_id'] : ''));
            $success_url = (($data['inputval1']) ? $data['inputval1'] : ($data['success_url'] ? $data['success_url'] : ''));
            $webhook_url = (($data['inputval4']) ? $data['inputval4'] : ($data['webhook_url'] ? $data['webhook_url'] : ''));
            foreach($this->helper->config_param as $param){
				
                $value = $this->table->fetch_value(['shop_id' => $shop_id, 'config_path' => $param], 'novalnet_lightspeed_merchant_configuration', ['value']);
                $config[$param] = $value;
            }
            $shop_param = $this->table->fetchAll('novalnet_lightspeed_installed_shop', $shop_id);
            $userSecret = md5($shop_param['token'].$this->api_secret);


            $api = new WebshopappApiClient($shop_param['cluster'], $this->api_key, $userSecret, $shop_param['lang']);
            $lang_code = $api->orders->get($order_id);
            $lang = (!empty($lang_code['quote']['resource']['embedded']['language']['code']) && $lang_code['quote']['resource']['embedded']['language']['code'] == 'de') ? 'de_DE' : 'en_US';
            $translator = new Translator();
            $translator->addTranslationFile('phparray', dirname(__DIR__). '/lang/'.$lang.'.php', '', $lang);
            $additional_data = array();
            if(isset($data['hash2']) && !empty($data['hash2']) && !$this->helper->checkHash($data)) {
                $text = $translator->translate('check_hash', '', $lang);
				return $this->redirect()->toRoute('backend', ['action' => 'denied','signature' => $text]);
            }
            if(!empty($data['tid']) && !empty($data['tid_status'])){
                $table = array(
                    'shop_id' => $shop_id,
                    'order_id' => $order_id,
                    'tid' => $data['tid'],
                    'gateway_status' => $data['tid_status'],
                    'total_amount'   => $data['amount']*100,
                    'customer_id' => $data['customer_no'],
                    'payment_name' => $data['payment_type'],
                    'webhook_url' => $webhook_url
                );
                if((in_array($data['payment_type'], array('ONLINE_TRANSFER', 'PAYPAL', 'IDEAL', 'EPS', 'GIROPAY', 'PRZELEWY24', 'CREDITCARD'))) && isset($data['hash2']) && !empty($data['hash2'])){

                    $table['total_amount'] = $this->helper->decode($data['amount'], $config['access_key'], $data['uniqid']);
                    $data['test_mode'] = $this->helper->decode($data['test_mode'], $config['access_key'], $data['uniqid']);
                }

                if(in_array($data['payment_type'], array('INVOICE_START', 'DIRECT_DEBIT_SEPA', 'CREDITCARD', 'PAYPAL', 'CASHPAYMENT','GUARANTEED_INVOICE', 'GUARANTEED_DIRECT_DEBIT_SEPA'))){
                    $additional_data['vendor'] = $config['vendor'];
                    $additional_data['auth_code'] = $config['auth_code'];
                    $additional_data['product'] = $config['product'];
                    $additional_data['tariff'] = $config['tariff'];
                    if(in_array($data['payment_type'] ,array('INVOICE_START','GUARANTEED_INVOICE'))){
                        $additional_data['invoice_bankplace'] = $data['invoice_bankplace'];
                        $additional_data['invoice_bankname'] = $data['invoice_bankname'];
                        $additional_data['invoice_bic'] = $data['invoice_bic'];
                        $additional_data['invoice_iban'] = $data['invoice_iban'];
                        $additional_data['invoice_account_holder'] = $data['invoice_account_holder'];
                        $additional_data['due_date'] = $data['due_date'];
                        $additional_data['invoice_type'] = $data['invoice_type'];
                    }elseif(in_array($data['payment_type'] ,array('DIRECT_DEBIT_SEPA','GUARANTEED_DIRECT_DEBIT_SEPA'))){
                    $additional_data['sepa_due_date'] = $data['sepa_due_date'];
                    }elseif($data['payment_type'] == 'CASHPAYMENT'){
                        $additional_data['cp_due_date'] = $data['cp_due_date'];
                        $additional_data['comments'] = $this->helper->prepareBarzahlenComments($data, $translator, $lang);
                    }
                }
                $table['additional_data'] = serialize($additional_data);
                $this->table->insert($table, 'novalnet_lightspeed_transaction_detail');
                if(in_array($data['tid_status'] , array('86','91','99', '98', '85', '90', '100', '75', '90'))){
                    $comments = $translator->translate('transaction_detail', '', $lang).PHP_EOL.PHP_EOL;
                    $comments .= $translator->translate('payment_method_comment', '', $lang);
                    $payment_type = ($data['key'] == '27') ? $data['invoice_type'] : $data['payment_type'];
                    $comments .= $this->helper->getPaymentName($payment_type, $translator, $lang);
                    $comments .= PHP_EOL;
                    if(in_array($data['payment_id'], array(40, 41))){
                        $comments .= $translator->translate('guarantee_payment', '', $lang). PHP_EOL;
                    }
                    $comments .=  $translator->translate('transaction_id', '', $lang).$data['tid'].PHP_EOL;
                    $comments .= ($data['test_mode'] == '1') ? $translator->translate('test', '', $lang).PHP_EOL : '';
                    if(in_array($data['tid_status'], array(100, 91))){
                        if(in_array($data['payment_type'], array('INVOICE_START','GUARANTEED_INVOICE'))){
                            $this->helper->prepareInvoiceComments($comments, $data, $translator, $lang, $config, $shop_id);
                        }
                        if($data['payment_type'] == 'CASHPAYMENT'){
                            $comments .= $this->helper->prepareBarzahlenComments($data, $translator, $lang);
                        }
                    }
                    if($data['tid_status'] == '75'){
                        if($data['payment_type'] == 'GUARANTEED_INVOICE') {
                            $comments .= $translator->translate('invoice_gurantee_text', '', $lang). PHP_EOL;
                        } else {
                            $comments .= $translator->translate('sepa_gurantee_text', '', $lang). PHP_EOL;
                        }
                    }
                }else{
                    $comments .= (!empty($data['status_desc'])) ? $data['status_desc'] :  $data['status_text'] . PHP_EOL;
                }
            }
            if(in_array($data['tid_status'], array('85','91','99', '98'))){
				
                $status = $this->table->fetch_value(['shop_id' => $shop_id, 'config_path' => 'confirmation_order_status'], 'novalnet_lightspeed_merchant_configuration', ['value']);
            }elseif(in_array($data['tid_status'], array('90','86','75'))){
				
                $status = $this->table->fetch_value(['shop_id' => $shop_id, 'config_path' => 'pending_order_status'], 'novalnet_lightspeed_merchant_configuration', ['value']);
            }elseif($data['tid_status'] == '100') {
                if($data['payment_type'] == 'GUARANTEED_INVOICE'){
					
                    $status = $this->table->fetch_value(['shop_id' => $shop_id, 'config_path' => 'callback_order_status'],'novalnet_lightspeed_merchant_configuration', ['value']);
                }else{
					
                    $status = $this->table->fetch_value(['shop_id' => $shop_id, 'config_path' => 'payment_confirmation_order_status'],'novalnet_lightspeed_merchant_configuration',['value']);
                }
            } else {
                $status = 'cancelled';
            }
            $update = array();
            $update['memo'] = $comments;
            if(empty($update['memo'])){
                $comments = '';
                $comments = $translator->translate('transaction_detail', '', $lang).PHP_EOL.PHP_EOL;

                if(!empty($data['key'])) {
                    $comments .= $translator->translate('payment_method_comment', '', $lang);
                    $payment_type = ($data['key'] == '27') ? $data['invoice_type'] : $data['payment_type'];
                    $comments .= $this->helper->getPaymentName($payment_type, $translator, $lang);
                    $comments .= PHP_EOL;
                }

                if(!empty($data['tid'])) {
                    $comments .= $translator->translate('transaction_id', '', $lang).$data['tid'].PHP_EOL;
                }
                $comments .= ($data['test_mode'] == '1') ? $translator->translate('test', '', $lang).PHP_EOL : '';
                $comments .= (!empty($data['status_desc'])) ? $data['status_desc'] :  $data['status_text'] . PHP_EOL;
                $update['memo'] = $comments;
            }
            if(in_array($status, array('on_hold','off_hold'))){
                $update['status'] = $status;
            }else{
                $update['paymentStatus'] = $status;
            }
            $api->orders->update($order_id, $update);
            header('Location:'.$success_url);
            exit;
        } else {
            $text = urlencode('Unauthorized access');
            return $this->redirect()->toRoute('backend', ['action' => 'denied','signature' => $text]);
        }
    }

    /**
     * Handle the response of the payment
     *
     * @param  none
     * @return none
     */
    public function failureAction()
    {
        $data = (array)$this->getRequest()->getPost();
        if(!empty($data) && (isset($data['inputval3']) || isset($data['shop_id']))) {
            $shop_id = (($data['inputval3']) ? $data['inputval3'] : ($data['shop_id'] ? $data['shop_id'] : ''));
            $shop_param = $this->table->fetchAll('novalnet_lightspeed_installed_shop', $shop_id);
            $userSecret = md5($shop_param['token'].$this->api_secret);
			$order_id = ((isset($data['inputval2']) && $data['inputval2']) ? $data['inputval2'] : (isset($data['order_id']) && $data['order_id'] ? $data['order_id'] : ''));
            $api = new WebshopappApiClient($shop_param['cluster'], $this->api_key, $userSecret, $shop_param['lang']);

            $lang_code = $api->orders->get($order_id);
            $lang = (!empty($lang_code['quote']['resource']['embedded']['language']['code']) && $lang_code['quote']['resource']['embedded']['language']['code'] == 'de') ? 'de_DE' : 'en_US';
            $translator = new Translator();
            $translator->addTranslationFile('phparray', dirname(__DIR__). '/lang/'.$lang.'.php', '', $lang);

            $success_url = (($data['inputval1']) ? $data['inputval1'] : ($data['success_url'] ? $data['success_url'] : ''));

            $update = array();
                    $comments = '';
                    $comments = $translator->translate('transaction_detail', '', $lang).PHP_EOL.PHP_EOL;

                    if(!empty($data['key'])) {
                        $comments .= $translator->translate('payment_method_comment', '', $lang);
                        $payment_type = ($data['key'] == '27') ? $data['invoice_type'] : $data['payment_type'];
                        $comments .= $this->helper->getPaymentName($payment_type, $translator, $lang);
                        $comments .= PHP_EOL;
                    }

                    if(!empty($data['tid'])) {
                        $comments .= $translator->translate('transaction_id', '', $lang).$data['tid'].PHP_EOL;
                    }
                    $comments .= ($data['test_mode'] == '1') ? $translator->translate('test', '', $lang).PHP_EOL : '';
                    $comments .= $data['status_desc'] . PHP_EOL;

                    $update['memo'] = $comments;
                    $update['paymentStatus'] = 'cancelled';
                    $update['status'] = 'cancelled';
                $api->orders->update($order_id, $update);
                header('Location:'.$success_url);
                exit;
        } else {
            $text = urlencode('Unauthorized access');
            return $this->redirect()->toRoute('backend', ['action' => 'denied','signature' => $text]);
        }
    }
}
