<?php
namespace Pleb\test\Model\TestClasses;

use Monolog\Logger;
use Pleb\core\Model\ModelStatic;

class DepartmentsStaticGen extends ModelStatic {
    protected static $selectSQL = "select d.`dept_no`, d.`dept_name
                                    from departments d ";
    protected static $selectSQLWhere = "d.`dept_no` = :dept_no";
    
    protected static $keyFields = ["dept_no"];
    protected static $dataLookup = [
        "d001" => ['dept_no' => "d001", 'dept_name' =>'department 001'],
        "d002" => ['dept_no' => "d002", 'dept_name' =>'department 002'],
        "d003" => ['dept_no' => "d003", 'dept_name' =>'department 003'],
        "d004" => ['dept_no' => "d004", 'dept_name' =>'department 004'],
        "d005" => ['dept_no' => "d005", 'dept_name' =>'department 005'],
        "d006" => ['dept_no' => "d006", 'dept_name' =>'department 06'],
        "d009" => ['dept_no' => "d009", 'dept_name' =>'department d009']
    ];
    
    public function getDeptNo():int {
        return $this->data['dept_no'];
    }

    public function getDeptName():string {
        return $this->data['dept_name'];
    }
   
    public function fetch( $keys ):bool    {
        return parent::fetchInt($keys['dept_no']);
    }
    
}
    