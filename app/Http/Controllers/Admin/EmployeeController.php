<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Employee;

class EmployeeController extends Controller
{
    public function index(){
        return Employee::latest()->get();
    }

    // public function getCurrentEmployee($user_id){        
    //     $employee = Employee::with('users')->where('users.employee_id',$user_id)->toSql();

    //     return $employee;
    // }
}
