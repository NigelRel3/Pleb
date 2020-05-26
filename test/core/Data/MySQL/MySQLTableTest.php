<?php

use PHPUnit\Framework\TestCase;
use Architect\pleb\Column;

use Pleb\core\Data\MySQL\MySQLInteger;
use Pleb\core\Data\MySQL\MySQLTable;
use Pleb\core\Data\MySQL\MySQLVarChar;
use Pleb\core\Data\MySQL\MySQLTimeStamp;

require_once __DIR__ . '/../../../../vendor/autoload.php';

class MySQLTableTest extends TestCase   {
    public function testCreateOK () {
        $id = MySQLInteger::id()->length(8)->autoIncrement();
        $employee = MySQLTable::Employee([$id,
            MySQLVarChar::name()->length(30)->nullable()->unique,
            MySQLTimeStamp::addedOn()->default('CURRENT_TIMESTAMP')
        ])->primaryKey($id);
    
        $this->assertNotNull ( $employee );
    }
 
    public function testCreateSQL () {
        $id = MySQLInteger::id()->length(8)->autoIncrement();
        $employee = MySQLTable::Employee([$id,
            MySQLVarChar::name()->length(30)->nullable()->unique,
            MySQLTimeStamp::addedOn()->default('CURRENT_TIMESTAMP')
        ])->primaryKey($id);
        
        $this->assertEquals ("create table `Employee` (
    `id`	int(8) not null auto_increment ,
    `name`	varchar(30) null  unique,
    `addedOn`	date default CURRENT_TIMESTAMP ,
    primary key (`id`)
)", $employee->generateSQL() );
    }
    
}