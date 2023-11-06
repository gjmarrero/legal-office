<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Znck\Eloquent\Traits\BelongsToThrough;
// use Znck\Eloquent\Relations\BelongsToThrough;



class DocumentReset extends Model
{
    use HasFactory;
    use BelongsToThrough;
    protected $fillable = [
        'date_received',
        'document_id',
    ];

    public function transactions(): HasMany{
        return $this->hasMany(Transaction::class,'document_id','document_id');
    }

    public function client(){
        return $this->belongsToThrough(Client::class, Document::class);
    }
    public function scopeActive($query){
        return $query->where('status',DocumentStatus::ACTIVE);
    }

    public function scopeReferral($query){
        return $query->whereIn('type',[DocumentType::MUNICIPAL_ORDINANCE,DocumentType::PROVINCIAL_ORDINANCE,DocumentType::OTHER_REFERRAL,DocumentType::CODE,DocumentType::ADMIN_DOCS]);
    }

    public function scopeCase($query){
        return $query->whereIn('type',[DocumentType::ADMINISTRATIVE,DocumentType::JUDICIAL,DocumentType::QUASI_JUDICIAL]);
    }

    public function scopeNearDueThreeDays($query){
        return $query->whereRaw("DATEDIFF('".now()."',document_resets.date_received) <= 3");
    }

    public function scopePastDueThreeDays($query){
        return $query->whereRaw("DATEDIFF('".now()."',document_resets.date_received) > 3");
    }    

    // public function scopeSevenDays($query){
    //     return $query->whereIn('type',[DocumentType::MUNICIPAL_ORDINANCE,DocumentType::OTHER_REFERRAL]);
    // }

    public function scopeNearDueSevenDays($query){
        return $query->whereRaw("DATEDIFF('".now()."',document_resets.date_received) >= 4")->whereRaw("DATEDIFF('".now()."',document_resets.date_received) <=7");
    }

    public function scopePastDueSevenDays($query){
        return $query->whereRaw("DATEDIFF('".now()."',document_resets.date_received) > 7");
    }

    // public function scopeTenDays($query){
    //     return $query->whereIn('type',[DocumentType::PROVINCIAL_ORDINANCE,DocumentType::CODE]);
    // }

    public function scopeNearDueTenDays($query){
        return $query->whereRaw("DATEDIFF('".now()."',date_received) >= 7")->whereRaw("DATEDIFF('".now()."',date_received) <=10");
    }

    public function scopePastDueTenDays($query){
        return $query->whereRaw("DATEDIFF('".now()."',document_resets.date_received) > 10");
    }

    public function scopeAdministrativeCases($query){
        return $query->where('type', DocumentType::ADMINISTRATIVE);
    }

    public function scopeJudicialCases($query){
        return $query->where('type', DocumentType::JUDICIAL);
    }

    public function scopeQuasiCases($query){
        return $query->where('type', DocumentType::QUASI_JUDICIAL);
    }

    public function scopeAdminDocs($query){
        return $query->where('type', DocumentType::ADMIN_DOCS);
    }

    public function scopeMunicipalOrdinances($query){
        return $query->where('type', DocumentType::MUNICIPAL_ORDINANCE);
    }

    public function scopeProvincialOrdinances($query){
        return $query->where('type', DocumentType::PROVINCIAL_ORDINANCE);
    }

    public function scopeCodes($query){
        return $query->where('type', DocumentType::CODE);
    }

    public function scopeOtherReferrals($query){
        return $query->where('type', DocumentType::OTHER_REFERRAL);
    }
    // public function scopeFifteenDays($query){
    //     return $query->whereIn('type',[DocumentType::JUDICIAL,DocumentType::ADMINISTRATIVE,DocumentType::QUASI_JUDICIAL]);
    // }

    public function scopeNearDueFifteenDays($query){
        return $query->whereRaw("DATEDIFF('".now()."',document_resets.date_received) >=12")->whereRaw("DATEDIFF('".now()."',document_resets.date_received) <=15");
    }

    public function scopePastDueFifteenDays($query){
        return $query->whereRaw("DATEDIFF('".now()."',document_resets.date_received) > 15");
    }

    public function scopeByDocType($query){
        return $query->active()
            ->when(request('doc_type') === 'cases', function ($query){
                $query->case();
            })
            ->when(request('doc_type') === 'administrative', function ($query){
                $query->administrativeCases();
            })
            ->when(request('doc_type') === 'judicial', function ($query) {
                $query->judicialCases();
            })
            ->when(request('doc_type') === 'quasi', function ($query) {
                $query->quasiCases();
            })
            ->when(request('doc_type') === 'referrals', function ($query){
                $query->referral();
            })
            ->when(request('doc_type') === 'admin_docs', function ($query){
                $query->adminDocs();
            })
            ->when(request('doc_type') === 'municipal', function ($query){
                $query->municipalOrdinances();
            })
            ->when(request('doc_type') === 'other_referral', function ($query){
                $query->otherReferrals();
            })
            ->when(request('doc_type') === 'provincial', function ($query){
                $query->provincialOrdinances();
            })
            ->when(request('doc_type') === 'code', function ($query) {
                $query->codes();
            });
    }
}
