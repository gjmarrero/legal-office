<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Fortify\UpdateUserPassword;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use App\Models\Transaction;
use App\Models\Document;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function index(Request $request)
    {
        return $request->user()->only(['id','name','email','role','avatar','employee_id']);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required'],
            'email' => ['required', 'email', Rule::unique('users')->ignore($request->user()->id)],
        ]);

        $request->user()->update($validated);

        return response()->json(['success' => true]);
    }
    public function uploadImage(Request $request)
    {
        $previousPath = $request->user()->getRawOriginal('avatar');
        if($request->hasFile('profile_picture')){
            $link = Storage::put('/public/photos', $request->file('profile_picture'));

            $request->user()->update(['avatar' => $link]);

            Storage::delete($previousPath);
            return response()->json(['message' => 'Profile picture uploaded successfully']);
        }
    }

    public function changePassword(Request $request, UpdateUserPassword $updater)
    {
        $updater->update(
            auth()->user(),
            [
                'current_password' => $request->currentPassword,
                'password' => $request->password,
                'password_confirmation' => $request->passwordConfirmation,
            ]
        );

        return response()->json(['message' => 'Password changed successfully']);
    }

    public function getEmployeeCounters()
    {        
        $totalCases = Document::case()->userCount()->count();
        
        $totalReferrals = Document::referral()->userCount()->count();

        $totalAdminDocs = Document::adminDocs()->userCount()->count();

        $totalNotaries = Document::notaries()->userCount()->count();

        return response() -> json([
            'totalCases' => $totalCases,
            'totalReferrals' => $totalReferrals,
            'totalAdminDocs' => $totalAdminDocs,
            'totalNotaries' => $totalNotaries,
        ]);
    }

    public function getEmployeeCountCases()
    {
        $totalCases = Document::case()
            ->when(request('selectedCaseType')==='cases', function($query){
                $query->case();
            })
            ->when(request('selectedCaseType')==='administrative', function($query){
                $query->administrativeCases();
            })
            ->when(request('selectedCaseType')==='judicial', function($query){
                $query->judicialCases();
            })
            ->when(request('selectedCaseType')==='quasi', function($query){
                $query->quasiCases();
            })
            ->userCount()->count();
        
        return response() -> json ([
            'caseTypeCount' => $totalCases,
        ]);
    }

    public function getEmployeeCountReferrals()
    {
        $totalReferrals = Document::referral()
            ->when(request('selectedReferralType')==='referrals', function($query){
                $query->referral();
            })
            ->when(request('selectedReferralType')==='municipal', function($query){
                $query->municipalOrdinances();
            })
            ->when(request('selectedReferralType')==='provincial', function($query){
                $query->provincialOrdinances();
            })
            ->when(request('selectedReferralType')==='other', function($query){
                $query->otherReferrals();
            })
            ->when(request('selectedReferralType')==='admin_docs', function($query){
                $query->adminDocs();
            })
            ->when(request('selectedReferralType')==='code', function($query){
                $query->codes();
            })
            ->userCount()->count();

        return response() -> json ([
            'referralTypeCount' => $totalReferrals,
        ]);

        // dd($totalReferrals);
    }
}