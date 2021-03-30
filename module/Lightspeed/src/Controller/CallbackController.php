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
 * Script: CallbackController.php
 */

namespace Lightspeed\Controller;

use Zend\Mail;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Lightspeed\Model\Lightspeed;
use Lightspeed\Model\LightspeedTable;
use Lightspeed\Helper\NovalnetHelper;
use Lightspeed\Helper\WebshopappApiClient;
use Zend\I18n\Translator\Translator;
use Zend\I18n\View\Helper\CurrencyFormat;
use Zend\I18n\View\Helper\DateFormat;
use Zend\Mvc\MvcEvent;
use Zend\Http\Client;

class CallbackController extends AbstractActionController
{
    protected $initial_payments = array('CREDITCARD', 'INVOICE_START', 'DIRECT_DEBIT_SEPA', 'GUARANTEED_DIRECT_DEBIT_SEPA', 'GUARANTEED_INVOICE', 'PAYPAL', 'ONLINE_TRANSFER', 'IDEAL','EPS', 'GIROPAY', 'PRZELEWY24', 'CASHPAYMENT');

    // Refund/ chargeback payments
    protected $charge_back_payments = array('RETURN_DEBIT_SEPA', 'REVERSAL', 'CREDITCARD_BOOKBACK',
    'CREDITCARD_CHARGEBACK','REFUND_BY_BANK_TRANSFER_EU', 'PAYPAL_BOOKBACK', 'PRZELEWY24_REFUND', 'CASHPAYMENT_REFUND', 'GUARANTEED_INVOICE_BOOKBACK','GUARANTEED_SEPA_BOOKBACK');

    // Payment received payments
    protected $collection_payments = array('INVOICE_CREDIT', 'CREDIT_ENTRY_CREDITCARD', 'CREDIT_ENTRY_SEPA', 'DEBT_COLLECTION_SEPA', 'DEBT_COLLECTION_CREDITCARD', 'ONLINE_TRANSFER_CREDIT', 'CASHPAYMENT_CREDIT', 'CREDIT_ENTRY_DE', 'DEBT_COLLECTION_DE');

    // Payment types available
    protected $payment_types = array(
        'novalnet_invoice'      => array('INVOICE_START', 'INVOICE_CREDIT', 'GUARANTEED_INVOICE', 'TRANSACTION_CANCELLATION', 'REFUND_BY_BANK_TRANSFER_EU', 'GUARANTEED_INVOICE_BOOKBACK','CREDIT_ENTRY_DE', 'DEBT_COLLECTION_DE'),
        'novalnet_prepayment'   => array('INVOICE_START', 'INVOICE_CREDIT', 'REFUND_BY_BANK_TRANSFER_EU','CREDIT_ENTRY_DE', 'DEBT_COLLECTION_DE'),
        'novalnet_paypal'       => array('PAYPAL', 'PAYPAL_BOOKBACK', 'TRANSACTION_CANCELLATION'),
        'novalnet_banktransfer' => array('ONLINE_TRANSFER', 'REFUND_BY_BANK_TRANSFER_EU', 'ONLINE_TRANSFER_CREDIT', 'REVERSAL','CREDIT_ENTRY_DE', 'DEBT_COLLECTION_DE'),
        'novalnet_cc'           => array('CREDITCARD', 'CREDITCARD_BOOKBACK', 'CREDITCARD_CHARGEBACK', 'CREDIT_ENTRY_CREDITCARD', 'DEBT_COLLECTION_CREDITCARD', 'TRANSACTION_CANCELLATION'),
        'novalnet_ideal'        => array('IDEAL', 'REFUND_BY_BANK_TRANSFER_EU', 'ONLINE_TRANSFER_CREDIT', 'REVERSAL','CREDIT_ENTRY_DE', 'DEBT_COLLECTION_DE'),
        'novalnet_sepa'         => array('DIRECT_DEBIT_SEPA', 'GUARANTEED_DIRECT_DEBIT_SEPA', 'RETURN_DEBIT_SEPA', 'REFUND_BY_BANK_TRANSFER_EU', 'CREDIT_ENTRY_SEPA', 'DEBT_COLLECTION_SEPA', 'TRANSACTION_CANCELLATION','GUARANTEED_SEPA_BOOKBACK'),
        'novalnet_eps'          => array('EPS', 'REFUND_BY_BANK_TRANSFER_EU', 'REVERSAL', 'ONLINE_TRANSFER_CREDIT','CREDIT_ENTRY_DE', 'DEBT_COLLECTION_DE'),
        'novalnet_giropay'      => array('GIROPAY', 'REFUND_BY_BANK_TRANSFER_EU', 'REVERSAL', 'ONLINE_TRANSFER_CREDIT','CREDIT_ENTRY_DE', 'DEBT_COLLECTION_DE'),
        'novalnet_przelewy24'   => array('PRZELEWY24', 'PRZELEWY24_REFUND'),
        'novalnet_barzahlen'    => array('CASHPAYMENT', 'CASHPAYMENT_REFUND', 'CASHPAYMENT_CREDIT'),
    );

    // Required vendor parameters
    protected $required_params      = array('vendor_id', 'status', 'payment_type', 'tid', 'tid_status');

    public $api_key = 'b6bf86a4938e57c09d37c09fb7b6ed0e';
    public $api_secret = '46c570387c3f3f59beef08c394755e36';
    protected $shop_id;
    private $helper;
    private $api;
    private $table;
    private $translator;
    private $lang;
    private $currencyformat;
    private $dateformat;
    private $currency_lang;

    /**
     * Initialize the controller, get the database properties
     *
     * @param  object $table
     * @return null
     */
    public function __construct(LightspeedTable $table)
    {

        $this->table = $table;
        $this->helper = new NovalnetHelper($this->table);
        $this->currencyformat  = new CurrencyFormat;
        $this->dateformat  = new DateFormat;

    }

