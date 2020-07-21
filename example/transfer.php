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
use Pleb\transfer\Adaptor\Generator;

// wh zl8xpDxtYqHw1VGt

// TODO test something like an employee load
// TODO check how joins are used in Model
// TODO can create table be called automatically? $e->getCode() === '42S02'
// TODO Elequent
// TODO logging at each stage
// TODO Split fields out to own classes and complete types
// TODO code EnumField including Model field types

// Specific issues

// TODO Should an update be OK without a WHERE clause?
// TODO Model group by and sum as native SQL
// TODO XML - partial
// TODO Order by for non database can't work with short definition as
//    no columns are defined.  Perhaps some way of delaying the check?
// TODO consider what orderBy and limit do in combination with setSQL


// Overall architecture

// TODO look into what count should do
// TODO database transaction and performance
// TODO can move the format steps into the method chain?
// TODO check methods have correct access level.
// TODO split out some Model specific stuff to a ModelUtils class
//		things like create
// TODO set DB as static for models?
// TODO flag options (create/drop) as static settings.
// TODO parameters?
// TODO some performance testing
// TODO How to manage hierarchical JSON & XML
// TODO ability to turn off validation - affect on performance
// TODO Possible to get aggregates to work with sequence file
//   (i.e. all employee records are sequential, so when it changes SUM()
//   can return the new value)
// TODO stats
// TODO test shutdown chain is setup properly
// TODO direct load of CSV to Model
//		https://stackoverflow.com/questions/11448307/importing-csv-data-using-php-mysql

// done add to testing of things like field validation with empty fields etc.
// 			If field is missing - is this valid?
//			Added ability to define nullable fields
// done test generator
// done Generator type class for source data.
// done test how database errors are processed
// done can remove loadColumns() and call it automatically?
// done test for exceptions thrown and other errors
// done consider if can be converted into generator to allow
//      data to be passed onto the user.
//      superceeded by sub streams
// done consider allowing iteration over dataset (may just be Transient)
// done option to get class to fetch definitions from DB
// done Model with SQL as input
// done configure format 1 field out of n
// done push data to stream
// done JSON
// done Not sure if lookups are managed properly ( why loadfrom )
// done some form of clean up method chain
// done consider what orderBy and limit do in combination with generateFetchSQL
// done set delimiter and enclosing for CSV
// done Model unit testing
// done Model update
// done Updateable trait?
// done split and process seem to do very similar things
// done validate input, What if format conversions fails etc.?
// done Ensure traits used properly?
// done Allow lookup to continue if not found
// done Check if configure is needed (alternate is in ModelTest.testFilter()
// done database create table (no indexes just columns)
//		due to limitations all columns are nullable
// done Unit testing make sure expected and actual are the right way round.

$db = new PDO("mysql:host=172.17.0.4;dbname=PlebTest",
		"Pleb", 'RNjjJBkgduyTx7zl');
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$employee = (new Model("employees3"))
	->setDB($db)
	->setFields( [ "emp_no" => Field::INT(),
			"first_name" => Field::STRING(100)
	])
	->drop()
	->create()
	->validationErrors(new CSV("errors.csv"))
	;

$db->beginTransaction();
$input = (new Generator(function ()	{
		for ( $i = 2; $i < 400; $i++ )	{
			yield [ "emp_no" => $i, "first_name" => "name {$i}"];
		}
	}))
	->saveTo($employee)
	->transfer();

$db->commit();
exit;	
$input = (new CSV("test.csv"))
	->setFields([
			"id" => Field::INT(),
			"first_name" => Field::STRING(),
			"dob" => Field::Date("d/m/Y")
	])
// 	->setFormatedFields( [
// 			"dob" => Field::Date("d/m/Y")
// 	])
// 	->map([ "id" => "emp_no",
// 			"dob" => "birth_date"
// 	])
// 	->set( [ 'hire_date' => '2010/01/01',
// 			'dept_dept_no' => "a1",
// 			'dept_from_date' => '2010/01/02',
// 	])
	->saveTo($employee)
	->transfer();
	
echo "Errors;".file_get_contents("errors.csv");
exit;
$csv = <<<TESTDATA
id,first_name,dob
3a,Nigell2,02/08/19661
3,Nigell2,02/08/1966
TESTDATA;

file_put_contents("test.csv", $csv);

// $db = new PDO("mysql:host=172.17.0.4;dbname=PlebTest",
// 		"Pleb", 'RNjjJBkgduyTx7zl');
// $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
// $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// $emp = (new Model("employees1"))
// 	->setDB($db)
// 	->setFields( [ "first_name" => Field::STRING(),
// 			"birth_date" => Field::Date("Y/m/d")
// 	])
// 	->filter("emp_no = :emp_no");
	
$input = (new CSV("test.csv"))
	->setFields( [ "emp_no" => Field::INT(),
			"first_name" => Field::STRING(),
			"birth_date" => Field::Date("d/m/Y")
	])
	->validationErrors(new CSV("errors.csv"));
	
$output = (new CSV("output.csv"))
	->setFields( [ "emp_no" => Field::INT(),
			"birth_date" => Field::Date("Y/m/d"),
			"first_name" => Field::STRING()
	]);
	
$input->saveTo($output)
	->transfer();

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
		return Entity::CONTINUE_PROCESSING; 
	})
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