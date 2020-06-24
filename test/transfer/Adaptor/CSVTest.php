<?php

use PHPUnit\Framework\TestCase;
use Pleb\transfer\Adaptor\CSV;
use Pleb\transfer\Adaptor\Transient;
use Pleb\transfer\Field\Field;
use Pleb\transfer\Entity;

require_once __DIR__ . '/../../../vendor/autoload.php';

class C1 extends CSV    {
    public function __call( $t1, $t2) {}
};

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

        $input = new class() extends CSV    {
            protected function configure()    {
                $this->setName ("testCSV.csv" );
                $this->setFields( [ "id" => Field::INT(), "name" => Field::STRING(),
                    "dob" => Field::Date("d/m/Y"), "address 1" => Field::STRING(),
                    "address 2" => Field::STRING(), "address 3" => Field::STRING(),
                    "shoe size" => Field::INT()
                ]);
            }
        };
    
        $output = new class() extends CSV   {
            protected function configure()    {
                $this->setName ("testCSVOut.csv" );
                $this->setFields( [ "shoe size" => Field::INT(), "id" => Field::INT(),
                    "dob" => Field::Date("Y/m/d"), "name" => Field::STRING()
                ]);
            }
        };
        $input
            ->modify (function(&$data) { $data['id'] += 2; 
                    return Entity::CONTINUE_PROCESSING; })
            ->saveTo($output)
            ->transfer();
        
        $output = file_get_contents("testCSVOut.csv");
        $this->assertEquals($output, $csvOutput);
        
        unlink("testCSV.csv");
        unlink("testCSVOut.csv");
            
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
        
        $input = new CSV("testCSV.csv");
        
        $input
            ->modify (function(&$data) { $data['id'] += 2; 
                    return Entity::CONTINUE_PROCESSING; })
            ->saveTo(new CSV("testCSVOut.csv"))
            ->transfer();
        
        $output = file_get_contents("testCSVOut.csv");
        $this->assertEquals($output, $csvOutput);
        
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
        
        $output = file_get_contents("testCSVOut.csv");
        $this->assertEquals($output, $csvOutput);
        
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
        
        $input = new class() extends CSV    {
            protected function configure()    {
                $this->setName ("testCSV.csv" );
                $this->setFields( [ "id" => Field::INT(), "name" => Field::STRING(),
                    "dob" => Field::Date("d/m/Y"), "address 1" => Field::STRING(),
                    "address 2" => Field::STRING(), "address 3" => Field::STRING(),
                    "shoe size" => Field::INT()
                ]);
            }
        };
        
        $output = new class() extends CSV   {
            protected function configure()    {
                $this->setName ("testCSVOut.csv" );
                $this->setFields( [ "shoe size" => Field::INT(), "id" => Field::INT(),
                    "dob" => Field::Date("Y/m/d"), "name" => Field::STRING()
                ]);
            }
        };
        $input
            ->setLimit(2)
            ->modify (function(&$data) { $data['id'] += 2; 
                    return Entity::CONTINUE_PROCESSING; })
            ->saveTo($output)
            ->transfer();
        
        $output = file_get_contents("testCSVOut.csv");
        $this->assertEquals($output, $csvOutput);
        
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
        
        $input = new class() extends CSV    {
            protected function configure()    {
                $this->setName ("testCSV.csv" );
                $this->setFields( [ "id" => Field::INT(), "name" => Field::STRING(),
                    "dob" => Field::Date("d/m/Y"), "address 1" => Field::STRING(),
                    "address 2" => Field::STRING(), "address 3" => Field::STRING(),
                    "shoe size" => Field::INT()
                ]);
            }
        };
        
        $output = new class() extends CSV   {
            protected function configure()    {
                $this->setName ("testCSVOut.csv" );
                $this->setFields( [ "shoe size" => Field::INT(), "id" => Field::INT(),
                    "dob" => Field::Date("Y/m/d"), "name" => Field::STRING()
                ]);
            }
        };
        $input
            ->setLimit(2,1)
            ->modify (function(&$data) { $data['id'] += 2; 
                    return Entity::CONTINUE_PROCESSING; })
            ->saveTo($output)
            ->transfer();
        
        $output = file_get_contents("testCSVOut.csv");
        $this->assertEquals($output, $csvOutput);
        
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
        
        $input = new class() extends CSV    {
            protected function configure()    {
                $this->setName ("testCSV.csv" );
                $this->setFields( [ "id" => Field::INT(), "name" => Field::STRING(),
                    "dob" => Field::Date("d/m/Y"), "address 1" => Field::STRING(),
                    "address 2" => Field::STRING(), "address 3" => Field::STRING(),
                    "shoe size" => Field::INT()
                ]);
            }
        };
        
        $output = new class() extends CSV   {
            protected function configure()    {
                $this->setName ("testCSVOut.csv" );
                $this->setFields( [ "shoe size" => Field::INT(), 
                    "emp_id" => Field::INT(),"id" => Field::INT(),
                    "dob" => Field::Date("Y/m/d"), "name" => Field::STRING()
                ]);
            }
        };
        $input
            ->map(["id" => "emp_id"])
            ->saveTo($output)
            ->transfer();
        
        $output = file_get_contents("testCSVOut.csv");
        $this->assertEquals($output, $csvOutput);
        
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
        
        $input = new class() extends CSV    {
            protected function configure()    {
                $this->setName ("testCSV.csv" );
                $this->setFields( [ "id" => Field::INT(), "name" => Field::STRING(),
                    "dob" => Field::Date("d/m/Y"), "address 1" => Field::STRING(),
                    "address 2" => Field::STRING(), "address 3" => Field::STRING(),
                    "shoe size" => Field::INT()
                ]);
            }
        };
        
        $output = new class() extends CSV   {
            protected function configure()    {
                $this->setName ("testCSVOut.csv" );
                $this->setFields( [ "shoe size" => Field::INT(), "id" => Field::INT(),
                    "dob" => Field::Date("Y/m/d"), "name" => Field::STRING()
                ]);
            }
        };
        $input
            ->filter(function($data) { 
                return $data['id'] == 12 || $data['id'] == 13; 
            })
            ->modify (function(&$data) { $data['id'] += 2; 
                    return Entity::CONTINUE_PROCESSING; })
            ->saveTo($output)
            ->transfer();
        
        $output = file_get_contents("testCSVOut.csv");
        $this->assertEquals($output, $csvOutput);
        
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
        
        $input = new class() extends CSV    {
            protected function configure()    {
                $this->setName ("testCSV.csv" );
                $this->setFields( [ "id" => Field::INT(), "name" => Field::STRING(),
                    "qty" => Field::INT(),
                    "qty2" => Field::INT()
                ]);
            }
        };
        
        $output = new class() extends CSV   {
            protected function configure()    {
                $this->setName ("testCSVOut.csv" );
                $this->setFields( [ "id" => Field::INT(),"sumqty2" => Field::INT(),
                    "sumqty" => Field::INT()
                ]);
            }
        };
        $input
            ->sum(['qty' => 'sumqty', 'qty2' => 'sumqty2'])
            ->groupBy(['id'])
            ->saveTo($output)
            ->transfer();
            
        $output = file_get_contents("testCSVOut.csv");
        $this->assertEquals($output, $csvOutput);
        
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
        
        $output = file_get_contents("testCSVOut.csv");
        $this->assertEquals($output, $csvOutput);
        
        unlink("testCSV.csv");
        unlink("testCSVOut.csv");
    }
    
    public function testLookup()   {
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
        $output = new class() extends CSV   {
            protected function configure()    {
                $this->setName ("testCSVOut.csv" );
                $this->setFields( [ "order" => Field::INT(),"productid" => Field::INT(),
                    "product_name" => Field::STRING(),
                    "qty" => Field::INT()
                ]);
            }
        };
        $productLookup = (new CSV("testProductsCSV.csv"))
            ->indexBy(["id"]);
        
        // Use this lookup
        $orders
            ->lookup($productLookup, ['productid'], "product_")
            ->saveTo($output)
            ->transfer();
        
        $output = file_get_contents("testCSVOut.csv");
        $this->assertEquals($output, $csvOutput);
        
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
        
        $input = new class() extends CSV    {
            protected function configure()    {
                $this->setName ("testCSV.csv" );
                $this->setFields( [ "id" => Field::INT(), "name" => Field::STRING(),
                    "dob" => Field::Date("d/m/Y"), "address 1" => Field::STRING(),
                    "address 2" => Field::STRING(), "address 3" => Field::STRING(),
                    "shoe size" => Field::INT()
                ]);
            }
        };
        
        $output = new class() extends CSV   {
            protected function configure()    {
                $this->setName ("testCSVOut.csv" );
                $this->setFields( [ "shoe size" => Field::INT(), "id" => Field::INT(),
                    "dob" => Field::Date("Y/m/d"), "name" => Field::STRING()
                ]);
            }
        };
        $output1 = new class() extends CSV   {
            protected function configure()    {
                $this->setName ("testCSVOut1.csv" );
                $this->setFields( [ "shoe size" => Field::INT(), "id" => Field::INT(),
                    "dob" => Field::Date("Y/m/d"), "name" => Field::STRING()
                ]);
            }
        };
        $input
            ->split(function($data) { return $data['id'] > 11; }, $output1)
            ->saveTo($output)
            ->transfer();
        
        $output = file_get_contents("testCSVOut.csv");
        $this->assertEquals($output, $csvOutput);
        $output = file_get_contents("testCSVOut1.csv");
        $this->assertEquals($output, $csvOutput1);
        
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
        
        $input = new class() extends CSV    {
            protected function configure()    {
                $this->setName ("testCSV.csv" );
                $this->setFields( [ "id" => Field::INT(), "name" => Field::STRING(),
                    "qty" => Field::INT(),
                    "qty2" => Field::INT()
                ]);
            }
        };
        
        $input
            ->orderBy(["id" => Entity::DESC, "qty2" => Entity::ASC])
            ->saveTo(new CSV("testCSVOut.csv"))
            ->transfer();
        
        $output = file_get_contents("testCSVOut.csv");
        $this->assertEquals($output, $csvOutput);
        
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
        
        $output = file_get_contents("testCSVOut.csv");
        $this->assertEquals($output, $csvOutput);
        
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
        
        $output = file_get_contents("testCSVOut.csv");
        $this->assertEquals($output, $csvOutput);
        
        unlink("testCSVOut.csv");
        
    }
    
}