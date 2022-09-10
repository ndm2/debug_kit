<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace DebugKit\Test\TestCase;

use Cake\Database\Driver\Postgres;
use Cake\Database\Driver\Sqlite;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;
use DebugKit\DebugSql;
use DebugKit\TestApp\Stub\DebugSqlStub;

/**
 * Test the debugging SQL
 */
class DebugSqlTest extends TestCase
{
    /**
     * @var \Cake\Database\Connection
     */
    public $connection;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->connection = ConnectionManager::get('test');
    }

    /**
     * Tests that a SQL string is outputted in a formatted and
     * highlighted fashion in a CLI environment.
     */
    public function testSqlCli()
    {
        $this->skipIf(!($this->connection->getDriver() instanceof Sqlite));

        $query = $this->newQuery()->select(['panels.id']);

        ob_start();
        $this->assertSame($query, DebugSql::sql($query));
        $result = ob_get_clean();

        if ($this->connection->getDriver()->isAutoQuotingEnabled()) {
            $expectedText = <<<EXPECTED
%s (line %d)
########## DEBUG ##########
[37mSELECT[0m
  [33;1m"panels"[0m.[0m[33;1m"id"[0m [37mAS[0m [33;1m"panels__id"[0m
[37mFROM[0m
  [33;1m"panels"[0m [33;1m"panels"[0m
###########################

EXPECTED;
        } else {
            $expectedText = <<<EXPECTED
%s (line %d)
########## DEBUG ##########
[37mSELECT[0m
  [36;1mpanels[0m.[0m[36;1mid[0m [37mAS[0m [36;1mpanels__id[0m
[37mFROM[0m
  [36;1mpanels[0m [36;1mpanels[0m
###########################

EXPECTED;
        }
        $expected = sprintf($expectedText, str_replace(ROOT, '', __FILE__), __LINE__ - 26);
        $this->assertSame($expected, $result);
    }

    /**
     * Tests that a SQL string is outputted as HTML in a CLI
     * environment.
     */
    public function testSqlHtmlOnCli()
    {
        $this->skipIf(!($this->connection->getDriver() instanceof Sqlite));

        $query = $this->newQuery()->select(['panels.id']);

        ob_start();
        $this->assertSame($query, DebugSql::sql($query, true, true));
        $result = ob_get_clean();

        if ($this->connection->getDriver()->isAutoQuotingEnabled()) {
            $expectedHtml = <<<EXPECTED
<div class="cake-debug-output">
<span><strong>%s</strong> (line <strong>%d</strong>)</span>
<pre class="cake-debug">
<span style="font-weight:bold;">SELECT</span>
  <span style="color: #004d40;">&quot;panels&quot;</span><span >.</span><span style="color: #004d40;">&quot;id&quot;</span> <span style="font-weight:bold;">AS</span> <span style="color: #004d40;">&quot;panels__id&quot;</span>
<span style="font-weight:bold;">FROM</span>
  <span style="color: #004d40;">&quot;panels&quot;</span> <span style="color: #004d40;">&quot;panels&quot;</span>
</pre>
</div>
EXPECTED;
        } else {
            $expectedHtml = <<<EXPECTED
<div class="cake-debug-output">
<span><strong>%s</strong> (line <strong>%d</strong>)</span>
<pre class="cake-debug">
<span style="font-weight:bold;">SELECT</span>
  <span style="color: #9c27b0;">panels</span><span >.</span><span style="color: #9c27b0;">id</span> <span style="font-weight:bold;">AS</span> <span style="color: #9c27b0;">panels__id</span>
<span style="font-weight:bold;">FROM</span>
  <span style="color: #9c27b0;">panels</span> <span style="color: #9c27b0;">panels</span>
</pre>
</div>
EXPECTED;
        }
        $expected = sprintf($expectedHtml, str_replace(ROOT, '', __FILE__), __LINE__ - 28);
        $this->assertSame(str_replace("\r", '', $expected), str_replace("\r", '', $result));
    }

    /**
     * Tests that a SQL string is outputted as HTML in a non-CLI
     * environment.
     */
    public function testSqlHtml()
    {
        $this->skipIf(!($this->connection->getDriver() instanceof Sqlite));

        $query = $this->newQuery()->select(['panels.id']);

        ob_start();
        DebugSqlStub::$isCli = false;
        $this->assertSame($query, DebugSqlStub::sql($query, true, true));
        DebugSqlStub::$isCli = true;
        $result = ob_get_clean();

        if ($this->connection->getDriver()->isAutoQuotingEnabled()) {
            $expectedHtml = <<<EXPECTED
<div class="cake-debug-output">
<span><strong>%s</strong> (line <strong>%d</strong>)</span>
<pre class="cake-debug">
<span style="font-weight:bold;">SELECT</span>
  <span style="color: #004d40;">&quot;panels&quot;</span><span >.</span><span style="color: #004d40;">&quot;id&quot;</span> <span style="font-weight:bold;">AS</span> <span style="color: #004d40;">&quot;panels__id&quot;</span>
<span style="font-weight:bold;">FROM</span>
  <span style="color: #004d40;">&quot;panels&quot;</span> <span style="color: #004d40;">&quot;panels&quot;</span>
</pre>
</div>
EXPECTED;
        } else {
            $expectedHtml = <<<EXPECTED
<div class="cake-debug-output">
<span><strong>%s</strong> (line <strong>%d</strong>)</span>
<pre class="cake-debug">
<span style="font-weight:bold;">SELECT</span>
  <span style="color: #9c27b0;">panels</span><span >.</span><span style="color: #9c27b0;">id</span> <span style="font-weight:bold;">AS</span> <span style="color: #9c27b0;">panels__id</span>
<span style="font-weight:bold;">FROM</span>
  <span style="color: #9c27b0;">panels</span> <span style="color: #9c27b0;">panels</span>
</pre>
</div>
EXPECTED;
        }
        $expected = sprintf($expectedHtml, str_replace(ROOT, '', __FILE__), __LINE__ - 29);
        $this->assertSame(str_replace("\r", '', $expected), str_replace("\r", '', $result));
    }

    /**
     * Tests that a SQL string is outputted as plain text in a non-CLI
     * environment.
     */
    public function testSqlPlain()
    {
        $this->skipIf(!($this->connection->getDriver() instanceof Sqlite));

        $query = $this->newQuery()->select(['panels.id']);

        ob_start();
        DebugSqlStub::$isCli = false;
        $this->assertSame($query, DebugSqlStub::sql($query, true, false));
        DebugSqlStub::$isCli = true;
        $result = ob_get_clean();

        if ($this->connection->getDriver()->isAutoQuotingEnabled()) {
            $expectedHtml = <<<EXPECTED
%s (line %s)
########## DEBUG ##########
SELECT
  "panels"."id" AS "panels__id"
FROM
  "panels" "panels"
###########################

EXPECTED;
        } else {
            $expectedHtml = <<<EXPECTED
%s (line %s)
########## DEBUG ##########
SELECT
  panels.id AS %s
FROM
  panels panels
###########################

EXPECTED;
        }
        $fieldName = $this->connection->getDriver() instanceof Postgres ? '"panels__id"' : 'panels__id';
        $expected = sprintf($expectedHtml, str_replace(ROOT, '', __FILE__), __LINE__ - 28, $fieldName);
        $this->assertSame(str_replace("\r", '', $expected), str_replace("\r", '', $result));
    }

    /**
     * Creates a Query object for testing.
     *
     * @return \Cake\ORM\Query
     */
    private function newQuery()
    {
        return $this->getTableLocator()->get('panels')->query();
    }
}
