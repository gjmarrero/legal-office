<?php

namespace App\Models;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'document_id',
        'action',
        'document_file',
        'status',
        'user_id',
        'type',
        'remarks',
    ];

    public function employee(): BelongsTo{
        return $this->belongsTo(Employee::class);
    }

    public function user(): BelongsTo{
        return $this->belongsTo(User::class);
    }

    public function document(): BelongsTo{
        return $this->belongsTo(related: Document::class);
    }

    public function formattedDateAssigned(): Attribute{
        return Attribute::make(
            get: fn() => $this->created_at->format('Y-m-d H:i:s'),
        );
    }

    // public function last_assignment(): Attribute{
    //     return $this->las
    // }
    protected $appends = ['formatted_date_assigned'];

    protected $casts = [
        'status' => TransactionStatus::class,
        'type' => TransactionType::class,
    ];
}
