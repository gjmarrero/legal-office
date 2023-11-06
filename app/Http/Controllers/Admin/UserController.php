<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function index(){
        $searchQuery = request('query');

        $users = User::query()
            ->when(request('query'), function($query, $searchQuery){
                $query->where('name', 'like', "%{$searchQuery}%");
            })
            ->latest()
            ->paginate(setting('pagination_limit'));

        return $users;
    }
    
    public function store(){
        request()->validate([
            'employee' => 'required',
            'name' => 'required',
            'email' => 'required|unique:users,email',
            'password' => 'required|min:8',
        ]);

        return User::create([
            'employee_id' => request('employee'),
            'name' => request('name'),
            'email' => request('email'),
            'password' => bcrypt(request('password')),
        ]);

    }

    public function update(User $user){
        request()->validate([
            'employee' => 'required',
            'name' => 'required',
            'email' => 'required|unique:users,email,' . $user->id,
            'password' => 'sometimes|min:8',
        ]);
        $user->update([
            'employee_id' =>request('employee'),
            'name' => request('name'),
            'email' => request('email'),
            'password' => request('password') ? bcrypt(request('password')) : $user->password,
        ]); 

        return $user;
    }

    public function delete(User $user){
        $user->delete();

        return response()->noContent();
    }

    public function changeRole(User $user){
        $user->update([
            'role' => request('role'),
        ]);

        return response()->json(['success' => true]);
    }

    public function bulkDelete(){
        User::whereIn('id', request('ids'))->delete();

        return response()->json(['message' => "Users deleted successfully"]);
    }

    public function getCurrentUser(){
        return Auth::user();
    }
}
