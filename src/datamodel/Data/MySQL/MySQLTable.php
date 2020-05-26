<?php
namespace Pleb\core\Data\MySQL;

use Pleb\core\Element;
use Pleb\core\Data\Column;
use Pleb\core\Data\Entity;
use Pleb\core\Data\ForeignKey;
use Pleb\core\Data\Option;

class MySQLTable extends Entity     {
    static protected $optional = ['cached', 'static', 'primarykey', 'index',
        'foreignkey', 'references'
    ];
    
    public static function importDef(array $defs)   {}
    
    public function generateSQL()   {
        $alter = '';
        $def = 'create table `'.$this->name.'` ('.PHP_EOL;
        $extra = '';
        foreach ( $this->details["columns"] as $column )   {
            $def .= "    ".$column->generateSQL().','.PHP_EOL;
            $options = $column->getOptions();
            if ( !empty($options) ) {
                $extra .= $options.','.PHP_EOL;
            }
        }
        $def = rtrim($def, ",".PHP_EOL);
        $extra = rtrim($extra, ",".PHP_EOL);
        if ( isset($this->details['primarykey']) )   {
            $def.= ','.PHP_EOL."    ".$this->getPrimaryKeyDef($this->details['primarykey']);
        }
        if ( isset($this->details['index']) )   {
            foreach ( $this->details['index'] as $ind ) {
                $alter.= $ind->generateSQL().';'.PHP_EOL;
            }
        }
        if ( isset($this->details['foreignkey']) )   {
            foreach ( $this->details['foreignkey'] as $fk ) {
                $alter.= $fk->generateSQL().';'.PHP_EOL;
            }
        }
        if ( !empty($extra) )   {
            $def .= ','.PHP_EOL."    ".$extra;
        }
        $def.= PHP_EOL.');';
        if ( isset($this->details['engine']) )   {
            $def.= PHP_EOL.'ENGINE = '.$this->details['engine'][0].PHP_EOL;
        }
        return [$def, $alter];
        
    }
    
    public static function __callstatic(string $name , array $arguments): Element    {
        $table = new static($name);
        foreach ( $arguments[0] as $column ) {
            if ( !$column instanceof Column )   {
                throw new \Exception("Array must only contain instances of ".Column::class.
                    " for ".print_r($column, true));
            }
            $table->details["columns"][$column->getName()] = $column;
        }
        
        return $table;
    }
    
    protected function validateCall ( string $name, array $arguments )  {
        if ( $name == 'primarykey' ){
            if ( $arguments instanceof Column ) {
                $arguments = [$arguments];
            }
            $this->checkColumnsDefined($arguments,
                "Column %s is not defined in table");
        }
        else if ( $name == 'foreignkey' ){
            foreach ( $arguments as $option )   {
                if ( !$option instanceof Option ) {
                    throw new \Exception("Foreign key must be an instance of ".ForeignKey::class);
                }
                $this->checkColumnsDefined($option->getColumns(),
                    "Foreign key ".$option->getName()." column %s is not defined in table");
            }
            
        }
        return parent::validateCall ( $name, $arguments );
    }
    
    protected function checkColumnsDefined ( array $columns, string $message ) {
        foreach ( $columns as $keyCol ) {
            $name = $this->decodeName($keyCol);
            if ( !isset($this->details["columns"][$name]) )    {
                throw new \Exception(sprintf($message, $name));
            }
        }
    }
    
    protected function getPrimaryKeyDef ( $keyDef ): string {
        $keyFields = [];
        foreach ( is_array($keyDef)?$keyDef:[$keyDef] as $keyCol ) {
            $keyFields[] = $this->decodeName($keyCol);
        }
        
        return 'primary key (`'.implode("`,`", $keyFields).'`)';
    }
    
}
