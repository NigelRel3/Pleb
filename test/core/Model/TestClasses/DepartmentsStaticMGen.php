<?php
namespace Pleb\test\Model\TestClasses;

use Monolog\Logger;
use Pleb\core\Model\ModelStatic;

class DepartmentsStaticMGen extends ModelStatic {
    protected static $keyFields = ["dept_no", "ver_no"];
    protected static $dataLookup = [
        '{"dept_no":"d001","ver_no":"1"}' => ['dept_no' => "d001", 'ver_no' => "1", 'dept_name' =>'dept d001'],
        '{"dept_no":"d001","ver_no":"2"}' => ['dept_no' => "d001", 'ver_no' => "2", 'dept_name' =>'depatment d001'],
        '{"dept_no":"d004","ver_no":"1"}' => ['dept_no' => "d004", 'ver_no' => "1", 'dept_name' =>'dept d004'],
        '{"dept_no":"d009","ver_no":"1"}' => ['dept_no' => "d009", 'ver_no' => "1", 'dept_name' =>'dept d009']
    ];
    
    public function getDeptNo():int {
        return $this->data['dept_no'];
    }

    public function getVerNo():int {
        return $this->data['dept_no'];
    }
    
    public function getDeptName():string {
        return $this->data['dept_name'];
    }
   
}
    