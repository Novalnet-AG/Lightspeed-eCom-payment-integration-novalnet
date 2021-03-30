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
 * Script: BackendController.php
 */

namespace Lightspeed\Controller;

use Lightspeed\Form\LightspeedForm;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Lightspeed\Model\LightspeedTable;
use Lightspeed\Model\Backend;
use Lightspeed\Helper\WebshopappApiClient;
use Lightspeed\Helper\NovalnetHelper;
use Zend\I18n\Translator\Translator;
use Zend\Mvc\MvcEvent;
use Zend\Http\Client;
use Zend\Session\Container as SessionContainer;

class BackendController extends AbstractActionController
{
    public $table;
    public $lightspeed_session;

    public $api_key = 'b6bf86a4938e57c09d37c09fb7b6ed0e';
    public $api_secret = '46c570387c3f3f59beef08c394755e36';

    /**
     * Initialize the controller, get the database properties
     *
     * @param object $table
     * @return null
     */
    public function __construct(LightspeedTable $table)
    {

        $this->table = $table;
        $this->helper = new NovalnetHelper($this->table);
        $this->sessionContainer =   new SessionContainer( 'lightspeed_novalnet_backend' );
    }

     /**
     * On dispatch event
     *
     * @param  MvcEvent $e
     */
    public function onDispatch(MvcEvent $e)
    {
        $authorized =   false;
        if( ! empty( $this->getRequest()->getHeaders('referer') )  ) {
            $parsed_url = parse_url( $this->getRequest()->getHeaders('referer')->getFieldValue('scheme') );
            if( in_array( $parsed_url['host'],[ 'api.webshopapp.com', 'lightspeed.novalnet.de'])) {
                $authorized =   true;
                $this->sessionContainer->offsetSet('user_logged_in', true );
            } else if(strpos($parsed_url['host'], '.webshopapp.com') !== false) {
                $authorized =   true;
                $this->sessionContainer->offsetSet('user_logged_in', true );
            }
        } else if( $this->sessionContainer->offsetExists('user_logged_in' ) == true) {
            $authorized =   true;
        } 
        
        if( $authorized == false ) {
            if( $this->params('action') != 'denied' ) {
                return $this->redirect()->toRoute('backend', ['action' => 'denied','signature' => urlencode('Please login to your Lightspeed shop backend to access this URL')]);
            }
        }
        return parent::onDispatch($e);
    }

    /**
     * General Function for redirecting to Novalnet Home Page
     */
    public function indexAction()
    {
        header('Location:https://www.novalnet.de/');
        exit;
    }

    /**
     * show access denied page
     *
     * @param  null
     * @return array 
     */
    public function deniedAction()
    {
         $msg = $this->params()->fromRoute('signature', 0);
         $this->sessionContainer->offsetUnset('user_logged_in');
         $viewData = ['msg' => urldecode($msg)];
         return $viewData;
    }

    /**
     * Renders the backend form, saves the configuration value
     *
     * @param  null
     * @return array
     */
    public function configAction()
    {
        $id = (int) $this->params()->fromRoute('id',0);
        $lang = $this->params()->fromRoute('lang',0);
        $this->sessionContainer->offsetSet('lang', $lang);
        if($lang == 0) {
            $lang = $this->sessionContainer->offsetGet('lang');
        }
        $language = (!empty($lang) && $lang == 'de') ? 'de_DE' : 'en_US';
        $translator = new Translator();
        $translator->addTranslationFile('phparray', dirname(__DIR__). '/lang/'.$language.'.php', '', $language);
        $hash = $this->params()->fromRoute('signature',0);
        $status = 0;
        $backend = new Backend();
        $this->table->getConfig($id, $backend);
        $form = new LightspeedForm($lang);
        $request = $this->getRequest();
        if (! $request->isPost()) {
            $form->bind($backend);
            $form->get('submit')->setValue($translator->translate('save', '', $language));
            $form->get('shop_id')->setValue($id);
            $form->get('callback_url')->setValue('https://lightspeed.novalnet.de/callback/'.$id);
            if($this->sessionContainer->offsetExists('success' )){
                $status = 1;
                $this->sessionContainer->offsetUnset('success');
            }
            $viewData = ['id' => $id, 'form' => $form,'hash' => $hash, 'lang' => $lang, 'success' => $status, 'translator' => $translator];
            return  $viewData;
        }

        $validate = true;
        $callback_mail_to = $this->getRequest()->getPost('callback_mail_to', 0);
        $callback_mail_bcc = $this->getRequest()->getPost('callback_mail_bcc', 0);

        if(!empty($callback_mail_bcc)) {
            $validate = $this->helper->validateEmail($callback_mail_bcc);
        }
        if(!empty($callback_mail_to) && $validate) {
            $validate = $this->helper->validateEmail($callback_mail_to);
        }
        $form->setInputFilter($backend->getInputFilter());
        $form->setData($request->getPost());
        if (!$form->isValid() || !$validate) {
			    $error = $form->getMessages();
			if(!empty($error['csrf'])) {
				return $this->redirect()->toRoute('backend', ['action' => 'denied','signature' => urlencode('Please login to your Lightspeed shop backend to access this URL')]);
			}
            $form->bind($backend);
            $form->get('submit')->setValue($translator->translate('save', '', $language));
            $form->get('shop_id')->setValue($id);
            $form->get('callback_url')->setValue('https://lightspeed.novalnet.de/callback/'.$id);
            $viewData = ['id' => $id, 'form' => $form ,'hash' => $hash, 'lang' => $lang, 'failure' => 1, 'translator' => $translator];
            return $viewData;
        }
        $this->sessionContainer->offsetSet('success', true );
        $post = $form->getData();
        $data = $backend->exchangeArray($post);
        $this->table->saveBackend($data, $backend);
        return $this->redirect()->toRoute('backend', ['action' => 'config', 'id' => $post['shop_id'], 'signature' => $hash,'lang'=> $lang]);
    }

