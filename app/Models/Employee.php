<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = ['emp_name','emp_position'];

    public function users(): HasMany{
        return $this->hasMany(User::class);
    }
}
