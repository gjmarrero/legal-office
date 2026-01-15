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
    public function documents()
    {
        $totalDocumentsCount = Document::query()
            ->when(request('status') === 'active', function ($query) {
                $query->where('status', DocumentStatus::ACTIVE);
            })
            ->when(request('status') === 'archived', function ($query) {
                $query->where('status', DocumentStatus::ARCHIVED);
            })
            ->count();

        return response()->json([
            'totalDocumentsCount' => $totalDocumentsCount,
        ]);
    }

    public function to_do()
    {
        $current_user = Auth::user()->employee_id;
        $totalToReceiveCount = Transaction::query()
            ->where([['status', TransactionStatus::PENDING], ['employee_id', $current_user], ['type', null]])
            ->whereHas('document', function ($query) {
                $query->where('status', DocumentStatus::ACTIVE);
            })
            ->distinct('document_id')
            ->count();

        $totalToReleaseCount = Transaction::query()
            ->where([
                ['status', TransactionStatus::PENDING],
                ['employee_id', $current_user],
                ['type', TransactionType::RECEIVED]
            ])
            ->whereHas('document', function ($query) {
                $query->where('status', DocumentStatus::ACTIVE);
            })
            ->distinct('document_id')
            ->count('document_id');


        return response()->json([
            'totalToReceiveCount' => $totalToReceiveCount,
            'totalToReleaseCount' => $totalToReleaseCount,
        ]);
    }

    public function totals()
    {
        $totalReferrals = Document::referral()
            ->when(request('ref_doc_type') === 'admin_docs', function ($query) {
                $query->adminDocs();
            })
            ->when(request('ref_doc_type') === 'municipal', function ($query) {
                $query->municipalOrdinances();
            })
            ->when(request('ref_doc_type') === 'provincial', function ($query) {
                $query->provincialOrdinances();
            })
            ->when(request('ref_doc_type') === 'other_referral', function ($query) {
                $query->otherReferrals();
            })
            ->when(request('ref_doc_type') === 'code', function ($query) {
                $query->codes();
            })
            ->when(request('ref_doc_type') === 'ctrc', function ($query){
                $query->ctrcs();
            })
            ->count();

        $totalCases = Document::case()
            ->when(request('case_doc_type') === 'administrative', function ($query) {
                $query->administrativeCases();
            })
            ->when(request('case_doc_type') === 'judicial', function ($query) {
                $query->judicialCases();
            })
            ->when(request('case_doc_type') === 'quasi', function ($query) {
                $query->quasiCases();
            })
            ->count();

        return response()->json([
            'totalReferrals' => $totalReferrals,
            'totalCases' => $totalCases,
        ]);
    }

    // public function getReferralNearDueCount($referral_type)
    // {
    //     $typeScope = [
    //         'referral' => 'referral',
    //         'code' => 'codes',
    //         'municipal' => 'municipalOrdinances',
    //         'provincial' => 'provincialOrdinances',
    //         'other_referral' => 'otherReferrals',
    //         'admin_docs' => 'adminDocs',
    //         'notaries' => 'notaries'
    //     ];

    //     $durationScope = [
    //         'referral' => null,
    //         'code' => 'nearDueTenDays',
    //         'municipal' => 'nearDueSevenDays',
    //         'provincial' => 'nearDueTenDays',
    //         'other_referral' => 'nearDueSevenDays',
    //         'admin_docs' => 'nearDueThreeDays',
    //         'notaries' => 'nearDueThreeDays'
    //     ];

    //     if (!isset($typeScope[$referral_type])) {
    //         return response()->json(['totalReferralNearDueCount' => 0]);
    //     }

    //     $scopeMethod = $typeScope[$referral_type];
    //     $durationMethod = $durationScope[$referral_type] ?? null;

    //     $document_reset = Document::active()->withReset();
    //     $document_no_reset = Document::active()->withoutReset();

    //     $document_reset = $document_reset->{$scopeMethod}();
    //     $document_no_reset = $document_no_reset->{$scopeMethod}();

    //     if ($durationMethod) {
    //         $document_reset = $document_reset->{$durationMethod}();
    //         $document_no_reset = $document_no_reset->{$durationMethod}();
    //     }

    //     $totalReferralNearDueCount = $document_reset->unionAll($document_no_reset)->count();

    //     // dd($totalReferralNearDueCount);
    //     return response()->json([
    //         'totalReferralNearDueCount' => $totalReferralNearDueCount,
    //     ]);

    // }


    public function getReferralNearDueCount($referral_type)
    {
        $typeScope = [
            'code' => 'codes',
            'municipal' => 'municipalOrdinances',
            'provincial' => 'provincialOrdinances',
            'other_referral' => 'otherReferrals',
            'admin_docs' => 'adminDocs',
            'notaries' => 'notaries',
            'ctrc' => 'ctrcs'
        ];

        $durationScope = [
            'code' => 'nearDueTenDays',
            'municipal' => 'nearDueSevenDays',
            'provincial' => 'nearDueTenDays',
            'other_referral' => 'nearDueSevenDays',
            'admin_docs' => 'nearDueThreeDays',
            'notaries' => 'nearDueThreeDays',
            'ctrc' => 'nearDueSevenDays',
        ];

        if ($referral_type === 'referral') {

            $total = 0;

            foreach ($typeScope as $type => $scopeMethod) {

                $durationMethod = $durationScope[$type];

                $withReset = Document::active()
                                ->withReset()
                        ->{$scopeMethod}()
                    ->{$durationMethod}()
                        ->count();

                $withoutReset = Document::active()
                                ->withoutReset()
                        ->{$scopeMethod}()
                    ->{$durationMethod}()
                        ->count();

                $total += ($withReset + $withoutReset);
            }

            return response()->json([
                'totalReferralNearDueCount' => $total
            ]);
        }

        if (!isset($typeScope[$referral_type])) {
            return response()->json(['totalReferralNearDueCount' => 0]);
        }

        $scopeMethod = $typeScope[$referral_type];
        $durationMethod = $durationScope[$referral_type];

        $withReset = Document::active()
                        ->withReset()
                ->{$scopeMethod}()
            ->{$durationMethod}();

        $withoutReset = Document::active()
                        ->withoutReset()
                ->{$scopeMethod}()
            ->{$durationMethod}();

        $total = $withReset
            ->unionAll($withoutReset)
            ->count();

        return response()->json([
            'totalReferralNearDueCount' => $total
        ]);
    }

    public function getCaseNearDueCount($case_type)
    {
        $typeScope = [
            'case' => 'case',
            'administrative' => 'administrativeCases',
            'judicial' => 'judicialCases',
            'quasi' => 'quasiCases',
        ];

        if (!isset($typeScope[$case_type])) {
            return response()->json(['totalCaseNearDueCount' => 0]);
        }

        $scopeMethod = $typeScope[$case_type];

        $document_reset = Document::active()->withReset()->{$scopeMethod}()->nearDueFifteenDays();

        $document_no_reset = Document::active()->withoutReset()->{$scopeMethod}()->nearDueFifteenDays();

        $totalCaseNearDueCount = $document_reset->unionAll($document_no_reset)->count();

        return response()->json([
            'totalCaseNearDueCount' => $totalCaseNearDueCount,
            'scopeType' => $case_type,
        ]);
    }

    public function getReferralPastDueCount($referral_type)
    {
        $typeScope = [
            'code' => 'codes',
            'municipal' => 'municipalOrdinances',
            'provincial' => 'provincialOrdinances',
            'other_referral' => 'otherReferrals',
            'admin_docs' => 'adminDocs',
            'notaries' => 'notaries',
            'ctrc' => 'ctrcs',
        ];

        $durationScope = [
            'code' => 'pastDueTenDays',
            'municipal' => 'pastDueSevenDays',
            'provincial' => 'pastDueTenDays',
            'other_referral' => 'pastDueSevenDays',
            'admin_docs' => 'pastDueThreeDays',
            'notaries' => 'pastDueThreeDays',
            'ctrc' => 'pastDueSevenDays',
        ];

        if ($referral_type === 'referral') {

            $total = 0;

            foreach ($typeScope as $type => $scopeMethod) {

                $durationMethod = $durationScope[$type];

                $withReset = Document::active()
                                ->withReset()
                        ->{$scopeMethod}()
                    ->{$durationMethod}()
                        ->count();

                $withoutReset = Document::active()
                                ->withoutReset()
                        ->{$scopeMethod}()
                    ->{$durationMethod}()
                        ->count();

                $total += ($withReset + $withoutReset);
            }

            return response()->json([
                'totalReferralPastDueCount' => $total
            ]);
        }

        if (!isset($typeScope[$referral_type])) {
            return response()->json(['totalReferralPastDueCount' => 0]);
        }

        $scopeMethod = $typeScope[$referral_type];
        $durationMethod = $durationScope[$referral_type];

        $withReset = Document::active()
                        ->withReset()
                ->{$scopeMethod}()
            ->{$durationMethod}();

        $withoutReset = Document::active()
                        ->withoutReset()
                ->{$scopeMethod}()
            ->{$durationMethod}();

        $total = $withReset
            ->unionAll($withoutReset)
            ->count();

        return response()->json([
            'totalReferralPastDueCount' => $total
        ]);
    }

    public function getCasePastDueCount($case_type)
    {
        $typeScope = [
            'case' => 'case',
            'administrative' => 'administrativeCases',
            'judicial' => 'judicialCases',
            'quasi' => 'quasiCases',
        ];

        if (!isset($typeScope[$case_type])) {
            return response()->json(['totalCasePastDueCount' => 0]);
        }

        $scopeMethod = $typeScope[$case_type];

        $document_reset = Document::active()->withReset()->{$scopeMethod}()->pastDueFifteenDays();

        $document_no_reset = Document::active()->withoutReset()->{$scopeMethod}()->pastDueFifteenDays();

        $totalCasePastDueCount = $document_reset->unionAll($document_no_reset)->count();

        return response()->json([
            'totalCasePastDueCount' => $totalCasePastDueCount,
            'scopeType' => $case_type,
        ]);
    }

    public function getFilteredDocuments()
    {
        $document_reset = Document::whereHas('document_resets', function ($query) {
            $query->byDocType();
        });

        $document_no_reset = Document::whereDoesntHave('document_resets')
            ->byDocType();

        return $document_reset->unionAll($document_no_reset)->get();
    }
    public function users()
    {
        $totalUsersCount = User::query()
            ->when(request('date_range') === 'today', function ($query) {
                $query->whereBetween('created_at', [now()->today(), now()]);
            })
            ->when(request('date_range') === '30_days', function ($query) {
                $query->whereBetween('created_at', [now()->subDays(30), now()]);
            })
            ->when(request('date_range') === '60_days', function ($query) {
                $query->whereBetween('created_at', [now()->subDays(60), now()]);
            })
            ->when(request('date_range') === '360_days', function ($query) {
                $query->whereBetween('created_at', [now()->subDays(360), now()]);
            })
            ->when(request('date_range') === 'month_to_date', function ($query) {
                $query->whereBetween('created_at', [now()->firstOfMonth(), now()]);
            })
            ->when(request('date_range') === 'year_to_date', function ($query) {
                $query->whereBetween('created_at', [now()->firstOfYear(), now()]);
            })
            ->count();

        return response()->json([
            'totalUsersCount' => $totalUsersCount,
        ]);
    }
}
