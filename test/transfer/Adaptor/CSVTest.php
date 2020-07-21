<?php

use PHPUnit\Framework\TestCase;
use Pleb\transfer\Adaptor\CSV;
use Pleb\transfer\Adaptor\Transient;
use Pleb\transfer\Field\Field;
use Pleb\transfer\Entity;
use Pleb\transfer\Adaptor\Generator;

require_once __DIR__ . '/../../../vendor/autoload.php';

class CSVTest extends TestCase   {
    public function testModify () {
        $csv = <<<TESTDATA
id,name,dob,address 1,address 2, address 3,shoe size
11,Fred,1/1/1980,1 Somewhere,Somehow,Somewhy,4
TESTDATA;
        
        $csvOutput = <<<TESTDATA
"shoe size",id,dob,name
4,13,1980/01/01,Fred

TESTDATA;
        file_put_contents("testCSV.csv", $csv);

        $output = (new CSV("testCSVOut.csv"))
        	->setFields( [ "shoe size" => Field::INT(), "id" => Field::INT(),
                "dob" => Field::Date("Y/m/d"), "name" => Field::STRING()
            ]);
        	
        $input = (new CSV("testCSV.csv"))
        	->setFields( [ "id" => Field::INT(), "name" => Field::STRING(),
        			"dob" => Field::Date("d/m/Y"), "address 1" => Field::STRING(),
        			"address 2" => Field::STRING(), "address 3" => Field::STRING(),
        			"shoe size" => Field::INT()
        	])
        	->modify (function(&$data) { $data['id'] += 2; 
                    return Entity::CONTINUE_PROCESSING; })
            ->saveTo($output)
            ->transfer();
        
        $this->assertEquals($csvOutput, file_get_contents("testCSVOut.csv") );
        
        unlink("testCSV.csv");
        unlink("testCSVOut.csv");
            
    }

    public function testFileNotFound () {
    	$filename = "testCSV.csv";
    	if ( file_exists($filename) ){
    		unlink($filename);
    	}
    	$this->expectException(\RuntimeException::class);
    	
    	$output = (new CSV("testCSVOut.csv"))
	    	->setFields( [ "shoe size" => Field::INT(), "id" => Field::INT(),
	    			"dob" => Field::Date("Y/m/d"), "name" => Field::STRING()
	    	]);
    	
    	$input = (new CSV("testCSV.csv"))
	    	->setFields( [ "id" => Field::INT(), "name" => Field::STRING(),
	    			"dob" => Field::Date("d/m/Y"), "address 1" => Field::STRING(),
	    			"address 2" => Field::STRING(), "address 3" => Field::STRING(),
	    			"shoe size" => Field::INT()
	    	])
	    	->modify (function(&$data) { $data['id'] += 2;
	    		return Entity::CONTINUE_PROCESSING; })
	    	->saveTo($output)
	    	->transfer();
    	
    }
    
    public function testWriteToNonSink () {
    	$this->expectException(\InvalidArgumentException::class);
    	
    	$output = new class() extends Entity{};
    	
    	$input = (new CSV("testCSV.csv"))
	    	->setFields( [ "id" => Field::INT(), "name" => Field::STRING(),
	    			"dob" => Field::Date("d/m/Y"), "address 1" => Field::STRING(),
	    			"address 2" => Field::STRING(), "address 3" => Field::STRING(),
	    			"shoe size" => Field::INT()
	    	])
	    	->modify (function(&$data) { $data['id'] += 2;
		    	return Entity::CONTINUE_PROCESSING; })
		    ->saveTo($output)
		    ->transfer();
    	
    }
    
    public function testInvalidFields ()	{
    	$this->expectException(\RuntimeException::class);
    	
    	$output = (new CSV("testCSVOut.csv"))
    	->setFields( [ "shoe size" => "a", "id" => Field::INT(),
    			"dob" => Field::Date("Y/m/d"), "name" => Field::STRING()
    	]);
    }
    
    public function testHeaders () {
        $csv = <<<TESTDATA
id,name,dob,address 1,address 2, address 3,shoe size
11,Fred,1/1/1980,1 Somewhere,Somehow,Somewhy,4
TESTDATA;
        
        $csvOutput = <<<TESTDATA
id,name,dob,"address 1","address 2","address 3","shoe size"
13,Fred,1/1/1980,"1 Somewhere",Somehow,Somewhy,4

TESTDATA;
        file_put_contents("testCSV.csv", $csv);
        
        $input = (new CSV("testCSV.csv"))
            ->modify (function(&$data) { $data['id'] += 2; 
                    return Entity::CONTINUE_PROCESSING; })
            ->saveTo(new CSV("testCSVOut.csv"))
            ->transfer();
        
        $this->assertEquals($csvOutput, file_get_contents("testCSVOut.csv"));
        
        unlink("testCSV.csv");
        unlink("testCSVOut.csv");
        
    }
 
