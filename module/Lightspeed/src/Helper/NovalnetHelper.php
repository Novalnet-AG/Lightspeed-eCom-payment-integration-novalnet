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
 * Script: NovalnetHelper.php
 */

namespace Lightspeed\Helper;
use Lightspeed\Model\LightspeedTable;
use Zend\I18n\View\Helper\CurrencyFormat;

class NovalnetHelper
{
    public  $config_param = array(
        'auth_code',
        'product',
        'vendor',
        'tariff',
        'access_key',
        'api_key'
    );

    public $encode_parameters = array(
        'auth_code',
        'product',
        'tariff',
        'amount',
        'test_mode'
    );

    public $table;
    private $currencyformat;

    public function __construct($tbl){
        $this->table = $tbl;
        $this->currencyformat  = new CurrencyFormat;
    }

    /**
     * validate merchant merchant configuration
     *
     * @param  string $shop_id
     *
     * @return boolean
     */
    public function validateConfigParam($shop_id)
    {
        foreach($this->config_param as $param){
			
            $value = $this->table->fetch_value(['shop_id' => $shop_id, 'config_path' => $param], 'novalnet_lightspeed_merchant_configuration', ['value']);
            $config[$param] = $value;
        }
        if(in_array(null, $config, true) || in_array('', $config, true)) {
            return false;
        }

        $shop_param = $this->table->fetchAll('novalnet_lightspeed_installed_shop', $shop_id);
        if(empty($shop_param)){
            return false;
        }
        return true;
    }

    /**
     * validate merchant merchant configuration
     *
     * @param  string $shop_id
     * @param  string $trans
     * @param  string $lang
     *
     * @return array
     */
    public function getPaymentDetails($paymentdetails, $trans, $lang)
    {
        switch ($paymentdetails) {
            case 'novalnet_invoice':
                $paymentdetails = [
                                    "payment_name" => $trans->translate('novalnet_invoice', '', $lang),
                                    "key"          => 27,
                                    "payment_type" => 'INVOICE_START',
                                    "invoice_type" => 'INVOICE',
                                    "logo"         => 'novalnet_invoice',
                                ];
                break;

            case 'novalnet_cc':
                $paymentdetails = [
                                    "payment_name" => $trans->translate('novalnet_cc', '', $lang),
                                    "key"          => 6,
                                    "payment_type" => 'CREDITCARD',
                                    "logo"         => 'novalnet_cc',
                                ];
                break;

            case 'novalnet_sepa':
                $paymentdetails = [
                                    "payment_name" => $trans->translate('novalnet_sepa', '', $lang),
                                    "key"          => 37,
                                    "payment_type" => 'DIRECT_DEBIT_SEPA',
                                    "logo"         => 'novalnet_sepa',
                                ];
                break;

            case 'novalnet_paypal':
                $paymentdetails = [
                                    "payment_name" => 'PayPal',
                                    "key"          => 34,
                                    "payment_type" => 'PAYPAL',
                                    "logo"         => 'novalnet_paypal',
                                ];
                break;

            case 'novalnet_barzahlen':
                $paymentdetails = [
                                    "payment_name" => 'Barzahlen/viacash',
                                    "key"          => 59,
                                    "payment_type" => 'CASHPAYMENT',
                                    "logo"         => 'novalnet_barzahlen',
                                ];
                break;

            case 'novalnet_sofort':
                $paymentdetails = [
                                    "payment_name" => $trans->translate('novalnet_sofort', '', $lang),
                                    "key"          => 33,
                                    "payment_type" => 'ONLINE_TRANSFER',
                                    "logo"         => 'novalnet_banktransfer',
                                ];
                break;

            case 'novalnet_ideal':
                $paymentdetails = [
                                    "payment_name" => 'iDEAL',
                                    "key"          => 49,
                                    "payment_type" => 'IDEAL',
                                    "logo"         => 'novalnet_ideal',
                                ];
                break;

            case 'novalnet_przelewy24':
                $paymentdetails = [
                                    "payment_name" => 'Przelewy24',
                                    "key"          => 78,
                                    "payment_type" => 'PRZELEWY24',
                                    "logo"         => 'novalnet_przelewy24',
                                ];
                break;

            case 'novalnet_prepayment':
                $paymentdetails = [
                                    "payment_name" => $trans->translate('novalnet_prepayment', '', $lang),
                                    "key"          => 27,
                                    "payment_type" => 'INVOICE_START',
                                    "invoice_type" => 'PREPAYMENT',
                                    "logo"         => 'novalnet_prepayment',
                                ];
                break;

            case 'novalnet_giropay':
                $paymentdetails = [
                                    "payment_name" => 'giropay ',
                                    "key"          => 69,
                                    "payment_type" => 'GIROPAY',
                                    "logo"         => 'novalnet_giropay',
                                ];
                break;

            case 'novalnet_eps':
                $paymentdetails = [
                                    "payment_name" => 'eps',
                                    "key"          => 50,
                                    "payment_type" => 'EPS',
                                    "logo"         => 'novalnet_eps',
                                ];
                break;
        }

        return $paymentdetails;
    }

