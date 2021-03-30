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
 * Script: Lightspeed.php
 */

namespace Lightspeed\Model;

use DomainException;

class Lightspeed
{
    public $shop_id;
    public $config_path;
    public $value;

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
}
