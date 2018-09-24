<?php
/**
 * Checks to see if an IP is block from a list of IPs, Ranges or ISO countries in a database
 * @author Adam Binnersley
 */
namespace Blocking;

use DBAL\Database;
use GeoIp2\Database\Reader;

class IPBlock{
    protected $db;
    protected $geoIP;

    protected $blocked_ip_table = 'blocked_ips';
    protected $blocked_range_table = 'blocked_ip_range';
    protected $blocked_iso_countries = 'blocked_iso_countries';

    /**
     * Adds a Database instance for the class to use
     * @param Database $db This should be an instance of the database connection
     */
    public function __construct(Database $db) {
        $this->db = $db;
        $this->geoIP = new Reader(dirname(__FILE__).DIRECTORY_SEPARATOR.'Geo-Country.mmdb');
    }
    
    /**
     * Change the default table name where the IP list is located
     * @param string $table This should be the name of the table where the list of IP are located
     * @return $this
     */
    public function setBlockedIPTable($table){
        if(is_string($table)){
            $this->blocked_ip_table = filter_var($table, FILTER_SANITIZE_STRING);
        }
        return $this;
    }
    
    /**
     * Returns the blocked IP's database table
     * @return string
     */
    public function getBlockedIPTable(){
        return $this->blocked_ip_table;
    }

    /**
     * Change the default table name where the IP Range list is located
     * @param string $table This should be the name of the table where the list of IP ranges are located
     * @return $this
     */
    public function setBlockedRangeTable($table){
        if(is_string($table)){
            $this->blocked_range_table = filter_var($table, FILTER_SANITIZE_STRING);
        }
        return $this;
    }
    
    /**
     * Returns the blocked IP range database table
     * @return string
     */
    public function getBlockedRangeTable(){
        return $this->blocked_range_table;
    }
    
    /**
     * Change the default table name where the ISO Country list is located
     * @param string $table This should be that table name where the ISO list is located
     * @return $this
     */
    public function setBlockedISOTable($table){
        if(is_string($table)){
            $this->blocked_iso_countries = filter_var($table, FILTER_SANITIZE_STRING);
        }
        return $this;
    }
    
    /**
     * Returns the blocked ISO database table
     * @return string
     */
    public function getBlockedISOTable(){
        return $this->blocked_iso_countries;
    }

    /**
     * Checks to see if the given IP is Blocked by listing or range
     * @param string $ip This should be the IP you are checking if it is blocked
     * @return boolean If the IP is listed will return true else will return false
     */
    public function isIPBlocked($ip){
        return ($this->isIPBlockedList($ip) || $this->isIPBlockedRange($ip) || $this->isISOBlocked($ip));
    }
    
    /**
     * Checks to see if the given IP is Blocked
     * @param string $ip This should be the IP you are checking if it is blocked
     * @return boolean If the IP is listed will return true else will return false
     */
    public function isIPBlockedList($ip){
        return $this->db->select($this->getBlockedIPTable(), ['ip' => $ip]);
    }
    
    /**
     * Checks to see if the given IP is within a blocked range
     * @param string $ip This should be the IP you are checking if it is blocked
     * @return boolean If the IP is within a blocked range will return true else will return false
     */
    public function isIPBlockedRange($ip){
        return $this->db->select($this->getBlockedRangeTable(), ['ip_start' => ['>=', $ip], 'ip_end' => ['<=', $ip]]);
    }
    
    /**
     * Check to see if the ISO county of the IP is blocked 
     * @param string $ip This should be the IP you are checking if it is blocked
     * @return boolean If the IP country is blocked will return true else returns false
     */
    public function isISOBlocked($ip){
        return $this->db->select($this->getBlockedISOTable(), ['iso' => $this->getIPCountryISO($ip)]);
    }
    
    /**
     * Adds an individual IP to the blocked list
     * @param string $ip This should be the IP address that you are blocking 
     * @return boolean If the IP has been successfully added will return true else return false
     */
    public function addIPtoBlock($ip){
        return $this->db->insert($this->getBlockedIPTable(), ['ip' => $ip]);
    }
    
    /**
     * Removes an IP address from the blocked list
     * @param string $ip This should be that IP address that you are removing from the blocked list
     * @return boolean If the IP address is successfully removed will return true else will return false
     */
    public function removeIPFromBlock($ip){
        return $this->db->delete($this->getBlockedIPTable(), ['ip' => $ip], 1);
    }
    
    /**
     * List all of the IP addresses blocked in the table
     * @return boolean|array An array will be return containing all blocked IP addresses if none exist will return false
     */
    public function listBlockedIPAddresses(){
        return $this->db->selectAll($this->getBlockedIPTable());
    }
    
    /**
     * Adds a IP range to block in the database where any IP between the start and end values should be blocked 
     * @param string $start This should be the start of the range you wish to block e.g. 255.255.255.0
     * @param string $end This should be the end of the range you wish to block e.g. 255.255.255.255
     * @return boolean If the range is successfully added will return true else returns false
     */
    public function addRangetoBlock($start, $end){
        return $this->db->insert($this->getBlockedRangeTable(), ['ip_start' => $start, 'ip_end' => $end]);
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
            $where = ['id' => $id];
        }
        else{
            $where = ['ip_start' => $start, 'ip_end' => $end];
        }
        return $this->db->delete($this->getBlockedRangeTable(), $where, 1);
    }
    
    /**
     * List all of the IP ranges blocked in the table
     * @return boolean|array An array will be return containing all blocked IP address ranges if none exist will return false
     */
    public function listBlockedIPRanges(){
        return $this->db->selectAll($this->getBlockedRangeTable());
    }
    
    /**
     * Returns the ISO county of an IP address
     * @param string $ip This should be the IP address you are checking
     * @return array|false 
     */
    public function getIPCountryISO($ip){
        try{
            $search = $this->geoIP->country($ip);
            if(is_object($search)){
                return $search->country->isoCode;
            }
        }
        catch(\Exception $e){
            // Cache any IP that arn't found in the database
        }
        return false;
    }
    
    /**
     * Add an ISO country to the blocked list
     * @param string $iso This should be the ISO county
     * @return boolean If inserted successfully will return true else will return false
     */
    public function addISOCountryBlock($iso){
        if(!empty(trim($iso)) && is_string($iso)){
            return $this->db->insert($this->getBlockedISOTable(), ['iso' => trim($iso)]);
        }
        return false;
    }
    
    /**
     * Remove an ISO country from the blocked list
     * @param string $iso This should be the ISO county
     * @return boolean If deleted will return true else will return false
     */
    public function removeISOCountryBlock($iso){
        if(!empty(trim($iso)) && is_string($iso)){
            return $this->db->delete($this->getBlockedISOTable(), ['iso' => trim($iso)]);
        }
        return false;
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
