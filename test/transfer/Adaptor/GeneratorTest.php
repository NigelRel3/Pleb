<?php

use PHPUnit\Framework\TestCase;
use Pleb\transfer\Adaptor\CSV;
use Pleb\transfer\Adaptor\Generator;
use Pleb\transfer\Adaptor\Transient;
use Pleb\transfer\Field\Field;
use Pleb\transfer\Entity;

require_once __DIR__ . '/../../../vendor/autoload.php';

class GeneratorTest extends TestCase   {
	private $testFile = "test.csv";
	
	public function testSimpleID () {
		if ( file_exists($this->testFile) )	{
			unlink($this->testFile);
		}
		$gen = (new Generator( function ()	{
				for ( $i = 0; $i < 5; $i++ )	{
					$data = [ "id" => $i ];
					yield $data;
				}
			}))
			->saveTo(new CSV($this->testFile))
			->transfer();
		
		$csvOutput = <<<TESTDATA
id
0
1
2
3
4

TESTDATA;
		$output = file_get_contents($this->testFile);
		$this->assertEquals($csvOutput, $output );
		unlink($this->testFile);
	}
	
	public function testSimpleID2 () {
		if ( file_exists($this->testFile) )	{
			unlink($this->testFile);
		}
		$gen = (new Generator( function ()	{
				for ( $i = 0; $i < 5; $i++ )	{
					$data = [ "id" => $i, "name" => "name ".($i+1) ];
					yield $data;
				}
			}))
			->saveTo(new CSV($this->testFile))
			->transfer();
		
		$csvOutput = <<<TESTDATA
id,name
0,"name 1"
1,"name 2"
2,"name 3"
3,"name 4"
4,"name 5"

TESTDATA;
		$output = file_get_contents($this->testFile);
		$this->assertEquals($csvOutput, $output );
		unlink($this->testFile);
		
	}
		
	public function testSimpleID2Modify () {
		if ( file_exists($this->testFile) )	{
			unlink($this->testFile);
		}
		$gen = (new Generator( function ()	{
				for ( $i = 0; $i < 5; $i++ )	{
					$data = [ "id" => $i, "name" => "name ".($i+1) ];
					yield $data;
				}
			}))
			->modify (function(&$data) { 
				$data['id'] += 2;
				$data['other column'] = "fred";
				return Entity::CONTINUE_PROCESSING; 
			})
			->saveTo(new CSV($this->testFile))
			->transfer();
		
		$csvOutput = <<<TESTDATA
id,name,"other column"
2,"name 1",fred
3,"name 2",fred
4,"name 3",fred
5,"name 4",fred
6,"name 5",fred

TESTDATA;
		$output = file_get_contents($this->testFile);
		$this->assertEquals($csvOutput, $output );
		unlink($this->testFile);
		
	}
}