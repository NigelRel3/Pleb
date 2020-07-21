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
    
    public static function getFieldFromType ( string $type )	{
    	foreach ( static::$types as $registeredType => $fieldType )	{
    		$prefix = $fieldType::getDBType();
    		if ( substr($type, 0, strlen($prefix)) === $prefix )	{
    			return Field::{$registeredType}(...$fieldType::getDefaultParams());
    		}
    	}
    	return false;
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
    
    abstract public static function getDBType() : string;
    abstract public function getDBDefinition() : string;
    public static function getDefaultParams()	{
    	return [];
    }
    
    protected $nullable = false;
    public function nullable()	{
    	$this->nullable = true;
    	return $this;
    }
    
    public function isNullable()	{
    	return $this->nullable;
    }
    
    public function isNull ( $value )	{
    	return ( $value === "" ||  $value === null) && 
    			$this->nullable === true;
    }
}

class IntField extends Field    {
    public function set ( $value ) : void    {
    	$this->value = $value;
    	// Check for not being numeric or empty and not nullable
    	if ( !is_int($value) && !ctype_digit($value) ||	$this->isNull( $value ) ) {
            throw new \InvalidArgumentException("Invalid integer");
        }
    }
    public function requiresFormatting () : bool   {
    	return true;
    }
    
    public static function getDBType() : string	{
    	return 'int';
    }
    public function getDBDefinition(): string {
		return 'int';
	}

}
Field::registerType("INT", IntField::class );

class StringField extends Field    {
	public static function getDBType() : string	{
		return 'varchar';
	}
	public function getDBDefinition(): string {
		// Perhaps should allow default to be configured
		return 'varchar('. ($this->format[0] ?? 100) .')';
	}

}
Field::registerType("STRING", StringField::class );

class EnumField extends Field    {
	public static function getDBType() : string	{
		return 'enum';
	}
	public function getDBDefinition(): string {
		return 'enum';
	}

	
}
Field::registerType("ENUM", EnumField::class );

class DateField extends Field    {
    public function set ( $value ) : void   {
    	if ( !is_string($value) || $this->isNull( $value ) )	{
    		$this->value = $value;
    	}
        elseif ( is_string($value) )    {
            $this->value = \DateTime::createFromFormat ( $this->format[0],
                $value);
            if ( $this->value === false )	{
            	throw new \InvalidArgumentException("Invalid date");
            }
        }
    }
    
    public function __toString() : string   {
    	if ( $this->value === false )	{
    		throw new \InvalidArgumentException("Invalid date");
    	}
    	if ( $this->isNull($this->value) )	{
    		return "";
    	}
    	return $this->value->format($this->format[0]);
    }
    
    public function requiresFormatting () : bool   {
        return true;
    }
    
    public static function getDBType() : string	{
    	return 'date';
    }
    public function getDBDefinition(): string {
		return 'date';
	}
	public static function getDefaultParams()	{
		return ["Y-m-d"];
	}
}
Field::registerType("DATE", DateField::class );
