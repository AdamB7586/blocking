<?php

use PHPUnit\Framework\TestCase;
use DBAL\Database;
use Blocking\IPBlock;

class IPBlockTest extends TestCase{
    protected $db;
    protected $ipblock;
    
    protected function setUp() {
        $this->db = new Database($GLOBALS['HOSTNAME'], $GLOBALS['USERNAME'], $GLOBALS['PASSWORD'], $GLOBALS['DATABASE']);
        if(!$this->db->isConnected()){
            $this->markTestSkipped(
                'No local database connection is available'
            );
        }
        $this->db->query(file_get_contents(dirname(dirname(__FILE__)).'/files/database/database.sql'));
        $this->ipblock = new IPBlock($this->db);
        $this->db->truncate($this->ipblock->getBlockedIPTable());
        $this->db->truncate($this->ipblock->getBlockedRangeTable());
    }
    
    protected function tearDown() {
        $this->db = null;
        $this->ipblock = null;
    }
    
    /**
     * @covers Blocking\IPBlock::__construct
     * @covers Blocking\IPBlock::setBlockedIPTable
     * @covers Blocking\IPBlock::getBlockedIPTable
     * @covers Blocking\IPBlock::setBlockedRangeTable
     * @covers Blocking\IPBlock::getBlockedRangeTable
     */
    public function testSetTables(){
        $this->assertEquals('blocked_ips', $this->ipblock->getBlockedIPTable());
        $this->assertObjectHasAttribute('db', $this->ipblock->setBlockedIPTable(false));
        $this->assertEquals('blocked_ips', $this->ipblock->getBlockedIPTable());
        $this->assertObjectHasAttribute('db', $this->ipblock->setBlockedIPTable(-1));
        $this->assertEquals('blocked_ips', $this->ipblock->getBlockedIPTable());
        $this->assertObjectHasAttribute('db', $this->ipblock->setBlockedIPTable('blocked_table'));
        $this->assertNotEquals('blocked_ips', $this->ipblock->getBlockedIPTable());
        $this->assertEquals('blocked_table', $this->ipblock->getBlockedIPTable());
        
        $this->assertEquals('blocked_ip_range', $this->ipblock->getBlockedRangeTable());
        $this->assertObjectHasAttribute('db', $this->ipblock->setBlockedRangeTable('blocked_range'));
        $this->assertNotEquals('blocked_ip_range', $this->ipblock->getBlockedRangeTable());
        $this->assertEquals('blocked_range', $this->ipblock->getBlockedRangeTable());
    }
    
    
}