    /**
     * default called function, intialize the callback process
     *
     * @param  null
     * @return null
     */
    public function indexAction()
    {
        $this->shop_id = (int) $this->params()->fromRoute('id', 0);
        $shop_param = $this->table->fetchAll('novalnet_lightspeed_installed_shop', $this->shop_id);

        $userSecret = md5($shop_param['token'].$this->api_secret);
        $this->api = new WebshopappApiClient($shop_param['cluster'], $this->api_key, $userSecret, $shop_param['lang']);
        $post = (array)$this->getRequest()->getPost();
        $callback_request_parameters = array_map('trim', $post); // Received parameters from Novalnet server
        // Calling the function to validate the required Novalnet parameters
        $this->validateCaptureParams($callback_request_parameters);
        // Executes the callback process for the requested transaction
        $lang = $this->api->orders->get($callback_request_parameters['callback_order_id']);
        $this->lang = (!empty($lang['quote']['resource']['embedded']['language']['code']) && $lang['quote']['resource']['embedded']['language']['code'] == 'de') ? 'de_DE' : 'en_US';
        $this->currency_lang = (!empty($callback_request_parameters['currency']) && $callback_request_parameters['currency'] == 'EUR') ? 'de_DE' : 'en_US';
        $this->translator = new Translator();
        $this->translator->addTranslationFile('phparray', dirname(__DIR__). '/lang/'.$this->lang.'.php', '', $this->lang);
        $this->performCallbackExecution($callback_request_parameters);

    }

    /**
     * validate the request params
     *
     * @param  array $callback_request_parameters
     * @return null
     */
    public function validateCaptureParams(&$callback_request_parameters)
    {
        $real_host_ip = gethostbyname('pay-nn.de'); // Getting Novalnet IP address of sub domain
        $callerIp  =  $_SERVER['REMOTE_ADDR']; // Getting remote IP address
        
        if ($real_host_ip != $callerIp && !($this->table->fetch_value(['shop_id' => $this->shop_id, 'config_path' => 'deactivate_ip_check'], 'novalnet_lightspeed_merchant_configuration', ['value']))) { // Condition to check whether the callback is called from authorized IP
            $this->displayMessage('Unauthorised access from the IP ' . $_SERVER['REMOTE_ADDR']);
        }

        $this->validateRequiredParameters($this->required_params, $callback_request_parameters);
        $tid_check = array($callback_request_parameters['tid']);
        $callback_request_parameters['shop_tid'] = $callback_request_parameters['tid'];

        if (isset($callback_request_parameters['payment_type'])
            && in_array($callback_request_parameters['payment_type'], array_merge($this->charge_back_payments, $this->collection_payments))) {
                array_push($this->required_params, 'tid_payment');
                $tid_check[] = $callback_request_parameters['tid_payment'];
                $callback_request_parameters['shop_tid'] = $callback_request_parameters['tid_payment'];
        }

        foreach ($tid_check as $tid) {
            if (!is_numeric($tid) || 17 != strlen($tid)) {
                $this->displayMessage('Novalnet callback received. Invalid TID [' . $tid . '] for Order.'.$callback_request_parameters['order_no']);
            }
        }
        if(isset($callback_request_parameters['order_id'])){
            $callback_request_parameters['callback_order_id'] = $callback_request_parameters['order_id'];
        }elseif(isset($callback_request_parameters['inputval1'])){
            $callback_request_parameters['callback_order_id'] = $callback_request_parameters['inputval1'];
        }
        if(empty($callback_request_parameters['callback_order_id'])){
            $this->displayMessage('Novalnet callback received. order_id is missing');
        }
    }

    /**
     * display the message
     *
     * @param  string $error_message
     *
     * @return null
     */
    public function displayMessage($error_message)
    {
        echo $error_message;exit;
    }

    /**
     * validate the Required Parameters
     *
     * @param  array $params_required
     * @param  array $callback_request_params
     *
     * @return null
     */
    public function validateRequiredParameters($params_required, $callback_request_params)
    {
        foreach ($params_required as $value) {
            if (empty($callback_request_params[$value])) {
                $this->displayMessage('Required param (' . $value . ') missing!');
            }
        }
    }

