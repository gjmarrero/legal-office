<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OutgoingDocument extends Model
{
    use HasFactory;

    protected $fillable = ['date_dispatched','recipient','subject','content','remarks','document_file'];
}