    /**
     * build novalnet params
     *
     * @param  array $data
     * @param  string $shop_id
     * @param  string $url
     *
     * @return array
     */
    public function buildNovalnetParams($data, $url, $remote_ip, $system_ip)
    {
        $shop_id = $data['shop']['id'];
        $backend_config = $this->table->fetchAll('novalnet_lightspeed_merchant_configuration', $shop_id);
        foreach($this->config_param as $param){
            $config[$param] = $backend_config[$param];
        }
        $params = array();
        
        $payment_type = ($data['payment_method']['data']['payment_type'] == 'INVOICE_START' && $data['payment_method']['data']['invoice_type'] == 'PREPAYMENT') ? $data['payment_method']['data']['invoice_type'] : $data['payment_method']['data']['payment_type'];

        $payment = $this->getPaymentType($payment_type);

        $params = $this->formPaymentParams($payment, $params, $backend_config, $data['order']['number']);


        $referer_id = $backend_config['referrer'];
        $uniq_id = $this->get_uniqueid();
        date_default_timezone_set('Europe/Berlin');
        $country_code = strtoupper($data['payment_method']['data']['country']);
        $lang_code = ($data['shop']['language'] == 'us') ? 'en' : $data['shop']['language'];
        $parameters = array(
            'vendor'           => $config['vendor'],
            'auth_code'        => $config['auth_code'],
            'product'          => $config['product'],
            'tariff'           => $config['tariff'],
            'currency'         => strtoupper($data['order']['currency']),
            'first_name'       => $data['customer']['firstname'],
            'last_name'        => $data['customer']['lastname'],
            'gender'           => ((!empty($data['customer']['gender'])) ?  (($data['customer']['gender'] == 'male') ? 'm' : (($data['customer']['gender'] == 'female') ? 'f' : 'u')) : 'u'),
            'email'            => $data['customer']['email'],
            'street'           => $data['payment_method']['data']['address'],
            'house_no'           => $data['payment_method']['data']['house_no'],
            'city'             => $data['payment_method']['data']['city'],
            'zip'              => $data['payment_method']['data']['zipcode'],
            'lang'             => $lang_code,
            'langauge'         => $lang_code,
            'country_code'     => $country_code,
            'country'          => $country_code,
            'remote_ip'        => $remote_ip,
            'customer_no'      => $data['customer']['id'],
            'amount'           => $data['order']['price_incl']*100,
            'system_name'      => 'Lightspeed eCom',
            'system_version'   => '1.8.0_NN2.0.1',
            'system_url'       => $url,
            'system_ip'        => $system_ip,
            'tel'              => $data['customer']['phone'],
            'mobile'           => !empty($data['customer']['mobile']) ? trim($data['customer']['mobile']) : '',
            'company'          => !empty($data['customer']['company']) ? trim($data['customer']['company']) : '',
            'notify_url'       => 'https://lightspeed.novalnet.de/callback/'.$shop_id,
            'order_no'         => $data['order']['number'],
            'input1'           => 'success_url',
            'inputval1'        => $data['redirect_url'],
            'input2'           => 'order_id',
            'inputval2'        => $data['order']['id'],
            'input3'            => 'shop_id',
            'inputval3'        => $shop_id,
            'input4'           => 'webhook_url',
            'inputval4'        => $data['webhook_url'],
            'return_url'       => 'https://lightspeed.novalnet.de/lightspeed/success',
            'error_return_url' => 'https://lightspeed.novalnet.de/lightspeed/failure',
            'implementation'   => 'ENC',
            'user_variable_0'  => $url,
            'uniqid'           => $uniq_id,
            'referrer_id'      => $referer_id,
            'chosen_only'      => '1',
            'address_form'     => '0',
            'hfooter'          => '0',
            'skip_suc'         => '1',
            'skip_sp'         => '1',
            'skip_cfm'         => '1',
            'thide'            => '1',
            'lhide'            => '1',
            'shide'            => '1',
            'purl'             => '1',
            'key'              => $data['payment_method']['data']['key'],
            'invoice_type'     => isset($data['payment_method']['data']['invoice_type']) ? $data['payment_method']['data']['invoice_type'] : '',
            'payment_type' => isset($data['payment_method']['data']['payment_type']) ? $data['payment_method']['data']['payment_type'] : '',
        );

        if(!empty($data['customer']['birthdate'])) {
            $parameters['birth_date'] = date('Y-m-d', $data['customer']['birthdate']);
        }

        $guarantee_invoice_enabled = $backend_config['novalnet_invoice_enable_guarantee'];
        $guarantee_sepa_enabled = $backend_config['novalnet_sepa_enable_guarantee'];
            $guarantee_invoice_minimum_amount = $backend_config['novalnet_invoice_guarantee_minimum_amount'];
            $min_invoice_amount = !empty($guarantee_invoice_minimum_amount) ? $guarantee_invoice_minimum_amount : 999;
        

            $guarantee_sepa_minimum_amount = $backend_config['novalnet_sepa_guarantee_minimum_amount'];
            $min_sepa_amount = !empty($guarantee_sepa_minimum_amount) ? $guarantee_sepa_minimum_amount : 999;
        

        $or_amount = (int)$data['order']['price_incl']*100;
        $diff = (isset($parameters['birth_date']) && !empty($parameters['birth_date'])) ? date('Y') - date('Y',strtotime($parameters['birth_date'])) : 0;
        $validate_birthdate = ($diff >= 18) ? 1 :0;

        $guarantee_condition = 0;
        if(($data['payment_method']['data']['payment_type'] == 'INVOICE_START' && $data['payment_method']['data']['invoice_type'] == 'INVOICE') || $data['payment_method']['data']['payment_type'] == 'DIRECT_DEBIT_SEPA') {
            $min_amount = ($data['payment_method']['data']['payment_type'] == 'INVOICE_START') ? $min_invoice_amount : $min_sepa_amount;
            $guarantee_condition = (((isset($parameters['birth_date']) && !empty($parameters['birth_date']) && $validate_birthdate) || !empty($parameters['company']) ) && $or_amount >= $min_amount && $parameters['currency'] == 'EUR' && in_array($parameters['country'], array('DE', 'AT', 'CH'))) ? 1 : 0;
        }

        if($data['payment_method']['data']['payment_type'] == 'DIRECT_DEBIT_SEPA' && $guarantee_condition && $guarantee_sepa_enabled && $data['payment_method']['data']['force_guarantee_sepa'] != 1 )  {
            $parameters['key'] = 40;
            $parameters['payment_type'] = 'GUARANTEED_DIRECT_DEBIT_SEPA';
        } elseif($data['payment_method']['data']['payment_type'] == 'DIRECT_DEBIT_SEPA' && $guarantee_sepa_enabled && $data['payment_method']['data']['force_guarantee_sepa'] != 1 && !$guarantee_condition && !$backend_config['novalnet_sepa_enable_force_guarantee']) {
            echo '';exit;
        }

        if($data['payment_method']['data']['payment_type'] == 'INVOICE_START' && $data['payment_method']['data']['invoice_type'] == 'INVOICE'&& $guarantee_condition && $guarantee_invoice_enabled && $data['payment_method']['data']['force_guarantee_invoice'] != 1)  {
            $parameters['key'] = 41;
            $parameters['payment_type'] = 'GUARANTEED_INVOICE';
        } elseif($data['payment_method']['data']['payment_type'] == 'INVOICE_START' && $data['payment_method']['data']['invoice_type'] == 'INVOICE' && $guarantee_invoice_enabled && $data['payment_method']['data']['force_guarantee_invoice'] != 1 &&  !$guarantee_condition && !$backend_config['novalnet_invoice_enable_force_guarantee']) {
            echo '';exit;
        }

        $payment_action = $backend_config['payment_action'];
        $manual_check_limit = $backend_config['manual_check_limit'];
        if ($payment_action == 'authorise' && ((int)$data['order']['price_incl']*100 > 0 && (int)$data['order']['price_incl']*100 >= (int)$manual_check_limit)) {
            $parameters['on_hold'] = 1;
        }
        $parameters = array_merge($parameters, $params);
        $this->encode_data($parameters, $uniq_id, $config['access_key']);
        
        return $parameters;
    }