    public function testHeadersExtract () {
        $csv = <<<TESTDATA
id,name,dob,address 1,address 2, address 3,shoe size
11,Fred,1/1/1980,1 Somewhere,Somehow,Somewhy,4
TESTDATA;
        
        $csvOutput = <<<TESTDATA
id,name,dob,"address 1","shoe size"
11,Fred,1/1/1980,"1 Somewhere",4

TESTDATA;
        file_put_contents("testCSV.csv", $csv);
        
        $input = (new CSV("testCSV.csv"))
            ->extract( ["id", "name", "dob", "address 1", "shoe size"])
            ->saveTo(new CSV("testCSVOut.csv"))
            ->transfer();
        
        $this->assertEquals($csvOutput, file_get_contents("testCSVOut.csv"));
        
        unlink("testCSV.csv");
        unlink("testCSVOut.csv");
        
    }
    
    public function testLimit () {
        $csv = <<<TESTDATA
id,name,dob,address 1,address 2, address 3,shoe size
11,Fred,1/1/1980,1 Somewhere,Somehow,Somewhy,4
12,Fred,1/1/1980,1 Somewhere,Somehow,Somewhy,41
13,Fred,1/1/1980,1 Somewhere,Somehow,Somewhy,42
14,Fred,1/1/1980,1 Somewhere,Somehow,Somewhy,43
TESTDATA;
        
        $csvOutput = <<<TESTDATA
"shoe size",id,dob,name
4,13,1980/01/01,Fred
41,14,1980/01/01,Fred

TESTDATA;
        file_put_contents("testCSV.csv", $csv);
        
        $input = (new CSV("testCSV.csv"))
        	->setFields( [ "id" => Field::INT(), "name" => Field::STRING(),
                    "dob" => Field::Date("d/m/Y"), "address 1" => Field::STRING(),
                    "address 2" => Field::STRING(), "address 3" => Field::STRING(),
                    "shoe size" => Field::INT()
                ]);
        	
        
        $output = (new CSV("testCSVOut.csv"))
        	->setFields( [ "shoe size" => Field::INT(), "id" => Field::INT(),
                    "dob" => Field::Date("Y/m/d"), "name" => Field::STRING()
                ]);
        	
        $input
            ->setLimit(2)
            ->modify (function(&$data) { $data['id'] += 2; 
                    return Entity::CONTINUE_PROCESSING; })
            ->saveTo($output)
            ->transfer();
        
        $this->assertEquals($csvOutput, file_get_contents("testCSVOut.csv"));
            
        unlink("testCSV.csv");
        unlink("testCSVOut.csv");
        
    }
    
    public function testLimitAndOffset () {
        $csv = <<<TESTDATA
id,name,dob,address 1,address 2, address 3,shoe size
11,Fred,1/1/1980,1 Somewhere,Somehow,Somewhy,4
12,Fred,1/1/1980,1 Somewhere,Somehow,Somewhy,41
13,Fred,1/1/1980,1 Somewhere,Somehow,Somewhy,42
14,Fred,1/1/1980,1 Somewhere,Somehow,Somewhy,43
TESTDATA;
        
        $csvOutput = <<<TESTDATA
"shoe size",id,dob,name
41,14,1980/01/01,Fred
42,15,1980/01/01,Fred

TESTDATA;
        file_put_contents("testCSV.csv", $csv);
        
        $input = (new CSV("testCSV.csv"))
        	->setFields( [ "id" => Field::INT(), "name" => Field::STRING(),
                    "dob" => Field::Date("d/m/Y"), "address 1" => Field::STRING(),
                    "address 2" => Field::STRING(), "address 3" => Field::STRING(),
                    "shoe size" => Field::INT()
                ]);
        
        $output = (new CSV("testCSVOut.csv"))
        	->setFields( [ "shoe size" => Field::INT(), "id" => Field::INT(),
                    "dob" => Field::Date("Y/m/d"), "name" => Field::STRING()
                ]);
        	
        $input
            ->setLimit(2,1)
            ->modify (function(&$data) { $data['id'] += 2; 
                    return Entity::CONTINUE_PROCESSING; })
            ->saveTo($output)
            ->transfer();
        
        $this->assertEquals($csvOutput, file_get_contents("testCSVOut.csv"));
            
        unlink("testCSV.csv");
        unlink("testCSVOut.csv");
        
    }
    
