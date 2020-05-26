<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use PHPUnit\DbUnit\TestCase;
use PHPUnit\DbUnit\DataSet\ReplacementDataSet;
use PHPUnit\DbUnit\DataSet\ArrayDataSet;
use Pleb\dataaccess\User;
use Pleb\dataaccess\UserData;
use Pleb\dataaccess\Template;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\StreamInterface;
use Tuupola\Http\Factory\RequestFactory;
use Tuupola\Http\Factory\ResponseFactory;
use Tuupola\Http\Factory\StreamFactory;
use Firebase\JWT\JWT;
use Pleb\ui\WebUser;
use Pleb\ui\WebUserData;

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
        $xml_dataset = $this->createXMLDataSet(__DIR__ . '/data/UserData.xml');
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
    
    public function setUp() : void  {
        parent::setUp();
        // Login & get JWT token
        $user = new User($this->getDB());
        $webUser = new WebUser($user);
        
        // Login user
        $this->rf = new RequestFactory();
        $response = (new ResponseFactory())->createResponse();
        $loginDetails = [ "username" => "admin", "password" => "locked" ];
        $stream = (new StreamFactory())-> createStream(json_encode($loginDetails));
        $request = $this->rf->createRequest("POST", "172.17.0.1/login")
            ->withHeader('Content-Type', 'text/json')
            ->withBody($stream);
        $request = $request->withParsedBody($loginDetails);
        $response = $webUser->login($request, $response, ["username" => "admin",
            "password" => "locked"
        ]);
        // extract JWT token
        $responseData = json_decode($response->getBody(), true);
        $this->JWT = $responseData['token'];
    }
    
    public function testFetchOK ()    {  
        $user = new UserData($this->getDB());
        $temp = new Template($this->getDB());
        $webUser = new WebUserData($user, $temp);
        $request = $this->rf->createRequest("GET", "172.17.0.1/userdata/test");
        $request = $request->withAddedHeader('Authorization', 'Bearer '.$this->JWT);
        $response = (new ResponseFactory())->createResponse();
        
        $response = $webUser->fetch($request, $response, ["infoType" => "test"]);
        
        $this->assertEquals("200", $response->getStatusCode());
        $this->assertIsArray($response->getHeader('Content-type'));
        $this->assertEquals('application/json', $response->getHeader('Content-type')[0]);
        $responseData = json_decode($response->getBody(), true);
        $this->assertEquals(['test'], array_keys($responseData));
        
        $this->assertEquals("value",$responseData['test']);
    }
    
    public function testFetchNoData ()    {
        $user = new UserData($this->getDB());
        $temp = new Template($this->getDB());
        $webUser = new WebUserData($user, $temp);
        $request = $this->rf->createRequest("GET", "172.17.0.1/userdata/test1");
        $request = $request->withAddedHeader('Authorization', 'Bearer '.$this->JWT);
        $response = (new ResponseFactory())->createResponse();
        
        $response = $webUser->fetch($request, $response, ["infoType" => "test1"]);
        
        $this->assertEquals("200", $response->getStatusCode());
        $this->assertIsArray($response->getHeader('Content-type'));
        $this->assertEquals('application/json', $response->getHeader('Content-type')[0]);
        $responseData = json_decode($response->getBody(), true);
        $this->assertEquals(['Error'], array_keys($responseData),
            print_r($responseData, true));
        
        $this->assertEquals("Not found:test1",$responseData['Error']);
    }
 
    public function testFetchNoDataButDefault ()    {
        $user = new UserData($this->getDB());
        $temp = new Template($this->getDB());
        $webUser = new WebUserData($user, $temp);
        $request = $this->rf->createRequest("GET", "172.17.0.1/userdata/test2");
        $request = $request->withAddedHeader('Authorization', 'Bearer '.$this->JWT);
        $response = (new ResponseFactory())->createResponse();
        
        $response = $webUser->fetch($request, $response, ["infoType" => "test2"]);
        
        $this->assertEquals("200", $response->getStatusCode());
        $this->assertIsArray($response->getHeader('Content-type'));
        $this->assertEquals('application/json', $response->getHeader('Content-type')[0]);
        $responseData = json_decode($response->getBody(), true);
        $this->assertEquals(['test2'], array_keys($responseData));
        
        $this->assertEquals("value2",$responseData['test2']);
    }

    public function testFetchDataButAlsoDefault ()    {
        $user = new UserData($this->getDB());
        $temp = new Template($this->getDB());
        $webUser = new WebUserData($user, $temp);
        $request = $this->rf->createRequest("GET", "172.17.0.1/userdata/test3");
        $request = $request->withAddedHeader('Authorization', 'Bearer '.$this->JWT);
        $response = (new ResponseFactory())->createResponse();
        
        $response = $webUser->fetch($request, $response, ["infoType" => "test3"]);
        
        $this->assertEquals("200", $response->getStatusCode());
        $this->assertIsArray($response->getHeader('Content-type'));
        $this->assertEquals('application/json', $response->getHeader('Content-type')[0]);
        $responseData = json_decode($response->getBody(), true);
        $this->assertEquals(['test3'], array_keys($responseData));
        
        $this->assertEquals("value3",$responseData['test3']);
    }
    
    public function testSaveNew ()    {
        $user = new UserData($this->getDB());
        $temp = new Template($this->getDB());
        $webUser = new WebUserData($user, $temp);
        $request = $this->rf->createRequest("POST", "172.17.0.1/userdata/test2");
        $request = $request->withAddedHeader('Content-Type', 'application/json');
        $request = $request->withAddedHeader('Authorization', 'Bearer '.$this->JWT);
        $body = $request->getBody();
        $data = ["test2" => "value1"];
        $body->write(json_encode($data));
        $body->rewind();
        $request = $request->withParsedBody($data);
        
        $response = (new ResponseFactory())->createResponse();
        
        $response = $webUser->save($request, $response, ["infoType" => "test2"]);
        
        $this->assertEquals("200", $response->getStatusCode());
        $this->assertIsArray($response->getHeader('Content-type'));
        $this->assertEquals('application/json', $response->getHeader('Content-type')[0]);
        $responseData = json_decode($response->getBody(), true);
        $this->assertEquals(['infoType', 'id'], array_keys($responseData));
        
        $this->assertEquals("test2",$responseData['infoType']);
        $this->assertEquals(3, $responseData['id']);
        
        $row=new ArrayDataSet([
            'userInfo' => [
                [ 'userID' => 1, "infoType" => "test2", "info" => '{"test2": "value1"}' ]
            ]
        ]);
        $queryTable = $this->getConnection()->createQueryTable(
            'userInfo', 'select userID, infoType, info
                            FROM userInfo
                            WHERE id = 3'
            );
        $this->assertTablesEqual($row->getTable('userInfo'), $queryTable);
        
    }
   
    public function testSaveUpdate ()    {
        $user = new UserData($this->getDB());
        $temp = new Template($this->getDB());
        $webUser = new WebUserData($user, $temp);
        $request = $this->rf->createRequest("POST", "172.17.0.1/userdata/test2");
        $request = $request->withAddedHeader('Content-Type', 'application/json');
        $request = $request->withAddedHeader('Authorization', 'Bearer '.$this->JWT);
        $body = $request->getBody();
        $data = ["test2" => "value1"];
        $body->write(json_encode($data));
        $body->rewind();
        $request = $request->withParsedBody($data);
        
        $response = (new ResponseFactory())->createResponse();
        
        $response = $webUser->save($request, $response, ["infoType" => "test2"]);
        
        $this->assertEquals("200", $response->getStatusCode());
        $this->assertIsArray($response->getHeader('Content-type'));
        $this->assertEquals('application/json', $response->getHeader('Content-type')[0]);
        $responseData = json_decode($response->getBody(), true);
        $this->assertEquals(['infoType', 'id'], array_keys($responseData));
        
        $this->assertEquals("test2",$responseData['infoType']);
        $this->assertEquals(3, $responseData['id']);
        
        $request = $this->rf->createRequest("POST", "172.17.0.1/userdata/test2");
        $request = $request->withAddedHeader('Content-Type', 'application/json');
        $request = $request->withAddedHeader('Authorization', 'Bearer '.$this->JWT);
        $body = $request->getBody();
        $data = ["test2" => "value11"];
        $body->write(json_encode($data));
        $body->rewind();
        $request = $request->withParsedBody($data);
        
        $response = (new ResponseFactory())->createResponse();
        
        $response = $webUser->save($request, $response, ["infoType" => "test2"]);
        
        $this->assertEquals("200", $response->getStatusCode());
        $this->assertIsArray($response->getHeader('Content-type'));
        $this->assertEquals('application/json', $response->getHeader('Content-type')[0]);
        $responseData = json_decode($response->getBody(), true);
        $this->assertEquals(['infoType', 'id'], array_keys($responseData));
        
        $this->assertEquals("test2",$responseData['infoType']);
        $this->assertEquals(3, $responseData['id']);
        
        $row=new ArrayDataSet([
            'userInfo' => [
                [ 'userID' => 1, "infoType" => "test2", "info" => '{"test2": "value11"}' ]
            ]
        ]);
        $queryTable = $this->getConnection()->createQueryTable(
            'userInfo', 'select userID, infoType, info
                            FROM userInfo
                            WHERE id = 3'
            );
        $this->assertTablesEqual($row->getTable('userInfo'), $queryTable);
    }
    
}