    /**
     * perform Callback Execution
     *
     * @param  array $callback_request_parameters
     *
     * @return null
     */
    public function performCallbackExecution(&$callback_request_parameters)
    {
        $this->getOrderReference($callback_request_parameters);
        $success_status = ('100' == $callback_request_parameters['status'] && '100' == $callback_request_parameters['tid_status']);
        $payment_type_level = $this->getPaymentTypeLevel($callback_request_parameters); // Getting payment level
        $shop_comment = $this->api->orders->get($callback_request_parameters['callback_order_id']);
        // Calling the function to get payment method using payment type
        if (2 == $payment_type_level && $success_status) {
            // Handling credit entry for Invoice, Prepayment and Cashpayment
            if (in_array($callback_request_parameters['payment_type'], array('INVOICE_CREDIT', 'CASHPAYMENT_CREDIT'))) {
                $paid_amount = 0;
                $pid_amount = $this->table->get_paid_amount('novalnet_lightspeed_callback_detail', $this->shop_id,$callback_request_parameters['callback_order_id']);
                $paid_amount = $pid_amount['amount'];
                $paid_amount = empty($paid_amount) ? 0 : $paid_amount;
                $total_sum = (int)$paid_amount + $callback_request_parameters['amount'];
                $callback_comments = '';
                if ((int)$paid_amount < $callback_request_parameters['total_amount']) {
                    
                    $callback_status = $this->table->fetch_value(['shop_id' => $this->shop_id, 'config_path' => 'callback_order_status'], 'novalnet_lightspeed_merchant_configuration', ['value']);
                    $callback_comments .= sprintf($this->translator->translate('credit_comment', '', $this->lang), $callback_request_parameters['shop_tid'],   $this->currencyformat->__invoke($callback_request_parameters['amount']/100, $callback_request_parameters['currency'], null, $this->currency_lang), date("d-m-Y H:i:s"), $callback_request_parameters['tid']);
                    $orders = $this->api->orders->get($callback_request_parameters['callback_order_id']);
                    if ($total_sum >= $callback_request_parameters['total_amount']) {
                        $update = array();
                        if(in_array($callback_status, array('on_hold','off_hold'))){
                            $update['status'] = $callback_status;
                        }else{
                            if($orders['status'] == 'on_hold'){
                                $update['status'] = 'off_hold';
                            }
                            $update['paymentStatus'] = $callback_status;
                        }
                        $this->api->orders->update($callback_request_parameters['callback_order_id'], $update);
                    }
                    // Calling the function to update comments into database
                    $comment = $shop_comment['memo'].PHP_EOL.$callback_comments;
                    $this->api->orders->update($callback_request_parameters['callback_order_id'], ["memo" => $comment]);

                    // Calling the function to update callback log into database
                    $this->insertIntoCallbackTable($callback_request_parameters);
                    // Calling the function to send mail notification for execution of INVOICE_CREDIT
                    $this->sendMailNotification($callback_comments, $total_sum > $callback_request_parameters['total_amount']);
                }

                $this->displayMessage('Novalnet callback received. Callback Script executed already. Refer Order: ' . $callback_request_parameters['order_no']);
            }elseif(isset($callback_request_parameters['payment_type']) && in_array($callback_request_parameters['payment_type'],array('CREDIT_ENTRY_CREDITCARD','CREDIT_ENTRY_SEPA','DEBT_COLLECTION_SEPA','DEBT_COLLECTION_CREDITCARD','ONLINE_TRANSFER_CREDIT', 'CREDIT_ENTRY_DE', 'DEBT_COLLECTION_DE'))) {
                
                $order_status  = $this->table->fetch_value( ['shop_id' => $this->shop_id, 'config_path' => 'payment_confirmation_order_status'], 'novalnet_lightspeed_merchant_configuration', ['value']);

                $callback_comments = sprintf($this->translator->translate('credit_comment', '', $this->lang), $callback_request_parameters['shop_tid'], $this->currencyformat->__invoke($callback_request_parameters['amount']/100, $callback_request_parameters['currency'], null, $this->currency_lang), date("d-m-Y H:i:s"), $callback_request_parameters['tid']);


                // Update order status
                $comment = $shop_comment['memo'].PHP_EOL.$callback_comments;
                $update = array();
                $update['memo'] = $comment;
                $orders = $this->api->orders->get($callback_request_parameters['callback_order_id']);
                if(in_array($order_status, array('on_hold','off_hold','cancelled'))){
                    $update['status'] = $order_status;
                }else{
                    if($orders['status'] == 'on_hold'){
                        $update['status'] = 'off_hold';
                    }
                    $update['paymentStatus'] = $order_status;
                }
                $this->api->orders->update($callback_request_parameters['callback_order_id'], $update);
                $this->insertIntoCallbackTable($callback_request_parameters);
                // Calling the function to send mail notification for execution of INVOICE_CREDIT
                $this->sendMailNotification($callback_comments);
                $this->displayMessage($callback_comments);

            }
            $this->displayMessage('Novalnet Callbackscript received. Payment type ( '.$callback_request_parameters['payment_type'].' ) is not applicable for this process!');
        } elseif (1 == $payment_type_level && $success_status) { // Chargeback and book back payments
            // Do the steps to update the status of the order or the user and note that the payment was reclaimed from user //
             if(in_array($callback_request_parameters['payment_type'] , array('CREDITCARD_CHARGEBACK', 'RETURN_DEBIT_SEPA','REVERSAL'))){
                $callback_comments = sprintf($this->translator->translate('chargeback_comment', '', $this->lang), $callback_request_parameters['shop_tid'], $this->currencyformat->__invoke($callback_request_parameters['amount']/100, $callback_request_parameters['currency'], null, $this->currency_lang), date("d-m-Y H:i:s"), $callback_request_parameters['tid']);
            }else{
                $callback_comments = sprintf($this->translator->translate('refund_comment', '', $this->lang), $callback_request_parameters['shop_tid'], $this->currencyformat->__invoke($callback_request_parameters['amount']/100, $callback_request_parameters['currency'], null, $this->currency_lang), date("d-m-Y H:i:s"), $callback_request_parameters['tid']);
            }
            // Calling the function to update chargeback comments into database
            $comment = $shop_comment['memo'].PHP_EOL.$callback_comments;
            $this->api->orders->update($callback_request_parameters['callback_order_id'], ["memo" => $comment]);

            // Calling the function to update callback log into database
            $this->insertIntoCallbackTable($callback_request_parameters);

            // Calling the function to send mail notification for execution of chargeback
            $this->sendMailNotification($callback_comments);
        } elseif (0 === $payment_type_level) { // Initial payments
            $table_fields = array('order_id', 'payment_name', 'total_amount', 'gateway_status');
            foreach($table_fields as $fields){
                $transaction_table_row[$fields] = $this->table->get_value($fields, 'novalnet_lightspeed_transaction_detail', $this->shop_id,$callback_request_parameters['shop_tid']);
            }
            if (in_array($callback_request_parameters['payment_type'], array('INVOICE_START', 'GUARANTEED_DIRECT_DEBIT_SEPA', 'GUARANTEED_INVOICE','DIRECT_DEBIT_SEPA', 'CREDITCARD', 'PAYPAL')) && in_array($transaction_table_row['gateway_status'], array('75', '91','99', '85', '98')) && $callback_request_parameters['status'] == 100) {
                $guarantee_callback_comments = '';

                $order_status ='';

                if ($transaction_table_row['gateway_status'] == '75' && in_array($callback_request_parameters['tid_status'] , array('91','99'))) { // Guarantee pending to onhold
                    
                    $order_status  = $this->table->fetch_value( ['shop_id' => $this->shop_id, 'config_path' => 'confirmation_order_status'], 'novalnet_lightspeed_merchant_configuration', ['value']);
                    $guarantee_callback_comments = PHP_EOL. sprintf($this->translator->translate('guarantee_payment_on_hold', '', $this->lang), $callback_request_parameters['shop_tid'], date('d-m-Y'), date('H:i:s'));
                    if (in_array($callback_request_parameters['payment_type'], array('INVOICE_START', 'GUARANTEED_INVOICE'))) {
                        $this->helper->prepareInvoiceComments($guarantee_callback_comments, $callback_request_parameters, $this->translator, $this->lang, array(), $this->shop_id);
                    }
                } elseif (in_array($transaction_table_row['gateway_status'], array('91', '99', '75', '85', '98')) && $callback_request_parameters['tid_status'] == '100') {
                     // On hold to confirm
                     
                    $order_status  = ($callback_request_parameters['payment_type'] == 'GUARANTEED_INVOICE') ? $this->table->fetch_value( ['shop_id' => $this->shop_id, 'config_path' => 'callback_order_status'], 'novalnet_lightspeed_merchant_configuration', ['value']) : $this->table->fetch_value( ['shop_id' => $this->shop_id, 'config_path' => 'payment_confirmation_order_status'], 'novalnet_lightspeed_merchant_configuration', ['value']);
                }

                if($callback_request_parameters['tid_status'] == '100'){
                    $guarantee_callback_comments .= PHP_EOL. sprintf($this->translator->translate('guarantee_payment_confirm', '', $this->lang), date('d-m-Y'), date('H:i:s'));
                    $order = $this->api->orders->get($transaction_table_row['order_id']);
                    $shop = $this->api->shopCompany->get();
                    $data = array(
                        'name' => $order['addressBillingName'],
                        'email_to_address' => $order['email'],
                        'email_from_address' => $shop['email'],
                        'email_from_name'    => $shop['name'],
                        'order_no'     => $order['number'],
                        'amount' => $this->currencyformat->__invoke($callback_request_parameters['amount']/100, $callback_request_parameters['currency'], null, $this->currency_lang),
                    );
                    $invoice_comments = '';
                    if (in_array($callback_request_parameters['payment_type'], array('INVOICE_START', 'GUARANTEED_INVOICE'))) {
                        $invoice_comments = $this->prepareTransactionComments($callback_request_parameters);
                    }
                    $comment = $guarantee_callback_comments.PHP_EOL.$invoice_comments;

                    $this->sendPaymentConfirmationMail($comment, $data);
                }
                // Calling the function to update comments into database
                if(empty($comment)){
                    $comment = $guarantee_callback_comments;
                }
                $order_comment = $shop_comment['memo'].PHP_EOL.$comment;
                $orders = $this->api->orders->get($callback_request_parameters['callback_order_id']);
                $update = array();
                $update['memo'] = $order_comment;
                if(!empty($order_status) && $orders['status'] != $order_status) {
                    if(in_array($order_status, array('on_hold','off_hold','cancelled'))){
                        $update['status'] = $order_status;
                    }else{
                        if($orders['status'] == 'on_hold'){
                            $update['status'] = 'off_hold';
                        }
                        $update['paymentStatus'] = $order_status;
                    }
                }
                $this->api->orders->update($callback_request_parameters['callback_order_id'], $update);

                // Calling the function to update transaction status details into database
                $this->table->update('novalnet_lightspeed_transaction_detail', array('gateway_status'=> (int)$callback_request_parameters['tid_status']), ['shop_id' => $this->shop_id, 'order_id' => $transaction_table_row['order_id']]);
                $this->sendMailNotification($comment);
                $this->displayMessage($comment);
            }
            // Handle PayPal payment receival for pending payment
            elseif ($callback_request_parameters['payment_type'] == 'PAYPAL' && $callback_request_parameters['tid_status'] == '100') {

                    $this->initialPaymentExecution($callback_request_parameters);

            } elseif ($callback_request_parameters['payment_type'] == 'PRZELEWY24' && $callback_request_parameters['tid_status'] != '86') { // Handle Przelewy24 after payment call
                if ($callback_request_parameters['tid_status'] == '100') {
                    $this->initialPaymentExecution($callback_request_parameters);
                } else {
                    $this->initialPaymentExecution($callback_request_parameters, true);
                }
            } else {
                $this->displayMessage('Novalnet Callbackscript received. Payment type ( '.$callback_request_parameters['payment_type'].' ) is not applicable for this process!');
            }
        }
         $this->displayMessage('Novalnet Callbackscript received. Payment type ( '.$callback_request_parameters['payment_type'].' ) is not applicable for this process!');
    }

