<?php

use PHPUnit\DbUnit\TestCase;
use PHPUnit\DbUnit\DataSet\ReplacementDataSet;
use PHPUnit\DbUnit\DataSet\ArrayDataSet;
use Pleb\transfer\Adaptor\CSV;
use Pleb\transfer\Adaptor\Transient;
use Pleb\transfer\Field\Field;
use Pleb\transfer\Entity;
use Pleb\transfer\Adaptor\Model;

require_once __DIR__ . '/../../../vendor/autoload.php';

class ModelTest extends TestCase   {
    private $db;
    
    protected function getDataSet() {
        $xml_dataset = $this->createXMLDataSet(__DIR__ . '/data/Employee1.xml');
        $xml_dataset_fixed = new ReplacementDataSet($xml_dataset, array('NOW' => date('Y.m.d H:i:s')));
        
        return $xml_dataset_fixed;
    }
    
    protected function getConnection() {
        $host = getenv("DB_HOST");
        $user = getenv("DB_USER");
        $password = getenv("DB_PASSWD");
        $database = getenv("DB_DBNAME");
        $db = new PDO("mysql:host={$host};dbname={$database}",
            $user, $password);
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->db = $db;
        $conn = $this->createDefaultDBConnection($db, "db");
        return $conn;
    }
    
    public function testModify () {
        $csvOutput = <<<TESTDATA
emp_no,birth_date,name,hire_date
1,01/01/1980,"Fred Smith",01/01/2000
2,02/01/1980,"Fred2 Smith2",02/01/2000
3,03/01/1980,"Fred3 Smith3",03/01/2000
4,04/01/1980,"Fred4 Smith4",04/01/2000

TESTDATA;
        $output = new class() extends CSV   {
            protected function configure()    {
                $this->setName ("testCSVOut.csv" );
                $this->setFields( [ "emp_no" => Field::INT(), 
                    "birth_date" => Field::Date("d/m/Y"), 
                    "name" => Field::STRING(),
                    "hire_date" => Field::Date("d/m/Y")
                ]);
            }
        };
        $input = (new Model("employees1"))
            ->setDB($this->db)
            ->loadColumns()
            ->modify (function(&$data) { 
                $data['name'] = $data['first_name'] . " " . $data['last_name']; 
                return Entity::CONTINUE_PROCESSING; })
            ->saveTo($output)
            ->transfer();
        
        $output = file_get_contents("testCSVOut.csv");
        $this->assertEquals($output, $csvOutput);
        
        unlink("testCSVOut.csv");
            
    }

    public function testLimit () {
        $csvOutput = <<<TESTDATA
emp_no,birth_date,name,hire_date
1,01/01/1980,"Fred Smith",01/01/2000
2,02/01/1980,"Fred2 Smith2",02/01/2000

TESTDATA;
        $output = new class() extends CSV   {
            protected function configure()    {
                $this->setName ("testCSVOut.csv" );
                $this->setFields( [ "emp_no" => Field::INT(),
                    "birth_date" => Field::Date("d/m/Y"),
                    "name" => Field::STRING(),
                    "hire_date" => Field::Date("d/m/Y")
                ]);
            }
        };
        $input = (new Model("employees1"))
            ->setDB($this->db)
            ->loadColumns()
            ->setLimit(2)
            ->modify (function(&$data) {
                $data['name'] = $data['first_name'] . " " . $data['last_name'];
                return Entity::CONTINUE_PROCESSING; })
            ->saveTo($output)
            ->transfer();
        
        $output = file_get_contents("testCSVOut.csv");
        $this->assertEquals($output, $csvOutput);
        
        unlink("testCSVOut.csv");
        
    }
    
    public function testLimitAndOffset () {
        $csvOutput = <<<TESTDATA
emp_no,birth_date,name,hire_date
3,03/01/1980,"Fred3 Smith3",03/01/2000

TESTDATA;
        $output = new class() extends CSV   {
            protected function configure()    {
                $this->setName ("testCSVOut.csv" );
                $this->setFields( [ "emp_no" => Field::INT(),
                    "birth_date" => Field::Date("d/m/Y"),
                    "name" => Field::STRING(),
                    "hire_date" => Field::Date("d/m/Y")
                ]);
            }
        };
        $input = (new Model("employees1"))
            ->setDB($this->db)
            ->loadColumns()
            ->setLimit(2,1)
            ->modify (function(&$data) {
                $data['name'] = $data['first_name'] . " " . $data['last_name'];
                return Entity::CONTINUE_PROCESSING; })
                ->saveTo($output)
            ->transfer();
        
        $output = file_get_contents("testCSVOut.csv");
        $this->assertEquals($output, $csvOutput);
        
        unlink("testCSVOut.csv");
        
    }
    
