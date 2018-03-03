<?php

use PHPUnit\Framework\TestCase;
use DBAL\Database;
use Blocking\BannedWords;

class BannedWordsTest extends TestCase{
    protected $db;
    protected $bannedwords;

    protected function setUp() {
        $this->db = new Database($GLOBALS['HOSTNAME'], $GLOBALS['USERNAME'], $GLOBALS['PASSWORD'], $GLOBALS['DATABASE']);
        if(!$this->db->isConnected()){
            $this->markTestSkipped(
                'No local database connection is available'
            );
        }
        $this->db->query(file_get_contents(dirname(dirname(__FILE__)).'/files/database/database.sql'));
        $this->bannedwords = new BannedWords($this->db);
        $this->db->truncate($this->bannedwords->getBannedWordsTable());
    }
    
    protected function tearDown() {
        $this->db = null;
        $this->bannedwords = null;
    }
    
    /**
     * @covers Blocking\BannedWords::__construct
     * @covers Blocking\BannedWords::setBannedWordsTable
     * @covers Blocking\BannedWords::getBannedWordsTable
     */
    public function testSetTable(){
        $this->assertEquals('blocked_words', $this->bannedwords->getBannedWordsTable());
        $this->assertObjectHasAttribute('db', $this->bannedwords->setBannedWordsTable(false));
        $this->assertEquals('blocked_words', $this->bannedwords->getBannedWordsTable());
        $this->assertObjectHasAttribute('db', $this->bannedwords->setBannedWordsTable(103));
        $this->assertEquals('blocked_words', $this->bannedwords->getBannedWordsTable());
        $this->assertObjectHasAttribute('db', $this->bannedwords->setBannedWordsTable('my_words_table'));
        $this->assertNotContains('blocked_words', $this->bannedwords->getBannedWordsTable());
        $this->assertEquals('my_words_table', $this->bannedwords->getBannedWordsTable());
    }
    
    /**
     * @covers Blocking\BannedWords::__construct
     * @covers Blocking\BannedWords::addBlockedWord
     * @covers Blocking\BannedWords::getBannedWordsTable
     */
    public function testAddBannedWord(){
        $this->assertTrue($this->bannedwords->addBlockedWord('improve ranking'));
        $this->assertFalse($this->bannedwords->addBlockedWord('improve ranking')); // Fail as same as before
        $this->assertFalse($this->bannedwords->addBlockedWord('imPRove RankIng')); // Fail as same but different case
    }
}
