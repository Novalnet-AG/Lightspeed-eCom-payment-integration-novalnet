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
 * Script: LightspeedTable.php
 */

namespace Lightspeed\Model;

use RuntimeException;
use Zend\Db\TableGateway\TableGatewayInterface;
use Lightspeed\Model\Lightspeed;
use Zend\Db\Sql\Sql;
use Zend\Db\Adapter\Adapter;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\TableGateway\TableGateway;
use Zend\Db\TableGateway\Feature\RowGatewayFeature;
use Zend\Db\RowGateway\RowGateway;
use Zend\Db\Sql\Select;

class LightspeedTable
{

    private $tableGateway;

    public function __construct($tableGateway)
    {
        $this->tableGateway = $tableGateway;
    }

    /**
     * get merchant data from novalnet database
     *
     * @param  string $table
     * @param  string $shop_id
     *
     * @return none
     */
    public function fetchAll($table, $shop_id)
    {
        $sql = new Sql($this->tableGateway);
        $select = $sql->select();
        $select->from($table);
        $select->where(['shop_id' => $shop_id]);
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        if(1 === $result->count())  {
            $array = $result->current();
        } else {
            foreach($result as $row) {
                $array[$row['config_path']] = $row['value'];
            }
        }
        return $array;
    }

    /**
     * get data from novalnet database
     *
     * @param  string $shop_id
     * @param  string $data
     * @param  string $table
     *
     * @return none
     */
    public function fetch_value($condition, $table, $column){
        $sql    = new Sql($this->tableGateway);
        $select = $sql->select();
        $select->from($table);
        $select->where($condition);
        $select->columns($column);
        $statement = $sql->prepareStatementForSqlObject($select);
        $results = $statement->execute();
        $array = $results->current();
        return $array['value'];
    }
    
	/**
     * get order data from novalnet database
     *
     * @param  string $field
     * @param  string $table
     * @param  string $shop_id
     * @param  string $tid
     *
     * @return none
     */
    public function get_value($field, $table, $shop_id, $tid){

        $sql    = new Sql($this->tableGateway);
        $select = $sql->select();
        $select->from($table);
        $select->where(['shop_id' => $shop_id, 'tid' => $tid]);
        $select->columns([$field]);
        $statement = $sql->prepareStatementForSqlObject($select);
        $results = $statement->execute();
        $array = $results->current();
        return $array[$field];
    }

    /**
     * insert data in novalnet database
     *
     * @param  array $data
     * @param  string $table
     *
     * @return none
     */
    public function insert($data, $table)
    {
		$resultSetPrototype = new ResultSet();
		$resultSetPrototype->setArrayObjectPrototype(new Lightspeed());
		$row = new TableGateway($table, $this->tableGateway, null, $resultSetPrototype);
		if($table != 'novalnet_lightspeed_installed_shop') {
			$row->insert($data);
		} else {
			$sql    = new Sql($this->tableGateway);
			$select = $sql->select();
			$select->from($table);
			$select->columns(['id']);
			$select->where(['shop_id' => $data['shop_id']]);
			$statement = $sql->prepareStatementForSqlObject($select);
			$results = $statement->execute();
			$array = $results->current();
			if(empty($array)){
				$row->insert($data);
				return true;
			}else{
				$row->update($data, ['shop_id' => $data['shop_id']]);
				return false;
			}
		}
    }

    /**
     * get data from novalnet database
     *
     * @param  string $order_id
     * @param  string $shop_id
     * @param  string $table
     *
     * @return array
     */
    public function get_status($order_id, $shop_id, $table){

        $sql    = new Sql($this->tableGateway);
        $select = $sql->select();
        $select->from($table);
        $select->where(['shop_id' => $shop_id, 'order_id' => $order_id]);
        $select->columns(['gateway_status', 'payment_name']);
        $statement = $sql->prepareStatementForSqlObject($select);
        $results = $statement->execute();
        $array = $results->current();
        return $array;
    }
    
    
    /**
     * get paid amount of the order
     *
     * @param  string $table
     * @param  string $shop_id
     * @param  string $order_id
     *
     * @return none
     */
    public function get_paid_amount($table, $shop_id, $order_id){

        $sql    = new Sql($this->tableGateway);
        $array['amount'] = 0;
        $select = $sql->select();
        $select->from($table);
        $select->where(['shop_id' => $shop_id, 'order_id' => $order_id]);
        $select->columns(['amount']);
        $statement = $sql->prepareStatementForSqlObject($select);
        $results = $statement->execute();
        if(1 === $results->count())  {
            $array = $results->current();
        } else {

            foreach($results as $row) {

                $array['amount'] = $array['amount'] + $row['amount'];
            }
        }
        return $array;
    }
    
