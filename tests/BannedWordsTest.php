<?php

namespace Blocking\Tests;

use PHPUnit\Framework\TestCase;
use DBAL\Database;
use Blocking\BannedWords;

class BannedWordsTest extends TestCase
{
    protected $db;
    protected $bannedwords;

    protected function setUp(): void
    {
        $this->db = new Database($GLOBALS['HOSTNAME'], $GLOBALS['USERNAME'], $GLOBALS['PASSWORD'], $GLOBALS['DATABASE']);
        if (!$this->db->isConnected()) {
            $this->markTestSkipped(
                'No local database connection is available'
            );
        }
        $this->db->query(file_get_contents(dirname(dirname(__FILE__)).'/files/database/database.sql'));
        $this->bannedwords = new BannedWords($this->db);
        $this->db->truncate($this->bannedwords->getBannedWordsTable());
    }
    
    protected function tearDown(): void
    {
        $this->db = null;
        $this->bannedwords = null;
    }
    
    /**
     * @covers Blocking\BannedWords::__construct
     * @covers Blocking\BannedWords::setBannedWordsTable
     * @covers Blocking\BannedWords::getBannedWordsTable
     */
    public function testSetTable()
    {
        $this->assertEquals('blocked_words', $this->bannedwords->getBannedWordsTable());
        $this->assertObjectHasAttribute('db', $this->bannedwords->setBannedWordsTable(false));
        $this->assertEquals('blocked_words', $this->bannedwords->getBannedWordsTable());
        $this->assertObjectHasAttribute('db', $this->bannedwords->setBannedWordsTable(103));
        $this->assertEquals('blocked_words', $this->bannedwords->getBannedWordsTable());
        $this->assertObjectHasAttribute('db', $this->bannedwords->setBannedWordsTable('my_words_table'));
        $this->assertStringNotContainsString('blocked_words', $this->bannedwords->getBannedWordsTable());
        $this->assertEquals('my_words_table', $this->bannedwords->getBannedWordsTable());
    }
    
    /**
     * @covers Blocking\BannedWords::__construct
     * @covers Blocking\BannedWords::addBlockedWord
     * @covers Blocking\BannedWords::getBannedWordsTable
     * @covers Blocking\BannedWords::containsBlockedWord
     * @covers Blocking\BannedWords::getBlockedWords
     * @covers Blocking\BannedWords::removeBlockedWords
     */
    public function testAddBannedWord()
    {
        $this->assertTrue($this->bannedwords->addBlockedWord('improve ranking'));
        $this->assertFalse($this->bannedwords->addBlockedWord('improve ranking')); // Fail as same as before
        $this->assertFalse($this->bannedwords->addBlockedWord('imPRove RankIng')); // Fail as same but different case
        $this->assertTrue($this->bannedwords->addBlockedWord('seo'));
        $this->assertTrue($this->bannedwords->containsBlockedWord('page 1 of Google, improve ranking of your website'));
        $this->assertFalse($this->bannedwords->containsBlockedWord('This is genuine information to help improve you business status'));
        $this->assertArrayHasKey('word', $this->bannedwords->getBlockedWords('improve')[0]);
        $this->assertFalse($this->bannedwords->removeBlockedWords(10));
        $this->assertTrue($this->bannedwords->removeBlockedWords(1));
        $this->assertFalse($this->bannedwords->containsBlockedWord('page 1 of Google, improve ranking of your website'));
        $this->assertTrue($this->bannedwords->containsBlockedWord('whitehat SEO'));
    }
}
