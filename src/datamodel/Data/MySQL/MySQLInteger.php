<?php
namespace Pleb\core\Data\MySQL;

use Pleb\core\Data\Column;

class MySQLInteger extends Column {
    static protected $type = "int";
    static protected $alias = ["INT", "TINYINT"];
    static protected $required = [];
    static protected $optional = [ 'length', 'autoincrement', 'default',
        'nullable', 'unique', 'unsigned' ];
    
    public function generateSQL()  {
        $def = "`{$this->name}`\t".static::$type;
        if ( isset($this->details['length']) )    {
            $def .= "(".$this->details['length'].")";
        }
        if ( array_key_exists('autoincrement', $this->details) )    {
            $def .= " not null auto_increment ";
        }
        if ( array_key_exists('unsigned', $this->details) )    {
            $def .= " unsigned ";
        }
        
        return $def.$this->addGenerateOptions();
    }
    
    static public function importDef(array $defs)  {
        $col = new MySQLInteger($defs['column_name']);
        $col->length($defs['numeric_precision']);
        if ( $defs['extra'] == 'auto_increment' )   {
            $col->autoIncrement();
        }
        $col->addImportOptions($defs);
        return $col;
    }
    
    protected function addQuotesToDefault () : bool {
        return false;
    }
}




// $id = MySQLInteger::id()->length(8)->autoIncrement()->default(4);
// echo $id->generateSQL().PHP_EOL;
// $columnJSON = '{
// 		  "column_name": "emp_no",
// 		  "column_default": "12",
// 		  "is_nullable": "YES",
// 		  "data_type": "int",
// 		  "character_maximum_length": null,
// 		  "numeric_precision": "10",
// 		  "numeric_scale": "0"
// 	       }';
// $column = json_decode($columnJSON, true);
// $newColumn = MySQLInteger::importDef($column);
// echo $newColumn->generateSQL().PHP_EOL;