    public function testMap () {
        $csv = <<<TESTDATA
id,name,dob,address 1,address 2, address 3,shoe size
11,Fred,1/1/1980,1 Somewhere,Somehow,Somewhy,4
TESTDATA;
        
        $csvOutput = <<<TESTDATA
"shoe size",emp_id,id,dob,name
4,11,,1980/01/01,Fred

TESTDATA;
        file_put_contents("testCSV.csv", $csv);
        
        $output = (new CSV("testCSVOut.csv"))
	        ->setFields( [ "shoe size" => Field::INT(),
	        		"emp_id" => Field::INT(), "id" => Field::INT(),
	        		"dob" => Field::Date("Y/m/d"), "name" => Field::STRING()
	        ]);
        
        $input = (new CSV("testCSV.csv"))
	        ->setFields( [ "id" => Field::INT(), "name" => Field::STRING(),
	        		"dob" => Field::Date("d/m/Y"), "address 1" => Field::STRING(),
	        		"address 2" => Field::STRING(), "address 3" => Field::STRING(),
	        		"shoe size" => Field::INT()
	        ])
	        ->map(["id" => "emp_id"])
	        ->saveTo($output)
	        ->transfer();
        
	    $this->assertEquals($csvOutput, file_get_contents("testCSVOut.csv"));
	        
        unlink("testCSV.csv");
        unlink("testCSVOut.csv");
        
    }
    
    public function testSet () {
    	$csv = <<<TESTDATA
id,name,dob,address 1,address 2, address 3,shoe size
11,Fred,1/1/1980,1 Somewhere,Somehow,Somewhy,4
TESTDATA;
    	
    	$csvOutput = <<<TESTDATA
"shoe size",id,dob,name,date_added
4,11,1980/01/01,Fred,4/6/2020

TESTDATA;
    	file_put_contents("testCSV.csv", $csv);
    	
    	$output = (new CSV("testCSVOut.csv"))
	    	->setFields( [ "shoe size" => Field::INT(),
	    			"id" => Field::INT(),
	    			"dob" => Field::Date("Y/m/d"), 
	    			"name" => Field::STRING(),
	    			"date_added" => Field::STRING()
	    	]);
    	
    	$input = (new CSV("testCSV.csv"))
	    	->setFields( [ "id" => Field::INT(), "name" => Field::STRING(),
	    			"dob" => Field::Date("d/m/Y"), "address 1" => Field::STRING(),
	    			"address 2" => Field::STRING(), "address 3" => Field::STRING(),
	    			"shoe size" => Field::INT()
	    	])
	    	->set(["date_added" => "4/6/2020"])
	    	->saveTo($output)
	    	->transfer();
    	
	   	$this->assertEquals($csvOutput, file_get_contents("testCSVOut.csv"));
	    	
    	unlink("testCSV.csv");
    	unlink("testCSVOut.csv");
    	
    }
    
    public function testFilter () {
        $csv = <<<TESTDATA
id,name,dob,address 1,address 2, address 3,shoe size
11,Fred,1/1/1980,1 Somewhere,Somehow,Somewhy,4
12,Fred,1/1/1980,1 Somewhere,Somehow,Somewhy,41
13,Fred,1/1/1980,1 Somewhere,Somehow,Somewhy,42
14,Fred,1/1/1980,1 Somewhere,Somehow,Somewhy,43
TESTDATA;
        
        $csvOutput = <<<TESTDATA
"shoe size",id,dob,name
41,14,1980/01/01,Fred
42,15,1980/01/01,Fred

TESTDATA;
        file_put_contents("testCSV.csv", $csv);
        
        $input = (new CSV("testCSV.csv"))
        	->setFields( [ "id" => Field::INT(), "name" => Field::STRING(),
                    "dob" => Field::Date("d/m/Y"), "address 1" => Field::STRING(),
                    "address 2" => Field::STRING(), "address 3" => Field::STRING(),
                    "shoe size" => Field::INT()
                ]);
        
        $output = (new CSV("testCSVOut.csv"))
        	->setFields( [ "shoe size" => Field::INT(), "id" => Field::INT(),
                    "dob" => Field::Date("Y/m/d"), "name" => Field::STRING()
                ]);
        	
        $input
            ->filter(function($data) { 
                return $data['id'] == 12 || $data['id'] == 13; 
            })
            ->modify (function(&$data) { $data['id'] += 2; 
                    return Entity::CONTINUE_PROCESSING; })
            ->saveTo($output)
            ->transfer();
        
        $this->assertEquals($csvOutput, file_get_contents("testCSVOut.csv"));
            
        unlink("testCSV.csv");
        unlink("testCSVOut.csv");
        
    }
 