    /**
     * perform get Order Reference
     *
     * @param  array $callback_request_parameters
     *
     * @return null
     */
    public function getOrderReference(&$callback_request_parameters)
    {
        $table_fields = array('order_id', 'payment_name', 'total_amount', 'gateway_status');
        foreach($table_fields as $fields){
            $transaction_exists[$fields] = $this->table->get_value($fields, 'novalnet_lightspeed_transaction_detail', $this->shop_id,$callback_request_parameters['shop_tid']);
        }
        $order = $this->api->orders->get($callback_request_parameters['callback_order_id']);
        if(isset($order['code']) && $order['code'] == '404'){
            $order = array();
        }
        if (empty($transaction_exists['order_id']) && isset($callback_request_parameters['callback_order_id']) && !in_array($callback_request_parameters['payment_type'], $this->charge_back_payments) && !empty($order)) { // Order reference not available in the database
                $this->handleCommunicationFailure($callback_request_parameters);
        } elseif (!empty($transaction_exists['order_id']) && !empty($transaction_exists['gateway_status'])) { // If order reference is available in the shop

            $callback_request_parameters['payment_method'] = $this->getPaymentType($transaction_exists['payment_name']);
            // Transaction cancallation process

            if ($callback_request_parameters['payment_type'] == 'TRANSACTION_CANCELLATION' && in_array($transaction_exists['gateway_status'], array('75', '91', '99', '98', '85')) && $callback_request_parameters['tid_status'] == '103') {

                $callback_comments = '';
                $callback_comments .= PHP_EOL . sprintf($this->translator->translate('guarantee_payment_cancel', '', $this->lang), date('d-m-Y'), date('H:i:s'));
                $order_status  = $this->table->fetch_value( ['shop_id' => $this->shop_id, 'config_path' => 'cancellation_order_status'], 'novalnet_lightspeed_merchant_configuration', ['value']);
                // Calling the function to update order status
                $shop_comment = $this->api->orders->get($callback_request_parameters['callback_order_id']);
                $comment = $shop_comment['memo'].PHP_EOL.$callback_comments;
                $orders = $this->api->orders->get($callback_request_parameters['callback_order_id']);
                $update = array();
                $update['memo'] = $comment;
                $update['status'] = $order_status;
                $this->api->orders->update($callback_request_parameters['callback_order_id'], $update);
                // Calling the function to update Novalnet transaction details in invoice pdf
                $this->table->update('novalnet_lightspeed_transaction_detail', array('gateway_status'=> (int)$callback_request_parameters['tid_status']), ['shop_id' => $this->shop_id, 'order_id' => $transaction_exists['order_id']]);
                $this->insertIntoCallbackTable($callback_request_parameters);
                // Calling the function to send mail notification for execution of INVOICE_CREDIT
                $this->sendMailNotification($callback_comments);
                $this->displayMessage($callback_comments);
            }
            if ((!array_key_exists($callback_request_parameters['payment_method'], $this->payment_types)) // Payment type validation
                || !in_array($callback_request_parameters['payment_type'], $this->payment_types[$callback_request_parameters['payment_method']])) {
                $this->displayMessage('Novalnet callback received. Payment type ['.$callback_request_parameters['payment_type'].'] is mismatched!');
            }

            $callback_request_parameters['payment_method'] = $transaction_exists['payment_name'];
            $callback_request_parameters['total_amount']   = $transaction_exists['total_amount'];
            $callback_request_parameters['order_id']   = $transaction_exists['order_id'];
        }elseif($callback_request_parameters['status'] == '100' || ($callback_request_parameters['payment_type'] == 'PAYPAL'
                && $callback_request_parameters['status'] == '90')) { // If order reference is not in the database
            $newLine = PHP_EOL;
            $comments  = 'Dear Technic team,'.$newLine.$newLine;
            $comments .= 'Please evaluate this transaction and contact our payment module team at Novalnet.'.$newLine.$newLine;
            $comments .= 'Merchant ID: '.$callback_request_parameters['vendor_id'].$newLine;
            $comments .= 'Project ID: '.$callback_request_parameters['product_id'].$newLine;
            $comments .= 'TID: '.$callback_request_parameters['tid'].$newLine;
            $comments .= 'TID status: '.$callback_request_parameters['tid_status'].$newLine;
            if(!empty($callback_request_parameters['order_no'])) {
                $comments .= 'Order no: '.$callback_request_parameters['order_no'].$newLine;
            }
            $comments .= 'Payment type: '.$callback_request_parameters['payment_type'].$newLine;
            $comments .= 'E-mail: '.$callback_request_parameters['email'].$newLine;
            $this->sendTechnicNotification(['message' => $comments, 'tid' => $callback_request_parameters['tid']]);
        }
    }

