<?php
namespace Pleb\core\Data;

class PrimaryKey extends Index  {
    public function generateSQL(): string   {
        // TODO allow to flag that is to be part of table creation
        // Currently this is coded to add the primary key.
        // Alternative is `, PRIMARY KEY (`a`)`
        $refTable = $this->details['on'];
        $def = 'alter table `'.$this->decodeName($refTable)
        .'` add primary key (';
        foreach ( $this->details['columns'] as $col )   {
            $def .= '`'.$this->decodeName($col).'`,';
        }
        $def = rtrim($def, ",").")";
        return $def;
    }
    
}
// $indexes = '[{"Table" : "employee", "Column_name":"emp_no",
//                     "Key_name":"dept_manager_ibfk_1",
//                     "Referenced_Table":"employees",
//                     "Referenced_Column":"emp_no",
//                     "Seq_in_index":"1",
//                     "Non_unique" : "0"},
//                     {"Table" : "employee", "Column_name":"dept_no",
//                     "Key_name":"dept_manager_ibfk_1",
//                     "Referenced_Table":"departments",
//                     "Referenced_Column":"dept_no",
//                     "Seq_in_index":"2",
//                     "Non_unique" : "0"},
// {"Table" : "employee", "Column_name":"emp_no",
//                     "Key_name":"PRIMARY",
//                     "Referenced_Table":"employees",
//                     "Referenced_Column":"emp_no",
//                     "Seq_in_index":"1",
//                     "Non_unique" : "0"}]';
// $options = Index::importDef( json_decode($indexes, true));
// foreach ( $options as $option ) {
//     echo $option->generateSQL().PHP_EOL;
// }
