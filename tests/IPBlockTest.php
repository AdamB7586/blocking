<?php

namespace Blocking\Tests;

use PHPUnit\Framework\TestCase;
use DBAL\Database;
use Blocking\IPBlock;

class IPBlockTest extends TestCase
{
    protected $db;
    protected $ipblock;
    
    protected function setUp(): void
    {
        $this->db = new Database($GLOBALS['HOSTNAME'], $GLOBALS['USERNAME'], $GLOBALS['PASSWORD'], $GLOBALS['DATABASE']);
        if (!$this->db->isConnected()) {
            $this->markTestSkipped(
                'No local database connection is available'
            );
        }
        $this->db->query(file_get_contents(dirname(dirname(__FILE__)).'/files/database/database.sql'));
        $this->ipblock = new IPBlock($this->db);
        $this->db->truncate($this->ipblock->getBlockedIPTable());
        $this->db->truncate($this->ipblock->getBlockedRangeTable());
        $this->db->truncate($this->ipblock->getBlockedISOTable());
    }
    
    protected function tearDown(): void
    {
        $this->db = null;
        $this->ipblock = null;
    }
    
    /**
     * @covers Blocking\IPBlock::__construct
     * @covers Blocking\IPBlock::setBlockedIPTable
     * @covers Blocking\IPBlock::getBlockedIPTable
     * @covers Blocking\IPBlock::setBlockedRangeTable
     * @covers Blocking\IPBlock::getBlockedRangeTable
     * @covers Blocking\IPBlock::setBlockedISOTable
     * @covers Blocking\IPBlock::getBlockedISOTable
     */
    public function testSetTables()
    {
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
        $this->assertObjectHasAttribute('db', $this->ipblock->setBlockedRangeTable('blocked_ip_range'));
        
        $this->assertEquals('blocked_iso_countries', $this->ipblock->getBlockedISOTable());
        $this->assertObjectHasAttribute('db', $this->ipblock->setBlockedISOTable('blocked_iso'));
        $this->assertNotEquals('blocked_iso_countries', $this->ipblock->getBlockedISOTable());
        $this->assertEquals('blocked_iso', $this->ipblock->getBlockedISOTable());
    }
    
    /**
     * @covers Blocking\IPBlock::__construct
     * @covers Blocking\IPBlock::getBlockedISOTable
     * @covers Blocking\IPBlock::addIPtoBlock
     * @covers Blocking\IPBlock::addIPtoBlock
     * @covers Blocking\IPBlock::getBlockedIPTable
     * @covers Blocking\IPBlock::getBlockedRangeTable
     * @covers Blocking\IPBlock::isIPBlocked
     * @covers Blocking\IPBlock::isIPBlockedList
     * @covers Blocking\IPBlock::removeIPFromBlock
     * @covers Blocking\IPBlock::getIPCountryISO
     * @covers Blocking\IPBlock::isIPBlockedRange
     * @covers Blocking\IPBlock::isISOBlocked
     * @covers Blocking\IPBlock::listBlockedIPAddresses
     */
    public function testBlockIP()
    {
        $this->assertFalse($this->ipblock->isIPBlockedList('9.9.9.9'));
        $this->assertTrue($this->ipblock->addIPtoBlock('9.9.9.9'));
        $this->assertFalse($this->ipblock->addIPtoBlock('9.9.9.9'));
        $this->assertTrue($this->ipblock->isIPBlockedList('9.9.9.9'));
        $this->assertTrue($this->ipblock->isIPBlocked('9.9.9.9'));
        $blockedAddresses = $this->ipblock->listBlockedIPAddresses();
        $this->assertArrayHasKey('ip', $blockedAddresses[0]);
        $this->assertEquals('9.9.9.9', $blockedAddresses[0]['ip']);
        $this->assertFalse($this->ipblock->removeIPFromBlock('8.8.8.8'));
        $this->assertTrue($this->ipblock->removeIPFromBlock('9.9.9.9'));
        $this->assertFalse($this->ipblock->isIPBlocked('9.9.9.9'));
        $this->assertFalse($this->ipblock->addIPtoBlock('hello'));
        $this->assertFalse($this->ipblock->addIPtoBlock(69));
        $this->assertFalse($this->ipblock->addIPtoBlock('300.300.300.300'));
    }
    
