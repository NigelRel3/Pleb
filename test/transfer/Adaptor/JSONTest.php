<?php

use PHPUnit\Framework\TestCase;
use Pleb\transfer\Adaptor\JSON;
use Pleb\transfer\Adaptor\CSV;
use Pleb\transfer\Adaptor\Transient;
use Pleb\transfer\Field\Field;
use Pleb\transfer\Entity;

require_once __DIR__ . '/../../../vendor/autoload.php';

class JSONTest extends TestCase   {
    public function testModify () {
        $csv = <<<TESTDATA
        { 
        "id" : 11, "name" : "Fred", "dob" : "1/1/1980",
        "address 1" : "1 Somewhere", "address 2" : "Somehow", 
        "address 3" : "Somewhy","shoe size" : 4
        }
TESTDATA;
        
        $csvOutput = <<<TESTDATA
"shoe size",id,dob,name
4,13,1980/01/01,Fred

TESTDATA;
        file_put_contents("test.json", $csv);

        $input = (new JSON("test.json"))
            ->setFormatedFields(["dob" => Field::Date("d/m/Y")]);
    
        $output = new class() extends CSV   {
            protected function configure()    {
                $this->setName ("testCSVOut.csv" );
                $this->setFields( [ "shoe size" => Field::INT(), "id" => Field::INT(),
                    "dob" => Field::Date("Y/m/d"), "name" => Field::STRING()
                ]);
            }
        };
        $input
            ->filter ("")
            ->modify (function(&$data) { $data['id'] += 2; 
                    return Entity::CONTINUE_PROCESSING; })
            ->saveTo($output)
            ->transfer();
        
        $output = file_get_contents("testCSVOut.csv");
        $this->assertEquals($output, $csvOutput);
        
        unlink("test.json");
        unlink("testCSVOut.csv");
            
    }

    public function testLimit () {
        $csv = <<<TESTDATA
[
    {
        "id" : 11, "name" : "Fred", "dob" : "1/1/1980",
        "address 1" : "1 Somewhere", "address 2" : "Somehow",
        "address 3" : "Somewhy","shoe size" : 4
    },
    {
        "id" : 12, "name" : "Fred", "dob" : "1/1/1980",
        "address 1" : "1 Somewhere", "address 2" : "Somehow",
        "address 3" : "Somewhy","shoe size" : 41
    },
    {
        "id" : 13, "name" : "Fred", "dob" : "1/1/1980",
        "address 1" : "1 Somewhere", "address 2" : "Somehow",
        "address 3" : "Somewhy","shoe size" : 42
    },
    {
        "id" : 14, "name" : "Fred", "dob" : "1/1/1980",
        "address 1" : "1 Somewhere", "address 2" : "Somehow",
        "address 3" : "Somewhy","shoe size" : 43
    }
]
TESTDATA;
        
        $csvOutput = <<<TESTDATA
"shoe size",id,dob,name
4,13,1980/01/01,Fred
41,14,1980/01/01,Fred

TESTDATA;
        file_put_contents("test.json", $csv);
        
        $input = (new JSON("test.json"))
            ->setFormatedFields(["dob" => Field::Date("d/m/Y")]);
        
        $output = new class() extends CSV   {
            protected function configure()    {
                $this->setName ("testCSVOut.csv" );
                $this->setFields( [ "shoe size" => Field::INT(), "id" => Field::INT(),
                    "dob" => Field::Date("Y/m/d"), "name" => Field::STRING()
                ]);
            }
        };
        $input
            ->filter("/\d*")
            ->setLimit(2)
            ->modify (function(&$data) { $data['id'] += 2; 
                    return Entity::CONTINUE_PROCESSING; })
            ->saveTo($output)
            ->transfer();
        
        $output = file_get_contents("testCSVOut.csv");
        $this->assertEquals($output, $csvOutput);
        
        unlink("test.json");
        unlink("testCSVOut.csv");
        
    }
    
