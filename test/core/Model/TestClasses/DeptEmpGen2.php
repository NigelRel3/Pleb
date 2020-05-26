<?php
namespace Pleb\test\Model\TestClasses;

use Monolog\Logger;
use Pleb\core\Model\Model;

class DeptEmpGen2 extends Model {
    
    /**
     * TODO
     * Need to think about many to many relationship.
     * @var array
     */
    
    /** Configured as something like 
     *       Departments => \ModelStatic::class, lazy
     *       Employee => \Model::class, lazy
     */
    protected static $keyFields = ["emp_no", "dept_no"];
    protected static $insertSQL = "insert into `dept_emp` (`emp_no`, `dept_no`, 
                    `from_date`, `to_date`)
                 values (:emp_no, :dept_no, :from_date, :to_date)";
    protected static $updateSQL = "update `dept_emp`
                            set `emp_no` = :emp_no,
                                `dept_no` = :dept_no,
                                `from_date` = :from_date,
                                `to_date` = :to_date
                            where `emp_no` = :emp_no_old and `dept_no` = :dept_no_old";
    protected static $selectSQL = "select de.`emp_no`, de.`dept_no`, de.`from_date`
                                        , de.`to_date`
                                from `dept_emp` de";
    protected static $selectSQLWhere = "de.`emp_no` = :emp_no_old and de.`dept_no` = :dept_no";
    protected static $deleteSQL = "delete from `dept_emp`
                                where `emp_no` = :emp_no and `dept_no` = :dept_no";
    
    protected $department = null;
    protected $employee = null;
    
    public function getEmpNo():string {
        return $this->data['emp_no'];
    }
    
    public function getDeptNo():string {
        return $this->data['dept_no'];
    }
    
    public function getFromDate():string {
        return $this->data['from_date'];
    }
    
    public function getToDate():string {
        return $this->data['to_date'];
    }
    
    public function getDepartment():DepartmentsStaticGen {
        if ( $this->department == null ) {
            $this->department = new DepartmentsStaticGen($this->db, $this->log);
            $ret = $this->department->fetch(["dept_no" => $this->data['dept_no']]);
            if ( ! $ret )   {
                $this->department == null;
            }
        }
        return $this->department;
    }
    
    public function getEmployee():EmployeeCacheGen {
        if ( $this->employee == null ) {
            $this->employee = new EmployeeCacheGen($this->db, $this->log);
            
            $ret = $this->employee->fetch(["emp_no" => $this->data['emp_no']]);
            if ( ! $ret )   {
                $this->employee == null;
            }
        }
        return $this->employee;
    }
    
    public function setEmpNo( string $empNo ):void {
        $this->data['dept_no'] = $empNo;
    }
    
    public function setDeptNo( string $deptNo ):void {
        $this->data['dept_name'] = $deptNo;
    }
    
    public function setFromDate( string $fromDate ):void {
        $this->data['from_date'] = $fromDate;
    }
    
    public function setToDate( string $toDate ):void {
        $this->data['to_date'] = $toDate;
    }
    
}