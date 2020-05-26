<?php
namespace Pleb\core\Data;

use Pleb\core\BaseElement;
use Pleb\core\Element;

abstract class DataElement extends BaseElement {
    static protected $alias = null;
    
    abstract public function generateSQL();
        
    abstract static public function importDef(array $def);
    
    public static function __callstatic(string $name , array $arguments): Element    {
        $created = new static($name);
        if ( !empty($arguments) )   {
            $created->details['columns'] = $arguments;
        }
        return $created;
    }
    
    protected function validateCall ( string $name, array $arguments )  {
        if ( in_array($name, static::$required) === false
            && in_array($name, static::$optional) === false )   {
                throw new \Exception("Invalid option '$name' for field '".
                    $this->name."' type ". static::class);
            }
            return true;
    }
}