    /**
     * update data in novalnet database
     *
     * @param  string $table
     * @param  array $data
     * @param  string $condition
     *
     * @return none
     */
    public function update($table, $data, $condition)
    {
		$resultSetPrototype = new ResultSet();
		$resultSetPrototype->setArrayObjectPrototype(new Lightspeed());
        $row = new TableGateway($table, $this->tableGateway, null, $resultSetPrototype);
        $row->update($data, $condition);
    }
    
    /**
     * save backend details in novalnet configuration table
     *
     * @param  array $data
     * @param  object $backend
     *
     * @return none
     */
    public function saveBackend($data, $Backend)
    {
		
        $row = new TableGateway('novalnet_lightspeed_merchant_configuration', $this->tableGateway, null, $this->resultSetPrototype);
        $enabled_payment = $data['payment_gateways_list'];
        $data['payment_gateways_list'] =json_encode($enabled_payment);

        $accepted_config = array('gateway', 'api_key', 'vendor', 'auth_code', 'product', 'tariff', 'access_key', 'tariff_val', 'test_mode', 'payment_action', 'manual_check_limit', 'referrer', 'confirmation_order_status', 'cancellation_order_status', 'deactivate_ip_check', 'callback_mail_notification', 'callback_mail_to', 'callback_mail_bcc', 'callback_url', 'pending_order_status', 'payment_confirmation_order_status', 'callback_order_status', 'novalnet_invoice_due_date', 'novalnet_barzahlen_due_date', 'novalnet_sepa_due_date', 'payment_gateways_list', 'novalnet_sepa_enable_guarantee', 'novalnet_invoice_enable_guarantee', 'novalnet_invoice_guarantee_minimum_amount', 'novalnet_sepa_guarantee_minimum_amount', 'novalnet_sepa_enable_force_guarantee', 'novalnet_invoice_enable_force_guarantee', 'novalnet_cc_enforce_3d');

        $configuration = array();

        foreach($accepted_config as $k => $v) {
            $configuration[$v] = $data[$v];
        }
        foreach($configuration as $k => $v){
            $config = [
                'shop_id'      => $Backend->shop_id,
                'config_path'  => $k,
                'value'        => $v,
            ];

            $sql    = new Sql($this->tableGateway);
            $select = $sql->select();
            $select->from('novalnet_lightspeed_merchant_configuration');
            $select->columns(['id']);
            $select->where(['shop_id' => $Backend->shop_id, 'config_path' => $config['config_path']]);
            $statement = $sql->prepareStatementForSqlObject($select);
            $results = $statement->execute();
            $array = $results->current();

            if(empty($array)){
                $row->insert($config);
            }else{
                $row->update($config, ['shop_id' => $Backend->shop_id, 'config_path' => $config['config_path']]);
            }
        }
    }
    
    /**
     * get merchant configuration details from novalnet merchant configuration table
     *
     * @param  string $shop_id
     * @param  object $backend
     *
     * @return none
     */
    public function getConfig($shop_id, $backend)
    {
        $row = array();
        $row['payment_gateways_list'] = '';
        $sql    = new Sql($this->tableGateway);
        $select = $sql->select();
        $select->from('novalnet_lightspeed_merchant_configuration');
        $select->where(['shop_id' => $shop_id]);
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        if(!empty($result)){
            foreach($result as $v){
            $row[$v['config_path']] = $v['value'];
            }
        }

        $enabled_payment = $row['payment_gateways_list'];
        $row['payment_gateways_list'] =json_decode($enabled_payment);
        $object = (object) $row;
        $backend->storeConfig($row);
        return $object;
    }
	
	/**
     * delete the data from novalnet database
     *
     * @param  string $table
     * @param  string $shop_id
     *
     * @return none
     */
    public function delete($table, $shop_id)
    {
        $row = new TableGateway($table, $this->tableGateway, null, $this->resultSetPrototype);
        $row->delete(['shop_id' => (int) $shop_id]);
    }
}
