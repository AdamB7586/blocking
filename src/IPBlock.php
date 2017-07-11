<?php
/**
 * Checks to see if an IP is block from a list of IP and Ranges in a database
 * @author Adam Binnersley
 * @version 1.0.0
 */
namespace Blocking;

use DBAL\Database;

class IPBlock{
    protected $db;
    protected static $blocked_ip_table = 'blocked_ips';
    protected static $blocked_range_table = 'blocked_ip_range';

    /**
     * Adds a Database instance for the class to use
     * @param Database $db This should be an instance of the database connection
     */
    public function __construct(Database $db) {
        $this->db = $db;
    }
    
    /**
     * Change the default table name where the IP list is located
     * @param string $table This should be the name of the table where the list of IP are located
     * @return $this
     */
    public function setBlockedIPTable($table){
        self::$blocked_ip_table = filter_var($table, FILTER_SANITIZE_STRING);
        return $this;
    }
    
    /**
     * Change the default table name where the IP Range list is located
     * @param string $table This should be the name of the table where the list of IP ranges are located
     * @return $this
     */
    public function setBlockedRangeTable($table){
        self::$blocked_range_table = filter_var($table, FILTER_SANITIZE_STRING);
        return $this;
    }
    
    /**
     * Checks to see if the given IP is Blocked
     * @param string $ip This should be the IP you are checking if it is blocked
     * @return boolean If the IP is listed will return true else will return false
     */
    public function isIPBlocked($ip){
        return $this->db->select(self::$blocked_ip_table, array('ip' => $ip));
    }
    
    /**
     * Checks to see if the given IP is within a blocked range
     * @param string $ip This should be the IP you are checking if it is blocked
     * @return boolean If the IP is within a blocked range will return true else will return false
     */
    public function isIPBlockedRange($ip){
        return $this->db->query("SELECT * FROM `".self::$blocked_range_table."` WHERE `start` <= :ip AND `end` >= :ip LIMIT 1;", array('ip' => $ip));
    }
    
    /**
     * Adds an individual IP to the blocked list
     * @param string $ip This should be the IP address that you are blocking 
     * @return boolean If the IP has been successfully added will return true else return false
     */
    public function addIPtoBlock($ip){
        return $this->db->insert(self::$blocked_ip_table, array('ip' => $ip));
    }
    
    /**
     * Removes an IP address from the blocked list
     * @param string $ip This should be that IP address that you are removing from the blocked list
     * @return boolean If the IP address is successfully removed will return true else will return false
     */
    public function removeIPFromBlock($ip){
        return $this->db->delete(self::$blocked_ip_table, array('ip' => $ip), 1);
    }
    
    /**
     * List all of the IP addresses blocked in the table
     * @return boolean|array An array will be return containing all blocked IP addresses if none exist will return false
     */
    public function listBlockedIPAddresses(){
        return $this->db->selectAll(self::$blocked_ip_table);
    }
    
    /**
     * Adds a IP range to block in the database where any IP between the start and end values should be blocked 
     * @param string $start This should be the start of the range you wish to block e.g. 255.255.255.0
     * @param string $end This should be the end of the range you wish to block e.g. 255.255.255.255
     * @return boolean If the range is successfully added will return true else returns false
     */
    public function addRangetoBlock($start, $end){
        return $this->db->insert(self::$blocked_range_table, array('start' => $start, 'end' => $end));
    }
    
    /**
     * Removes a blocked range from the database
     * @param int|boolean $id If you know the ID of the range you wish to remove set this else set to false
     * @param NULL|string $start If you don't know the ID of the range you want to unblock enter the IP at the start of the IP range
     * @param NULL|string $end If you don't know the ID of the range you want to unblock enter the IP at the end of the IP range
     * @return boolean If the range is removed from the database will return true else return false
     */
    public function removeRangeFromBlock($id, $start = NULL, $end = NULL){
        if(is_numeric($id)){
            $where = array('id' => $id);
        }
        else{
            $where = array('start' => $start, 'end' => $end);
        }
        return $this->db->delete(self::$blocked_range_table, $where, 1);
    }
    
    /**
     * List all of the IP ranges blocked in the table
     * @return boolean|array An array will be return containing all blocked IP address ranges if none exist will return false
     */
    public function listBlockedIPRanges(){
        return $this->db->selectAll(self::$blocked_range_table);
    }

    /**
     * Gets and return the most likely IP address for the user
     * @return string the users IP will be returned
     */
    public function getUserIP(){
        if(isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] != ''){
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        else{
            return $_SERVER['REMOTE_ADDR'];
        }
    }
}
