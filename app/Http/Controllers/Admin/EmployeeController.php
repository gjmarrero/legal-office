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

    public function store(){
        request()->validate([
            'emp_name' => 'required',
            'emp_position' => 'required',
        ]);

        return Employee::create([
            'emp_name' => request('emp_name'),
            'emp_position' => request('emp_position'),
        ]);
    }

    // public function getCurrentEmployee($user_id){        
    //     $employee = Employee::with('users')->where('users.employee_id',$user_id)->toSql();

    //     return $employee;
    // }
}
