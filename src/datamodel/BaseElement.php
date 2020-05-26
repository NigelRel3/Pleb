<?php
namespace Pleb\core;


abstract class BaseElement implements Element {
    protected $name = null;
    protected $details = null;
    static protected $required = [];
    static protected $options = [];
    
    protected function __construct( string $name ) {
        $this->name = $name;
    }
    
    public static function __callstatic(string $name , array $arguments): Element    {
        return new static($name);
    }
    
    public function __call(string $name , array $arguments = [] ): Element    {
        $optionName = strtolower($name);
        static::validateCall($optionName, $arguments);
        
        if ( count($arguments) == 0)    {
            $arguments = null;
        }
        else if ( count($arguments) == 1) {
            $arguments = $arguments[0];
        }
        
        // Check if field already set
        if ( isset($this->details[$optionName]))    {
            // If instance of a BaseElement, convert to an array
            if ( $this->details[$optionName] instanceof BaseElement )    {
                $this->details[$optionName] = [$this->details[$optionName]];
            }
            $this->details[$optionName][] = $arguments;
        }
        else    {
            $this->details[$optionName] = $arguments;
        }
        return $this;
    }
    
    protected function validateCall ( string $name, array $arguments )  {
        return true;
    }
    
    public function decodeName( $element ) : string {
        return ($element instanceof BaseElement)?$element->getName():$element;
    }
    
    public function getName() : string  {
        return $this->name;
    }
    
    public function __get(string $name ): Element  {
        return $this->__call($name);
    }
 
    public function getDetail ( string $detailName )   {
        return $this->details[$detailName] ?? false;
    }
}