    public function testFilterNonCallable () {
    	$csv = <<<TESTDATA
id,name,dob,address 1,address 2, address 3,shoe size
11,Fred,1/1/1980,1 Somewhere,Somehow,Somewhy,4
12,Fred,1/1/1980,1 Somewhere,Somehow,Somewhy,41
13,Fred,1/1/1980,1 Somewhere,Somehow,Somewhy,42
14,Fred,1/1/1980,1 Somewhere,Somehow,Somewhy,43
TESTDATA;
    	
    	$csvOutput = <<<TESTDATA
"shoe size",id,dob,name
41,14,1980/01/01,Fred
42,15,1980/01/01,Fred

TESTDATA;
    	file_put_contents("testCSV.csv", $csv);
    	
    	$this->expectException(\InvalidArgumentException::class);
    	
    	$input = (new CSV("testCSV.csv"))
	    	->setFields( [ "id" => Field::INT(), "name" => Field::STRING(),
	    			"dob" => Field::Date("d/m/Y"), "address 1" => Field::STRING(),
	    			"address 2" => Field::STRING(), "address 3" => Field::STRING(),
	    			"shoe size" => Field::INT()
	    	]);
    	
    	$output = (new CSV("testCSVOut.csv"))
	    	->setFields( [ "shoe size" => Field::INT(), "id" => Field::INT(),
	    			"dob" => Field::Date("Y/m/d"), "name" => Field::STRING()
	    	]);
	    	
    	$input
	    	->filter("Bert")
	    	->modify (function(&$data) { $data['id'] += 2;
	    		return Entity::CONTINUE_PROCESSING; })
	    	->saveTo($output)
	    	->transfer();
    	
    	unlink("testCSV.csv");
    	unlink("testCSVOut.csv");
    	
    }
    
    public function testSum()   {
        $csv = <<<TESTDATA
id,name,qty,qty2
11,abc,4,41
11,abc,4,42
11,abc,4,43
12,abc,4,44
TESTDATA;
        
        $csvOutput = <<<TESTDATA
id,sumqty2,sumqty
11,126,12
12,44,4

TESTDATA;
        file_put_contents("testCSV.csv", $csv);
        
        $input = (new CSV("testCSV.csv"))
        	->setFields( [ "id" => Field::INT(), "name" => Field::STRING(),
                    "qty" => Field::INT(),
                    "qty2" => Field::INT()
                ]);
        
        $output = (new CSV("testCSVOut.csv"))
        	->setFields( [ "id" => Field::INT(),"sumqty2" => Field::INT(),
                    "sumqty" => Field::INT()
                ]);
        	
        $input
            ->sum(['qty' => 'sumqty', 'qty2' => 'sumqty2'])
            ->groupBy(['id'])
            ->saveTo($output)
            ->transfer();
            
        $this->assertEquals($csvOutput, file_get_contents("testCSVOut.csv"));
            
        unlink("testCSV.csv");
        unlink("testCSVOut.csv");
    }
    
    public function testSumShort()   {
        $csv = <<<TESTDATA
id,name,qty,qty2
11,abc,4,41
11,abc,4,42
11,abc,4,43
12,abc,4,44
TESTDATA;
        
        $csvOutput = <<<TESTDATA
id,sumqty,sumqty2
11,12,126
12,4,44

TESTDATA;
        file_put_contents("testCSV.csv", $csv);
        
        $input = new CSV("testCSV.csv");
        $input
            ->sum(['qty' => 'sumqty', 'qty2' => 'sumqty2'])
            ->groupBy(['id'])
            ->saveTo(new CSV("testCSVOut.csv"))
            ->transfer();
        
        $this->assertEquals($csvOutput, file_get_contents("testCSVOut.csv"));
            
        unlink("testCSV.csv");
        unlink("testCSVOut.csv");
    }
    
//     public function testCount()   {
//     	$csv = <<<TESTDATA
// id,name,qty,qty2
// 11,abc,4,41
// 11,abc2,4,42
// 11,abc,4,43
// 12,abc,4,44
// TESTDATA;
    	
//     	$csvOutput = <<<TESTDATA
// id
// 3
// 1

// TESTDATA;
//     	file_put_contents("testCSV.csv", $csv);
    	
//     	$input = (new CSV("testCSV.csv"))
//     		->count("name")
// 	    	->groupBy(['id'])
// 	    	->saveTo(new CSV("testCSVOut.csv"))
// 	    	->transfer();
    	
//     	$this->assertEquals($csvOutput, file_get_contents("testCSVOut.csv"));
    	
//     	unlink("testCSV.csv");
//     	unlink("testCSVOut.csv");
//     }
    
