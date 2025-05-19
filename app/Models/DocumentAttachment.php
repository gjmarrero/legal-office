<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentAttachment extends Model
{
    use HasFactory;

    public function document(): BelongsTo{
        return $this->belongsTo(Document::class);
    }
    protected $fillable = ['document_id', 'document_file'];
}
