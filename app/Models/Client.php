<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Client extends Model
{
    use HasFactory;

    protected $fillable = ['name','office'];

    public function document_resets(): HasManyThrough{
        return $this->HasManyThrough(DocumentReset::class, Document::class);
    }
}
