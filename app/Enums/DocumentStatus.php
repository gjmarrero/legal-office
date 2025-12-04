<?php

namespace App\Enums;

enum DocumentStatus: int{
    case ACTIVE = 1;
    case ARCHIVED = 2;

    public function color(): string{
        return match($this){
            DocumentStatus::ACTIVE => 'primary',
            DocumentStatus::ARCHIVED => 'success',
        };
    }

    public function label() {
        return match($this){
            DocumentStatus::ACTIVE => 'Active',
            DocumentStatus::ARCHIVED => 'Archived',
        };
    }
}
