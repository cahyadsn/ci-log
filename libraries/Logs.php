<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Class Logs.php
 * Handle Logging data
 * @package libraries
 * @author Cahya DSN
 * @version 1.0.0
 * @date_create 29/12/2017
 * @date_update 08/01/2018
**/
class Logs
{
    private $_store_in;
    private $ci;
    public function __construct()
    {
        $this->ci = & get_instance();
        $this->ci->load->config('log', true);
        $this->_store_in = $this->ci->config->item('store_in', 'log');
        $this->_set_directory();
        $this->_verify_settings();
    }

    /**
     * Log something
     *
     * @param string $message
     * @param integer $code
     * @param integer $user_id
     */
    public function log($message, $code = 0, $user_id = 0)
    {
        $session_user_id = $this->ci->config->item('session_user_id','log');
        if(($user_id==0) && !empty($session_user_id)) {
            $user_id = isset($_SESSION[$session_user_id]) ? $_SESSION[$session_user_id] : '0';
        }
        if($this->_set_message($message,$user_id,$code)) {
            return TRUE;
        } else {
            show_error('That log... you must pop it... or repair the library...');
        }
        return FALSE;
    }

    /**
     * Delete logs
     *
     * @param integer $user_id
     * @param string $date
     */
    public function delete_log($user_id = NULL, $date = NULL)
    {
        if($this->_delete_logs($user_id, $date)) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    /**
     * Retrieve something
     *
     * @param integer $user_id
     * @param integer $code
     * @param string $date
     * @param string $order_by
     * @param string $limit
     */
    public function get_log($user_id = NULL, $code = NULL, $date = NULL, $order_by = NULL, $limit = NULL)
    {
        return $this->_get_messages($user_id, $code, $date, $order_by, $limit);
    }

    /**
     * Set Message
     *
     * @param string $message
     * @param integer $user_id
     * @param integer $code
     */
    private function _set_message($message,$user_id,$code)
    {
        if ($this->_store_in == 'database') {
            $date_time = date('Y-m-d H:i:s');
            $insert_data = array(
                'user_id' => $user_id,
                'date_time' => $date_time,
                'code' => $code,
                'ip_addess'=> $this->get_user_ip(),
                'message' => $message
            );
            if($this->ci->log_model->set_message($insert_data)) {
                return TRUE;
            }
        } else {
            $ip_address= $this->get_user_ip();
            $date = date('Y-m-d');
            $date_time = date('Y-m-d H:i:s');
            $file = $this->_store_in.'/log-' . $user_id . '-' . $date . '.php';
            $log_message = $date_time . ' *-* ' . $code . ' *-* ' . $ip_address '. *-* ' . $message . "\r\n";
            if (!file_exists($file)) {
                $log_message = "<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>\r\n\r\n" . $log_message;
            }
            $log = fopen($file, "a");
            if (fwrite($log, $log_message)) {
                fclose($log);
                return TRUE;
            } else {
                show_error('Couldn\'t write on the file');
            }
        }
        return FALSE;
    }

    /**
     * Get Messages
     *
     * @param integer $user_id
     * @param integer $code
     * @param string $date
     * @param string $order_by
     * @param string $limit
     */
    private function _get_messages($user_id = NULL, $code = NULL, $date = NULL, $order_by = NULL, $limit = NULL)
    {
        if($this->_store_in == 'database') {
            $where = array();
            if (isset($user_id)) $where['user_id'] = $user_id;
            if (isset($code)) $where['code'] = $code;
            if (isset($date)) {
                $where['date_time >='] = $date.' 00:00:00';
                $where['date_time <='] = $date.' 23:59:59';
            }
            if (!isset($order_by)) $order_by = 'date_time DESC';
            return $this->ci->log_model->get_messages($where, $order_by, $limit);
        } else {
            $user_id = (isset($user_id)) ? $user_id : '*';
            $date = (isset($date)) ? $date : '*';
            $files = $this->_store_in.'/log-' . $user_id . '-' . $date . '.php';
            $messages = array();
            foreach (glob($files) as $filename) {
                $log = file_get_contents($filename);
                $lines = explode("\r\n",$log);
                for ($k=2; $k<count($lines); $k++) {
                    if(strlen($lines[$k])>0) {
                        $line = explode('*-*',$lines[$k]);
                        $date_time = $line[0];
                        $code = $line[1];
                        $ip_address = $line[2];
                        $message = $line[3];
                        $messages[] = array('user_id'=>$user_id,'date_time'=>$date_time,'code'=>$code,'ip'=>$ip_address,'message'=>$message);
                    }
                }
            }
            return json_decode(json_encode($messages));
        }
    }

    /**
     * Delete Logs
     *
     * @param integer $user_id
     * @param string $date
     */
    private function _delete_logs($user_id = NULL,$date = NULL)
    {
        $where = array();
        if ($this->_store_in == 'database') {
            if (isset($user_id)) $where['user_id'] = $user_id;
            if (isset($date)) {
                $where['date_time >='] = $date.' 00:00:00';
                $where['date_time <='] = $date.' 23:59:59';
            }
            if($this->ci->log_model->delete_messages($where)) {
                return TRUE;
            }
        } else {
            $user_id = (isset($user_id)) ? $user_id : '*';
            $date = (isset($date)) ? $date : '*';
            $files = $this->_store_in.'/log-' . $user_id . '-' . $date . '.php';
            $deleted = 0;
            foreach (glob($files) as $filename) {
                if(unlink($filename)) {
                    $deleted++;
                }
            }
            return $deleted;
        }
    }

    /**
     * Set Directory
     *
     */
    private function _set_directory()
    {
        if($this->_store_in!=='database') {
            $this->_store_in = (strlen($this->_store_in) == 0) ? APPPATH . 'logs' : APPPATH.trim($this->_store_in,'/\\');
        }
    }

    /**
     * Verify Settings
     *
     */
    private function _verify_settings()
    {
        if($this->_store_in == 'database') {
            $this->ci->load->model('log_model');
        } else {
            if(!is_really_writable($this->_store_in)) {
                show_error('The Log: The directory '.$this->_store_in.' is not writable.');
            }
        }
    }
    
    /**
    * Get User IP
    *
    */
    function get_user_ip()
    {
        $ip='';
        if (function_exists('file_get_contents')) {
            $ipify   = @file_get_contents('https://api.ipify.org');
            if (filter_var($ipify, FILTER_VALIDATE_IP)) {
                $ip = $ipify;
            }
        } else {
            $client  = @$_SERVER['HTTP_CLIENT_IP'];
            $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
            $remote  = $_SERVER['REMOTE_ADDR'];
            if (filter_var($client, FILTER_VALIDATE_IP)) {
                $ip = $client;
            } elseif(filter_var($forward, FILTER_VALIDATE_IP)) {
                $ip = $forward;
            } else {
                $ip = $remote;
            }
        }
        return $ip;
    }
}
