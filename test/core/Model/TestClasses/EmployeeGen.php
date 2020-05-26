<?php
namespace Pleb\test\Model\TestClasses;

use Monolog\Logger;
use Pleb\core\Model\Model;

class EmployeeGen extends Model {
    
    protected static $keyFields = ["emp_no"];
    protected static $insertSQL = "insert into `employees` (`emp_no`, `birth_date`,
                `first_name`, `last_name`, `gender`, `hire_date`)
            values (:emp_no, :birth_date, :first_name, 
                :first_name, :last_name, :gender, :hire_date)";
    protected static $updateSQL = "update `employees` 
            set `emp_no` = :emp_no, `birth_date` = :birth_date,
                `first_name` = :first_name, `last_name` = :last_name,
                `gender` = :gender, `hire_date` = :hire_date
            where `emp_no` = :emp_no_old";
    protected static $selectSQL = "select e.`emp_no`, e.`birth_date`,
                e.`first_name`, e.`last_name`, e.`gender`, e.`hire_date`
            from `employees` e";
    protected static $selectSQLWhere = "e.`emp_no` = :emp_no";
    protected static $deleteSQL = "delete from `employees`
            where `emp_no` = :emp_no";
    
    public function getEmpNo():int {
        return $this->data['emp_no'];
    }

    public function getBirthDate():string {
        return $this->data['birth_date'];
    }
   
    public function getFirstName():string {
        return $this->data['first_name'];
    }
    
    public function getLastName():string {
        return $this->data['last_name'];
    }
    
    public function getGender():string {
        return $this->data['gender'];
    }
    
    public function getHireDate():string {
        return $this->data['hire_date'];
    }
    
    public function setEmpNo( string $empNo ):void {
        $this->data['emp_no'] = $empNo;
    }
    
    public function setBirthDate( string $birthDate ):void {
        $this->data['birth_date'] = $birthDate;
    }
    
    public function setFirstName( string $firstName ):void {
        $this->data['first_name'] = $firstName;
    }
    
    public function setLastName( string $lastName ):void {
        $this->data['last_name'] = $lastName;
    }
    
    public function setGender( string $gender ):void {
        $this->data['gender'] = $gender;
    }
    
    public function setHireDate( string $hireDate ):void {
        $this->data['hire_date'] = $hireDate;
    }
    
}
    