    /**
     * get PaymentType by request parameters
     *
     * @param  string $payment_type
     *
     * @return string $payment_type_names[$payment_type];
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
     * send Mail Notification
     *
     * @param string $comments
     * @param boolean $exceeded_amount
     *
     * @return null
     */
    public function sendMailNotification($comments, $exceeded_amount = false)
    {
        if ($this->table->fetch_value( ['shop_id' => $this->shop_id, 'config_path' => 'callback_mail_notification'], 'novalnet_lightspeed_merchant_configuration', ['value'])) {
            $shop = $this->api->shopCompany->get();
            $email_from_address = $shop['email']; // Sender email from shop configuration
            $email_from_name    = $shop['name']; // Sender name from shop configuration
            
            $email_to_address   = $this->table->fetch_value( ['shop_id' => $this->shop_id, 'config_path' => 'callback_mail_to'], 'novalnet_lightspeed_merchant_configuration', ['value']); // Receiver email from Novalnet configuration
            
            $email_bcc_address   = $this->table->fetch_value( ['shop_id' => $this->shop_id, 'config_path' => 'callback_mail_bcc'], 'novalnet_lightspeed_merchant_configuration', ['value']); // Receiver email from Novalnet configuration
             if (!empty($email_to_address)) {
                $mail = new Mail\Message();

                $html = new \Zend\Mime\Part(nl2br($comments));
                $html->type = 'text/html';
                $body = new \Zend\Mime\Message;
                $body->setParts(array($html));
                $mail->setBody($body);

                $headers = $mail->getHeaders();
                $headers->removeHeader('Content-Type');
                $headers->addHeaderLine('Content-Type', 'text/html; charset=UTF-8');

                $mail->setFrom($email_from_address, $email_from_name);
                $mail->addTo($email_to_address);
                if(!empty($email_bcc_address)){
                    $email_to = array();
                    $email_to = explode(',',$email_bcc_address);
                    if(!empty($email_to)){
                        $mail->addBcc($email_to);
                    }else{
                        $mail->addBcc($email_bcc_address);
                    }
                }
                $mail->setEncoding('utf-8');
                $mail->setSubject('Novalnet Callback script notification');

                $transport = new Mail\Transport\Sendmail();
                $transport->send($mail);
            }
        }
        if (!empty($exceeded_amount)) {
            $comments .= PHP_EOL . 'Customer has paid more than the Order amount.';
        }
        $this->displayMessage($comments);
    }

