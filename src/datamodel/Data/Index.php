<?php
namespace Pleb\core\Data;

class Index extends Option             {
    static protected $required = ['on'];
    static protected $optional = ['unique'];
    
    public static function importDef(array $defs)   {
        $indicies = [];
        // Group by key name
        foreach ( $defs as $definition ) {
            $indicies[$definition["Key_name"]][$definition["Seq_in_index"]] = $definition;
        }
        $options = [];
        foreach ( $indicies as $name => $index ) {
            $firstKey = array_keys($index)[0];
            if ( $name == "PRIMARY" )   {
                $option = PrimaryKey::{$name}(...array_column($index, "Column_name"));
            }
            else    {
                $option = Index::{$name}(...array_column($index, "Column_name"));
            }
            // TODO how to handle collation?
            if ( $index[$firstKey]["Non_unique"] == 0 )    {
                $option->unique;
            }
            $options[] = $option->on($index[$firstKey]['Table']);
        }
        
        return $options;
    }
    
    public function generateSQL(): string   {
        $refTable = $this->details['on'];
        $def = 'alter table `'.$this->decodeName($refTable);
        $def .= '` add index `'.$this->name.'` ';
        if ( isset($this->details['unique']) )      {
            $def .= 'unique ';
        }
        // Add in the columns for the index
        $def .= ' (';
        foreach ( $this->details['columns'] as $col )   {
            $def .= '`'.$this->decodeName($col).'`,';
        }
        $def = rtrim($def, ",").")";
        return $def;
    }
    
}