    public function testLimitAndOffset () {
        $csv = <<<TESTDATA
[
    {
        "id" : 11, "name" : "Fred", "dob" : "1/1/1980",
        "address 1" : "1 Somewhere", "address 2" : "Somehow",
        "address 3" : "Somewhy","shoe size" : 4
    },
    {
        "id" : 12, "name" : "Fred", "dob" : "1/1/1980",
        "address 1" : "1 Somewhere", "address 2" : "Somehow",
        "address 3" : "Somewhy","shoe size" : 41
    },
    {
        "id" : 13, "name" : "Fred", "dob" : "1/1/1980",
        "address 1" : "1 Somewhere", "address 2" : "Somehow",
        "address 3" : "Somewhy","shoe size" : 42
    },
    {
        "id" : 14, "name" : "Fred", "dob" : "1/1/1980",
        "address 1" : "1 Somewhere", "address 2" : "Somehow",
        "address 3" : "Somewhy","shoe size" : 43
    }
]
TESTDATA;
        
        $csvOutput = <<<TESTDATA
"shoe size",id,dob,name
41,14,1980/01/01,Fred
42,15,1980/01/01,Fred

TESTDATA;
        file_put_contents("test.json", $csv);
        
        $input = (new JSON("test.json"))
            ->setFormatedFields(["dob" => Field::Date("d/m/Y")]);
        
        $output = new class() extends CSV   {
            protected function configure()    {
                $this->setName ("testCSVOut.csv" );
                $this->setFields( [ "shoe size" => Field::INT(), "id" => Field::INT(),
                    "dob" => Field::Date("Y/m/d"), "name" => Field::STRING()
                ]);
            }
        };
        $input
            ->filter("/\d*")
            ->setLimit(2,1)
            ->modify (function(&$data) { $data['id'] += 2; 
                    return Entity::CONTINUE_PROCESSING; })
            ->saveTo($output)
            ->transfer();
        
        $output = file_get_contents("testCSVOut.csv");
        $this->assertEquals($output, $csvOutput);
        
        unlink("test.json");
        unlink("testCSVOut.csv");
        
    }
    
    public function testSum()   {
        $csv = <<<TESTDATA
{
    "items" : [
        { "id" : 11, "name" : "abc", "qty" : 4, "qty2" : 41 },
        { "id" : 11, "name" : "abc", "qty" : 4, "qty2" : 42 },
        { "id" : 11, "name" : "abc", "qty" : 4, "qty2" : 43 },
        { "id" : 12, "name" : "abc", "qty" : 4, "qty2" : 44 }
    ]
}
TESTDATA;
        
        $csvOutput = <<<TESTDATA
id,sumqty,sumqty2
11,12,126
12,4,44

TESTDATA;
        file_put_contents("test.json", $csv);
        
        $input = (new JSON("test.json"))
            ->filter("/items/\d*")
            ->sum(['qty' => 'sumqty', 'qty2' => 'sumqty2'])
            ->groupBy(['id'])
            ->saveTo(new CSV("testCSVOut.csv"))
            ->transfer();
        
        $output = file_get_contents("testCSVOut.csv");
        $this->assertEquals($output, $csvOutput);
        
        unlink("test.json");
        unlink("testCSVOut.csv");
    }
    
    public function testLookup()   {
        $set1 = <<<TESTDATA
{
    "items" : [
        { "type_id" : 11, "name" : "abc", "qty" : 4, "qty2" : 41 },
        { "type_id" : 12, "name" : "abc", "qty" : 4, "qty2" : 42 },
        { "type_id" : 13, "name" : "abc", "qty" : 4, "qty2" : 43 },
        { "type_id" : 14, "name" : "abc", "qty" : 4, "qty2" : 44 }
    ]
}
TESTDATA;
        
        $set2 = <<<TESTDATA
{
    "items" : [
        { "id" : 11, "name" : "abc1", "set1Type" : 13 },
        { "id" : 12, "name" : "abc2", "set1Type" : 12 }
    ]
}
TESTDATA;
        file_put_contents("set1.json", $set1);
        file_put_contents("set2.json", $set2);
        
        $csvOutput = <<<TESTDATA
id1,set1Id,product_name,qty
11,13,abc1,43
12,12,abc2,42

TESTDATA;
        
        $set1JSON = (new JSON("set1.json"))
            ->filter("/items/\d*")
            ->indexBy(["type_id"]);
        
        $set2JSON = new JSON("set2.json");
        
        // Use this lookup
        $set2JSON
            ->filter("/items/\d*")
            ->lookup($set1JSON, ['set1Type'], "set1Type_")
            ->map([ "id" => "id1", "set1Type_type_id" => "set1Id",
                "name" =>"product_name",
                "set1Type_qty2" => "qty"
            ])
            ->extract(["id1", "set1Id", "product_name", "qty"])
            ->saveTo(new CSV("testCSVOut.csv"))
            ->transfer();
        
        $output = file_get_contents("testCSVOut.csv");
        $this->assertEquals($output, $csvOutput);
        
        unlink("set1.json");
        unlink("set2.json");
        unlink("testCSVOut.csv");
    }
    
