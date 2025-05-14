<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OutgoingDocument extends Model
{
    use HasFactory;

    protected $fillable = ['date_dispatched','recipient','recipient_office','subject','content','remarks','document_file'];

    public function attachments(): HasMany{
        return $this->hasMany(DocumentAttachmentOutgoing::class,"document_id","id");
    }
}
