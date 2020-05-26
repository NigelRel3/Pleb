<?php
namespace Pleb\core\Data;

class ForeignKey extends Option             {
    static protected $required = ['references'];
    static protected $optional = ['ondeletecascade', 'for'];
    
    /**
     * Import data from array of index data (example data)...
     * Foreign Keys -
     *  "Column_name":"emp_no"
     *  "constraint_name":"PRIMARY"
     *  "ordinal_position": "1",
     *  "Referenced_Table":null
     *  "Referenced_Column":null
     *
     * @param string $tableName
     * @param array $def
     * @return array Option
     */
    public static function importDef(array $defs)    {
        $indicies = [];
        // Group by key name
        foreach ( $defs as $definition ) {
            $indicies[$definition["constraint_name"]][$definition["ordinal_position"]] = $definition;
        }
        $options = [];
        foreach ( $indicies as $name => $index ) {
            $firstKey = array_keys($index)[0];
            $option = ForeignKey::{$name}(...array_column($index, "column_name"));
            $referenced = $index[$firstKey]["referenced_table_name"];
            // Ensure FK
            if ( $referenced != null )  {
                $options[] = $option->references($referenced,
                    ...array_column($index, "referenced_column_name"));
            }
        }
        return $options;
    }
    
    public function generateSQL() : string  {
        if ( isset($this->details['for']) ) {
            $options = $this->details['for'];
            $def = "alter table `". $this->decodeName($options[0]).
            "` add constraint `{$this->name}` foreign key (";
            array_shift($options);
            foreach ( $options as $col )   {
                $def .= '`'.$this->decodeName($col).'`,';
            }
            $def = rtrim($def, ",");
            
        }
        else   {
            $def = 'constraint foreign key `'.$this->name.'`(';
            foreach ( $this->details['columns'] as $col )   {
                $def .= '`'.$this->decodeName($col).'`,';
            }
            $def = rtrim($def, ",");
        }
        $ref = $this->details['references'];
        $table = array_shift($ref);
        $def .=  ") ".PHP_EOL."        references `".($this->decodeName($table)).'`(';
        foreach ( $ref as $col )   {
            $def .= '`'.$this->decodeName($col).'`,';
        }
        $def = rtrim($def, ",").")";
        
        if ( isset($this->details['ondeletecascade']) ) {
            $def .= PHP_EOL.'        ON DELETE CASCADE ';
        }
        return $def;
        
    }
    
}
// $indexes = '[{"Column_name":"emp_no",
//                     "Key_name":"dept_manager_ibfk_1",
//                     "Referenced_Table":"employees",
//                     "Referenced_Column":"emp_no",
//                     "Seq_in_index":"1"},
//                     {"Column_name":"dept_no",
//                     "Key_name":"dept_manager_ibfk_1",
//                     "Referenced_Table":"departments",
//                     "Referenced_Column":"dept_no",
//                     "Seq_in_index":"2"}]';
// $options = ForeignKey::importDef( json_decode($indexes, true));
// echo $options[0]->generateSQL().PHP_EOL;
