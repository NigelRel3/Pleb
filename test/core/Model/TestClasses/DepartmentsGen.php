<?php
namespace Pleb\test\Model\TestClasses;

use Monolog\Logger;
use Pleb\core\Model\Model;

class DepartmentsGen extends Model {
        
    protected static $keyFields = ["dept_no"];
    protected static $insertSQL = "insert into `departments` (`dept_no`, `dept_name`)
                                values (:dept_no, :dept_name)";
    protected static $updateSQL = "update `departments` 
                            set `dept_no` = :dept_no,
                                `dept_name` = :dempt_name
                            where `dept_no` = :dept_no_old";
    protected static $selectSQL = "select d.`dept_no`, d.`dept_name`
                                from `departments` d";
    protected static $selectSQLWhere = "d.`dept_no` = :dept_no";
    protected static $deleteSQL = "delete from `departments`
                                where `dept_no` = :dept_no";
    
    public function getDeptNo():string {
        return $this->data['dept_no'];
    }

    public function getDeptName():string {
        return $this->data['dept_name'];
    }
   
    public function setDeptNo( string $deptNo ):void {
        $this->data['dept_no'] = $deptNo;
    }
    
    public function setDeptName( string $deptName ):void {
        $this->data['dept_name'] = $deptName;
    }
    
}
    