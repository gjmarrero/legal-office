<?php

namespace App\Enums;

enum TransactionStatus: int{
    case COMPLETED = 1;
    case PENDING = 2;

    public function color(): string{
        return match($this){
            TransactionStatus::COMPLETED => 'warning',
            TransactionStatus::PENDING => 'success',
        };
    }
}