    /**
     * @covers Blocking\IPBlock::__construct
     * @covers Blocking\IPBlock::getBlockedIPTable
     * @covers Blocking\IPBlock::getBlockedISOTable
     * @covers Blocking\IPBlock::getBlockedRangeTable
     * @covers Blocking\IPBlock::listBlockedIPRanges
     * @covers Blocking\IPBlock::getIPCountryISO
     * @covers Blocking\IPBlock::isIPBlocked
     * @covers Blocking\IPBlock::isIPBlockedList
     * @covers Blocking\IPBlock::isIPBlockedRange
     * @covers Blocking\IPBlock::isISOBlocked
     * @covers Blocking\IPBlock::addRangetoBlock
     * @covers Blocking\IPBlock::removeRangeFromBlock
     */
    public function testBlockRange()
    {
        $this->assertFalse($this->ipblock->listBlockedIPRanges());
        $this->assertFalse($this->ipblock->isIPBlocked('9.9.9.9'));
        $this->assertTrue($this->ipblock->addRangetoBlock('9.9.9.0', '9.9.9.255'));
        $this->assertTrue($this->ipblock->isIPBlocked('9.9.9.9'));
        $this->assertFalse($this->ipblock->addRangetoBlock('9.9.9.0', '9.9.9.255'));
        $this->assertFalse($this->ipblock->addRangetoBlock('355.9.9.0', '355.9.9.255'));
        $this->assertFalse($this->ipblock->addRangetoBlock('Test', 'Sample'));
        $this->assertFalse($this->ipblock->addRangetoBlock(56, 99));
        $this->assertFalse($this->ipblock->removeRangeFromBlock(2));
        $this->assertTrue($this->ipblock->removeRangeFromBlock(1));
        $this->assertTrue($this->ipblock->addRangetoBlock('9.9.9.0', '9.9.9.255'));
        $range = $this->ipblock->listBlockedIPRanges()[0];
        $this->assertArrayHasKey('ip_start', $range);
        $this->assertFalse($this->ipblock->removeRangeFromBlock(false));
        $this->assertTrue($this->ipblock->removeRangeFromBlock(false, '9.9.9.0', '9.9.9.255'));
    }
    
    /**
     * @covers Blocking\IPBlock::__construct
     * @covers Blocking\IPBlock::getIPCountryISO
     * @covers Blocking\IPBlock::addISOCountryBlock
     * @covers Blocking\IPBlock::getBlockedIPTable
     * @covers Blocking\IPBlock::getBlockedISOTable
     * @covers Blocking\IPBlock::getBlockedRangeTable
     * @covers Blocking\IPBlock::isIPBlocked
     * @covers Blocking\IPBlock::isIPBlockedList
     * @covers Blocking\IPBlock::isIPBlockedRange
     * @covers Blocking\IPBlock::isISOBlocked
     * @covers Blocking\IPBlock::removeISOCountryBlock
     */
    public function testBlockISOCountry()
    {
        $this->assertFalse($this->ipblock->getIPCountryISO('wrong'));
        $this->assertEquals('GB', $this->ipblock->getIPCountryISO('212.42.18.1'));
        $this->assertFalse($this->ipblock->isIPBlocked('1.0.1.1'));
        $this->assertTrue($this->ipblock->addISOCountryBlock('CN'));
        $this->assertTrue($this->ipblock->isIPBlocked('1.0.1.1'));
        $this->assertFalse($this->ipblock->addISOCountryBlock('Test'));
        $this->assertFalse($this->ipblock->addISOCountryBlock(69));
        $this->assertFalse($this->ipblock->removeISOCountryBlock('Help'));
        $this->assertFalse($this->ipblock->removeISOCountryBlock('FR'));
        $this->assertTrue($this->ipblock->removeISOCountryBlock('CN'));
        $this->assertFalse($this->ipblock->isIPBlocked('1.0.1.1'));
    }
        
    /**
     * @covers Blocking\IPBlock::__construct
     * @covers Blocking\IPBlock::getUserIP
     * @covers Blocking\IPBlock::getBlockedIPTable
     * @covers Blocking\IPBlock::getBlockedISOTable
     * @covers Blocking\IPBlock::getBlockedRangeTable
     */
    public function testGetUserIP()
    {
        $this->assertEquals('212.42.18.1', $this->ipblock->getUserIP());
        $_SERVER["HTTP_X_FORWARDED_FOR"] = '127.127.0.1'; // Set forward for to test
        $this->assertEquals('127.127.0.1', $this->ipblock->getUserIP());
        $_SERVER["HTTP_CF_CONNECTING_IP"] = '8.8.8.8'; // Set sample cloudflare ip address
        $this->assertEquals('8.8.8.8', $this->ipblock->getUserIP());
    }
}
