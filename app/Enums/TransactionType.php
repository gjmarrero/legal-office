<?php

namespace App\Enums;

enum TransactionType: int{
    case RECEIVED = 1;
    case RELEASED = 2;

    public function color(): string{
        return match($this){
            TransactionType::RECEIVED => 'primary',
            TransactionType::RELEASED => 'success',
        };
    }
}
