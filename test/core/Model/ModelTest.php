<?php
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/TestClasses/DepartmentsGen.php';
require_once __DIR__ . '/TestClasses/DepartmentsStaticGen.php';
require_once __DIR__ . '/TestClasses/DepartmentsStaticMGen.php';
require_once __DIR__ . '/TestClasses/DepartmentsCacheGen.php';

use PHPUnit\Framework\TestCase;
use Predis\Collection\Iterator\Keyspace;
use Predis\Client;
use Pleb\test\Model\TestClasses\DepartmentsGen;
use Pleb\test\Model\TestClasses\DepartmentsStaticGen;
use Pleb\test\Model\TestClasses\DepartmentsStaticMGen;
use Pleb\test\Model\TestClasses\DepartmentsCacheGen;
use Pleb\core\Util\Cache;


/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class ModelTest extends TestCase
{
    public function testFetchExists ()    {
        $stmtMock = $this->createMock(\PDOStatement::class);
        $pdoMock = $this->createMock(\PDO::class);
        
        $stmtMock->expects($this->once())
            ->method('fetch')
            ->willReturn(["dept_no" => 'd009', 'dept_name' => 'Department d009']);
        $stmtMock->expects($this->once())
            ->method('execute')
            ->with($this->equalTo(["dept_no" => "d009"]))  
            ->willReturn(true);
        $pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($stmtMock);
        
        $departments = new DepartmentsGen($pdoMock);
        $this->assertTrue($departments->fetch(["dept_no" => "d009"]));
        $this->assertEquals ("d009", $departments->getDeptNo());
    }
    
    public function testFetchNotExists ()    {
        $stmtMock = $this->createMock(\PDOStatement::class);
        $pdoMock = $this->createMock(\PDO::class);
        
        $stmtMock->expects($this->once())
            ->method('fetch')
            ->willReturn(false);
        $stmtMock->expects($this->once())
            ->method('execute')
            ->with($this->equalTo(["dept_no" => "d009aa"]))  
            ->willReturn(true);
        $pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($stmtMock);
        
        $departments = new DepartmentsGen($pdoMock);
        $this->assertFalse($departments->fetch(["dept_no" => "d009aa"]));
    }
 
    public function testStaticFetchExists ()    {
        $departments = new DepartmentsStaticGen();
        $this->assertTrue($departments->fetch(["dept_no" => "d004"]));
        $this->assertEquals ("department 004", $departments->getDeptName());
    }
    
    public function testStaticFetchNotExists ()    {
        $departments = new DepartmentsStaticGen();
        $this->assertFalse($departments->fetch(["dept_no" => "d009aa"]));
    }
    
    public function testStatic2FetchExists ()    {
        $departments = new DepartmentsStaticMGen();
        $this->assertTrue($departments->fetch(
            ["dept_no" => "d001", "ver_no" => "1"]), "Fetch 1");
        $this->assertEquals ("dept d001", $departments->getDeptName());

        $this->assertTrue($departments->fetch(
            ["dept_no" => "d001", "ver_no" => "2"]), "Fetch 2");
        $this->assertEquals ("depatment d001", $departments->getDeptName());
    }
    
    public function testStatic2FetchNotExists ()    {
        $departments = new DepartmentsStaticMGen();
        $this->assertFalse($departments->fetch(
            ["dept_no" => "d001", "ver_no" => "3"]));
    }
    
    public function testInsert ()    {
        $stmtMock = $this->createMock(\PDOStatement::class);
        $pdoMock = $this->createMock(\PDO::class);
        
        $stmtMock->expects($this->once())
            ->method('execute')
            ->with($this->equalTo(["dept_no" => "d010","dept_name" => "d0010 namea"]))
            ->willReturn(true);
        $pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->equalTo("insert into `departments` (`dept_no`, `dept_name`)
                                values (:dept_no, :dept_name)"))
            ->willReturn($stmtMock);
                                
        $departments = new DepartmentsGen($pdoMock);
        $departments->setDeptNo("d010");
        $departments->setDeptName("d0010 namea");
        $departments->insert();

    }
    
    public function testDeleteExists ()    {
        $stmtMock = $this->createMock(\PDOStatement::class);
        $pdoMock = $this->createMock(\PDO::class);
        
        $stmtMock->expects($this->once())
            ->method('execute')
            ->with($this->equalTo(["dept_no" => "d009"]))
            ->willReturn(true);
        $pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($stmtMock);
            $departments = new DepartmentsGen($pdoMock);
        $this->assertTrue($departments->delete(["dept_no" => "d009"]));

    }
    
    public function testCacheFetch() {
        $stmtMock = $this->createMock(\PDOStatement::class);
        $pdoMock = $this->createMock(\PDO::class);
        
        $stmtMock->expects($this->once())
            ->method('fetch')
            ->willReturn(["dept_no" => 'd009', 'dept_name' => 'Department d009a']);
        $stmtMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);
        $pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($stmtMock);
        
        // Mock cache rather than use actual Cache class
        $cache = $this->createMock(Cache::class);
        $cache->expects($this->once())
            ->method('set')
            ->with($this->equalTo('{"0":"Pleb\\\test\\\Model\\\TestClasses\\\DepartmentsCacheGen","dept_no":"d009"}'),
                    $this->equalTo("{\"dept_no\":\"d009\",\"dept_name\":\"Department d009a\"}"))
            ->willReturn(true);
        $cache->expects($this->once())
            ->method('get')
            ->with($this->equalTo('{"0":"Pleb\\\test\\\Model\\\TestClasses\\\DepartmentsCacheGen","dept_no":"d009"}'))
            ->willReturn(false);
            
        $departments = new DepartmentsCacheGen($pdoMock);
        DepartmentsCacheGen::setCache($cache);
        $this->assertTrue($departments->fetch(["dept_no" => "d009"]));
        $this->assertEquals ("d009", $departments->getDeptNo());
    }
    
    /**
     * Check subsequent fetch is from cache
     */
    public function testCacheFetched() {
        $stmtMock = $this->createMock(\PDOStatement::class);
        $pdoMock = $this->createMock(\PDO::class);
        
        $stmtMock->expects($this->once())
            ->method('fetch')
            ->willReturn(["dept_no" => 'd009', 'dept_name' => 'Department d009']);
        $stmtMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);
        $pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($stmtMock);
        // Mock cache rather than use actual Cache class
        $cache = $this->createMock(Cache::class);
        $cache->expects($this->once())
            ->method('set')
            ->with($this->equalTo('{"0":"Pleb\\\test\\\Model\\\TestClasses\\\DepartmentsCacheGen","dept_no":"d009"}'),
                    $this->equalTo("{\"dept_no\":\"d009\",\"dept_name\":\"Department d009\"}"))
            ->willReturn(true);
        $cache->expects($this->exactly(2))
            ->method('get')
            ->with($this->equalTo('{"0":"Pleb\\\test\\\Model\\\TestClasses\\\DepartmentsCacheGen","dept_no":"d009"}'))
            ->willReturnOnConsecutiveCalls( false, "{\"dept_no\":\"d009\",\"dept_name\":\"Department d009\"}");

        $departments = new DepartmentsCacheGen($pdoMock);
        DepartmentsCacheGen::setCache($cache);
        $this->assertTrue($departments->fetch(["dept_no" => "d009"]));
        $this->assertEquals ("d009", $departments->getDeptNo());
       
        // Check re-fetch is from cache
        $departments = new DepartmentsCacheGen($pdoMock);
        DepartmentsCacheGen::setCache($cache);
        $this->assertTrue($departments->fetch(["dept_no" => "d009"]));
    }
    
    public function testCacheInsert() {
        $stmtMock = $this->createMock(\PDOStatement::class);
        $pdoMock = $this->createMock(\PDO::class);
        
        $stmtMock->expects($this->once())
            ->method('execute')
            ->with($this->equalTo(["dept_no" => "d010","dept_name" => "d0010 name"]))
            ->willReturn(true);
        $pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->equalTo("insert into `departments` (`dept_no`, `dept_name`)
                                values (:dept_no, :dept_name)"))
            ->willReturn($stmtMock);
        
        // Mock cache rather than use actual Cache class
        $cache = $this->createMock(Cache::class);
        $cache->expects($this->once())
            ->method('set')
            ->with($this->equalTo('{"0":"Pleb\\\test\\\Model\\\TestClasses\\\DepartmentsCacheGen","dept_no":"d010"}'),
                $this->equalTo("{\"dept_no\":\"d010\",\"dept_name\":\"d0010 name\"}"))
            ->willReturn(true);
        
        $departments = new DepartmentsCacheGen($pdoMock);
        DepartmentsCacheGen::setCache($cache);
        $departments->setDeptNo("d010");
        $departments->setDeptName("d0010 name");
        $this->assertTrue($departments->insert());
        
    }
    
}


