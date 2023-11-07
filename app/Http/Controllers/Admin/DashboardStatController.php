<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Models\DocumentReset;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Models\Document;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardStatController extends Controller
{
    public function documents(){
        $totalDocumentsCount = Document::query()
            ->when(request('status') === 'active', function ($query) {
                $query->where('status', DocumentStatus::ACTIVE);
            })
            ->when(request('status') === 'archived', function ($query) {
                $query->where('status', DocumentStatus::ARCHIVED);
            })
            ->count();

            return response() -> json ([
                'totalDocumentsCount' => $totalDocumentsCount,
            ]);
    }

    public function to_do(){
        $current_user = Auth::user()->employee_id;
        $totalToReceiveCount = Transaction::query()
            ->where([['status',TransactionStatus::PENDING],['employee_id',$current_user],['type',null]])
            ->count();

        $totalToReleaseCount = Transaction::query()
            ->where([['status',TransactionStatus::PENDING],['employee_id',$current_user],['type', TransactionType::RECEIVED]])
            ->count();


        return response() -> json ([
            'totalToReceiveCount' => $totalToReceiveCount,
            'totalToReleaseCount' => $totalToReleaseCount,
        ]);
    }

    public function totals(){
        $totalReferrals = Document::referral()
            ->when(request('ref_doc_type') === 'admin_docs', function($query){
                $query->adminDocs();
            })
            ->when(request('ref_doc_type') === 'municipal', function($query){
                $query->municipalOrdinances();
            })
            ->when(request('ref_doc_type') === 'provincial', function($query){
                $query->provincialOrdinances();
            })
            ->when(request('ref_doc_type') === 'other_referral', function($query){
                $query->otherReferrals();
            })
            ->when(request('ref_doc_type') === 'code', function($query){
                $query->codes();
            })
            ->count();

        $totalCases = Document::case()
            ->when(request('case_doc_type') === 'administrative', function($query){
                $query->administrativeCases();
            })
            ->when(request('case_doc_type') === 'judicial', function($query){
                $query->judicialCases();
            })
            ->when(request('case_doc_type') === 'quasi', function($query){
                $query->quasiCases();
            })
            ->count();

        return response() -> json ([
            'totalReferrals' => $totalReferrals,
            'totalCases' => $totalCases,
        ]);
    }

    public function getReferralNearDueCount(){
        $document_reset = Document::active()->withResetNearDueReferrals();

        $document_no_reset = Document::active()->withoutResetNearDueReferrals();

        $totalReferralNearDueCount = $document_reset->unionAll($document_no_reset)->count();            

        return response() -> json ([
            'totalReferralNearDueCount' => $totalReferralNearDueCount,
        ]);

    }

    public function getCaseNearDueCount(){
        $document_reset = Document::active()->withResetNearDueCases();
        
        $document_no_reset = Document::active()->withoutResetNearDueCases();

        $totalCaseNearDueCount = $document_reset->unionAll($document_no_reset)->count();           
        
        return response() -> json ([
            'totalCaseNearDueCount' => $totalCaseNearDueCount,
        ]);
    }

    public function getReferralPastDueCount(){
        $document_reset = Document::active()->withResetPastDueReferrals();

        $document_no_reset = Document::active()->withoutResetPastDueReferrals();

        $totalReferralPastDueCount = $document_reset->unionAll($document_no_reset)->count();
        
        return response() -> json ([
            'totalReferralPastDueCount' => $totalReferralPastDueCount,
        ]);
    }

    public function getCasePastDueCount(){
        $document_reset = Document::active()->withResetPastDueCases();

        $document_no_reset = Document::active()->withoutResetPastDueCases();

        $totalCasePastDueCount = $document_reset->unionAll($document_no_reset)->count();
        
        return response() -> json ([
            'totalCasePastDueCount' => $totalCasePastDueCount,
        ]);
    }

    public function getFilteredDocuments(){
        $document_reset = Document::whereHas('document_resets', function($query){
            $query->byDocType();
        });

        $document_no_reset = Document::whereDoesntHave('document_resets')
            ->byDocType();

        return $document_reset->unionAll($document_no_reset)->get();
    }
    public function users(){
        $totalUsersCount = User::query()
            ->when(request('date_range') === 'today', function($query) {
                $query->whereBetween('created_at', [now()->today(), now()]);
            })
            ->when(request('date_range') === '30_days', function($query) {
                $query->whereBetween('created_at', [now()->subDays(30), now()]);
            })
            ->when(request('date_range') === '60_days', function($query) {
                $query->whereBetween('created_at', [now()->subDays(60), now()]);
            })
            ->when(request('date_range') === '360_days', function($query) {
                $query->whereBetween('created_at', [now()->subDays(360), now()]);
            })
            ->when(request('date_range') === 'month_to_date', function($query) {
                $query->whereBetween('created_at', [now()->firstOfMonth(), now()]);
            })
            ->when(request('date_range') === 'year_to_date', function($query) {
                $query->whereBetween('created_at', [now()->firstOfYear(), now()]);
            })
            ->count();

        return response()->json([
            'totalUsersCount' => $totalUsersCount,
        ]);
    }
}