    public function testFilter () {
        $csvOutput = <<<TESTDATA
emp_no,birth_date,name,hire_date
3,03/01/1980,"Fred3 Smith3",03/01/2000

TESTDATA;
        $output = new class() extends CSV   {
            protected function configure()    {
                $this->setName ("testCSVOut.csv" );
                $this->setFields( [ "emp_no" => Field::INT(),
                    "birth_date" => Field::Date("d/m/Y"),
                    "name" => Field::STRING(),
                    "hire_date" => Field::Date("d/m/Y")
                ]);
            }
        };
        $input = (new Model("employees1"))
            ->setDB($this->db)
            ->loadColumns()
            ->filter("emp_no = 3")
            ->modify (function(&$data) {
                $data['name'] = $data['first_name'] . " " . $data['last_name'];
                return Entity::CONTINUE_PROCESSING; })
            ->saveTo($output)
            ->transfer();
                
        $output = file_get_contents("testCSVOut.csv");
        $this->assertEquals($output, $csvOutput);
        
        unlink("testCSVOut.csv");
        
    }
  
    public function testFilterCallback () {
        $csvOutput = <<<TESTDATA
emp_no,birth_date,name,hire_date
3,03/01/1980,"Fred3 Smith3",03/01/2000

TESTDATA;
        $output = new class() extends CSV   {
            protected function configure()    {
                $this->setName ("testCSVOut.csv" );
                $this->setFields( [ "emp_no" => Field::INT(),
                    "birth_date" => Field::Date("d/m/Y"),
                    "name" => Field::STRING(),
                    "hire_date" => Field::Date("d/m/Y")
                ]);
            }
        };
        $input = (new Model("employees1"))
            ->setDB($this->db)
            ->loadColumns()
            ->modify (function(&$data) {
                $data['name'] = $data['first_name'] . " " . $data['last_name'];
                return Entity::CONTINUE_PROCESSING; })
            ->filter(function($data) { return $data["emp_no"] == 3;})
            ->saveTo($output)
            ->transfer();
        
        $output = file_get_contents("testCSVOut.csv");
        $this->assertEquals($output, $csvOutput);
        
        unlink("testCSVOut.csv");
                
    }
    
//     public function testSum()   {
//         $csv = <<<TESTDATA
// id,name,qty,qty2
// 11,abc,4,41
// 11,abc,4,42
// 11,abc,4,43
// 12,abc,4,44
// TESTDATA;
        
//         $csvOutput = <<<TESTDATA
// id,sumqty2,sumqty
// 11,126,12
// 12,44,4

// TESTDATA;
//         file_put_contents("testCSV.csv", $csv);
        
//         $input = new class() extends CSV    {
//             protected function configure()    {
//                 $this->setName ("testCSV.csv" );
//                 $this->setFields( [ "id" => Field::INT(), "name" => Field::STRING(),
//                     "qty" => Field::INT(),
//                     "qty2" => Field::INT()
//                 ]);
//             }
//         };
        
//         $output = new class() extends CSV   {
//             protected function configure()    {
//                 $this->setName ("testCSVOut.csv" );
//                 $this->setFields( [ "id" => Field::INT(),"sumqty2" => Field::INT(),
//                     "sumqty" => Field::INT()
//                 ]);
//             }
//         };
//         $input
//             ->sum(['qty' => 'sumqty', 'qty2' => 'sumqty2'])
//             ->groupBy(['id'])
//             ->saveTo($output)
//             ->transfer();
            
//         $output = file_get_contents("testCSVOut.csv");
//         $this->assertEquals($output, $csvOutput);
        
//         unlink("testCSV.csv");
//         unlink("testCSVOut.csv");
//     }
        
