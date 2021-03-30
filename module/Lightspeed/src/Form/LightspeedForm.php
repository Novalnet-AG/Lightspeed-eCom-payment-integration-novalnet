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
 * Script: LightspeedForm.php
 */


namespace Lightspeed\Form;

use Zend\Form\Form;
use Zend\Form\Element;
use Zend\I18n\Translator\Translator;

class LightspeedForm extends Form
{
    /**
     * Create form for merchant configuration
     *
     * @param  string $lang
     *
     * @return none
     */
    public function __construct($lang)
    {

        $lang = (!empty($lang) && $lang == 'de') ? 'de_DE' : 'en_US';
        $translator = new Translator();
        $translator->addTranslationFile('phparray', dirname(__DIR__). '/lang/'.$lang.'.php', '', $lang);
        parent::__construct('global_cfg_form');

        $this->add([
            'name' => 'shop_id',
            'type' => 'hidden',

        ]);
        $this->add([
            'name' => 'auth_validate',
            'type' => 'hidden',

        ]);
        $this->add([
            'name' => 'api_key',
            'type' => 'text',
            'options' => [
                'label' => $translator->translate('api_key_title', '', $lang).'&nbsp;<span  class="nntool glyphicon glyphicon-question-sign data-toggle="tooltip" title="'.$translator->translate('api_key_tooltip', '', $lang).'"></span>',
                'label_attributes' => array(
                    'class' => 'col-xs-12 col-sm-12 col-md-4 control-label',
                ),
                'label_options' => array(
                    'disable_html_escape' => true,
                ),
            ],
            'attributes' => [
                'id' => 'api_key',
                'class'       => 'form-control',
                'data-container' =>'body',
            ],
        ]);
        $this->add([
            'name' => 'vendor',
            'type' => 'hidden',
            'attributes' => [
                'id' => 'vendor',
        ]]);
        
        $this->add([
            'name' => 'guarntee_minimum_amount_error',
            'type' => 'hidden',
            'attributes' => [
                'id' => 'guarntee_minimum_amount_error',
        ]]);
        
        $this->add([
            'name' => 'sepa_due_date_error',
            'type' => 'hidden',
            'attributes' => [
                'id' => 'sepa_due_date_error',
        ]]);
        
        $this->add([
            'name' => 'invoice_due_date_error',
            'type' => 'hidden',
            'attributes' => [
                'id' => 'invoice_due_date_error',
        ]]);
        
        $this->add([
            'name' => 'mail_error',
            'type' => 'hidden',
            'attributes' => [
                'id' => 'mail_error',
        ]]);
        
        $this->add([
            'name' => 'config_error_desc',
            'type' => 'hidden',
            'attributes' => [
                'id' => 'config_error_desc',
        ]]);

        $this->add([
            'name' => 'auth_code',
            'type' => 'hidden',
            'attributes' => [
                'id' => 'auth_code',
        ]]);

        $this->add([
            'name' => 'product',
            'type' => 'hidden',
            'attributes' => [
                'id' => 'product',

        ]]);

        $this->add([
            'name' => 'tariff',
            'type' => 'select',
            'options' => [
                'label' => $translator->translate('tariff_title', '', $lang).'&nbsp;<span  class="nntool glyphicon glyphicon-question-sign data-toggle="tooltip" title="'.$translator->translate('tariff_tooltip', '', $lang).'"></span>',
                'disable_inarray_validator' => true,
                'label_attributes' => array(
                    'class' => 'col-xs-12 col-sm-12 col-md-4 control-label',
                ),
                'label_options' => array(
                    'disable_html_escape' => true,
                ),
            ],
            'attributes' => [
                'id' => 'tariff',
                'class' => 'form-control',
                'data-container' =>'body',
            ],
        ]);

        $this->add([
            'name' => 'access_key',
            'type' => 'hidden',
            'attributes' => [
                'id' => 'access_key',
        ]]);

        $this->add([
            'name' => 'tariff_val',
            'type' => 'hidden',
            'attributes' => [
                'id' => 'tariff_val',
        ]]);

        $this->add([
            'name' => 'submit',
            'type' => 'submit',
            'onClick' => 'loadicon()',
            'disable_html_escape' => true,
            'attributes' => [
                'value'  => 'submit',
                'id'    => 'submitbutton',
                'class' => 'btn btn-info',
            ],
        ]);

        $this->add([
            'type' => Element\Checkbox::class,
            'name' => 'test_mode',
            'options' => [
                'label' => $translator->translate('test_mode_title', '', $lang).'&nbsp;<span  class="nntool glyphicon glyphicon-question-sign data-toggle="tooltip" title="'.$translator->translate('test_mode_title_tooltip', '', $lang).'"></span>',
                'label_attributes' => array(
                    'class' => 'col-xs-12 col-sm-12 col-md-4 control-label',
                ),
                'label_options' => array(
                    'disable_html_escape' => true,
                ),
                'use_hidden_element' => true,
                'checked_value' => '1',
                'unchecked_value' => '0',
            ],
            'attributes' => [
                'id' => 'test_mode',
                'value' => '0'
            ],
        ]);
        $this->add([
            'type' => Element\Select::class,
            'name' => 'payment_action',
            'options' => [
                'label' => $translator->translate('payment_action_title', '', $lang).'&nbsp;<span  class="nntool glyphicon glyphicon-question-sign data-toggle="tooltip" title="'.$translator->translate('payment_action_title_tooltip', '', $lang).'"></span>',
                'label_attributes' => array(
                    'class' => 'col-xs-12 col-sm-12 col-md-4 control-label',
                ),
                'label_options' => array(
                    'disable_html_escape' => true,
                ),
                'value_options' => [
                    'capture'   => $translator->translate('capture', '', $lang),
                    'authorise' => $translator->translate('authorise', '', $lang)

                ],
            ],
            'attributes' => [
                'id'    => 'payment_action',
                'class' => 'form-control',
            ],
        ]);
        $this->add([
            'name' => 'manual_check_limit',
            'type' => 'text',
            'options' => [
                'label' => $translator->translate('manual_check_limit', '', $lang).'&nbsp;<span  class="nntool glyphicon glyphicon-question-sign data-toggle="tooltip" title="'.$translator->translate('manual_check_limit_desc', '', $lang).'"></span>',
                'disable_inarray_validator' => true,
                'label_attributes' => array(
                    'class' => 'col-xs-12 col-sm-12 col-md-4 control-label',
                ),
                'label_options' => array(
                    'disable_html_escape' => true,
                ),
            ],
            'attributes' => [
                'id'    => 'manual_check_limit',
                'class' => 'form-control',
                'data-container' =>'body',
            ],
        ]);

        $this->add([
            'name' => 'gateway',
            'type' => 'text',
            'options' => [
                'label' => $translator->translate('gateway_timeout_title', '', $lang).'&nbsp;<span  class="nntool glyphicon glyphicon-question-sign data-toggle="tooltip"  title="'.$translator->translate('gateway_tooltip', '', $lang).'"></span>',
                'label_attributes' => array(
                    'class' => 'col-xs-12 col-sm-12 col-md-4 control-label',
                ),
                'label_options' => array(
                    'disable_html_escape' => true,
                ),
            ],
            'attributes' => [
                 'id' => 'gateway',
                 'value' => '240',
                 'class' => 'form-control',
                 'data-container' =>'body',
            ],
        ]);

        $this->add([
            'name' => 'referrer',
            'type' => 'text',
            'options' => [
                'label' => $translator->translate('referrer_id_title', '', $lang).'&nbsp;<span  class="nntool glyphicon glyphicon-question-sign data-toggle="tooltip" title="'.$translator->translate('referrer_tooltip', '', $lang).'"></span>',
                'label_attributes' => array(
                    'class' => 'col-xs-12 col-sm-12 col-md-4 control-label',
                ),
                'label_options' => array(
                    'disable_html_escape' => true,
                ),
            ],
            'attributes' => [
                'class' => 'form-control',
                'data-container' =>'body',
                'id'    => 'referrer_id',
            ],
        ]);

        $this->add([
            'type' => Element\MultiCheckbox::class,
            'name' => 'payment_gateways_list',
            'options' => [
                'disable_inarray_validator' => true,
                'value_options' => [
                    [
                        'value' => 'novalnet_cc',
                        'label' => $translator->translate('novalnet_cc', '', $lang).'&nbsp;<img src="https://lightspeed.novalnet.de/img/novalnet_cc.png" height="32" alt=" Credit Card">',
                        'label_attributes' => [
                            'id' => 'CREDITCARD',
                        ],
                    ],
                    [
                        'value' => 'novalnet_sepa',
                        'label' => $translator->translate('novalnet_sepa', '', $lang).'&nbsp;<img src="https://lightspeed.novalnet.de/img/novalnet_sepa.png" height="32" alt=" Direct Debit SEPA">',
                        'label_attributes' => [
                            'id' => 'DIRECT_DEBIT_SEPA',
                        ],
                    ],
                    [
                        'value' => 'novalnet_invoice',
                        'label' => $translator->translate('novalnet_invoice', '', $lang).'&nbsp;<img src="https://lightspeed.novalnet.de/img/novalnet_invoice.png" height="32" alt=" Invoice">',
                        'label_attributes' => [
                            'id' => 'INVOICE_START',
                        ],
                    ],
                    [
                        'value' => 'novalnet_prepayment',
                        'label' => $translator->translate('novalnet_prepayment', '', $lang).'&nbsp;<img  src="https://lightspeed.novalnet.de/img/novalnet_prepayment.png" height="32" alt=" Prepayment">',
                        'label_attributes' => [
                            'id' => 'PREPAYMENT',
                        ],
                    ],
                    [
                        'value' => 'novalnet_barzahlen',
                        'label' => ' Barzahlen/viacash'.'&nbsp;<img src="https://lightspeed.novalnet.de/img/novalnet_barzahlen.png" height="32" alt="Barzahlen">',
                        'label_attributes' => [
                            'id' => 'CASHPAYMENT',
                        ],
                    ],
                    [
                        'value' => 'novalnet_sofort',
                        'label' => $translator->translate('novalnet_sofort', '', $lang).'&nbsp;<img src="https://lightspeed.novalnet.de/img/novalnet_banktransfer.png" height="32" alt=" Instant Bank Transfer">',
                        'label_attributes' => [
                            'id' => 'ONLINE_TRANSFER',
                        ],
                    ],
                    [
                        'value' => 'novalnet_ideal',
                        'label' => ' iDEAL'.'&nbsp;<img src="https://lightspeed.novalnet.de/img/novalnet_ideal.png" height="32" alt="iDEAL">',
                        'label_attributes' => [
                            'id' => 'IDEAL',
                        ],
                    ],
                    [
                        'value' => 'novalnet_giropay',
                        'label' => ' giropay'.'&nbsp;<img src="https://lightspeed.novalnet.de/img/novalnet_giropay.png" height="32" alt="giropay ">',
                        'label_attributes' => [
                            'id' => 'GIROPAY',
                        ],
                    ],
                    [
                        'value' => 'novalnet_eps',
                        'label' => ' eps'.'&nbsp;<img src="https://lightspeed.novalnet.de/img/novalnet_eps.png" height="32" alt="eps">',
                        'label_attributes' => [
                            'id' => 'EPS',
                        ],
                    ],
                    [
                        'value' => 'novalnet_przelewy24',
                        'label' => ' Przelewy24'.'&nbsp;<img  src="https://lightspeed.novalnet.de/img/novalnet_przelewy24.png" height="32" alt="Przelewy24">',
                        'label_attributes' => [
                            'id' => 'PRZELEWY24',
                        ],
                    ],
                    [
                        'value' => 'novalnet_paypal',
                        'label' => ' PayPal'. '&nbsp;<img src="https://lightspeed.novalnet.de/img/novalnet_paypal.png" height="32" alt="PayPal">',
                        'label_attributes' => [
                            'id' => 'PAYPAL',
                        ],
                    ],

                ],

                'label_attributes' => [
                    'class' => 'col-xs-12 col-sm-12 col-md-6 payment-gateway-label control-label',
                    'name' => 'payment_gateways_list'
                ],
                'label_options' => [
                    'disable_html_escape' => true,
                ],

                'attributes' => [
                    'class' => 'form-control',
                    'data-container' =>'body',
                ]
            ],
        ]);

        $this->add([
            'type' => Element\Select::class,
            'name' => 'confirmation_order_status',
            'options' => [
                'label' => $translator->translate('onhold_order_status_title', '', $lang).'&nbsp;<span  class="nntool glyphicon glyphicon-question-sign data-toggle="tooltip" title="'.$translator->translate('onhold_order_status_title_tooltip', '', $lang).'"></span>',
                'label_attributes' => array(
                    'class' => 'col-xs-12 col-sm-12 col-md-4 control-label',
                ),
                'label_options' => array(
                    'disable_html_escape' => true,
                ),
                'value_options' => [
                    'not_paid' => $translator->translate('not_paid', '', $lang),
                    'paid' => $translator->translate('paid', '', $lang),
                    'cancelled' => $translator->translate('cancelled', '', $lang),
                    'on_hold' => $translator->translate('on_hold', '', $lang),

                ],
            ],
            'attributes' => [
                'class' => 'form-control',
            ],
        ]);

        $this->add([
            'type' => Element\Select::class,
            'name' => 'cancellation_order_status',
            'options' => [
                'label' => $translator->translate('cancellation_order_status', '', $lang).'&nbsp;<span  class="nntool glyphicon glyphicon-question-sign data-toggle="tooltip" title="'.$translator->translate('cancellation_order_status_title_tooltip', '', $lang).'"></span>',
                'label_attributes' => array(
                    'class' => 'col-xs-12 col-sm-12 col-md-4 control-label',
                ),
                'label_options' => array(
                    'disable_html_escape' => true,
                ),
                'value_options' => [
                    'not_paid' => $translator->translate('not_paid', '', $lang),
                    'paid' => $translator->translate('paid', '', $lang),
                    'cancelled' => $translator->translate('cancelled', '', $lang),
                    'on_hold' => $translator->translate('on_hold', '', $lang),
                ],
            ],
            'attributes' => [
                'class' => 'form-control',
            ],
        ]);

        $this->add([
            'type' => Element\Checkbox::class,
            'name' => 'deactivate_ip_check',
            'options' => [
                'label' => $translator->translate('deactivate_ip_check_title', '', $lang).'&nbsp;<span  class="nntool glyphicon glyphicon-question-sign data-toggle="tooltip" title="'.$translator->translate('deactivate_ip_check_title_tooltip', '', $lang).'"></span>',
                'label_attributes' => array(
                    'class' => 'col-xs-12 col-sm-12 col-md-4 control-label',
                ),
                'label_options' => array(
                    'disable_html_escape' => true,
                ),
                'use_hidden_element' => true,
                'checked_value' => '1',
                'unchecked_value' => '0',
            ],
            'attributes' => [
                'id' => 'deactivate_ip_check',
            ],
        ]);
        
        $this->add([
            'name' => 'novalnet_cc_enforce_3d',
            'type' => Element\Checkbox::class,
            'options' => [
                'label' => $translator->translate('novalnet_cc_enforce_3d_title', '', $lang).'&nbsp<span  class="nntool glyphicon glyphicon-question-sign data-toggle="tooltip" title="'.$translator->translate('novalnet_cc_enforce_3d_tooltip', '', $lang).'"></span>',
                'label_attributes' => array(
                    'class' => 'col-md-4 control-label',
                ),
                'label_options' => array(
                    'disable_html_escape' => true,
                ),
                'use_hidden_element' => true,
                'checked_value' => '1',
                'unchecked_value' => '0',
            ],
            'attributes' =>[
                'id' => 'novalnet_cc_enforce_3d',
            ],
        ]);

        $this->add([
            'type' => Element\Checkbox::class,
            'name' => 'callback_mail_notification',
            'options' => [
                'label' => $translator->translate('callback_mail_notification_title', '', $lang).'&nbsp;<span  class="nntool glyphicon glyphicon-question-sign data-toggle="tooltip" title="'.$translator->translate('callback_mail_notification_title_tooltip', '', $lang).'"></span>',
                'label_attributes' => array(
                    'class' => 'col-xs-12 col-sm-12 col-md-4 control-label',
                ),
                'label_options' => array(
                    'disable_html_escape' => true,
                ),
                'use_hidden_element' => true,
                'checked_value' => '1',
                'unchecked_value' => '0',
            ],
            'attributes' => [
                'id' => 'callback_mail_notification',
            ],
        ]);

        $this->add([
            'name' => 'callback_mail_to',
            'type' => 'text',
            'options' => [
                'label' => $translator->translate('callback_mail_to_title', '', $lang).'&nbsp;<span  class="nntool glyphicon glyphicon-question-sign data-toggle="tooltip" title="'.$translator->translate('callback_mail_to_tooltip', '', $lang).'"></span>',
                'label_attributes' => array(
                    'class' => 'col-xs-12 col-sm-12 col-md-4 control-label',
            ),
            'label_options' => array(
                    'disable_html_escape' => true,
            ),
            ],
            'attributes' => [
                'class' => 'form-control',
                'id' => 'callback_mail_to',
            ],
        ]);

        $this->add([
            'name' => 'callback_mail_bcc',
            'type' => 'text',
            'options' => [
                'label' => $translator->translate('callback_mail_bcc_title', '', $lang).'&nbsp;<span  class="nntool glyphicon glyphicon-question-sign data-toggle="tooltip"  title="'.$translator->translate('callback_mail_bcc_tooltip', '', $lang).'"></span>',
                'label_attributes' => array(
                    'class' => 'col-xs-12 col-sm-12 col-md-4 control-label',
            ),
            'label_options' => array(
                    'disable_html_escape' => true,
            )
            ],
            'attributes' => [
                'class' => 'form-control',
                'id' => 'callback_mail_bcc',
            ],
        ]);

        $this->add([
            'name' => 'callback_url',
            'type' => 'text',
            'options' => [
                'label' => $translator->translate('callback_url', '', $lang).'&nbsp;<span  class="nntool glyphicon glyphicon-question-sign data-toggle="tooltip" title="'.$translator->translate('callback_url_tooltip', '', $lang).'"></span>',
                'label_attributes' => array(
                    'class' => 'col-xs-12 col-sm-12 col-md-4 control-label',
            ),
            'label_options' => array(
                    'disable_html_escape' => true,
            )],
            'attributes' => [
                'class' => 'form-control',
                'readonly' => true,
            ],
        ]);

        $this->add([
            'type' => Element\Csrf::class,
            'name' => 'csrf',
            'options' => [
                'csrf_options' => [
                    'timeout' => 136800,
                ],
            ],
        ]);

        $this->add([
            'type' => Element\Select::class,
            'name' => 'pending_order_status',
            'options' => [
                'label' => $translator->translate('payment_pending_order_status_title', '', $lang).'&nbsp;<span  class="nntool glyphicon glyphicon-question-sign data-toggle="tooltip" title="'.$translator->translate('payment_pending_order_status_title_tooltip', '', $lang).'"></span>',
                'label_attributes' => array(
                    'class' => 'col-xs-12 col-sm-12 col-md-4 control-label',
                 ),
                 'label_options' => array(
                    'disable_html_escape' => true,
            ),
                'value_options' => [
                    'not_paid' => $translator->translate('not_paid', '', $lang),
                    'paid' => $translator->translate('paid', '', $lang),
                    'cancelled' => $translator->translate('cancelled', '', $lang),
                    'on_hold' => $translator->translate('on_hold', '', $lang),
                ],
            ],
            'attributes' => [
                'class' => 'form-control',
            ],
        ]);

        $this->add([
            'type' => Element\Select::class,
            'name' => 'payment_confirmation_order_status',
            'options' => [
                'label' => $translator->translate('payment_confirmation_order_status', '', $lang).'&nbsp;<span  class="nntool glyphicon glyphicon-question-sign data-toggle="tooltip" title="'.$translator->translate('payment_confirmation_order_status_tooltip', '', $lang).'"></span>',
                'label_attributes' => array(
                    'class' => 'col-xs-12 col-sm-12 col-md-4 control-label',
                 ),
                 'label_options' => array(
                    'disable_html_escape' => true,
                ),
                'value_options' => [
                    'not_paid' => $translator->translate('not_paid', '', $lang),
                    'paid' => $translator->translate('paid', '', $lang),
                    'cancelled' => $translator->translate('cancelled', '', $lang),
                    'on_hold' => $translator->translate('on_hold', '', $lang),
                ],
            ],
            'attributes' => [
                'class' => 'form-control',
            ],
        ]);

        $this->add([
            'type' => Element\Select::class,
            'name' => 'callback_order_status',
            'options' => [
                'label' => $translator->translate('callback_order_status_title', '', $lang).'&nbsp;<span  class="nntool glyphicon glyphicon-question-sign data-toggle="tooltip" title="'.$translator->translate('callback_order_status_title_tooltip', '', $lang).'"></span>',
                'label_attributes' => array(
                    'class' => 'col-xs-12 col-sm-12 col-md-4 control-label',
                 ),
                 'label_options' => array(
                    'disable_html_escape' => true,
                ),
                'value_options' => [
                    'not_paid' => $translator->translate('not_paid', '', $lang),
                    'paid' => $translator->translate('paid', '', $lang),
                    'cancelled' => $translator->translate('cancelled', '', $lang),
                    'on_hold' => $translator->translate('on_hold', '', $lang),
                ],
            ],
            'attributes' => [
                'class' => 'form-control',
            ],
        ]);

        $this->add([
            'name' => 'novalnet_invoice_due_date',
            'type' => 'text',
            'options' => [
                'label' => $translator->translate('invoice_due_date_title', '', $lang).'&nbsp;<span  class="nntool glyphicon glyphicon-question-sign data-toggle="tooltip" title="'.$translator->translate('invoice_due_date_tooltip', '', $lang).'"></span>',
                'label_attributes' => array(
                'class' => 'col-xs-12 col-sm-12 col-md-4 control-label payment-settings',
                ),
                'label_options' => array(
                    'disable_html_escape' => true,
                )
            ],
            'attributes' => [
                'class' => 'form-control',
                'id' => 'novalnet_invoice_due_date',
            ],
        ]);

        $this->add([
            'name' => 'novalnet_barzahlen_due_date',
            'type' => 'text',
            'options' => [
                'label' => $translator->translate('slip_due_date_title', '', $lang).'&nbsp;<span  class="nntool glyphicon glyphicon-question-sign data-toggle="tooltip" title="'.$translator->translate('barzahlen_due_date_tooltip', '', $lang).'"></span>',
                'label_attributes' => array(
                'class' => 'col-xs-12 col-sm-12 col-md-4 control-label payment-settings',
                ),
                'label_options' => array(
                    'disable_html_escape' => true,
                )
            ],
            'attributes' => [
                'class' => 'form-control',
                'id' => 'novalnet_barzahlen_due_date',
            ],
        ]);

        $this->add([
            'name' => 'novalnet_sepa_due_date',
            'type' => 'text',
            'options' => [
                'label' => $translator->translate('sepa_due_date_title', '', $lang).'&nbsp;<span  class="nntool glyphicon glyphicon-question-sign data-toggle="tooltip" title="'.$translator->translate('sepa_due_date_tooltip', '', $lang).'"></span>',
                'label_attributes' => array(
                    'class' => 'col-xs-12 col-sm-12 col-md-4 control-label payment-settings',
                ),
                'label_options' => array(
                    'disable_html_escape' => true,
                )
            ],
            'attributes' => [
                'class' => 'form-control',
                'id' => 'novalnet_sepa_due_date',
            ],
        ]);

        $this->add([
            'name' => 'novalnet_sepa_enable_guarantee',
            'type' => Element\Checkbox::class,
            'options' => [
                'label' => $translator->translate('enable_payment_guarantee', '', $lang).'&nbsp;<span  class="nntool glyphicon glyphicon-question-sign data-toggle="tooltip" title="'.$translator->translate('enable_payment_guarantee_tooltip', '', $lang).'"></span>',
                'label_attributes' => array(
                    'class' => 'col-xs-12 col-sm-12 col-md-4 control-label payment-settings',
                ),
                'label_options' => array(
                    'disable_html_escape' => true,
                ),
                'use_hidden_element' => true,
                'checked_value' => '1',
                'unchecked_value' => '0',
            ],
            'attributes' =>[
                'id' => 'novalnet_sepa_enable_guarantee',
            ],
        ]);
        $this->add([
            'name' => 'novalnet_invoice_enable_guarantee',
            'type' => Element\Checkbox::class,
            'options' => [
                'label' => $translator->translate('enable_payment_guarantee', '', $lang).'&nbsp;<span  class="nntool glyphicon glyphicon-question-sign data-toggle="tooltip" title="'.$translator->translate('enable_payment_guarantee_tooltip', '', $lang).'"></span>',
                'label_attributes' => array(
                    'class' => 'col-xs-12 col-sm-12 col-md-4 control-label payment-settings',
                ),
                'label_options' => array(
                    'disable_html_escape' => true,
                ),
                'use_hidden_element' => true,
                'checked_value' => '1',
                'unchecked_value' => '0',
            ],
            'attributes' =>[
                'id' => 'novalnet_invoice_enable_guarantee',
            ],
        ]);

        $this->add([
            'name' => 'novalnet_invoice_guarantee_minimum_amount',
            'type' => 'text',
            'options' => [
                'label' => $translator->translate('minimum_amount', '', $lang).'&nbsp;<span  class="nntool glyphicon glyphicon-question-sign data-toggle="tooltip" title="'.$translator->translate('minimum_amount_tooltip', '', $lang).'"></span>',
                'label_attributes' => array(
                    'class' => 'col-xs-12 col-sm-12 col-md-4 control-label payment-settings',
                ),
                'label_options' => array(
                    'disable_html_escape' => true,
                )
            ],
            'attributes' => [
                'class' => 'form-control',
                'id' => 'novalnet_invoice_guarantee_minimum_amount',
            ],
        ]);

        $this->add([
            'name' => 'novalnet_sepa_guarantee_minimum_amount',
            'type' => 'text',
            'options' => [
                'label' => $translator->translate('minimum_amount', '', $lang).'&nbsp;<span  class="nntool glyphicon glyphicon-question-sign data-toggle="tooltip" title="'.$translator->translate('minimum_amount_tooltip', '', $lang).'"></span>',
                'label_attributes' => array(
                    'class' => 'col-xs-12 col-sm-12 col-md-4 control-label payment-settings',
                ),
                'label_options' => array(
                    'disable_html_escape' => true,
                )
            ],
            'attributes' => [
                'class' => 'form-control',
                'id' => 'novalnet_sepa_guarantee_minimum_amount',
            ],
        ]);

        $this->add([
            'name' => 'novalnet_sepa_enable_force_guarantee',
            'type' => Element\Checkbox::class,
            'options' => [
                'label' => $translator->translate('force_guarantee', '', $lang).'&nbsp;<span  class="nntool glyphicon glyphicon-question-sign data-toggle="tooltip" title="'.$translator->translate('force_guarantee_tooltip', '', $lang).'"></span>',
                'label_attributes' => array(
                    'class' => 'col-xs-12 col-sm-12 col-md-4 control-label payment-settings',
                ),
                'label_options' => array(
                    'disable_html_escape' => true,
                ),
                'use_hidden_element' => true,
                'checked_value' => '1',
                'unchecked_value' => '0',
            ],
            'attributes' =>[
                'id' => 'novalnet_sepa_enable_force_guarantee',
            ],
        ]);
        $this->add([
            'name' => 'novalnet_invoice_enable_force_guarantee',
            'type' => Element\Checkbox::class,
            'options' => [
                'label' => $translator->translate('force_guarantee', '', $lang).'&nbsp;<span  class="nntool glyphicon glyphicon-question-sign data-toggle="tooltip" title="'.$translator->translate('force_guarantee_tooltip', '', $lang).'"></span>',
                'label_attributes' => array(
                    'class' => 'col-xs-12 col-sm-12 col-md-4 control-label payment-settings',
                ),
                'label_options' => array(
                    'disable_html_escape' => true,
                ),
                'use_hidden_element' => true,
                'checked_value' => '1',
                'unchecked_value' => '0',
            ],
            'attributes' =>[
                'id' => 'novalnet_invoice_enable_force_guarantee',
            ],
        ]);
    }
}
