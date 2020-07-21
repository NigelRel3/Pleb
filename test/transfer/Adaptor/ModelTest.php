<?php

use PHPUnit\Framework\TestCase;
use Pleb\transfer\Adaptor\CSV;
use Pleb\transfer\Adaptor\Transient;
use Pleb\transfer\Field\Field;
use Pleb\transfer\Entity;
use Pleb\transfer\Adaptor\Model;

require_once __DIR__ . '/../../../vendor/autoload.php';

class ModelTest extends TestCase   {
    private $db;
    
    protected function setUp() : void	{
    	$db = $this->getConnection();
    	$db->query("truncate table employees1");
    	$db->query("truncate table dept_emp2");

    	$db->query("insert into employees1 (emp_no, birth_date, first_name, 
						last_name, gender, hire_date)
				values ( 1, '1980-01-01', 'Fred', 'Smith', 'm', '2000-01-01'),
					 ( 2, '1980-01-02', 'Fred2', 'Smith2', 'm', '2000-01-02'),
					( 3, '1980-01-03', 'Fred3', 'Smith3', 'm', '2000-01-03'),
					( 4, '1980-01-04', 'Fred4', 'Smith4', 'm', '2000-01-04')");

    	$db->query("insert into dept_emp2 (emp_no, dept_no, from_date,
						to_date)
				values ( 1, 'd001', '2000-01-01', '2000-01-01'),
					 ( 1, 'd002', '2001-01-01', '2002-01-01'),
					( 2, 'd001', '2000-01-02', '2001-01-02'),
					( 3, 'd001', '2000-01-03', '2001-01-03')");
    	
    }
    
    protected function select ( string $query ) : array	{
    	$db = $this->getConnection();
    	$stmt = $db->query($query);
    	return $stmt->fetchAll();
    }
    
    protected function getConnection() {
    	if ( $this->db === null )	{
	        $host = getenv("DB_HOST");
	        $user = getenv("DB_USER");
	        $password = getenv("DB_PASSWD");
	        $database = getenv("DB_DBNAME");
	        $db = new PDO("mysql:host={$host};dbname={$database}",
	            $user, $password);
	        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
	        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	        $this->db = $db;
    	}
    	return $this->db;
    }
    
