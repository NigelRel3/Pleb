<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use PHPUnit\DbUnit\TestCase;
use PHPUnit\DbUnit\DataSet\ReplacementDataSet;
use PHPUnit\DbUnit\DataSet\ArrayDataSet;
use Pleb\dataaccess\User;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Tuupola\Http\Factory\StreamFactory;
use Tuupola\Http\Factory\RequestFactory;
use Tuupola\Http\Factory\ResponseFactory;
use Firebase\JWT\JWT;
use Pleb\ui\WebUser;

/**
 * User test case.
 */
/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class WebUserTest extends TestCase
{

    protected function getDataSet() {
        $xml_dataset = $this->createXMLDataSet(__DIR__ . '/data/User.xml');
        $xml_dataset_fixed = new ReplacementDataSet($xml_dataset, array('NOW' => date('Y.m.d H:i:s')));
        
        return $xml_dataset_fixed;
    }
    
    protected function getConnection() {
        $conn = $this->createDefaultDBConnection($this->getDB(), "db");
        return $conn;
    }
    
    private function getDB ()   {
        $host = getenv("DB_HOST");
        $user = getenv("DB_USER");
        $password = getenv("DB_PASSWD");
        $database = getenv("DB_DBNAME");
        $db = new PDO("mysql:host={$host};dbname={$database}", $user, $password);
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        return $db;
    }
    
    public function testLoginOK ()    {  
        $user = new User($this->getDB());
        $webUser = new WebUser($user);
        
        $rf = new RequestFactory();        
        $loginDetails = [ "username" => "admin", "password" => "locked" ];
        $stream = (new StreamFactory())-> createStream(json_encode($loginDetails));
        $request = $rf->createRequest("POST", "172.17.0.1/login")
            ->withHeader('Content-Type', 'text/json')
            ->withBody($stream);
        $request = $request->withParsedBody($loginDetails);
        
        $response = (new ResponseFactory())->createResponse();        
        $response = $webUser->login($request, $response);
        
        $this->assertEquals("200", $response->getStatusCode());
        $this->assertIsArray($response->getHeader('Content-type'));
        $this->assertEquals('application/json', $response->getHeader('Content-type')[0]);
        $responseData = json_decode($response->getBody(), true);
        $this->assertEquals(['token', 'expires'], array_keys($responseData));
        $decoded = JWT::decode( $responseData['token'],  getenv("JWT_SECRET"),
            ["HS256"]);
        
        $this->assertEquals("55f910de-7405-11ea-ac31-0242ac110004", 
            $decoded->uuid);
    }
    
    public function testLoginFailPassword ()    {
        $user = new User($this->getDB());
        $webUser = new WebUser($user);
        
        $rf = new RequestFactory();
        
        $loginDetails = [ "username" => "admin", "password" => "locked1" ];
        $stream = (new StreamFactory())-> createStream(json_encode($loginDetails));
        $request = $rf->createRequest("POST", "172.17.0.1/login")
            ->withHeader('Content-Type', 'text/json')
            ->withBody($stream);
        $request = $request->withParsedBody($loginDetails);
        
        $response = (new ResponseFactory())->createResponse();
        
        $response = $webUser->login($request, $response);
        
        $this->assertEquals("401", $response->getStatusCode());
        $this->assertIsArray($response->getHeader('Content-type'));
        $this->assertEquals('application/json', $response->getHeader('Content-type')[0]);
        $this->assertEquals( "[]", (string)$response->getBody());
    }
 
    public function testLoginFailUser ()    {
        $user = new User($this->getDB());
        $webUser = new WebUser($user);
        
        $rf = new RequestFactory();
        
        $loginDetails = [ "username" => "admin1", "password" => "locked1" ];
        $stream = (new StreamFactory())-> createStream(json_encode($loginDetails));
        $request = $rf->createRequest("POST", "172.17.0.1/login")
            ->withHeader('Content-Type', 'text/json')
            ->withBody($stream);
        $request = $request->withParsedBody($loginDetails);
        $response = (new ResponseFactory())->createResponse();
        
        $response = $webUser->login($request, $response);
        
        $this->assertEquals("401", $response->getStatusCode());
        $this->assertIsArray($response->getHeader('Content-type'));
        $this->assertEquals('application/json', $response->getHeader('Content-type')[0]);
        $this->assertEquals( "[]", (string)$response->getBody());
    }

    public function testLoginFailNoUser ()    {
        $user = new User($this->getDB());
        $webUser = new WebUser($user);
        
        $rf = new RequestFactory();
        
        $request = $rf->createRequest("POST", "172.17.0.1/login");
        
        $response = (new ResponseFactory())->createResponse();
        
        $response = $webUser->login($request, $response);
        
        $this->assertEquals("401", $response->getStatusCode());
        $this->assertIsArray($response->getHeader('Content-type'));
        $this->assertEquals('application/json', $response->getHeader('Content-type')[0]);
        $this->assertEquals( "[]", (string)$response->getBody());
    }
    
}

