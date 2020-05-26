<?php
namespace Pleb\core\Data\MySQL;

use Pleb\core\Element;
use Pleb\core\Data\Column;
use Pleb\core\Data\ForeignKey;
use Pleb\core\Data\Index;
use Pleb\core\Data\PrimaryKey;
use Pleb\core\Data\Schema;

require_once __DIR__ . '/../../../../vendor/autoload.php';

/**
 * Class to start how to import and export to MySQL
 */
class MySQL extends Schema {
    static protected $registered = [];
    
    static public function register( string $class )  {
        if ( class_exists($class) == false && is_subclass_of($class, Column::class))    {
            throw new \TypeError("Class {$class} not found, or not a Column class");
        }
        foreach ( $class::getAlias() as $alias )   {
            static::$registered[strtolower($alias)] = $class;
        }
    }
    
    static public function initialize() {
        self::register(MySQLInteger::class);
        self::register(MySQLTimeStamp::class);
        self::register(MySQLVarChar::class);
        self::register(MySQLEnum::class);
    }
    
    public static function __callstatic(string $name , array $arguments): Element    {
        if ( $arguments[0] instanceof \mysqli ) {
            $newInstance = new MySQL($name, $arguments[0]);
        }
        else   {
            throw new \Exception("Argument must be instances of \mysqli");
        }
        
        return $newInstance;
    }
    
    protected function __construct( string $schema, \mysqli $db )  {
        $this->schemaName = $schema;
        $this->db = $db;
    }
    
    /**
     * $tables is an array of table names, or empty for all tables.
     * @param array $def
     */
    public function importTables (array $tables = []) : int    {
        if ( empty ( $tables ))    {
            $sql = "show tables";
            $result = $this->db->query($sql);
            $tables = $result->fetch_all(MYSQLI_ASSOC);
            $key = array_keys($tables[0])[0];
            $tables = array_column($tables, $key);
        }
        foreach ( $tables as $tableName ){
            $this->details['tables'][$tableName] = $this->importTable($tableName);
        }
        
        return count($this->details['tables']);
    }
    
    
    public function importTable( string $tableName ) : MySQLTable    {
        $table = MySQLTable::{$tableName}($this->importColumns($tableName));
        
        // Indicies
        $indexes = $this->importIndexes($tableName);
        foreach ( $indexes as $index )  {
            if ( $index instanceof PrimaryKey ){
                $table->primaryKey(...$index->getColumns());
            }
            else    {
                $table->index($index);
            }
        }
        
        // FK's & Extract referenced table from FK's
        $refTables = [];
        $fks = $this->importFKs($tableName);
        foreach ( $fks as $fk ) {
            $fk->for($tableName, ...$fk->getColumns());
            $table->foreignKey($fk);
            $refTables[] = $fk->getDetail ("references")[0];
        }
        $table->references($refTables);
        
        return $table;
    }
    
    protected function importColumns( string $tableName ) : array  {
        $sql = "SELECT column_name, column_default, is_nullable, data_type,
                    character_maximum_length, numeric_precision, numeric_scale,
                    column_type, extra
                FROM `information_schema`.`COLUMNS`
                    where table_schema=? and table_name = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ss", $this->schemaName, $tableName);
        $stmt->execute();
        $result = $stmt->get_result();
        $columns = [];
        while ($col = $result->fetch_assoc() )    {
            $class = static::$registered[$col['data_type']];
            $columns[] = $class::importDef($col);
        }
        
        return $columns;
    }
    
    protected function importIndexes( string $tableName ) : array  {
        $sql = "show indexes from ".$tableName;
        $result = $this->db->query($sql);
        return Index::importDef( $result->fetch_all(MYSQLI_ASSOC));
    }
   
    protected function importFKs( string $tableName ) : array  {
        $sql = "SELECT table_name, constraint_name, column_name, ordinal_position, 
                    referenced_table_name, referenced_column_name 
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = '{$this->schemaName}' AND TABLE_NAME = '$tableName'
                ORDER BY constraint_name, ordinal_position";
        $result = $this->db->query($sql);
        $fks = $result->fetch_all(MYSQLI_ASSOC);
        return ForeignKey::importDef( $fks );
    }
    
    public static function importDef(array $def)    {
    }

    // TODO generate script from definitions
    public function generateSQL()
    {}

    
}
// $id = Integer::id()->length(8)->autoIncrement();
// $employee = Table::Employee([$id,
//     VarChar::name()->length(30)->nullable(),
//     TimeStamp::addedOn()->default('CURRENT_TIMESTAMP')
// ])->primaryKey($id);

// // print_r($employee);
// echo $employee->generateSQL();

MySQL::initialize();
$conn = new \mysqli("172.17.0.3", "root","a177fgvTRw", "employees" );
$mySQL = MySQL::employees( $conn );
$category = $mySQL->importTable("dept_emp");
$category->static;
echo implode(PHP_EOL, $category->generateSQL() );
print_r($category->getDetail("references"));

// echo $mySQL->importTables(["dept_emp"]);
// echo $mySQL->importTables();

// TODO See how model would manage a many to many link - should be able to do directly
// rather than having to add extra for middle table.
