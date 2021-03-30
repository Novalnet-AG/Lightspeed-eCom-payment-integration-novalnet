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
 * Script: Backend.php
 */

namespace Lightspeed\Model;

use DomainException;
use Zend\Filter\StringTrim;
use Zend\Filter\StripTags;
use Zend\Filter\ToInt;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\InputFilterInterface;
use Zend\Validator\Digits;
use Zend\Validator\EmailAddress;
use Zend\Validator\GreaterThan;
use Zend\Validator\LessThan;
use Zend\Validator\StringLength;
use Zend\Validator\Regex;

class Backend implements InputFilterAwareInterface
{
    public $id;
    public $shop_id;
    public $config_path;
    public $value;
    public $config;

    /**
     * store the current value submitted by the form
     *
     * @param  array $data
     *
     * @return array
     */
    public function exchangeArray(array $data)
    {
        $this->shop_id   = !empty($data['shop_id']) ? $data['shop_id'] : null;
        $this->config_path   = !empty($data['config_path']) ? $data['config_path'] : null;
        $this->value   = !empty($data['value']) ? $data['value'] : null;
        unset($data['shop_id']);
        unset($data['submit']);
        return $data;
    }

    /**
     * store configuration details
     *
     * @param  array $row
     *
     * @return void
     */
    public function storeConfig($row){

        $this->config = $row;
    }

    /**
     * return configuration details as an array
     *
     * @param  void
     *
     * @return array
     */
    public function getArrayCopy()
    {
        return $this->config;
    }

    /**
     * set input filter
     *
     * @param  object $inputFilter
     *
     * @return void
     */
    public function setInputFilter(InputFilterInterface $inputFilter)
    {
        throw new DomainException(sprintf(
            '%s does not allow injection of an alternate input filter',
            __CLASS__
        ));
    }

    /**
     * set vaidation for the fields
     *
     * @param  void
     *
     * @return object
     */
    public function getInputFilter()
    {
        $inputFilter = new InputFilter();
        $inputFilter->add([
            'name' => 'novalnet_sepa_due_date',
            'required' => false,
            'allow_empty' => true,
            'validators' => [
                [
                    'name' => Digits::class,
                ],
                [
                    'name' => GreaterThan::class,
                    'options' => [
                        'min' => 2,
                        'inclusive' => true,
                    ],
                ],
                [
                    'name' => LessThan::class,
                    'options' => [
                        'max' => 14,
                        'inclusive' => true,
                    ],
                ],
                [
                    'name' => StringLength::class,
                    'options' => [
                        'encoding' => 'UTF-8',
                        'min' => 0,
                        'max' => 100,
                    ],
                ],
            ],
        ]);
        $inputFilter->add([
            'name' => 'payment_gateways_list',
            'required' => false,
        ]);
        $inputFilter->add([
            'name' => 'api_key',
            'required' => true,
        ]);
        $inputFilter->add([
            'name' => 'tariff',
            'required' => true,
            'validators' => [
                [
                    'name' => Digits::class,
                ],
                [
                    'name' => StringLength::class,
                    'options' => [
                        'encoding' => 'UTF-8',
                        'min' => 1,
                        'max' => 100,
                    ],
                ],
            ],
        ]);
        $inputFilter->add([
            'name' => 'tariff_val',
            'required' => true,
            'validators' => [
                [
                    'name' => Digits::class,
                ],
                [
                    'name' => StringLength::class,
                    'options' => [
                        'encoding' => 'UTF-8',
                        'min' => 1,
                        'max' => 100,
                    ],
                ],
            ],
        ]);
        $inputFilter->add([
            'name' => 'novalnet_invoice_due_date',
            'required' => false,
            'allow_empty' => true,
            'validators' => [
                [
                    'name' => Digits::class,
                ],
                [
                    'name' => GreaterThan::class,
                    'options' => [
                        'min' => 7,
                        'inclusive' => true,
                    ],
                ],
                [
                    'name' => StringLength::class,
                    'options' => [
                        'encoding' => 'UTF-8',
                        'min' => 0,
                        'max' => 100,
                    ],
                ],
            ],
        ]);
        $inputFilter->add([
            'name' => 'novalnet_barzahlen_due_date',
            'required' => false,
            'allow_empty' => true,
            'validators' => [
                [
                    'name' => Digits::class,
                ],
                [
                    'name' => StringLength::class,
                    'options' => [
                        'encoding' => 'UTF-8',
                        'min' => 0,
                        'max' => 100,
                    ],
                ],
            ],
        ]);
        $inputFilter->add([
            'name' => 'novalnet_sepa_guarantee_minimum_amount',
            'required' => false,
            'allow_empty' => true,
            'validators' => [
                [
                    'name' => Digits::class,
                ],
                [
                    'name' => StringLength::class,
                    'options' => [
                        'encoding' => 'UTF-8',
                        'min' => 0,
                        'max' => 200,
                    ],
                ],
                [
                    'name' => GreaterThan::class,
                    'options' => [
                        'min' => 999,
                        'inclusive' => true,
                    ],
                ],
            ],
        ]);
        $inputFilter->add([
            'name' => 'novalnet_invoice_guarantee_minimum_amount',
            'required' => false,
            'allow_empty' => true,
            'validators' => [
                [
                    'name' => Digits::class,
                ],
                [
                    'name' => StringLength::class,
                    'options' => [
                        'encoding' => 'UTF-8',
                        'min' => 0,
                        'max' => 200,
                    ],
                ],
            ],
        ]);
        $inputFilter->add([
            'name' => 'manual_check_limit',
            'required' => false,
            'allow_empty' => true,
            'validators' => [
                [
                    'name' => Digits::class,
                ],
                [
                    'name' => StringLength::class,
                    'options' => [
                        'encoding' => 'UTF-8',
                        'min' => 0,
                        'max' => 200,
                    ],
                ],
            ],
        ]);
        $inputFilter->add([
            'name' => 'callback_mail_to',
            'required' => false,
            'allow_empty' => true,
            'validators' => [
                [
                    'name' => StringLength::class,
                    'options' => [
                        'encoding' => 'UTF-8',
                        'min' => 0,
                        'max' => 250,
                    ],
                ],
            ],
        ]);
        $inputFilter->add([
            'name' => 'callback_mail_bcc',
            'required' => false,
            'allow_empty' => true,
            'validators' => [
                [
                    'name' => StringLength::class,
                    'options' => [
                        'encoding' => 'UTF-8',
                        'min' => 0,
                        'max' => 250,
                    ],
                ],
            ],
        ]);
        $inputFilter->add([
            'name' => 'gateway',
            'required' => false,
            'allow_empty' => true,
            'validators' => [
                [
                    'name' => Digits::class,
                ],
                [
                    'name' => StringLength::class,
                    'options' => [
                        'encoding' => 'UTF-8',
                        'min' => 0,
                        'max' => 250,
                    ],
                ],
            ],
        ]);
        $inputFilter->add([
            'name' => 'referrer',
            'required' => false,
            'allow_empty' => true,
            'validators' => [
                [
                    'name' => Digits::class,
                ],
                [
                    'name' => StringLength::class,
                    'options' => [
                        'encoding' => 'UTF-8',
                        'min' => 0,
                        'max' => 250,
                    ],
                ],
            ],
        ]);
        return $inputFilter;
    }
}