    /**
     * build payment params
     *
     * @param  array $params
     * @param  string $payment
     * @param  string $shop_id
     *
     * @return array
     */
    public function formPaymentParams($payment, $params, $backend_config, $order_no)
    {
        $params['test_mode'] = $backend_config['test_mode'];

        if(in_array($payment, array('novalnet_invoice', 'novalnet_barzahlen'))) {
            $value = $backend_config[$payment.'_due_date'];
            $date = !empty($value) ? $value : 14;
            if($payment == 'novalnet_invoice'){
                $params['due_date'] = $date;
            }else{
                $params['cp_due_date'] = date('Y-m-d', strtotime('+'.$date.' days'));
            }
        }
        if(in_array($payment, array('novalnet_invoice', 'novalnet_prepayment'))) {
            $params['invoice_ref'] = 'BNR-' . $backend_config['product'] . '-' . $order_no;
        } elseif($payment == 'novalnet_sepa') {
            $value = $backend_config[$payment.'_due_date'];
            if(!empty($value) && is_numeric($value)){
                $duedate = date('Y-m-d', strtotime('+'.$value.' days'));
                $params['sepa_due_date'] = $duedate;
            }
        } elseif($payment == 'novalnet_cc') {
            if($backend_config[$payment.'_enforce_3d'] == 1) {
                $params['enforce_3d'] = 1;
            }
        }
        return $params;
    }

