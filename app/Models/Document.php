<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Enums\DocumentType;
use Illuminate\Database\Eloquent\Relations\HasMany;
use DB;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class Document extends Model
{
    use HasFactory;

    public function client(): BelongsTo{
        return $this->belongsTo(Client::class);
    }

    public function transactions(): HasMany{
        return $this->hasMany(Transaction::class);
    }

    public function document_resets(): HasOne{
        return $this->hasOne(DocumentReset::class)->latestOfMany();
    }

    public function dateToCount(): Attribute{
        return Attribute::make(
            get: fn() => $this->document_resets()->latest('id')->first()->date_received ?? $this->date_received,
        );
    }

    public function lastAssignment(): Attribute{
        return Attribute::make(
            get: fn() => $this->transactions()->latest('id')->first()->employee_id,
        );   
    }

    public function lastTransactionType(): Attribute{
        return Attribute::make(
            get: fn() => $this->transactions()->latest('id')->first()->type,
        );
    }

    public function formattedDateToCount(): Attribute{
        return Attribute::make(
            get: fn() => $this->date_to_count->format('Y-m-d'),
        );
    }

    public function daysActive(): Attribute{
        $startDate = now();
        $endDate = Carbon::parse($this->date_to_count);

        $holidays = [
            Carbon::parse("2023-10-02"),
            Carbon::parse("2023-09-29")
        ];

        return Attribute::make(
            get: fn() => $startDate->diffInDaysFiltered(function (Carbon $date) use ($holidays) {
                return $date->isWeekday() && !in_array($date, $holidays);
            }, $endDate)
        );
    }
    
    public function scopeLastDocumentReceipt($query){
        return $query->where(function($query){
            $query->has('documents_resets');
        })->latest()->first();
    }
//
    public function scopeOverdue($query){
        return $query->whereRaw("DATEDIFF('".now()."',date_received) > 30")->where('status',1);
    }

    public function scopeActive($query){
        return $query->where('status',DocumentStatus::ACTIVE);
    }

    public function scopeWithReset($query){
        return $query->has('document_resets');
    }

    public function scopeWithoutReset($query){
        return $query->doesntHave('document_resets');
    }

    public function scopeWithResetNearDueReferrals($query){
        return $query
            ->whereHas('document_resets', function ($query) {    
                $query->when(request('referral_type') === 'admin_docs', function($query){
                    $query->nearDueThreeDays()->adminDocs();
                })
                ->when(request('referral_type') === 'municipal', function($query){
                    $query->nearDueSevenDays()->municipalOrdinances();
                })
                ->when(request('referral_type') === 'provincial', function($query){
                    $query->nearDueSevenDays()->provincialOrdinances();
                })
                ->when(request('referral_type') === 'code', function($query){
                    $query->nearDueTenDays()->codes();
                }) 
                ->when(request('referral_type') === 'other_referral', function($query){
                    $query->nearDueSevenDays()->otherReferrals();
                })       
                ->when(request('referral_type') === 'referrals', function($query){
                    $query->where(function($query){
                        $query->nearDueThreeDays()->adminDocs();
                    })
                    ->orWhere(function($query){
                        $query->nearDueSevenDays()->municipalOrdinances();
                    })
                    ->orWhere(function($query){
                        $query->nearDueSevenDays()->provincialOrdinances();
                    })
                    ->orWhere(function($query){
                        $query->nearDueTenDays()->codes();
                    })
                    ->orWhere(function($query){
                        $query->nearDueSevenDays()->otherReferrals();
                    });
                });
                
            });                                               
    }

    public function scopeWithoutResetNearDueReferrals($query){
        return $query
            ->whereDoesntHave('document_resets')
            ->when(request('referral_type') === 'referrals', function($query){
                $query->where(function($query){
                    $query->nearDueThreeDays()->adminDocs();
                })
                ->orWhere(function($query){
                    $query->nearDueSevenDays()->municipalOrdinances();
                })
                ->orWhere(function($query){
                    $query->nearDueSevenDays()->provincialOrdinances();
                })
                ->orWhere(function($query){
                    $query->nearDueTenDays()->codes();
                })
                ->orWhere(function($query){
                    $query->nearDueSevenDays()->otherReferrals();
                });
            })
            ->when(request('referral_type') === 'admin_docs', function($query){
                $query->nearDueThreeDays()->adminDocs();
            })
            ->when(request('referral_type') === 'municipal', function($query){
                $query->nearDueSevenDays()->municipalOrdinances();                
            })
            ->when(request('referral_type') === 'provincial', function($query){
                $query->nearDueSevenDays()->provincialOrdinances();
            })
            ->when(request('referral_type') === 'other_referral', function($query){
                $query->nearDueSevenDays()->otherReferrals();
            })
            ->when(request('referral_type') === 'code', function($query){
                $query->nearDueTenDays()->codes();
            });
    }

    public function scopeWithResetPastDueReferrals($query){
        return $query
            ->whereHas('document_resets', function ($query) {    
                $query->when(request('referral_type') === 'admin_docs', function($query){
                    $query->pastDueThreeDays()->adminDocs();
                })
                ->when(request('referral_type') === 'municipal', function($query){
                    $query->pastDueSevenDays()->municipalOrdinances();                
                })
                ->when(request('referral_type') === 'provincial', function($query){
                    $query->pastDueSevenDays()->provincialOrdinances();
                })
                ->when(request('referral_type') === 'code', function($query){
                    $query->pastDueTenDays()->codes();
                })
                ->when(request('referral_type') === 'other_referral', function($query){
                    $query->pastDueSevenDays()->otherReferrals();
                })        
                ->when(request('referral_type') === 'referrals', function($query){
                    $query->where(function($query){
                        $query->pastDueThreeDays()->adminDocs();
                    })
                    ->orWhere(function($query){
                        $query->pastDueSevenDays()->municipalOrdinances();
                    })
                    ->orWhere(function($query){
                        $query->pastDueSevenDays()->provincialOrdinances();
                    })
                    ->orWhere(function($query){
                        $query->pastDueTenDays()->codes();
                    })
                    ->orWhere(function($query){
                        $query->pastDueSevenDays()->otherReferrals();
                    });
                });
            
        });          
    }

    public function scopeWithoutResetPastDueReferrals($query){
        return $query
            ->whereDoesntHave('document_resets')
            ->when(request('referral_type') === 'referrals', function($query){
                $query->where(function($query){
                    $query->pastDueThreeDays()->adminDocs();
                })
                ->orWhere(function($query){
                    $query->pastDueSevenDays()->municipalOrdinances();
                })
                ->orWhere(function($query){
                    $query->pastDueSevenDays()->provincialOrdinances();
                })
                ->orWhere(function($query){
                    $query->pastDueTenDays()->codes();
                })
                ->orWhere(function($query){
                    $query->pastDueSevenDays()->otherReferrals();
                });
            })
            ->when(request('referral_type') === 'admin_docs', function($query){
                $query->pastDueThreeDays()->adminDocs();
            })
            ->when(request('referral_type') === 'municipal', function($query){
                $query->pastDueSevenDays()->municipalOrdinances();    
            })
            ->when(request('referral_type') === 'provincial', function($query){
                $query->pastDueSevenDays()->provincialOrdinances();
            })
            ->when(request('referral_type') === 'other_referral', function($query){
                $query->pastDueSevenDays()->otherReferrals();
            })
            ->when(request('referral_type') === 'code', function($query){
                $query->pastDueTenDays()->codes();
            });
    }

    public function scopeWithResetNearDueCases($query){
        return $query
            ->whereHas('document_resets', function ($query){
                $query->case()->nearDueFifteenDays()
                    ->when(request('case_type') === 'administrative', function($query){
                        $query->administrativeCases();
                    })
                    ->when(request('case_type') === 'judicial', function($query){
                        $query->judicialCases();
                    })
                    ->when(request('case_type') === 'quasi', function($query){
                        $query->quasiCases();
                    });
            });
    }

    public function scopeWithoutResetNearDueCases($query){
        return $query 
            ->whereDoesntHave('document_resets')->case()->nearDueFifteenDays()
                ->when(request('case_type') === 'administrative', function($query){
                    $query->administrativeCases();
                })
                ->when(request('case_type') === 'judicial', function($query){
                    $query->judicialCases();
                })
                ->when(request('case_type') === 'quasi', function($query){
                    $query->quasiCases();
                });
    }

    public function scopeWithResetPastDueCases($query){
        return $query 
            ->whereHas('document_resets', function ($query){
                $query->case()->pastDueFifteenDays()->latest()
                    ->when(request('case_type') === 'administrative', function($query){
                        $query->administrativeCases();
                    })
                    ->when(request('case_type') === 'judicial', function($query){
                        $query->judicialCases();
                    })
                    ->when(request('case_type') === 'quasi', function($query){
                        $query->quasiCases();
                    });
            });
    }

    public function scopeWithoutResetPastDueCases($query){
        return $query
            ->whereDoesntHave('document_resets')->case()->pastDueFifteenDays()
                ->when(request('case_type') === 'administrative', function($query){
                    $query->administrativeCases();
                })
                ->when(request('case_type') === 'judicial', function($query){
                    $query->judicialCases();
                })
                ->when(request('case_type') === 'quasi', function($query){
                    $query->quasiCases();
                });
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
    public function scopeReferral($query){
        return $query->whereIn('type',[DocumentType::MUNICIPAL_ORDINANCE,DocumentType::PROVINCIAL_ORDINANCE,DocumentType::OTHER_REFERRAL,DocumentType::CODE,DocumentType::ADMIN_DOCS]);
    }

    public function scopeCase($query){
        return $query->whereIn('type',[DocumentType::ADMINISTRATIVE,DocumentType::JUDICIAL,DocumentType::QUASI_JUDICIAL]);
    }

    public function scopeNearDueThreeDays($query){
        return $query->whereRaw("DATEDIFF('".now()."',date_received) <= 3");
    }

    public function scopePastDueThreeDays($query){
        return $query->whereRaw("DATEDIFF('".now()."',date_received) > 3");
    }

    public function scopeNearDueSevenDays($query){
        return $query->whereRaw("DATEDIFF('".now()."',date_received) >= 4")->whereRaw("DATEDIFF('".now()."',date_received) <=7");
    }

    public function scopePastDueSevenDays($query){
        return $query->whereRaw("DATEDIFF('".now()."',date_received) > 7");
    }

    public function scopeNearDueTenDays($query){
        return $query->whereRaw("DATEDIFF('".now()."',date_received) >= 7")->whereRaw("DATEDIFF('".now()."',date_received) <=10");
    }

    public function scopePastDueTenDays($query){
        return $query->whereRaw("DATEDIFF('".now()."',date_received) > 10");
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

    public function scopeNotaries($query){
        return $query->where('type', DocumentType::NOTARY);
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

    public function scopeNearDueFifteenDays($query){
        return $query->whereRaw("DATEDIFF('".now()."',date_received) >=12")->whereRaw("DATEDIFF('".now()."',date_received) <=15");
    }

    public function scopePastDueFifteenDays($query){
        return $query->whereRaw("DATEDIFF('".now()."',date_received) > 15");
    }

    public function scopeUserCount($query){
        $current_user = Auth::user()->employee_id;

        return $query->whereHas('transactions', function($query) use ($current_user){
            $query->where('employee_id', $current_user);
        });
    }    

    protected $guarded = [];
    protected $appends = ['days_active','date_to_count','last_assignment','last_transaction_type'];

    protected $casts = [
        'status' => DocumentStatus::class,
        'type' => DocumentType::class,
    ];

}