    public function testJoin()   {
        $csv = <<<TESTDATA
id,name,qty,qty2
1,abc1,4,41
2,abc2,4,42
3,abc3,4,43
4,abc4,4,44
TESTDATA;
        
        file_put_contents("testProductsCSV.csv", $csv);
        $csv1 = <<<TESTDATA
order,productid,qty
1001,1,2
1002,3,2
1003,4,2
TESTDATA;
        
        file_put_contents("testCSV.csv", $csv1);
        $csvOutput = <<<TESTDATA
order,productid,product_name,qty
1001,1,abc1,2
1002,3,abc3,2
1003,4,abc4,2

TESTDATA;
        
        $orders = new CSV("testCSV.csv");
        $output = (new CSV("testCSVOut.csv"))
        	->setFields( [ "order" => Field::INT(),"productid" => Field::INT(),
                    "product_name" => Field::STRING(),
                    "qty" => Field::INT()
                ]);
        	
        $productLookup = (new CSV("testProductsCSV.csv"))
            ->indexBy(["id"]);
        
        // Use this lookup
        $orders
            ->join($productLookup, ['productid'], "product_")
            ->saveTo($output)
            ->transfer();
        
        $this->assertEquals($csvOutput, file_get_contents("testCSVOut.csv"));
            
        unlink("testProductsCSV.csv");
        unlink("testCSV.csv");
        unlink("testCSVOut.csv");
    }
  
    public function testLeftJoin()   {
    	$csv = <<<TESTDATA
id,name,qty,qty2
1,abc1,4,41
2,abc2,4,42
3,abc3,4,43
4,abc4,4,44
TESTDATA;
    	
    	file_put_contents("testProductsCSV.csv", $csv);
    	$csv1 = <<<TESTDATA
order,productid,qty
1001,9,2
1002,3,2
1003,7,2
TESTDATA;
    	
    	file_put_contents("testCSV.csv", $csv1);
    	$csvOutput = <<<TESTDATA
order,productid,product_name,qty
1001,9,,2
1002,3,abc3,2
1003,7,,2

TESTDATA;
    	
    	$orders = new CSV("testCSV.csv");
    	$output = (new CSV("testCSVOut.csv"))
    		->setFields( [ "order" => Field::INT(),"productid" => Field::INT(),
    					"product_name" => Field::STRING(),
    					"qty" => Field::INT()
    			]);
    		
    	$productLookup = (new CSV("testProductsCSV.csv"))
    		->indexBy(["id"]);
    	
    	// Use this lookup
    	$orders
    		->leftJoin($productLookup, ['productid'], "product_")
    		->saveTo($output)
    		->transfer();
    	
    	$this->assertEquals($csvOutput, file_get_contents("testCSVOut.csv"));
    		
    	unlink("testProductsCSV.csv");
    	unlink("testCSV.csv");
    	unlink("testCSVOut.csv");
    }
    
    public function testSplit () {
        $csv = <<<TESTDATA
id,name,dob,address 1,address 2, address 3,shoe size
11,Fred,1/1/1980,1 Somewhere,Somehow,Somewhy,4
12,Fred1,1/1/1980,1 Somewhere,Somehow,Somewhy,4
TESTDATA;
        
        $csvOutput = <<<TESTDATA
"shoe size",id,dob,name
4,11,1980/01/01,Fred

TESTDATA;
        $csvOutput1 = <<<TESTDATA
"shoe size",id,dob,name
4,12,1980/01/01,Fred1

TESTDATA;
        file_put_contents("testCSV.csv", $csv);
        
        $input = (new CSV("testCSV.csv"))
        	->setFields( [ "id" => Field::INT(), "name" => Field::STRING(),
                    "dob" => Field::Date("d/m/Y"), "address 1" => Field::STRING(),
                    "address 2" => Field::STRING(), "address 3" => Field::STRING(),
                    "shoe size" => Field::INT()
                ]);
        
        $output = (new CSV("testCSVOut.csv"))
        	->setFields( [ "shoe size" => Field::INT(), "id" => Field::INT(),
                    "dob" => Field::Date("Y/m/d"), "name" => Field::STRING()
                ]);
        	
        $output1 = (new CSV("testCSVOut1.csv"))
        	->setFields( [ "shoe size" => Field::INT(), "id" => Field::INT(),
                    "dob" => Field::Date("Y/m/d"), "name" => Field::STRING()
                ]);
        	
        $input
            ->split(function($data) { return $data['id'] > 11; }, 
        		(new Transient())->saveTo($output1))
            ->saveTo($output)
            ->transfer();
        
        $this->assertTrue(file_exists("testCSVOut.csv"));
        $this->assertTrue(file_exists("testCSVOut1.csv"));
        $this->assertEquals($csvOutput, file_get_contents("testCSVOut.csv"));
        $this->assertEquals($csvOutput1, file_get_contents("testCSVOut1.csv"));
        
        unlink("testCSV.csv");
        unlink("testCSVOut.csv");
        unlink("testCSVOut1.csv");
        
    }
    
