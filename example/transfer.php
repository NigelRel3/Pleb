<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', "1");

require_once __DIR__ . '/../vendor/autoload.php';

use Pleb\transfer\Field\Field;
use Pleb\transfer\Source;
use Pleb\transfer\Entity;
use Pleb\transfer\Adaptor\CSV;
use Pleb\transfer\Adaptor\JSON;
use Pleb\transfer\Adaptor\XML;

use Pleb\transfer\Adaptor\Model;
use Pleb\transfer\Adaptor\Transient;
use JsonStreamingParser\Listener\RegexListener;
use JsonStreamingParser\Parser;




// done option to get class to fetch definitions from DB
// done Model with SQL as input
// done configure format 1 field out of n
// done push data to stream
// done JSON
// done Not sure if lookups are managed properly ( why loadfrom )
// done some form of clean up method chain
// TODO XML
// TODO How to manage hierarchical JSON & XML
// TODO some performance testing
// TODO logging at each stage
// TODO test for exceptions thrown and other errors
// TODO validate input, What if format conversions fails etc.?
// TODO parameters?
// TODO Model group by and sum as native SQL
// TODO Allow lookup to continue if not found

// TODO Javascript web maintenance of transformation

// TODO consider if can be converted into generator to allow 
//      data to be passed onto the user.
// TODO consider allowing iteration over dataset (may just be Transient)
// TODO Order by for non database can't work with short definition as 
//    no columns are defined.  Perhaps some way of delaying the check?
// TODO Elequent
// TODO Possible to get aggregates to work with sequence file
//   (i.e. all employee records are sequential, so when it changes SUM()
//   can return the new value)
// TODO consider what orderBy and limit do in combination with setSQL
// done consider what orderBy and limit do in combination with generateFetchSQL
// done set delimiter and enclosing for CSV
// done Model unit testing
// TODO Model update not done yet
// TODO Updateable trait?
// TODO stats

// TODO Unit testing make sure expected and actual are the right way round.

$csv = <<<TESTDATA
id,1,2
1,2,3
1,3,3
TESTDATA;

$csvOutput = <<<TESTDATA
id,sumqty,sumqty2
11,12,126
12,4,44

TESTDATA;

$subProc = (new Transient())
	->modify (function(&$data) { $data['2'] += 2;
		return Entity::CONTINUE_PROCESSING; })
;
		
file_put_contents("test.csv", $csv);

$input = (new CSV("test.csv"))

	->process( function ($v) { return $v['id'] == 1;},
			$subProc)
	
	->saveTo(new CSV("testCSVOut.csv"))
	->transfer();

$output = file_get_contents("testCSVOut.csv");
echo ">".$output;
exit;
$json = <<<TESTDATA
        {
        "id" : 11, "name" : "Fred", "dob" : "1/1/1980",
        "address 1" : "1 Somewhere", "address 2" : "Somehow",
        "address 3" : "Somewhy","shoe size" : 4
        }
TESTDATA;

file_put_contents("test.json", $json);


$output = (new JSON("testOutput.json"))
->setFields( [ "shoe size" => Field::INT(), "id" => Field::INT(),
    "dob" => Field::Date("Y/m/d"), "name" => Field::STRING()]);

$input = (new JSON("test.json"))
->setFormatedFields(["dob" => Field::Date("d/m/Y")])
->filter ("")
->modify (function(&$data) { $data['id'] += 2;
return Entity::CONTINUE_PROCESSING; })
->saveTo($output)
->transfer();

echo file_get_contents("testOutput.json");
exit;

$db = new PDO("mysql:host=172.17.0.3;dbname=employees",
    "root", 'a177fgvTRw');
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$employee = (new Model("employees1"))
    ->setDB($db)
    ->loadColumns()
//             ->setFormatedFields(["birth_date" => Field::Date("Y-m-d"),
    //                 "hire_date" => Field::Date("Y-m-d")])
;

$dept = (new Model("dept_emp2"))
    ->setDB($db)
    ->loadColumns()
    ->indexBy("emp_no = :emp_no")
//              ->setFormatedFields(["from_date" => Field::Date("Y-m-d"),
    //                  "to_date" => Field::Date("Y-m-d")
    //              ])
    ->orderBy(["from_date" => Entity::DESC])
    ->setLimit(1);

$output = new class() extends CSV   {
    protected function configure()    {
        $this->setName ("testCSVOut.csv" );
        $this->setFields( [ "emp_no" => Field::INT(),
            "birth_date" => Field::Date("d/m/Y"),
            "first_name" => Field::STRING(),
            "hire_date" => Field::Date("d/m/Y"),
            "dept_dept_no" => Field::STRING() //,
            //"dept_from_date" => Field::Date("d/m/Y")
        ]);
    }
};

$employee
    ->lookup($dept, ['emp_no'], "dept_")
    ->saveTo($output)
    ->transfer();

$output = file_get_contents("testCSVOut.csv");

exit;
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
id,name,qty,qty2
12,abc,4,44
11,abc,4,41
11,abc,4,42
11,abc,4,43

TESTDATA;
file_put_contents("test.json", $csv);

