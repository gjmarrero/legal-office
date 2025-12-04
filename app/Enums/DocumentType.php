<?php

namespace App\Enums;

enum DocumentType: int
{
    case MUNICIPAL_ORDINANCE = 1;
    case PROVINCIAL_ORDINANCE = 2;
    case CODE = 3;
    case OTHER_REFERRAL = 4;
    case JUDICIAL = 5;
    case ADMINISTRATIVE = 6;
    case QUASI_JUDICIAL = 7;
    case ADMIN_DOCS = 8;
    case NOTARY = 9;



    public function timeline(): int
    {
        return match ($this) {
            DocumentType::MUNICIPAL_ORDINANCE => 7,
            DocumentType::PROVINCIAL_ORDINANCE => 10,
            DocumentType::CODE => 10,
            DocumentType::OTHER_REFERRAL => 7,
            DocumentType::JUDICIAL => 15,
            DocumentType::ADMINISTRATIVE => 15,
            DocumentType::QUASI_JUDICIAL => 15,
            DocumentType::ADMIN_DOCS => 3,
            DocumentType::NOTARY => 3,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::MUNICIPAL_ORDINANCE => 'Municipal Ordinance',
            self::PROVINCIAL_ORDINANCE => 'Provincial Ordinance',
            self::CODE => 'Code',
            self::OTHER_REFERRAL => 'Other Referral',
            self::JUDICIAL => 'Judicial',
            self::ADMINISTRATIVE => 'Administrative',
            self::QUASI_JUDICIAL => 'Quasi Judicial',
            self::ADMIN_DOCS => 'Admin Docs',
            self::NOTARY => 'Notary',
        };
    }
}

