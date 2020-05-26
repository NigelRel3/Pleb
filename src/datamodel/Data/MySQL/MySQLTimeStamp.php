<?php
namespace Pleb\core\Data\MySQL;

use Pleb\core\Data\Column;

class MySQLTimeStamp extends Column {
    static protected $type = "date";
    // TODO check all aliases
    static protected $alias = [ "DATE", "DATETIME", "TIMESTAMP"];
    static protected $required = [];
    static protected $optional = [ 'length', 'autoincrement', 'default',
        'nullable', 'unique', 'unsigned' ];
    
    public function generateSQL() : string  {
        $def = "`{$this->name}`\t".static::$type;
        if ( isset($this->details['length']) )    {
            $def .= "(".$this->details['length'].")";
        }
        if ( isset($this->details['autoincrement']) )    {
            $def .= " NOT NULL AUTO_INCREMENT ";
        }
        if ( isset($this->details['unsigned']) )    {
            $def .= " UNSIGNED ";
        }
        
        return $def.$this->addGenerateOptions();
    }
    
    static public function importDef(array $defs)  {
        $col = new MySQLTimeStamp($defs['column_name']);
        $col->length($defs['numeric_precision']);
        
        $col->addImportOptions($defs);
        return $col;
    }
    
    protected function addQuotesToDefault () : bool {
        return ($this->details['default']??'') != 'CURRENT_TIMESTAMP';
    }
}
// $columnJSON = '{
// 		  "column_name": "last_name",
// 		  "column_default": "CURRENT_TIMESTAMP",
// 		  "is_nullable": "NO",
// 		  "data_type": "date",
// 		  "character_maximum_length": "16",
// 		  "numeric_precision": null,
// 		  "numeric_scale": null
// 	       }';
// $column = json_decode($columnJSON, true);
// $newColumn = MySQLTimeStamp::importDef($column);
// echo $newColumn->generateSQL().PHP_EOL;