    /**
     * send Technic Mail Notification
     *
     * @param string $data
     *
     * @return null
     */
    public function sendTechnicNotification($data)
    {
            $shop = $this->api->shopCompany->get();
            $email_from_address = $shop['email']; // Sender email from shop configuration
            $email_from_name    = $shop['name']; // Sender name from shop configuration
            $email_to = 'technic@novalnet.de';
            $mail = new Mail\Message();
            $mail->setBody($data['message']);
            $mail->setFrom($email_from_address, $email_from_name);
            $mail->addTo($email_to);

            $mail->setSubject('Critical error on shop system '.$email_from_name.': order not found for TID: '.$data['tid']);

            $transport = new Mail\Transport\Sendmail();
            $transport->send($mail);
            $this->displayMessage($data['message']);
    }

    /**
     * get Payment Type Level
     *
     * @param array $callback_request_parameters
     *
     * @return int
     */
    public function getPaymentTypeLevel($callback_request_parameters)
    {
        if (in_array($callback_request_parameters['payment_type'], $this->initial_payments)) {
            return 0;
        } elseif (in_array($callback_request_parameters['payment_type'], $this->charge_back_payments)) {
            return 1;
        } elseif (in_array($callback_request_parameters['payment_type'], $this->collection_payments)) {
            return 2;
        }
    }

    /**
     * insert data Into CallbackTable
     *
     * @param array $callback_request_parameters
     *
     * @return null
     */
    public function insertIntoCallbackTable($callback_request_parameters)
    {

           $data =  array(
                'order_id'      => $callback_request_parameters['order_id'],
                'amount'        => $callback_request_parameters['amount'],
                'reference_tid' => $callback_request_parameters['tid'],
                'tid'           => $callback_request_parameters['shop_tid'],
                'payment_type'  => $callback_request_parameters['payment_type'],
                'shop_id'       => $this->shop_id,
                'updated_date'  => date("Y-m-d H:i:s"),
            );
            $this->table->insert($data, 'novalnet_lightspeed_callback_detail');
    }

    /**
     * prepare Transaction Comments
     *
     * @param array $vendor_params
     * @param string $order_no
     *
     * @return string $nn_comments
     */
    public function prepareTransactionComments($vendor_params)
    {
            $nn_comments  = $this->translator->translate('trans_details', '', $this->lang) . PHP_EOL;


            $nn_comments .= $this->translator->translate('payment_method_comment', '', $this->lang);
            $payment_type = ($vendor_params['payment_type'] == 'INVOICE_START') ? $vendor_params['invoice_type'] : $vendor_params['payment_type'];
            $nn_comments .= $this->helper->getPaymentName($payment_type, $this->translator, $this->lang);

            $nn_comments .= PHP_EOL . $this->translator->translate('transaction_id', '', $this->lang) . $vendor_params['tid'] . PHP_EOL;
            $nn_comments .= (isset($vendor_params['test_mode']) && '1' == $vendor_params['test_mode']) ? $this->translator->translate('test_order', '', $this->lang) : '';

            if (in_array($vendor_params['payment_type'], array('INVOICE_START', 'GUARANTEED_INVOICE'))) {
                // Enter the necessary reference & bank account details in the new order confirmation E-mail //
                $vendor_params['amount']   = sprintf('%.2f', ($vendor_params['amount']));
                $guarantee_comment = '';
                if(in_array($vendor_params['payment_type'],array('GUARANTEED_DIRECT_DEBIT_SEPA', 'GUARANTEED_INVOICE'))) {
                    $guarantee_comment  = PHP_EOL . $this->translator->translate('guarantee_payment_text', '', $this->lang) . PHP_EOL;
                    $nn_comments  = $guarantee_comment . $nn_comments;
                }
                // Calling the function to get Novalnet account details
                if('100' == $vendor_params['tid_status']) {
                    $this->helper->prepareInvoiceComments($nn_comments, $vendor_params, $this->translator, $this->lang, array(), $this->shop_id);
                }
            }
            return $nn_comments;
    }

    /**
     * initial Payment Execution
     *
     * @param array $callback_request_parameters
     * @param boolean $on_failure
     *
     * @return null
     */
    public function initialPaymentExecution($callback_request_parameters, $on_failure = false)
    {
        $order_status  = $this->table->fetch_value( ['shop_id' => $this->shop_id, 'config_path' => 'cancellation_order_status'], 'novalnet_lightspeed_merchant_configuration', ['value']);
        if (!$on_failure) {
            $order_status  = $this->table->fetch_value( ['shop_id' => $this->shop_id, 'config_path' => 'payment_confirmation_order_status'], 'novalnet_lightspeed_merchant_configuration', ['value']);

            $callback_comments = sprintf($this->translator->translate('guarantee_payment_confirm', '', $this->lang), date('d-m-Y'), date('H:i:s'));

        }else {
            
            $order_status  = $this->table->fetch_value( ['shop_id' => $this->shop_id, 'config_path' => 'cancellation_order_status'], 'novalnet_lightspeed_merchant_configuration', ['value']);
            $callback_comments = $this->translator->translate('cancel_comment', '', $this->lang). $this->helper->getResponseText($callback_request_parameters);
        }

        // Calling the function to update comments into database
        $shop_comment = $this->api->orders->get($callback_request_parameters['order_id']);
        $comment = $shop_comment['memo'].PHP_EOL.$callback_comments;
        $orders = $this->api->orders->get($callback_request_parameters['callback_order_id']);
        $update = array();
        $update['memo'] = $comment;
        if(in_array($order_status, array('on_hold','cancelled'))){
            $update['status'] = $order_status;
        }else{
            if($orders['status'] == 'on_hold'){
                $update['status'] = 'off_hold';
            }
            $update['paymentStatus'] = $order_status;
        }
        $this->api->orders->update($callback_request_parameters['order_id'], $update);

        // Calling the function to update callback log into database
        $this->insertIntoCallbackTable($callback_request_parameters);

        // Calling the function to update transaction status details into database
        $this->table->update('novalnet_lightspeed_transaction_detail', array('gateway_status'=> (int)$callback_request_parameters['tid_status']), ['shop_id' => $this->shop_id, 'order_id' => $callback_request_parameters['order_id']]);

        // Calling the function to send mail notification for execution of PayPal payment received
        $this->sendMailNotification(html_entity_decode($callback_comments));
    }

