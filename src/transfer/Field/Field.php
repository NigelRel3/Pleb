<?php
namespace Pleb\transfer\Field;

abstract class Field    {
    protected $value = null;
    protected $format = null;
    
    private static $types = [];
    
    public static function __callstatic(string $name , array $arguments): Field    {
        if ( !isset(Field::$types[ strtolower($name) ]) ){
            throw new \InvalidArgumentException("Field type {$name} not found");
        }
        
        $className = Field::$types[ strtolower($name) ];
        $newField = new $className($name);
        if ( !empty($arguments) ){
            $newField->format = $arguments;
        }
        return $newField;
    }
    
    public static function registerType ( string $type, string $class ) : void {
        Field::$types[ strtolower($type) ] = $class;
    }
    
    public function setFormat ( $format ) : void  {
        $this->format = $format;
    }
    
    public function set ( $value ) : void    {
        $this->value = $value;
    }
    
    public function __toString() : string   {
        return $this->value;
    }
    
    public function requiresFormatting () : bool   {
        return false;
    }
    
    public function get()   {
        return $this->value;
    }
}

class IntField extends Field    {
    public function set ( $value ) : void    {
        if ( !ctype_digit($value) ) {
            throw new \InvalidArgumentException("Value {$value} is not an integer");
        }
        $this->value = $value;
    }
}
Field::registerType("INT", IntField::class );

class StringField extends Field    {
}
Field::registerType("STRING", StringField::class );

// TODO code EnumField including Model field types
class EnumField extends Field    {
}
Field::registerType("ENUM", EnumField::class );

class DateField extends Field    {
    public function set ( $value ) : void   {
        if ( is_string($value) )    {
            $this->value = \DateTime::createFromFormat ( $this->format[0],
                $value);
        }
        else    {
            $this->value = $value;
        }
    }
    
    public function __toString() : string   {
        return $this->value->format($this->format[0]);
    }
    
    public function requiresFormatting () : bool   {
        return true;
    }
}
Field::registerType("DATE", DateField::class );