    public function testModify () {
        $csvOutput = <<<TESTDATA
emp_no,birth_date,name,hire_date
1,01/01/1980,"Fred Smith",01/01/2000
2,02/01/1980,"Fred2 Smith2",02/01/2000
3,03/01/1980,"Fred3 Smith3",03/01/2000
4,04/01/1980,"Fred4 Smith4",04/01/2000

TESTDATA;
        $output = (new CSV ("testCSVOut.csv")) 
        	->setFields( [ "emp_no" => Field::INT(), 
                    "birth_date" => Field::Date("d/m/Y"), 
                    "name" => Field::STRING(),
                    "hire_date" => Field::Date("d/m/Y")
        ]);
        
        $input = (new Model("employees1"))
            ->setDB($this->db)
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
        $output = (new CSV ("testCSVOut.csv"))
        	->setFields( [ "emp_no" => Field::INT(),
                    "birth_date" => Field::Date("d/m/Y"),
                    "name" => Field::STRING(),
                    "hire_date" => Field::Date("d/m/Y")
                ]);
        $input = (new Model("employees1"))
            ->setDB($this->db)
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
        $output = (new CSV ("testCSVOut.csv"))
        	->setFields( [ "emp_no" => Field::INT(),
                    "birth_date" => Field::Date("d/m/Y"),
                    "name" => Field::STRING(),
                    "hire_date" => Field::Date("d/m/Y")
                ]);
        	
        $input = (new Model("employees1"))
            ->setDB($this->db)
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
        $output = (new CSV("testCSVOut.csv"))
        	->setFields( [ "emp_no" => Field::INT(),
        		"birth_date" => Field::Date("d/m/Y"),
                "name" => Field::STRING(),
                "hire_date" => Field::Date("d/m/Y")
           	]);
        $input = (new Model("employees1"))
            ->setDB($this->db)
            ->filter("emp_no = 3")
            ->modify (function(&$data) {
                	$data['name'] = $data['first_name'] . " " . $data['last_name'];
                	return Entity::CONTINUE_PROCESSING; 
            	})
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
        $output = (new CSV ("testCSVOut.csv"))
        	->setFields( [ "emp_no" => Field::INT(),
                    "birth_date" => Field::Date("d/m/Y"),
                    "name" => Field::STRING(),
                    "hire_date" => Field::Date("d/m/Y")
                ]);
        $input = (new Model("employees1"))
            ->setDB($this->db)
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
            ->indexBy("emp_no = :emp_no");
        
        $dept = (new Model("dept_emp2"))
            ->setDB($this->db);
        
        $output = (new CSV ("testCSVOut.csv"))
            ->setFields( [ "emp_emp_no" => Field::INT(),
                    "emp_birth_date" => Field::Date("d/m/Y"),
                    "emp_first_name" => Field::STRING(),
                    "emp_hire_date" => Field::Date("d/m/Y"),
                    "dept_no" => Field::STRING()
                ]);
        
        $dept
            ->join($employee, ['emp_no'], "emp_")
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
            ->setDB($this->db);
        
        $dept = (new Model("dept_emp2"))
            ->setDB($this->db)
            ->indexBy("emp_no = :emp_no")
            ->orderBy(["from_date" => Entity::DESC])
            ->setLimit(1);
        
        $output = (new CSV ("testCSVOut.csv"))
            ->setFields( [ "emp_no" => Field::INT(),
                    "birth_date" => Field::Date("d/m/Y"),
                    "first_name" => Field::STRING(),
                    "hire_date" => Field::Date("d/m/Y"),
                    "dept_dept_no" => Field::STRING(),
                    "dept_from_date" => Field::Date("d/m/Y")
                ]);
        
        $employee
        	->join($dept, ['emp_no'], "dept_")
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
        $output = (new CSV ("testCSVOut.csv"))
        	->setFields( [ "emp_no" => Field::INT(),
                    "birth_date" => Field::Date("d/m/Y"),
                    "name" => Field::STRING(),
                    "hire_date" => Field::Date("d/m/Y")
                ]);
        	
        $input = (new Model("employees1"))
            ->setDB($this->db)
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
    
    public function testUpdate()   {
    	$csv = <<<TESTDATA
id,first_name,dob
3,Nigell2,02/08/1966
TESTDATA;
    	file_put_contents("test.csv", $csv);
    	
    	$emp = (new Model("employees1"))
    		->setDB($this->db)
	    	->setFields( [ "first_name" => Field::STRING(),
	    			"birth_date" => Field::Date("Y/m/d")
	    	])
	    	->filter("emp_no = :emp_no");
    	
	    $input = (new CSV("test.csv" ))
	    	->setFields( [ "emp_no" => Field::INT(),
    					"first_name" => Field::STRING(),
    					"birth_date" => Field::Date("d/m/Y")
    		]);
    	
    	$input->update($emp)
    		->transfer();

    	$row= [
    				[ 'emp_no' => "1", 'first_name' => 'Fred', "birth_date" => "1980-01-01" ],
    				[ 'emp_no' => "2", 'first_name' => 'Fred2', "birth_date" => "1980-01-02" ],
    				[ 'emp_no' => "3", 'first_name' => 'Nigell2', "birth_date" => "1966-08-02" ],
    				[ 'emp_no' => "4", 'first_name' => 'Fred4', "birth_date" => "1980-01-04" ],
    		];
    	$queryTable = $this->select(
    				'select emp_no, first_name, birth_date
                            FROM employees1'
    				);
    	$this->assertEquals($row, $queryTable);
    		
    	unlink("test.csv");
    }
   
    public function testUpdateNoWhere()   {
    	$csv = <<<TESTDATA
id,first_name,dob
3,Nigell2,02/08/1966
TESTDATA;
    	file_put_contents("test.csv", $csv);
    	
    	$emp = (new Model("employees1"))
	    	->setDB($this->db)
	    	->setFields( [ "first_name" => Field::STRING(),
	    			"birth_date" => Field::Date("Y/m/d")
	    	]);
    	
    	$input = (new CSV("test.csv" ))
	    	->setFields( [ "emp_no" => Field::INT(),
	    			"first_name" => Field::STRING(),
	    			"birth_date" => Field::Date("d/m/Y")
	    	]);
    	
	    $input->extract(["first_name", "birth_date"])
	    	->update($emp)
    		->transfer();
    	
    	$row= [
    				[ 'emp_no' => "1", 'first_name' => 'Nigell2', "birth_date" => "1966-08-02" ],
    					[ 'emp_no' => "2", 'first_name' => 'Nigell2', "birth_date" => "1966-08-02" ],
    					[ 'emp_no' => "3", 'first_name' => 'Nigell2', "birth_date" => "1966-08-02" ],
    					[ 'emp_no' => "4", 'first_name' => 'Nigell2', "birth_date" => "1966-08-02" ],
    			];
    	$queryTable = $this->select(
    			'select emp_no, first_name, birth_date
                            FROM employees1'
    			);
    	$this->assertEquals($row, $queryTable);
    	
    	unlink("test.csv");
    }
    
    public function testCreate()   {
    	$csv = <<<TESTDATA
id,first_name,dob
3,Nigell2,02/08/1966
132,Nigell2,02/08/1966
TESTDATA;
    	
    	file_put_contents("test.csv", $csv);
    	
    	$employee = (new Model("employees2"))
    		->setDB($this->db)
	    	->setFields( [ "emp_no" => Field::INT(),
	    			"birth_date" => Field::Date("Y/m/d"),
	    			"first_name" => Field::STRING(100),
	    			"hire_date" => Field::Date("Y/m/d"),
	    			"dept_dept_no" => Field::STRING(100),
	    			"dept_from_date" => Field::Date("Y/m/d")
	    	])
	    	->drop()
	    	->create();
    	
	    $input = (new CSV("test.csv"))
	    	->setFormatedFields( [
	    			"dob" => Field::Date("d/m/Y")
	    	])
	    	->map([ "id" => "emp_no",
	    			"dob" => "birth_date"
	    	])
	    	->set( [ 'hire_date' => '2010/01/01',
	    			'dept_dept_no' => "a1",
	    			'dept_from_date' => '2010/01/02',
	    	]
	    			)
	    	->saveTo($employee)
	    	->transfer();
    	    	
    	$row=[
    					[ 'emp_no' => "3", 'first_name' => 'Nigell2', "birth_date" => "1966-08-02",
    							'hire_date' => '2010-01-01', 'dept_dept_no' => "a1",
    							'dept_from_date' => '2010-01-02'
    					],
    					[ 'emp_no' => "132", 'first_name' => 'Nigell2', "birth_date" => "1966-08-02",
    							'hire_date' => '2010-01-01', 'dept_dept_no' => "a1",
    							'dept_from_date' => '2010-01-02']
    			];
    	$queryTable = $this->select(
    			'select emp_no, first_name, birth_date, hire_date, dept_dept_no, dept_from_date
                            FROM employees2'
    			);
    	$this->assertEquals($row, $queryTable);
    	
    	unlink("test.csv");
    }
    
    public function testCreateValidationErrors()   {
    	$csv = <<<TESTDATA
id,first_name,dob
3,Nigell2,02/08/1966
132,Nigell2,02/08/19661
TESTDATA;
    	
    	file_put_contents("test.csv", $csv);
    	
    	$employee = (new Model("employees2"))
	    	->setDB($this->db)
	    	->setFields( [ "emp_no" => Field::INT(),
	    			"birth_date" => Field::Date("Y/m/d"),
	    			"first_name" => Field::STRING(100),
	    			"hire_date" => Field::Date("Y/m/d"),
	    			"dept_dept_no" => Field::STRING(100),
	    			"dept_from_date" => Field::Date("Y/m/d")
	    	])
	    	->drop()
	    	->create();
    	
    	$input = (new CSV("test.csv"))
	    	->setFormatedFields( [
	    			"dob" => Field::Date("d/m/Y")
	    	])
	    	->map([ "id" => "emp_no",
	    			"dob" => "birth_date"
	    	])
	    	->set( [ 'hire_date' => '2010/01/01',
	    			'dept_dept_no' => "a1",
	    			'dept_from_date' => '2010/01/02',
	    	]
	    			)
    		->saveTo($employee)
    		->transfer();
    			
    	$row=[
    			[ 'emp_no' => "3", 'first_name' => 'Nigell2', "birth_date" => "1966-08-02",
    				'hire_date' => '2010-01-01', 'dept_dept_no' => "a1",
    				'dept_from_date' => '2010-01-02'
    			]
    		];
    	$queryTable = $this->select(
    		'select emp_no, first_name, birth_date, hire_date, dept_dept_no, dept_from_date
                            FROM employees2'
    		);
    	$this->assertEquals($row, $queryTable);
    			
    	unlink("test.csv");
    }
    
    public function testDirectLoad()   {
    	$csv = <<<TESTDATA
id,first_name,dob
3,Nigell2,02/08/1966
132,Nigell2,02/08/1966
TESTDATA;
    	
    	file_put_contents("test.csv", $csv);
    	
    	$employee = (new Model("employees2"))
	    	->setDB($this->db)
	    	->setFields( [ "emp_no" => Field::INT(),
	    			"birth_date" => Field::Date("Y/m/d"),
	    			"first_name" => Field::STRING(100),
	    			"hire_date" => Field::Date("Y/m/d"),
	    			"dept_dept_no" => Field::STRING(100),
	    			"dept_from_date" => Field::Date("Y/m/d")
	    	])
	    	->drop()
	    	->create();
    	
    	$input = (new CSV("test.csv"))
	    	->setFormatedFields( [
	    			"dob" => Field::Date("d/m/Y")
	    	])
	    	->map([ "id" => "emp_no",
	    			"dob" => "birth_date"
	    	])
	    	->set( [ 'hire_date' => '2010/01/01',
	    			'dept_dept_no' => "a1",
	    			'dept_from_date' => '2010/01/02',
	    	])
    		->saveTo($employee)
    		->transfer();
    			
 		$row=[
    			[ 'emp_no' => "3", 'first_name' => 'Nigell2', "birth_date" => "1966-08-02",
    					'hire_date' => '2010-01-01', 'dept_dept_no' => "a1",
    					'dept_from_date' => '2010-01-02'
    			],
    			[ 'emp_no' => "132", 'first_name' => 'Nigell2', "birth_date" => "1966-08-02",
    					'hire_date' => '2010-01-01', 'dept_dept_no' => "a1",
    					'dept_from_date' => '2010-01-02']
    			];
 		$queryTable = $this->select(
 				'select emp_no, first_name, birth_date, hire_date, dept_dept_no, dept_from_date
                            FROM employees2'
    			);
    	$this->assertEquals($row, $queryTable);
    			
    	unlink("test.csv");
    }
    
}