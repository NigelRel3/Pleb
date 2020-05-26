<?php
namespace Pleb\core\Data\MySQL;

use Pleb\core\Data\Column;

class MySQLVarChar extends Column {
    static protected $type = "varchar";
    static protected $alias = ["VARCHAR", "CHAR", "BINARY"];
    static protected $required = ['length'];
    static protected $optional = [ 'default', 'nullable', 'unique' ];
    public static function importDef(array $defs)    {
        $col = new MySQLVarChar($defs['column_name']);
        $col->length($defs['character_maximum_length']);
        
        $col->addImportOptions($defs);
        return $col;
    }
    
    public function generateSQL()   {
        $def = "`{$this->name}`\t".static::$type."(".$this->details['length'].")";
        
        return $def.$this->addGenerateOptions();
    }
}
// $columnJSON = '{
// 		  "column_name": "last_name",
// 		  "column_default": null,
// 		  "is_nullable": "NO",
// 		  "data_type": "varchar",
// 		  "character_maximum_length": "16",
// 		  "numeric_precision": null,
// 		  "numeric_scale": null
// 	       }';
// $column = json_decode($columnJSON, true);
// $newColumn = MySQLVarChar::importDef($column);
// echo $newColumn->generateSQL().PHP_EOL;