    public function testOrderBy()   {
        $csv = <<<TESTDATA
id,name,qty,qty2
11,abca,4,43
11,abcb,4,41
12,abcc,4,44
11,abcd,4,42
TESTDATA;
        
        $csvOutput = <<<TESTDATA
id,name,qty,qty2
12,abcc,4,44
11,abcb,4,41
11,abcd,4,42
11,abca,4,43

TESTDATA;
        file_put_contents("testCSV.csv", $csv);
        
        $input = (new CSV("testCSV.csv"))
        	->setFields( [ "id" => Field::INT(), "name" => Field::STRING(),
                    "qty" => Field::INT(),
                    "qty2" => Field::INT()
                ]);
        
        $input
            ->orderBy(["id" => Entity::DESC, "qty2" => Entity::ASC])
            ->saveTo(new CSV("testCSVOut.csv"))
            ->transfer();
        
        $this->assertEquals($csvOutput, file_get_contents("testCSVOut.csv"));
        
        unlink("testCSV.csv");
        unlink("testCSVOut.csv");
    }
    
    public function testOrderByInvalidType()   {
    	$csv = <<<TESTDATA
id,name,qty,qty2
11,abca,4,43
11,abcb,4,41
12,abcc,4,44
11,abcd,4,42
TESTDATA;
    	
    	$csvOutput = <<<TESTDATA
id,name,qty,qty2
12,abcc,4,44
11,abcb,4,41
11,abcd,4,42
11,abca,4,43

TESTDATA;
    	file_put_contents("testCSV.csv", $csv);
    	
    	$this->expectException(\InvalidArgumentException::class);
    	
    	$input = (new CSV("testCSV.csv"))
	    	->setFields( [ "id" => Field::INT(), "name" => Field::STRING(),
	    			"qty" => Field::INT(),
	    			"qty2" => Field::INT()
	    	]);
	    	
    	$input
    	// Should be "qty2" => Entity::ASC or DESC
	    	->orderBy(["id" => Entity::DESC, "qty2" => 6])
	    	->saveTo(new CSV("testCSVOut.csv"))
	    	->transfer();
    	
    	$this->assertEquals($csvOutput, file_get_contents("testCSVOut.csv"));
    	
    	unlink("testCSV.csv");
    	unlink("testCSVOut.csv");
    }
    
    public function testOrderByInvalidField()   {
    	$csv = <<<TESTDATA
id,name,qty,qty2
11,abca,4,43
11,abcb,4,41
12,abcc,4,44
11,abcd,4,42
TESTDATA;
    	
    	$csvOutput = <<<TESTDATA
id,name,qty,qty2
12,abcc,4,44
11,abcb,4,41
11,abcd,4,42
11,abca,4,43

TESTDATA;
    	file_put_contents("testCSV.csv", $csv);
    	
    	$this->expectException(\InvalidArgumentException::class);
    	
    	$input = (new CSV("testCSV.csv"))
	    	->setFields( [ "id" => Field::INT(), "name" => Field::STRING(),
	    			"qty" => Field::INT(),
	    			"qty2" => Field::INT()
	    	]);
    	
    	$input
	    	// Should be "qty2" => Entity::ASC
	    	->orderBy(["id" => Entity::DESC, "Bert" => Entity::ASC])
	    	->saveTo(new CSV("testCSVOut.csv"))
	    	->transfer();
    	
    	$this->assertEquals($csvOutput, file_get_contents("testCSVOut.csv"));
    	
    	unlink("testCSV.csv");
    	unlink("testCSVOut.csv");
    }
    
    public function testDelimtiterEnclosure()   {
        $csv = <<<TESTDATA
id|name|qty|qty2
11|abc|4|41
11|abc|4|#42#
TESTDATA;
        
        $csvOutput = <<<TESTDATA
id#sumqty#!sumqty 2!
11#8#83

TESTDATA;
        file_put_contents("testCSV.csv", $csv);
        
        $input = new CSV("testCSV.csv");
        $input
            ->setDelimeter("|")
            ->setEnclosure("#")
            ->sum(['qty' => 'sumqty', 'qty2' => 'sumqty 2'])
            ->groupBy(['id'])
            ->saveTo((new CSV("testCSVOut.csv"))                
                ->setDelimeter("#")
                ->setEnclosure("!"))
            ->transfer();
        
        $this->assertEquals($csvOutput, file_get_contents("testCSVOut.csv"));
        
        unlink("testCSV.csv");
        unlink("testCSVOut.csv");
    }
 
    public function testPush () {
        $inputData = [ "id" => 11, "name" => "Fred", "dob" => "1/1/1980",
            "address 1" => "Somewhere", "address 2" => "Somehow", 
            "address 3" => "Somewhy", "shoe size" => 4];
        
        $csvOutput = <<<TESTDATA
id,name,dob,"address 1","address 2","address 3","shoe size"
11,Fred,1/1/1980,Somewhere,Somehow,Somewhy,4

TESTDATA;
        $input = (new Transient())
            ->saveTo(new CSV("testCSVOut.csv"));
        
        $input->push($inputData);
        
        $this->assertEquals($csvOutput, file_get_contents("testCSVOut.csv"));
        
        unlink("testCSVOut.csv");
        
    }
    
