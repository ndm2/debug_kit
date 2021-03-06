<?php
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
namespace DebugKit\Test\Middleware;

use Cake\Core\Configure;
use Cake\Database\Driver\Sqlite;
use Cake\Datasource\ConnectionManager;
use Cake\Http\CallbackStream;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use DebugKit\Middleware\DebugKitMiddleware;

/**
 * Test the middleware object
 */
class DebugKitMiddlewareTest extends TestCase
{

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'plugin.debug_kit.requests',
        'plugin.debug_kit.panels'
    ];
    /**
     * setup
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $connection = ConnectionManager::get('test');
        $this->skipIf($connection->driver() instanceof Sqlite, 'Schema insertion/removal breaks SQLite');
    }

    /**
     * Ensure data is saved for HTML requests
     *
     * @return void
     */
    public function testInvokeSaveData()
    {
        $request = new ServerRequest([
            'url' => '/articles',
            'environment' => ['REQUEST_METHOD' => 'GET']
        ]);
        $response = new Response([
            'statusCode' => 200,
            'type' => 'text/html',
            'body' => '<html><title>test</title><body><p>some text</p></body>'
        ]);

        $layer = new DebugKitMiddleware();
        $next = function ($req, $res) {
            return $res;
        };

        $response = $layer($request, $response, $next);
        $this->assertInstanceOf(Response::class, $response, 'Should return the response');

        $requests = TableRegistry::get('DebugKit.Requests');
        $result = $requests->find()
            ->order(['Requests.requested_at' => 'DESC'])
            ->contain('Panels')
            ->first();

        $this->assertEquals('GET', $result->method);
        $this->assertEquals('/articles', $result->url);
        $this->assertNotEmpty($result->requested_at);
        $this->assertNotEmpty('text/html', $result->content_type);
        $this->assertEquals(200, $result->status_code);
        $this->assertGreaterThan(1, $result->panels);

        $this->assertEquals('SqlLog', $result->panels[10]->panel);
        $this->assertEquals('DebugKit.sql_log_panel', $result->panels[10]->element);
        $this->assertNotNull($result->panels[10]->summary);
        $this->assertEquals('Sql Log', $result->panels[10]->title);

        $expected = '<html><title>test</title><body><p>some text</p>' .
            '<script id="__debug_kit" data-id="' . $result->id . '" ' .
            'data-url="http://localhost/" src="/debug_kit/js/toolbar.js"></script>' .
            '</body>';
        $body = $response->getBody();
        $this->assertTextEquals($expected, '' . $body);
    }

    /**
     * Ensure that streaming results are tracked, but not modified.
     *
     * @return void
     */
    public function testInvokeNoModifyBinaryResponse()
    {
        $request = new ServerRequest([
            'url' => '/articles',
            'environment' => ['REQUEST_METHOD' => 'GET']
        ]);
        $response = new Response([
            'statusCode' => 200,
            'type' => 'text/html',
        ]);

        $layer = new DebugKitMiddleware();
        $next = function ($req, $res) {
            $stream = new CallbackStream(function () {
                return 'hi!';
            });

            return $res->withBody($stream);
        };
        $result = $layer($request, $response, $next);
        $this->assertInstanceOf(Response::class, $result, 'Should return a response');

        $requests = TableRegistry::get('DebugKit.Requests');
        $total = $requests->find()->where(['url' => '/articles'])->count();

        $this->assertEquals(1, $total, 'Should track response');
        $body = $result->getBody();
        $this->assertNotContains('__debug_kit', '' . $body);
        $this->assertNotContains('<script', '' . $body);
    }

    /**
     * Ensure that no script tag is added to non html responses.
     *
     * @return void
     */
    public function testInvokeNoModifyNonHtmlResponse()
    {
        $request = new ServerRequest([
            'url' => '/articles',
            'environment' => ['REQUEST_METHOD' => 'GET']
        ]);
        $response = new Response([
            'statusCode' => 200,
            'type' => 'text/plain',
            'body' => 'OK'
        ]);

        $layer = new DebugKitMiddleware();
        $next = function ($req, $res) {
            return $res;
        };
        $result = $layer($request, $response, $next);
        $this->assertInstanceOf(Response::class, $result, 'Should return a response');

        $requests = TableRegistry::get('DebugKit.Requests');
        $total = $requests->find()->where(['url' => '/articles'])->count();

        $this->assertEquals(1, $total, 'Should track response');
        $body = $result->getBody();
        $this->assertSame('OK', '' . $body);
    }

    /**
     * Test that requestAction requests are not tracked or modified.
     *
     * @return void
     */
    public function testInvokeNoModifyRequestAction()
    {
        $request = new ServerRequest([
            'url' => '/articles',
            'environment' => ['REQUEST_METHOD' => 'GET'],
            'params' => ['requested' => true],
        ]);
        $response = new Response([
            'statusCode' => 200,
            'type' => 'text/html',
            'body' => '<body><p>things</p></body>'
        ]);

        $layer = new DebugKitMiddleware();
        $next = function ($req, $res) {
            return $res;
        };
        $result = $layer($request, $response, $next);
        $this->assertInstanceOf(Response::class, $result, 'Should return a response');

        $requests = TableRegistry::get('DebugKit.Requests');
        $total = $requests->find()->where(['url' => '/articles'])->count();

        $this->assertEquals(0, $total, 'Should not track sub-requests');
        $body = $result->getBody();
        $this->assertNotContains('<script', '' . $body);
    }
}
