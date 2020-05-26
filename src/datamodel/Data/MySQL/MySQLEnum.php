<?php
namespace Pleb\core\Data\MySQL;

use Pleb\core\Data\Column;

class MySQLEnum extends Column {
    static protected $type = "enum";
    static protected $alias = [ "ENUM" ];
    static protected $required = ['values'];
    static protected $optional = [ 'default', 'nullable' ];
    public static function importDef(array $defs)    {
        $col = new MySQLEnum($defs['column_name']);
        $values = substr($defs['column_type'], 5, -1);
        $values = str_getcsv($values,",", "'");
        $col->values(...$values);
        $col->addImportOptions($defs);
        return $col;
    }
    
    public function generateSQL()   {
        $def = "`{$this->name}`\t{$this->type}('".
            implode("','", $this->details['values'])."')";
            
        return $def.$this->addGenerateOptions();;
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