    public function testSplit () {
        $csv = <<<TESTDATA
{
    "items" : [
        { "type_id" : 11, "name" : "abc1", "qty" : 4, "qty2" : 41 },
        { "type_id" : 12, "name" : "abc2", "qty" : 4, "qty2" : 42 },
        { "type_id" : 13, "name" : "abc3", "qty" : 4, "qty2" : 43 },
        { "type_id" : 14, "name" : "abc4", "qty" : 4, "qty2" : 44 }
    ]
}
TESTDATA;
        
        $csvOutput = <<<TESTDATA
type_id,name,qty,qty2
11,abc1,4,41
12,abc2,4,42

TESTDATA;
        $csvOutput1 = <<<TESTDATA
type_id,name,qty,qty2
13,abc3,4,43
14,abc4,4,44

TESTDATA;
        file_put_contents("test.json", $csv);
        
        $input = new JSON("test.json");
        
        $input
            ->filter("/items/\d*")
            ->split(function($data) { return $data['type_id'] < 13; }, 
                   new CSV("testCSVOut.csv"))
            ->saveTo(new CSV("testCSVOut1.csv"))
            ->transfer();
        
        $output = file_get_contents("testCSVOut.csv");
        $this->assertEquals($csvOutput, $output);
        $output = file_get_contents("testCSVOut1.csv");
        $this->assertEquals($csvOutput1, $output);
        
        unlink("test.json");
        unlink("testCSVOut.csv");
        unlink("testCSVOut1.csv");
        
    }
    
    public function testOrderBy()   {
        $csv = <<<TESTDATA
{
    "items" : [
        { "type_id" : 11, "name" : "abcd", "qty" : 4, "qty2" : 42 },
        { "type_id" : 11, "name" : "abca", "qty" : 4, "qty2" : 43 },
        { "type_id" : 11, "name" : "abcb", "qty" : 4, "qty2" : 41 },
        { "type_id" : 12, "name" : "abcc", "qty" : 4, "qty2" : 44 }
    ]
}
TESTDATA;

        $csvOutput = <<<TESTDATA
type_id,name,qty,qty2
12,abcc,4,44
11,abcb,4,41
11,abcd,4,42
11,abca,4,43

TESTDATA;
        file_put_contents("test.json", $csv);
        
        $input = new class() extends JSON    {
            protected function configure()    {
                $this->setName ("test.json" );
                $this->setFields( [ "type_id" => Field::INT(), "name" => Field::STRING(),
                    "qty" => Field::INT(),
                    "qty2" => Field::INT()
                ]);
            }
        };
        
        $input
            ->filter("/items/\d*")
            ->orderBy(["type_id" => Entity::DESC, "qty2" => Entity::ASC])
            ->saveTo(new CSV("testCSVOut.csv"))
            ->transfer();
        
        $output = file_get_contents("testCSVOut.csv");
        $this->assertEquals($output, $csvOutput);
        
        unlink("test.json");
        unlink("testCSVOut.csv");
    }

    public function testWrite () {
        $json = <<<TESTDATA
        {
        "id" : 11, "name" : "Fred", "dob" : "1/1/1980",
        "address 1" : "1 Somewhere", "address 2" : "Somehow",
        "address 3" : "Somewhy","shoe size" : 4
        }
TESTDATA;
        
        $jsonOutput = <<<TESTDATA
[
    {
        "shoe size": 4,
        "id": 13,
        "dob": "01/01/1980",
        "name": "Fred"
    }
]
TESTDATA;
        file_put_contents("test.json", $json);
        
        $output = (new JSON("testOutput.json"))
            ->setFields( [ "shoe size" => Field::INT(), "id" => Field::INT(),
                    "dob" => Field::Date("d/m/Y"), "name" => Field::STRING()]);
            
        $input = (new JSON("test.json"))
            ->setFormatedFields(["dob" => Field::Date("d/m/Y")])
            ->filter ("")
            ->modify (function(&$data) { $data['id'] += 2;
                return Entity::CONTINUE_PROCESSING; })
            ->saveTo($output)
            ->transfer();
        
        $output = file_get_contents("testOutput.json");
        $this->assertEquals($output, $jsonOutput);
        
        unlink("test.json");
        unlink("testOutput.json");
        
    }
   
    public function testWriteFlags () {
        $json = <<<TESTDATA
        {
        "id" : 11, "name" : "Fred", "dob" : "1/1/1980",
        "address 1" : "1 Somewhere", "address 2" : "Somehow",
        "address 3" : "Somewhy","shoe size" : 4
        }
TESTDATA;
        
        $jsonOutput = <<<TESTDATA
[{"shoe size":4,"id":13,"dob":"01\/01\/1980","name":"Fred"}]
TESTDATA;
        file_put_contents("test.json", $json);
        
        $output = (new JSON("testOutput.json"))
            ->setFields( [ "shoe size" => Field::INT(), "id" => Field::INT(),
                "dob" => Field::Date("d/m/Y"), "name" => Field::STRING()])
            ->outputFormat (0) ;
        
        $input = (new JSON("test.json"))
            ->setFormatedFields(["dob" => Field::Date("d/m/Y")])
            ->filter ("")
            ->modify (function(&$data) { $data['id'] += 2;
                return Entity::CONTINUE_PROCESSING; })
            ->saveTo($output)
            ->transfer();
            
        $output = file_get_contents("testOutput.json");
        $this->assertEquals($output, $jsonOutput);
        
        unlink("test.json");
        unlink("testOutput.json");
        
    }
    
}