$input = new class() extends JSON    {
    protected function configure()    {
        $this->setName ("test.json" );
        $this->setFields( [ "id" => Field::INT(), 
            "name" => Field::STRING(),
            "qty" => Field::INT(),
            "qty2" => Field::INT()
        ]);
    }
};

$input
    ->filter ( "(/items/\d*)" )
    ->orderBy(["id" => Entity::DESC, "qty2" => Entity::ASC])
    ->saveTo(new CSV("testCSVOut.csv"))
    ->transfer();

echo file_get_contents("testCSVOut.csv");
exit;


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
            "emp_birth_date" => Field::Date("d/m/Y"),
            "emp_first_name" => Field::STRING(),
            "emp_hire_date" => Field::Date("d/m/Y"),
            "dept_no" => Field::STRING()
        ]);
    }
};
$employee = (new Model("employees1"))
    ->setDB($db)
    ->filter("emp_no = :emp_no")
    ->loadColumns()
    ->setFormatedFields(["birth_date" => Field::Date("Y-m-d"),
        "hire_date" => Field::Date("Y-m-d")]
        )
        ;

$dept = (new Model("dept_emp2"))
    ->setDB($db)
    ->loadColumns()    
    ->setFormatedFields(["from_date" => Field::Date("Y-m-d")]
        )
        ;

$dept
    ->lookup($employee, ['emp_no'], "emp_")
    ->saveTo($output)
    ->transfer();
    
    $output = file_get_contents("testCSVOut.csv");
    
    echo file_get_contents("testCSVOut.csv");
    
exit;

$output = (new Model("employees1"))
    ->setDB($db)
    ->loadColumns();

$input = (new Model("employees"))
    ->setDB($db)
    ->loadColumns()
    ->setLimit ( 10, 5 )
    ->orderBy([ "emp_no" => Entity::DESC ])
    ->saveTo($output)
    ->transfer();

exit;

$csv = <<<TESTDATA
id,name,qty,qty2
11,abca,4,43
11,abcb,4,41
12,abcc,4,44
11,abcd,4,42
TESTDATA;

$csvOutput = <<<TESTDATA
id,name,qty,qty2
12,abc,4,44
11,abc,4,41
11,abc,4,42
11,abc,4,43

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

echo file_get_contents("testCSVOut.csv");
exit;

//--------------------------------------------------------------
$json = <<<TESTDATA
[{ "id" : 1, "name": "fred"},
{ "id" : 2, "name": "bert"},
{ "id" : 3, "name": "ted"}]
TESTDATA;

file_put_contents("testJSON.json", $json);

$input = new class() extends JSON    {
    protected function configure()    {
        $this->setFileName ("testJSON.json" );
    }
};

$output = new OutputCSV("testCSVOut.csv");
$output1 = new class() extends CSV   {
    protected function configure()    {
        $this->setFileName ("testCSVOut1.csv" );
        $this->setFields( [ "id" => Field::INT(),"sumqty2" => Field::INT(),
            "sumqty" => Field::INT(),
            "count" => Field::INT()
        ]);
    }
};

$input
    ->saveTo($output)
    ->transfer();

unlink("testCSV.csv");
echo "file".PHP_EOL;
echo file_get_contents("testCSVOut.csv");
unlink("testCSVOut.csv");
exit;

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

$products = new class() extends CSV    {
    protected function configure()    {
        $this->setFileName ("testProductsCSV.csv" );
        $this->setFields( [ "id" => Field::INT(), "name" => Field::STRING(),
            "qty" => Field::INT(),
            "qty2" => Field::INT()
        ]);
    }
};

$orders = new class() extends CSV    {
    protected function configure()    {
        $this->setFileName ("testCSV.csv" );
        $this->setFields( [ "order" => Field::INT(), "productid" => Field::INT(),
            "qty" => Field::INT()
        ]);
    }
};

$output = new class() extends CSV   {
    protected function configure()    {
        $this->setFileName ("testCSVOut.csv" );
        $this->setFields( [ "order" => Field::INT(),"productid" => Field::INT(),
            "product_name" => Field::STRING(),
            "qty" => Field::INT()
        ]);
    }
};
$productLookup = (new Transient())
    ->indexBy(["id"])
    ->loadFrom($products)
;
// Use this lookup
$orders
    ->lookup($productLookup, ['productid'], "product_")
    ->saveTo($output)
    ->transfer();

echo "file".PHP_EOL;
echo file_get_contents("testCSVOut.csv");
 
exit;

// abstract class Stats    {
// }

// use Illuminate\Database\Capsule\Manager as Capsule;

// $capsule = new Capsule;
// $capsule->addConnection([
//     "driver" => "mysql",
//     "host" =>"172.17.0.3",
//     "database" => "employees",
//     "username" => "root",
//     "password" => "a177fgvTRw"
// ]);

// //Make this Capsule instance available globally.
// $capsule->setAsGlobal();

// // Setup the Eloquent ORM.
// $capsule->bootEloquent();
// $capsule->bootEloquent();


// Capsule::schema()->create('users', function ($table) {
//     $table->increments('id');
//     $table->string('name');
//     $table->string('email')->unique();
//     $table->string('password');
//     $table->string('userimage')->nullable();
//     $table->string('api_key')->nullable()->unique();
//     $table->rememberToken();
//     $table->timestamps();
// });
    
//     exit;