<?php

/**
 * Checks the database to see if the text you are giving the object is listed a a blocked word or phrase
 *
 * @author Adam Binnersley
 * @version 1.0.0
 */
namespace Blocking;

use DBAL\Database;

class BannedWords{
    
    protected $db;

    protected static $banned_words_table = 'blocked_words';
    protected $blockedWords;
    
    /**
     * Initiates the Database Instance
     * @param Database $db This should be an instance of he database
     */
    public function __construct(Database $db) {
        $this->db = $db;
    }
    
    /**
     * Sets the name of the table where the list of banned words should be located
     * @param string $table This should be the table name
     * @return $this BannedWords
     */
    public function setBannedWordTable($table){
        self::$banned_words_table = filter_var($table, FILTER_SANITIZE_STRING);
        return $this;
    }
    
    /**
     * Checks to see if the string given contains any of the banned words
     * @param string $text This should be the string of text that you wish to check for the banned words
     * @return boolean if the test contain a banned word will return true else return false
     */
    public function containsBlockedWord($text){
        if(!is_array($this->blockedWords)){$this->getBlockedWords();}
        foreach($this->blockedWords as $a => $words){
            if(strpos(strtolower($text), strtolower($words['word'])) !== false){return true;}
        }
        return false;
    }
    
    /**
     * Adds a word to the blocked list used to detect spam
     * @param string $text This should be the word or words you wish to block/use to detect spam
     * @return boolean Returns true if added else returns false
     */
    public function addBlockedWord($text){
        if(!$this->db->count(self::$banned_words_table, array('word' => strtolower($text)))){
            return $this->db->insert(self::$banned_words_table, array('word' => strtolower($text)));
        }
        return false;
    }
    
    /**
     * Lists all of the blocked words within the database
     * @param string $search If you want to search for any words within the blocked list enter a string here
     * @return array|boolean If blocked words exist returns array else returns false
     */
    public function getBlockedWords($search = ''){
        if(!empty($search)){
            $where = array();
            $where['word'] = array('LIKE', '%'.$search.'%');
        }
        $this->blockedWords = $this->db->selectAll(self::$banned_words_table, $where);
        return $this->blockedWords;
    }
    
    /**
     * Removes a blocked word of phrase from the database
     * @param int $id This should be the unique ID given to the blocked word or phrase
     * @return boolean If the item is successfully removed from the database will return true else return false
     */
    public function removeBlockedWords($id){
        return $this->db->delete(self::$banned_words_table, array('id' => intval($id)));
    }
}
