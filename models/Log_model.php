<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Class Log_model.php
 * Handle Logging model data
 * @package models
 * @author Cahya DSN
 * @version 1.0.0
 * @date_create 29/12/2017
**/
class Log_model extends CI_Model
{
    private $_log_table;
    public function __construct()
    {
        parent::__construct();
        $this->load->config('log', TRUE);
        $this->_log_table = $this->config->item('table_name','log');
        if(empty($this->_log_table)) $this->_log_table = 'logs';
        $this->_verify_table();
    }

    /**
     * Verify Table
     *
     */
    private function _verify_table()
    {
        if(!$this->db->table_exists($this->_log_table)) {
            show_error('That log won\'t squeal a thing because he has no database table set up...');
        }
    }

    /**
     * Set Message
     *
     * @param array $insert_data
     */
    public function set_message($insert_data)
    {
        if($this->db->insert($this->_log_table,$insert_data)) {
            return TRUE;
        } else {
            show_error('That log... you must pop it... or repair the table...');
        }
        return FALSE;
    }

    /**
     * Get Messages
     *
     * @param string/array $where
     * @param string $order_by
     * @param string/array $limit
     */
    public function get_messages($where = NULL, $order_by = NULL, $limit = NULL)
    {
        if(isset($where) && !empty($where)) $this->db->where($where);
        if(isset($order_by)) $this->db->order_by($order_by);
        if(isset($limit)) {
            if(is_array($limit)) {
                $this->db->limit($limit[0],$limit[1]);
            } else {
                $this->db->limit($limit);
            }
        }
        $query = $this->db->get($this->_log_table);
        if($query->num_rows()>0) {
            return $query->result();
        }
        return FALSE;
    }

    /**
    * Delete Messages
    *
    * @param string/array $where
    */
    public function delete_messages($where=NULL)
    {
        if(isset($where) && !empty($where)) $this->db->where($where);
        $this->db->delete($this->_log_table);
        return TRUE;
    }
}