    public function testProcessSimple1 () {
    	$csv = <<<TESTDATA
id,name,qty,qty2
11,abc,4,41
11,abc1,4,42
TESTDATA;
    	file_put_contents("testCSV.csv", $csv);
    	
    	$input = new CSV("testCSV.csv");

    	$subProc = (new Transient())
    		->modify (function(&$data) { $data['qty2'] += 2;
    			return Entity::CONTINUE_PROCESSING; 
    	});
    	
    	$csvOutput = <<<TESTDATA
id,name,qty,qty2
11,abc,4,41
11,abc1,4,44

TESTDATA;
    	$input->process( function ($v) { return $v['name'] == 'abc1';},$subProc	)
    		->saveTo(new CSV("testCSVOut.csv"))
    		->transfer();
    	
    	$this->assertEquals($csvOutput, file_get_contents("testCSVOut.csv"));
    	
    	unlink("testCSVOut.csv");
    	
    }
    
    /**
     * Process which saves the processed data to a separate output
     */
    public function testProcessSimple2 () {
    	$csv = <<<TESTDATA
id,name,qty,qty2
11,abc,4,41
11,abc1,4,42
TESTDATA;
    	file_put_contents("testCSV.csv", $csv);
    	file_put_contents("testCSVOut.csv", '');
    	file_put_contents("testCSVOut1.csv", '');
    	
    	
    	$csvOutput = <<<TESTDATA
id,name,qty,qty2
11,abc,4,41
11,abc1,4,44

TESTDATA;
    	$csvOutput1 = <<<TESTDATA
id,name,qty,qty2
11,abc1,4,44

TESTDATA;

    	$input = (new CSV("testCSV.csv"))
    		->process( function ($v) { return $v['name'] == 'abc1';},
		    	(new Transient())
		    	->modify (function(&$data) { $data['qty2'] += 2;
		    					return Entity::CONTINUE_PROCESSING;
	    		})
		    	->saveTo(new CSV("testCSVOut1.csv"))
	    		)
    		->saveTo(new CSV("testCSVOut.csv"))
    		->transfer();
    	
    	$this->assertEquals($csvOutput, file_get_contents("testCSVOut.csv"));
    	$this->assertEquals($csvOutput1, file_get_contents("testCSVOut1.csv"));
    	
    	unlink("testCSVOut.csv");
    	unlink("testCSVOut1.csv");
    	
    }
    
    public function testValidationErrorsNonNullable () {
    	$csv = <<<TESTDATA
id,first_name,dob
3a,Nigell2,02/08/19661
3,Nigell2,02/08/1966
4,Nigell2,
TESTDATA;
    	
    	$csvError = <<<TESTDATA
emp_no,first_name,birth_date,#Message
3a,Nigell2,02/08/19661,"emp_no:Invalid integer, birth_date:Invalid date"
4,Nigell2,,"birth_date:Invalid date"

TESTDATA;
    	$csvOutput = <<<TESTDATA
emp_no,first_name,birth_date
3,Nigell2,1966/08/02

TESTDATA;
    	file_put_contents("testCSV.csv", $csv);
    		    	
	    $input = (new CSV("testCSV.csv"))
	    	->setFields( [ "emp_no" => Field::INT(),
	    			"first_name" => Field::STRING(),
	    			"birth_date" => Field::Date("d/m/Y")
	    	])
	    	->validationErrors(new CSV("errors.csv"))
	    	->saveTo((new CSV("testCSVOut.csv"))
	    		->setFields( [ "emp_no" => Field::INT(),
	    			"first_name" => Field::STRING(),
	    			"birth_date" => Field::Date("Y/m/d")
	    	]))
	    	->transfer();
	    
    	$this->assertEquals($csvOutput, file_get_contents("testCSVOut.csv"));
    	$this->assertEquals($csvError, file_get_contents("errors.csv"));
    	
    	unlink("testCSV.csv");
    	unlink("testCSVOut.csv");
    	unlink("errors.csv");
    	
    }
    