    /**
     * Generate a uique id
     *
     * @return string
     */
    public function get_uniqueid()
    {
        $randomwordarray = array('8','7','6','5','4','3','2','1','9','0','9','7','6','1','2','3','4','5','6','7','8','9','0');
        shuffle($randomwordarray);
        return substr(implode($randomwordarray, ''), 0, 16);
    }

    /**
     * Encode the param
     *
     * @param  array $parameters
     * @param  string $uniq_id
     * @param  string $access_key
     *
     */
    public function encode_data(&$parameters, $uniq_id, $access_key)
    {
        foreach ($this->encode_parameters as $key) {
            // Encoding process
            $parameters[$key] = htmlentities(base64_encode(openssl_encrypt($parameters[$key], "aes-256-cbc", $access_key, true, $uniq_id)));
        }
        $parameters['hash'] = $this->generatesha256_value($parameters, $access_key);
    }


    /**
     * ENC Encryption process
     *
     * @param  array $data
     * @param  string $access_key
     *
     * @return string
     */
    public function generatesha256_value($data, $access_key)
    {
        // Hash generation using sha256 and encoded merchant details
        return hash('sha256', ($data['auth_code'].$data['product'].$data['tariff'].$data['amount'].$data['test_mode'].$data['uniqid'].strrev($access_key)));
    }