    /**
     * handle Communication Failure
     *
     * @param array $callback_request_parameters
     *
     * @return null
     */
    public function handleCommunicationFailure($callback_request_parameters)
    {
        $order = $this->api->orders->get($callback_request_parameters['callback_order_id']);
        $customer_id = $order['customer']['resource']['id'];
        if(isset($callback_request_parameters['webhook_url'])){
            $webhook_url = $callback_request_parameters['webhook_url'];
        }elseif(isset($callback_request_parameters['inputval4'])){
            $webhook_url = $callback_request_parameters['inputval4'];
        }
        $payment_name = $callback_request_parameters['payment_type'];
        if($callback_request_parameters['payment_type'] == 'ONLINE_TRANSFER_CREDIT') {
			$orderobj = $this->api->orders->get($callback_request_parameters['callback_order_id']);
			
			$payment_name = $orderobj['paymentData']['payment_type'];
		}

        if (!empty($order))
        {
            $table = array(
                'shop_id' => $this->shop_id,
                'order_id' => $callback_request_parameters['callback_order_id'],
                'tid' => $callback_request_parameters['shop_tid'],
                'gateway_status' => $callback_request_parameters['tid_status'],
                'total_amount'   => $callback_request_parameters['amount'],
                'customer_id' => $customer_id,
                'payment_name' => $payment_name,
                'webhook_url' => $webhook_url,
            );
            $additional_data = array();
            if(in_array($callback_request_parameters['payment_type'], array('INVOICE_START', 'DIRECT_DEBIT_SEPA', 'CREDITCARD', 'PAYPAL', 'CASHPAYMENT'))){

                $additional_data['vendor'] = $callback_request_parameters['vendor_id'];
                $additional_data['auth_code'] = $callback_request_parameters['vendor_authcode'];
                $additional_data['product'] = $callback_request_parameters['product_id'];
                $additional_data['tariff'] = $callback_request_parameters['tariff_id'];
                if($callback_request_parameters['payment_type'] == 'INVOICE_START'){
                    $table['payment_name'] = $callback_request_parameters['invoice_type'];
                    $additional_data['invoice_bankplace'] = $callback_request_parameters['invoice_bankplace'];
                    $additional_data['invoice_bankname'] = $callback_request_parameters['invoice_bankname'];
                    $additional_data['invoice_bic'] = $callback_request_parameters['invoice_bic'];
                    $additional_data['invoice_iban'] = $callback_request_parameters['invoice_iban'];
                    $additional_data['invoice_account_holder'] = $callback_request_parameters['invoice_account_holder'];
                    $additional_data['due_date'] = $callback_request_parameters['due_date'];
                }elseif($callback_request_parameters['payment_type'] == 'DIRECT_DEBIT_SEPA'){
                $additional_data['sepa_due_date'] = $callback_request_parameters['sepa_due_date'];
                }elseif($callback_request_parameters['payment_type'] == 'CASHPAYMENT'){
                    $additional_data['cp_due_date'] = $callback_request_parameters['cp_due_date'];
                    $additional_data['comments'] = $this->helper->prepareBarzahlenComments($callback_request_parameters);
                }
            }
            $table['additional_data'] = serialize($additional_data);
            $this->table->insert($table, 'novalnet_lightspeed_transaction_detail');
                if(in_array($callback_request_parameters['tid_status'] , array('86','91','99','94','95', '98', '85', '90', '75'))){
                    
					$status = $this->table->fetch_value( ['shop_id' => $this->shop_id, 'config_path' => 'confirmation_order_status'], 'novalnet_lightspeed_merchant_configuration', ['value']);
					if(in_array($callback_request_parameters['tid_status'], array('90','86','75'))){
						
						$status = $this->table->fetch_value( ['shop_id' => $this->shop_id, 'config_path' => 'pending_order_status'], 'novalnet_lightspeed_merchant_configuration', ['value']);
					}
					$payment_status = 'not_paid';
                }elseif($callback_request_parameters['tid_status'] == '100'){
                    
                    $status = $this->table->fetch_value( ['shop_id' => $this->shop_id, 'config_path' => 'payment_confirmation_order_status'], 'novalnet_lightspeed_merchant_configuration', ['value']);
                    $payment_status = 'paid';
                } else{
                     $payment_status = 'cancelled';
                    $status = 'cancelled';
                }
                
                if($callback_request_parameters['tid_status'] == '94'){
                     $status = 'cancelled';
				}
				
				if($callback_request_parameters['payment_type'] == 'ONLINE_TRANSFER_CREDIT') {
					$orderobj = $this->api->orders->get($callback_request_parameters['callback_order_id']);
					
					$callback_comments = sprintf($this->translator->translate('credit_comment', '', $this->lang), $callback_request_parameters['shop_tid'], $this->currencyformat->__invoke($callback_request_parameters['amount']/100, $callback_request_parameters['currency'], null, $this->currency_lang), date("d-m-Y H:i:s"), $callback_request_parameters['tid']);


					// Update order status
					$comments = $orderobj['memo'].PHP_EOL.$callback_comments;
						
					
				} else {

					$comments = 'Novalnet transaction details'.PHP_EOL.PHP_EOL;
					if(in_array($callback_request_parameters['payment_type'], array('GUARANTEED_DIRECT_DEBIT_SEPA', 'GUARANTEED_INVOICE'))){
						$comments .= 'This is processed as a guarantee payment'. PHP_EOL;
					}
					$comments .= $this->translator->translate('payment_method_comment', '', $this->lang);
					$comments .= $this->helper->getPaymentName($callback_request_parameters['payment_type'], $this->translator, $this->lang);
					$comments .= PHP_EOL;
					$comments .=  'Novalnet transaction ID:'.$callback_request_parameters['tid'].PHP_EOL;

					$comments .= ($callback_request_parameters['test_mode'] == '1') ? 'Test order'.PHP_EOL : '';
					if($callback_request_parameters['tid_status'] == 100){
						if($callback_request_parameters['payment_type'] == 'INVOICE_START'){
							$comments .= $this->helper->prepareInvoiceComments($callback_request_parameters);
						}
						if($callback_request_parameters['payment_type'] == 'CASHPAYMENT'){
							$comments .= $this->helper->prepareBarzahlenComments($callback_request_parameters);
						}
					}
					if($callback_request_parameters['tid_status'] == '75'){
						if($callback_request_parameters['payment_type'] == 'GUARANTEED_INVOICE') {
							$comments .= $translator->translate('invoice_gurantee_text', '', $lang). PHP_EOL;
						} else {
							$comments .= $translator->translate('sepa_gurantee_text', '', $lang). PHP_EOL;
						}
					}
				}
				$order_mail = array();
                if($callback_request_parameters['tid_status'] != 100){
                    $order_mail = $this->api->orders->get($callback_request_parameters['callback_order_id']);
                    $email_to =  $this->table->fetch_value( ['shop_id' => $this->shop_id, 'config_path' => 'callback_mail_to'], 'novalnet_lightspeed_merchant_configuration', ['value']);
                   
                    $email_bcc = $this->table->fetch_value( ['shop_id' => $this->shop_id, 'config_path' => 'callback_mail_bcc'], 'novalnet_lightspeed_merchant_configuration', ['value']);
                }else{
                    $order_mail = $this->api->orders->get($callback_request_parameters['callback_order_id']);
                    $email_to = $order_mail['email'];
                }
                $shop = $this->api->shopCompany->get();
                $from_mail = $shop['email'];
                $mail = new Mail\Message();
                $html = new \Zend\Mime\Part(nl2br($comments));
                $html->type = 'text/html';
                $body = new \Zend\Mime\Message;
                $body->setParts(array($html));
                $mail->setBody($body);

                $headers = $mail->getHeaders();
                $headers->removeHeader('Content-Type');
                $headers->addHeaderLine('Content-Type', 'text/html; charset=UTF-8');

                $mail->setFrom($from_mail, $shop['name']);
                $mail->addTo($email_to, $order_mail['firstname'].' '.$order_mail['lastname']);
                if(!empty($email_bcc)){
                    $email_to = array();
                    $email_to = explode(',',$email_bcc);
                    if(!empty($email_to)){
                        $mail->addBcc($email_to);
                    }else{
                        $mail->addBcc($email_bcc);
                    }
                }
                $mail->setEncoding('utf-8');
                $mail->setSubject('Novalnet transaction details');
                $transport = new Mail\Transport\Sendmail();
                $transport->send($mail);
                $orders = $this->api->orders->get($callback_request_parameters['callback_order_id']);
                $update = array();
                $update['memo'] = $comments;
                if(in_array($status, array('on_hold','off_hold'))){
                    $update['status'] = $status;
                }else{
                    if($orders['status'] == 'on_hold'){
                        $update['status'] = 'off_hold';
                    }
                    $update['status'] = $status;
                    $update['paymentStatus'] = $status;
                }

                $this->api->orders->update($callback_request_parameters['callback_order_id'], $update);
                $this->displayMessage($comments);
        } else { // If order reference is not in the database
                $this->displayMessage('Transaction Mapping Failed');
        }
    }