    public function testLookup()   {
        $csvOutput = <<<TESTDATA
emp_emp_no,emp_birth_date,emp_first_name,emp_hire_date,dept_no
1,01/01/1980,Fred,01/01/2000,d001
1,01/01/1980,Fred,01/01/2000,d002
2,02/01/1980,Fred2,02/01/2000,d001
3,03/01/1980,Fred3,03/01/2000,d001

TESTDATA;
        
        $employee = (new Model("employees1"))
            ->setDB($this->db)
            ->loadColumns()
            ->indexBy("emp_no = :emp_no");
        
        $dept = (new Model("dept_emp2"))
            ->setDB($this->db)
            ->loadColumns();
        
        $output = new class() extends CSV   {
            protected function configure()    {
                $this->setName ("testCSVOut.csv" );
                $this->setFields( [ "emp_emp_no" => Field::INT(),
                    "emp_birth_date" => Field::Date("d/m/Y"),
                    "emp_first_name" => Field::STRING(),
                    "emp_hire_date" => Field::Date("d/m/Y"),
                    "dept_no" => Field::STRING()
                ]);
            }
        };
        
        $dept
            ->lookup($employee, ['emp_no'], "emp_")
            ->saveTo($output)
            ->transfer();
        
        $output = file_get_contents("testCSVOut.csv");
        $this->assertEquals($output, $csvOutput);
        
        unlink("testCSVOut.csv");
    }
    
    public function testLookupLastDate()   {
        $csvOutput = <<<TESTDATA
emp_no,birth_date,first_name,hire_date,dept_dept_no,dept_from_date
1,01/01/1980,Fred,01/01/2000,d002,01/01/2001
2,02/01/1980,Fred2,02/01/2000,d001,02/01/2000
3,03/01/1980,Fred3,03/01/2000,d001,03/01/2000

TESTDATA;
        
        $employee = (new Model("employees1"))
            ->setDB($this->db)
            ->loadColumns();
        
        $dept = (new Model("dept_emp2"))
            ->setDB($this->db)
            ->loadColumns()
            ->indexBy("emp_no = :emp_no")
            ->orderBy(["from_date" => Entity::DESC])
            ->setLimit(1);
        
        $output = new class() extends CSV   {
            protected function configure()    {
                $this->setName ("testCSVOut.csv" );
                $this->setFields( [ "emp_no" => Field::INT(),
                    "birth_date" => Field::Date("d/m/Y"),
                    "first_name" => Field::STRING(),
                    "hire_date" => Field::Date("d/m/Y"),
                    "dept_dept_no" => Field::STRING(),
                    "dept_from_date" => Field::Date("d/m/Y")
                ]);
            }
        };
        
        $employee
            ->lookup($dept, ['emp_no'], "dept_")
            ->saveTo($output)
            ->transfer();
        
        $output = file_get_contents("testCSVOut.csv");
        $this->assertEquals($output, $csvOutput);
        
        unlink("testCSVOut.csv");
    }
    
    public function testOrderBy()   {
        $csvOutput = <<<TESTDATA
emp_no,birth_date,name,hire_date
4,04/01/1980,"Fred4 Smith4",04/01/2000
3,03/01/1980,"Fred3 Smith3",03/01/2000
2,02/01/1980,"Fred2 Smith2",02/01/2000
1,01/01/1980,"Fred Smith",01/01/2000

TESTDATA;
        $output = new class() extends CSV   {
            protected function configure()    {
                $this->setName ("testCSVOut.csv" );
                $this->setFields( [ "emp_no" => Field::INT(),
                    "birth_date" => Field::Date("d/m/Y"),
                    "name" => Field::STRING(),
                    "hire_date" => Field::Date("d/m/Y")
                ]);
            }
        };
        $input = (new Model("employees1"))
            ->setDB($this->db)
            ->loadColumns()
            ->modify (function(&$data) {
                $data['name'] = $data['first_name'] . " " . $data['last_name'];
                return Entity::CONTINUE_PROCESSING; })
            ->orderBy(["emp_no" => Entity::DESC])
            ->saveTo($output)
            ->transfer();
        
        $output = file_get_contents("testCSVOut.csv");
        $this->assertEquals($output, $csvOutput);
        
        unlink("testCSVOut.csv");
    }
    
        
}