    /**
     * Returns Nearest strore details for barzahlen
     *
     * @param  array $response
     * @param  array $trans
     * @param  array $lang
     *
     * @return string
     */
    public function prepareBarzahlenComments($response, $trans, $lang)
    {
        $i = 1;
        $comments = '';
        $comments .= sprintf($trans->translate('slip_date', '', $lang).': %s', $response['cp_due_date']) . PHP_EOL;
        $comments .= PHP_EOL . $trans->translate('stores', '', $lang). PHP_EOL;
        foreach ($response as $key => $value) {
            if (strpos($key, 'nearest_store') !== false) {
                if (!empty($response['nearest_store_title_'.$i])) {
                    $comments .= PHP_EOL. $response['nearest_store_title_'.$i]. PHP_EOL;
                }
                if (!empty($response['nearest_store_street_'.$i])) {
                    $comments .= $response['nearest_store_street_'.$i]. PHP_EOL;
                }
                if (!empty($response['nearest_store_city_'.$i])) {
                    $comments .= $response['nearest_store_city_'.$i]. PHP_EOL;
                }
                if (!empty($response['nearest_store_zipcode_'.$i])) {
                    $comments .= $response['nearest_store_zipcode_'.$i]. PHP_EOL;
                }
                if (!empty($response['nearest_store_country_'.$i])) {
                    $comments .= $response['nearest_store_country_'.$i]. PHP_EOL;
                }
                $i++;
            }
        }
        return $comments;
    }

    /**
     * Prepare invoice comments
     *
     * @param  array $invoice_details
     * @param  object $trans
     * @param  string $lang
     * @param  string $config
     *
     * @return string
     */
    public function prepareInvoiceComments(&$comments, $invoice_details, $trans, $lang, $config, $shop_id)
    {
		if(empty($config)) {
			$bank_details = unserialize($this->table->get_value('additional_data','novalnet_lightspeed_transaction_detail', $shop_id,$invoice_details['shop_tid']));
		}
        $comments .= PHP_EOL . $trans->translate('transfer_amount', '', $lang) . PHP_EOL;
        if (!empty($invoice_details['due_date'])) {
            $comments .= $trans->translate('due_date', '', $lang) . $invoice_details['due_date']. PHP_EOL;
        }
        if(isset($invoice_details['invoice_account_holder']) && !empty($invoice_details['invoice_account_holder'])) {
			$comments .= $trans->translate('account_holder', '', $lang) .''. $invoice_details['invoice_account_holder'] . PHP_EOL;
			$comments .= 'IBAN: '. $invoice_details['invoice_iban'] . PHP_EOL;
			$comments .= 'BIC: '. $invoice_details['invoice_bic'] . PHP_EOL;
			$comments .= 'Bank: ' . $invoice_details['invoice_bankname'] . ' ' . $invoice_details['invoice_bankplace']
			. PHP_EOL;
			$amount =  $invoice_details['amount'];
		} else {
			$comments .= $trans->translate('account_holder', '', $lang). '' . $bank_details['invoice_account_holder'] . PHP_EOL;
			$comments .= 'IBAN: ' . $bank_details['invoice_iban'] . PHP_EOL;
			$comments .= 'BIC: '  . $bank_details['invoice_bic'] . PHP_EOL;
			$comments .= 'Bank: ' . $bank_details['invoice_bankname']. PHP_EOL;
			$amount = $invoice_details['amount']/100;
		}
        $currency_lang = (!empty($invoice_details['currency']) && $invoice_details['currency'] == 'EUR') ? 'de_DE' : 'en_US';
		$comments .= 'Amount: ' . $this->currencyformat->__invoke(($amount), $invoice_details['currency'], null, $currency_lang). PHP_EOL;

        $comments .= $trans->translate('reference_desc', '', $lang) . PHP_EOL;
        $product = (isset($config['product']) && !empty($config['product'])) ? $config['product'] : $invoice_details['product_id'];
        $comments .= $trans->translate('reference1', '', $lang) . 'BNR-' . $product . '-' . $invoice_details['order_no'] . PHP_EOL;
        $comments .= $trans->translate('reference2', '', $lang) . 'TID ' . $invoice_details['tid'];
    }

    /**
     * get status message from response
     *
     * @param  array $data
     *
     * @return string
     */
    public static function getResponseText($data)
    {
        return !empty($data['status_desc']) ? $data['status_desc'] : (!empty($data['status_text']) ? $data['status_text'] : (!empty($data['termination_reason']) ? $data['termination_reason'] : (!empty($data['status_message']) ? $data['status_message'] : (!empty($data['pin_status']['status_message']) ? $data['pin_status']['status_message'] : 'Payment could not be completed'))));
    }

    /**
     * check hash for redirect payment
     *
     * @param  array $data
     *
     * @return boolean
     */
    public function checkHash($data){

        if($data['hash'] == $data['hash2']){
            return true;
        }
        return false;
    }