    public function testValidationErrorsMixedNullable () {
    	$csv = <<<TESTDATA
id,first_name,dob
3a,Nigell2,02/08/19661
3,Nigell2,02/08/1966
4,Nigell2,
TESTDATA;
    	
    	$csvError = <<<TESTDATA
emp_no,first_name,birth_date,#Message
3a,Nigell2,02/08/19661,"emp_no:Invalid integer, birth_date:Invalid date"

TESTDATA;
    	$csvErrorOut = <<<TESTDATA
emp_no,first_name,birth_date,#Message
4,Nigell2,,"birth_date:Invalid date"

TESTDATA;
    	$csvOutput = <<<TESTDATA
emp_no,first_name,birth_date
3,Nigell2,1966/08/02

TESTDATA;
    	file_put_contents("testCSV.csv", $csv);
    	
    	$input = (new CSV("testCSV.csv"))
	    	->setFields( [ "emp_no" => Field::INT(),
	    			"first_name" => Field::STRING(),
	    			"birth_date" => Field::Date("d/m/Y")->nullable()
	    	])
	    	->validationErrors(new CSV("errors.csv"))
	    	->saveTo((new CSV("testCSVOut.csv"))
		    	->setFields( [ "emp_no" => Field::INT(),
		    			"first_name" => Field::STRING(),
		    			// Output date is not nullable
		    			"birth_date" => Field::Date("Y/m/d")
		    	])
		    	->validationErrors(new CSV("errorsout.csv")))
	    	->transfer();
    			
    	$this->assertEquals($csvError, file_get_contents("errors.csv"));
    	$this->assertEquals($csvErrorOut, file_get_contents("errorsout.csv"));
    	$this->assertEquals($csvOutput, file_get_contents("testCSVOut.csv"));
    	
    	unlink("testCSV.csv");
    	unlink("testCSVOut.csv");
    	unlink("errors.csv");
    	unlink("errorsout.csv");
    	
    }
    
    public function testValidationErrorsNullable () {
    	$csv = <<<TESTDATA
id,first_name,dob
3a,Nigell2,02/08/19661
3,Nigell2,02/08/1966
4,Nigell2,
TESTDATA;
    	
    	$csvError = <<<TESTDATA
emp_no,first_name,birth_date,#Message
3a,Nigell2,02/08/19661,"emp_no:Invalid integer, birth_date:Invalid date"

TESTDATA;
    	$csvOutput = <<<TESTDATA
emp_no,first_name,birth_date
3,Nigell2,1966/08/02
4,Nigell2,

TESTDATA;
    	file_put_contents("testCSV.csv", $csv);
    	
    	$input = (new CSV("testCSV.csv"))
    	->setFields( [ "emp_no" => Field::INT(),
    			"first_name" => Field::STRING(),
    			"birth_date" => Field::Date("d/m/Y")->nullable()
    	])
    	->validationErrors(new CSV("errors.csv"))
    	->saveTo((new CSV("testCSVOut.csv"))
    			->setFields( [ "emp_no" => Field::INT(),
    					"first_name" => Field::STRING(),
    					"birth_date" => Field::Date("Y/m/d")->nullable()
    			]))
    	->transfer();
    			
    	$this->assertEquals($csvOutput, file_get_contents("testCSVOut.csv"));
    	$this->assertEquals($csvError, file_get_contents("errors.csv"));
    	
    	unlink("testCSV.csv");
    	unlink("testCSVOut.csv");
    	unlink("errors.csv");
    			
    }
 
    public function testUpdateFail () {
    	$csv = <<<TESTDATA
id,name,dob,address 1,address 2, address 3,shoe size
11,Fred,1/1/1980,1 Somewhere,Somehow,Somewhy,4
TESTDATA;
    	
    	$csvOutput = <<<TESTDATA
"shoe size",id,dob,name
4,13,1980/01/01,Fred

TESTDATA;
    	file_put_contents("testCSV.csv", $csv);
    	
    	$this->expectException(\InvalidArgumentException::class);
    	
    	$output = (new CSV("testCSVOut.csv"))
	    	->setFields( [ "shoe size" => Field::INT(), "id" => Field::INT(),
	    			"dob" => Field::Date("Y/m/d"), "name" => Field::STRING()
	    	]);
    	
    	$input = (new CSV("testCSV.csv"))
	    	->setFields( [ "id" => Field::INT(), "name" => Field::STRING(),
	    			"dob" => Field::Date("d/m/Y"), "address 1" => Field::STRING(),
	    			"address 2" => Field::STRING(), "address 3" => Field::STRING(),
	    			"shoe size" => Field::INT()
	    	])
	    	->modify (function(&$data) { $data['id'] += 2;
	    		return Entity::CONTINUE_PROCESSING; })
	    	// CSV isn't updateable
	    	->update($output)
	    	->transfer();
    	
    	$this->assertEquals($csvOutput, file_get_contents("testCSVOut.csv"));
    	
    	unlink("testCSV.csv");
    	unlink("testCSVOut.csv");
    	
    }
    
    public function testGenerator () {
    	$csvOutput = <<<TESTDATA
id,name
2,"name 2"
3,"name 3"

TESTDATA;
    	$input = (new Generator(function ()	{
	    		for ( $i = 2; $i < 4; $i++ )	{
	    			yield [ "id" => $i, "name" => "name {$i}"];
	    		}
	    	}))
    		->saveTo(new CSV("testCSVOut.csv"))
    		->transfer();
    	
    	$this->assertEquals($csvOutput, file_get_contents("testCSVOut.csv") );
    	
    	unlink("testCSV.csv");
    	unlink("testCSVOut.csv");
    	
    }
    
}