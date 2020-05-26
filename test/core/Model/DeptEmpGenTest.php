<?php
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/TestClasses/DeptEmpGen.php';
require_once __DIR__ . '/TestClasses/DeptEmpGen2.php';
require_once __DIR__ . '/TestClasses/EmployeeGen.php';
require_once __DIR__ . '/TestClasses/EmployeeCacheGen.php';
require_once __DIR__ . '/TestClasses/DepartmentsStaticGen.php';

use PHPUnit\Framework\TestCase;
use Predis\Collection\Iterator\Keyspace;
use Predis\Client;
use Pleb\test\Model\TestClasses\DeptEmpGen;
use Pleb\test\Model\TestClasses\DeptEmpGen2;
use Pleb\test\Model\TestClasses\EmployeeGen;
use Pleb\test\Model\TestClasses\EmployeeCacheGen;
use Pleb\test\Model\TestClasses\DepartmentsStaticGen;
use Pleb\core\Util\Cache;


/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class DeptEmpGenTest extends TestCase
{
    public function testFetchExists ()    {
        $stmtMock = $this->createMock(\PDOStatement::class);
        $pdoMock = $this->createMock(\PDO::class);
        
        $stmtMock->expects($this->once())
            ->method('fetch')
            ->willReturn(["emp_no" => "1", "dept_no" => 'd002', 
                'from_date' => '1986-06-26', 'to_date' => '1987-06-26',
                
                "birth_date" => "1966-06-26", "first_name" => 'a',
                'last_name' => 'e', 'gender' => 'M', 
                'hire_date' => '1986-06-26'
            ]);
        $stmtMock->expects($this->once())
            ->method('execute')
            ->with($this->equalTo(["emp_no" => "1",
                "dept_no" => "d002"]))  
            ->willReturn(true);
        $pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($stmtMock);
        
        $departments = new DeptEmpGen($pdoMock);
        $this->assertTrue($departments->fetch(["emp_no" => "1",
            "dept_no" => "d002"]));
        $this->assertEquals ('1987-06-26', $departments->getToDate());
    }
    
    public function testFetchNotExists ()    {
        $stmtMock = $this->createMock(\PDOStatement::class);
        $pdoMock = $this->createMock(\PDO::class);
        
        $stmtMock->expects($this->once())
            ->method('fetch')
            ->willReturn(false);
        $stmtMock->expects($this->once())
            ->method('execute')
            ->with($this->equalTo(["emp_no" => "1", "dept_no" => "d009aa"]))  
            ->willReturn(true);
        $pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($stmtMock);
        
        $departments = new DeptEmpGen($pdoMock);
        $this->assertFalse($departments->fetch(["emp_no" => "1",
                "dept_no" => "d009aa"]));
    }
 
    public function testFetchCheckDeptEmpName ()    {
        $stmtMock = $this->createMock(\PDOStatement::class);
        $pdoMock = $this->createMock(\PDO::class);
        
        $stmtMock->expects($this->once())
        ->method('fetch')
        ->willReturn(["emp_no" => "1", "dept_no" => 'd002',
            'from_date' => '1986-06-26', 'to_date' => '1987-06-26',
            // employee data
            "birth_date" => "1966-06-26", "first_name" => 'a',
            'last_name' => 'e', 'gender' => 'M',
            'hire_date' => '1986-06-26'
        ]);
        $stmtMock->expects($this->once())
            ->method('execute')
            ->with($this->equalTo(["emp_no" => "1",
                "dept_no" => "d002"]))
            ->willReturn(true);
        $pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($stmtMock);
            
        $departments = new DeptEmpGen($pdoMock);
        $this->assertTrue($departments->fetch(["emp_no" => "1",
                "dept_no" => "d002"]));
        $this->assertEquals ('department 002', $departments->getDepartment()->getDeptName());
        $this->assertEquals ('a', $departments->getEmployee()->getFirstName());
    }
    
    public function testFetchCheckDeptEmpCacheName ()    {
        $stmtMock = $this->createMock(\PDOStatement::class);
        $stmtEmpMock = $this->createMock(\PDOStatement::class);
        $pdoMock = $this->createMock(\PDO::class);
        
        $stmtMock->expects($this->once())
            ->method('fetch')
            ->willReturn(["emp_no" => "1", "dept_no" => 'd002',
                'from_date' => '1986-06-26', 'to_date' => '1987-06-26'
        ]);
        $stmtMock->expects($this->once())
            ->method('execute')
            ->with($this->equalTo(["emp_no" => "1",
                "dept_no" => "d"]))
            ->willReturn(true);
            
        $stmtEmpMock->expects($this->once())
            ->method('fetch')
            ->willReturn(["birth_date" => "1966-06-26", "first_name" => 'a',
                'last_name' => 'e', 'gender' => 'M',
                'hire_date' => '1986-06-26'
            ]);
        $stmtEmpMock->expects($this->once())
            ->method('execute')
            ->with($this->equalTo(["emp_no" => "1"]))
                ->willReturn(true);
                
        $pdoMock->expects($this->at(0))
            ->method('prepare')
            ->willReturn($stmtMock);
        $pdoMock->expects($this->at(1))
            ->method('prepare')
            ->willReturn($stmtEmpMock);
            
        $cacheMock = $this->createMock(Cache::class);
        $cacheMock->expects($this->once())
            ->method('get')
            ->with($this->equalTo('{"0":"Pleb\\\test\\\Model\\\TestClasses\\\EmployeeCacheGen","emp_no":"1"}'))
            ->willReturn(false);
        $cacheMock->expects($this->once())
            ->method('set')
            ->with($this->equalTo('{"0":"Pleb\\\test\\\Model\\\TestClasses\\\EmployeeCacheGen","emp_no":"1"}'))
            ->willReturn(false);
        
        EmployeeCacheGen::setCache($cacheMock);
        
        $departments = new DeptEmpGen2($pdoMock);
        $this->assertTrue($departments->fetch(["emp_no" => "1",
            "dept_no" => "d"]));
        $this->assertEquals ('department 002', $departments->getDepartment()->getDeptName());
        $this->assertEquals ('a', $departments->getEmployee()->getFirstName());
    }
  
    public function testFetchCheckDeptEmpCheckCache ()    {
        $stmtMock = $this->createMock(\PDOStatement::class);
        $stmtEmpMock = $this->createMock(\PDOStatement::class);
        $pdoMock = $this->createMock(\PDO::class);
        
        $stmtMock->expects($this->once())
        ->method('fetch')
        ->willReturn(["emp_no" => "1", "dept_no" => 'd002',
            'from_date' => '1986-06-26', 'to_date' => '1987-06-26'
        ]);
        $stmtMock->expects($this->once())
        ->method('execute')
        ->with($this->equalTo(["emp_no" => "1",
            "dept_no" => "d"]))
            ->willReturn(true);
            
            $pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($stmtMock);
            
        $cacheMock = $this->createMock(Cache::class);
        $cacheMock->expects($this->once())
            ->method('get')
            ->with($this->equalTo('{"0":"Pleb\\\test\\\Model\\\TestClasses\\\EmployeeCacheGen","emp_no":"1"}'))
            ->willReturn('{"emp_no":"1", "first_name" : "a" }');
        
        EmployeeCacheGen::setCache($cacheMock);
        
        $departments = new DeptEmpGen2($pdoMock);
        $this->assertTrue($departments->fetch(["emp_no" => "1",
            "dept_no" => "d"]));
        $this->assertEquals ('department 002', $departments->getDepartment()->getDeptName());
        $this->assertEquals ('a', $departments->getEmployee()->getFirstName());
        
    }
 

}