    /**
     * Decode the encoded data
     *
     * @param  string $data
     * @param  string $payment_access_key
     * @param  string $uniqid
     *
     * @return string
     */
    public function decode($data, $payment_access_key, $uniqid)
    {
         return openssl_decrypt(base64_decode($data), "aes-256-cbc", $payment_access_key, true, $uniqid);
    }
    /**
     * Get payment type from response
     *
     * @param  string $payment_type
     *
     * @return string
     */
    public function getPaymentType($payment_type)
    {
        $payment_type_names = array(
            'CREDITCARD'                   => 'novalnet_cc',
            'DIRECT_DEBIT_SEPA'            => 'novalnet_sepa',
            'GUARANTEED_DIRECT_DEBIT_SEPA' => 'novalnet_sepa',
            'INVOICE_START'                => 'novalnet_invoice',
            'GUARANTEED_INVOICE'           => 'novalnet_invoice',
            'PREPAYMENT'                   => 'novalnet_prepayment',
            'PAYPAL'                       => 'novalnet_paypal',
            'IDEAL'                        => 'novalnet_ideal',
            'ONLINE_TRANSFER'              => 'novalnet_banktransfer',
            'EPS'                          => 'novalnet_eps',
            'GIROPAY'                      => 'novalnet_giropay',
            'PRZELEWY24'                   => 'novalnet_przelewy24',
            'CASHPAYMENT'                  => 'novalnet_barzahlen',
        );
        return $payment_type_names[$payment_type];
    }

    /**
     * Get Payment Name
     *
     * @param  string $payment_type
     * @param  object $translator
     * @param  string $language
     *
     * @return string
     */
    public function getPaymentName($payment_type, $translator, $language)
    {
        $payment_type_names = array(
            'CREDITCARD'                   => $translator->translate('credit_card', '', $language),
            'DIRECT_DEBIT_SEPA'            => $translator->translate('sepa', '', $language),
            'GUARANTEED_DIRECT_DEBIT_SEPA' => $translator->translate('sepa', '', $language),
            'INVOICE'                      => $translator->translate('invoice', '', $language),
            'GUARANTEED_INVOICE'           => $translator->translate('invoice', '', $language),
            'PREPAYMENT'                   => $translator->translate('prepayment', '', $language),
            'PAYPAL'                       => 'Paypal',
            'IDEAL'                        => 'iDEAL',
            'ONLINE_TRANSFER'              => $translator->translate('sofort', '', $language),
            'EPS'                          => 'eps',
            'GIROPAY'                      => 'Giropay',
            'PRZELEWY24'                   => 'Przelewy24',
            'CASHPAYMENT'                  => 'Barzahlen/viacash',
        );
        return $payment_type_names[$payment_type];
    }


    /**
     * Validate Email
     *
     * @param  string $mail
     *
     * @return boolean
     */
    public function validateEmail($mail) {
        $valid = true;
        foreach(explode(",", $mail) as $email) {
           if(!filter_var($email, FILTER_VALIDATE_EMAIL))
           {
             $valid = false;
           }
        }
        return $valid;
    }

    /**
     * Send Response
     *
     * @param  array $response
     *
     * @return void
     */
    public function sendResponse($data) {
        if(!empty($data)) {
            $data = json_encode($data);
        }
        echo $data;
        exit;
    }

    /**
     * Validate Email
     *
     * @param  string $mail
     *
     * @return boolean
     */
    public function validateRequest($request, $api, $action) {
        $lang = $request->getQuery('language');
        $shop_id = $request->getQuery('shop_id');
        $signature = $request->getQuery('signature');
        $timestamp = $request->getQuery('timestamp');
        $token = $request->getQuery('token');

        if (isset($lang)
            && isset($shop_id)
            && isset($signature)
            && isset($timestamp))
        {
            // Create the signature
            $params = [
                'language'  => $lang,
                'shop_id'   => $shop_id,
                'timestamp' => $timestamp,
                'token'     => $token // in between token
            ];

            if($action == 'uninstall') {
                unset($params['token']);
            }

            ksort($params);

            $signature = '';

            $signature = http_build_query($params, null, '');

            $signature = md5($signature.$api);

            // Validate the signature
            if ($signature == $_GET['signature']) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
}
