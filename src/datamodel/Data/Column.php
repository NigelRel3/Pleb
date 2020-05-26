<?php
namespace Pleb\core\Data;

abstract class Column extends DataElement     {
    protected function addGenerateOptions (): string   {
        $options = '';
        if ( $this->details['nullable']??"YES" == "NO" )    {
            $options .= " not null ";
        }
        if ( isset($this->details['default']) )    {
            $options .= " default ";
            $value = $this->details['default'];
            if ( $this->addQuotesToDefault() )  {
                $options .="'".$value."' ";
            }
            else    {
                $options .= $value." ";
            }
        }
        
        if ( array_key_exists('unique', $this->details) )  {
            $options .= " unique";
        }
        
        return $options;
    }
    
    protected function addQuotesToDefault () : bool {
        return true;
    }
    
    protected function addImportOptions(array $defs)  {
        $this->nullable(( $defs['is_nullable']??'' == "YES" ));
        if ( !empty($defs['column_default']) )   {
            $this->default($defs['column_default']);
        }
    }
    
    // TODO Check needed
    public function getOptions(): string    {
        $ret = '';
        if ( isset($this->details['unique']) )  {
            $ret .= 'UNIQUE `'.$this->name.'IX` (`'.$this->name.'`)';
        }
        return $ret;
    }
    
    public static function getAlias() : array   {
        return static::$alias;
    }
}