    /**
     * send Payment Confirmation Mail
     *
     * @param string $order_comments
     * @param array $data
     *
     * @return null
     */
    public function sendPaymentConfirmationMail($order_comments, $data)
    {
        $comments = 'Dear Mr./Ms./Mrs.'.$data['name'] .PHP_EOL.PHP_EOL.$this->translator->translate('msg', '', $this->lang).PHP_EOL.PHP_EOL.'Subject:'.sprintf($this->translator->translate('order_confirmnation', '', $this->lang), $data['order_no'], $data['amount']).PHP_EOL.PHP_EOL.'Payment Information:'.PHP_EOL.PHP_EOL . $order_comments .PHP_EOL;
        $mail = new Mail\Message();

        $html = new \Zend\Mime\Part(nl2br($comments));
        $html->type = 'text/html';
        $body = new \Zend\Mime\Message;
        $body->setParts(array($html));
        $mail->setBody($body);

        $headers = $mail->getHeaders();
        $headers->removeHeader('Content-Type');
        $headers->addHeaderLine('Content-Type', 'text/html; charset=UTF-8');

        $mail->setFrom($data['email_from_address'], $data['email_from_name']);
        $mail->addTo($data['email_to_address']);
        $mail->setEncoding('utf-8');
        $mail->setSubject('Novalnet Callback script notification');

        $transport = new Mail\Transport\Sendmail();
        $transport->send($mail);
    }
}