    /**
     * Auto config request is sent through curl
     */
    public function apiAction()
    {
		
        $gateway_timeout = $this->table->fetch_value(['shop_id' => $this->getRequest()->getPost('shop_id', 0), 'config_path' => 'gateway'], 'novalnet_lightspeed_merchant_configuration', ['value']);
        $client = new Client('https://payport.novalnet.de/autoconfig', [
            'maxredirects' => 0,
            'timeout' => ($gateway_timeout && $gateway_timeout > 0) ? $gateway_timeout : 240
        ]);
        $data = array(
            'hash' => $this->getRequest()->getPost('api_config_hash', 0),
            'lang' => $this->getRequest()->getPost('lang', 0),
        );
        $client->setMethod('POST');
        $client->setParameterPost( $data );
        echo utf8_decode($client->send()->getBody());exit;
    }

    /**
     * Install tables and configuration values
	 *
	 * @param  null
     * @return array
	 *
     */
    public function installAction()
    {
        // Validate the signature
        $validate = $this->helper->validateRequest($this->getRequest(), $this->api_secret, 'install');
        $lang = $this->getRequest()->getQuery('language');
        $language = (!empty($lang) && $lang == 'de') ? 'de_DE' : 'en_US';
        $translator = new Translator();
        $translator->addTranslationFile('phparray', dirname(__DIR__). '/lang/'.$language.'.php', '', $language);
        $shop_id = $this->getRequest()->getQuery('shop_id');

        if ($validate)
        {
            // Store the store identifier (ID), API token and store language for later use
            // Each API token represents a single store
            $data = [
                'shop_id' => $shop_id,
                'cluster' => $this->getRequest()->getQuery('cluster_id'),
                'lang' => $lang,
                'token' => $this->getRequest()->getQuery('token'),
                'timestamp' => $this->getRequest()->getQuery('timestamp'),
            ];
            $table = $this->table->insert($data, 'novalnet_lightspeed_installed_shop');
            if($table){
                $userSecret = md5($data['token'].$this->api_secret);
                $api = new WebshopappApiClient($data['cluster'], $this->api_key, $userSecret, $data['lang']);
                $payment_integeration = $api->external_services->create([
                    "type"        => "payment",
                    "name"        => "Novalnet",
                    "urlEndpoint" => "https://lightspeed.novalnet.de/lightspeed",
                    "isActive"    => true
                ]);
                $update = ['payment_id' => $payment_integeration['id']];
                $this->table->update('novalnet_lightspeed_installed_shop', $update, ['shop_id' => $shop_id]);          
            }
            return $this->redirect()->toRoute('backend', array(
                'action' => 'config',
                'id' => $shop_id,
                'signature' => $this->getRequest()->getQuery('signature'),
                'lang'   => $lang,
                'auth_validate' => $this->lightspeed_session,
            ));
        } else {
            return $this->redirect()->toRoute('backend', ['action' => 'denied','signature' => urlencode('Unauthorized access')]);
        }
    }

    /**
     * uninstall the novalnet payment app from theshop
     */
    public function uninstallAction()
    {
        $validate = $this->helper->validateRequest($this->getRequest(), $this->api_secret, 'uninstall');
        $lang = $this->getRequest()->getQuery('language');
        $language = (!empty($lang) && $lang == 'de') ? 'de_DE' : 'en_US';
        $translator = new Translator();
        $translator->addTranslationFile('phparray', dirname(__DIR__). '/lang/'.$language.'.php', '', $language);
        $shop_id = $this->getRequest()->getQuery('shop_id');

            // Validate the signature
        if ($validate) {
            // Delete the store information from the database
            $shop_data = $this->table->fetchAll('novalnet_lightspeed_installed_shop', $shop_id);
            if(!empty($shop_data['payment_id'])){
                $userSecret = md5($shop_data['token'].$this->api_secret);
                $api = new WebshopappApiClient($shop_data['cluster'], $this->api_key, $userSecret, $lang);
                $api->external_services->delete($shop_data['payment_id']);
            }
            $table_name = array('novalnet_lightspeed_installed_shop','novalnet_lightspeed_merchant_configuration');
            foreach($table_name as $table){
                $this->table->delete($table ,$shop_id);
            }
            $data = [
                'shop_id' => $shop_id,
                'cluster' => $this->getRequest()->getQuery('cluster_id'),
                'timestamp' => $this->getRequest()->getQuery('timestamp'),
            ];
            $this->table->insert($data, 'novalnet_lightspeed_uninstalled_shop');
            return $this->redirect()->toRoute('backend', ['action' => 'denied','signature' => urlencode('uninstalled successfully')]);
        } else {
            return $this->redirect()->toRoute('backend', ['action' => 'denied','signature' => urlencode('Unauthorized access')]);
        }
    }
}
