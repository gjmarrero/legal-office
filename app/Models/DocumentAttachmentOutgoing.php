<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentAttachmentOutgoing extends Model
{
    use HasFactory;

    protected $fillable = ['document_id','document_file'];

    public function outgoing_document(): BelongsTo{
        return $this->belongsTo(OutgoingDocument::class);
    }
}
