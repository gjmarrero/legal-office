<?php

use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\DashboardStatController;
use App\Http\Controllers\Admin\DocumentStatusController;
use App\Http\Controllers\Admin\OutgoingDocumentController;
use App\Http\Controllers\Admin\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\DocumentController;
use App\Http\Controllers\Admin\TransactionController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\DocumentTypeController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

//Route::get('/admin/dashboard', function () {
//    return view('dashboard');
//});

Route::middleware('auth')->group(function() {
    Route::get('/api/stats/documents', [DashboardStatController::class, 'documents']);

    Route::get('/api/stats/users', [DashboardStatController::class, 'users']);

    Route::get('/api/users',[UserController::class, 'index']);
    Route::post('/api/users', [UserController::class, 'store']);
    Route::put('/api/users/{user}', [UserController::class, 'update']);
    Route::delete('/api/users/{user}', [UserController::class, 'delete']);
    Route::delete('/api/users', [UserController::class, 'bulkDelete']);

    Route::get('/api/clients',[ClientController::class, 'index']);
    Route::post('/api/clients', [ClientController::class,'store']);
    Route::put('/api/clients/{client}', [ClientController::class,'update']);
    Route::delete('/api/clients/{client}', [ClientController::class, 'delete']);
    Route::delete('/api/clients', [ClientController::class,'bulkDelete']);

    Route::get('/api/users/getUser', [UserController::class, 'getCurrentUser']);
    Route::patch('/api/users/{user}/change-role', [UserController::class, 'changeRole']);

    Route::get('/api/employees/getCurrentEmployee', [EmployeeController::class, 'getCurrentEmployee']);   

    Route::get('/api/stats/filtered_documents', [DashboardStatController::class, 'getFilteredDocuments']);
    Route::get('/api/stats/referrals', [DashboardStatController::class, 'getReferralNearDueCount']);
    Route::get('/api/stats/cases', [DashboardStatController::class, 'getCaseNearDueCount']);

    Route::get('/api/stats/past_due_referrals', [DashboardStatController::class, 'getReferralPastDueCount']);
    Route::get('/api/stats/past_due_cases', [DashboardStatController::class, 'getCasePastDueCount']);

    Route::get('/api/stats/to-do', [DashboardStatController::class, 'to_do']);

    Route::get('/api/dashboard/stats/totals', [DashboardStatController::class,'totals']);

    Route::get('/api/profile/employee_counter', [ProfileController::class, 'getEmployeeCounters']);
    Route::get('/api/profile/employee_counter_cases', [ProfileController::class, 'getEmployeeCountCases']);
    Route::get('/api/profile/employee_counter_referrals', [ProfileController::class, 'getEmployeeCountReferrals']);

    Route::get('/api/documents', [DocumentController::class, 'index']);

    Route::get('/api/documents/overdue',[DocumentController::class, 'getOverdueDocuments']);

    Route::get('/api/documents/all', [DocumentController::class, 'index']);

    Route::get('/api/document-status', [DocumentStatusController::class, 'getStatusWithCount']);
    Route::get('/api/document-type', [DocumentTypeController::class, 'getDocumentType']);

    Route::post('/api/documents/create', [DocumentController::class, 'store']);

    Route::get('/api/clients', [ClientController::class, 'index']);
    Route::get('/api/clients/get_clients', [ClientController::class,'get_clients']);

    Route::get('/api/documents/{document}/edit', [DocumentController::class, 'edit']);

    Route::post('/api/documents/{document}/edit', [DocumentController::class, 'update']);

    Route::delete('/api/documents/{document}', [DocumentController::class, 'destroy']);

    Route::get('/api/document/file_location', [DocumentController::class,'file_location']);

    Route::get('/api/outgoing_documents', [OutgoingDocumentController::class, 'index']);
    Route::post('/api/outgoing/create', [OutgoingDocumentController::class, 'store']);
    Route::get('/api/outgoing/{document}/edit', [OutgoingDocumentController::class, 'edit']);
    Route::post('/api/outgoing/{document}/edit', [OutgoingDocumentController::class, 'update']);

    Route::get('/api/documents/document/{document}', [DocumentController::class, 'getDocument']);
    Route::post('/api/documents/archive/{document}', [DocumentController::class, 'archive']);
    Route::get('/api/documents/transactions/{document}', [TransactionController::class, 'transactions']);
    Route::get('/api/documents/{transaction}/edit', [TransactionController::class, 'edit']);
    Route::post('/api/documents/transaction/{transaction}/edit', [TransactionController::class, 'update']);
    Route::post('/api/documents/reset/{document}', [DocumentController::class, 'reset']);
    Route::post('/api/documents/receive/{document}', [TransactionController::class, 'receive']);

    Route::post('/api/documents/route', [TransactionController::class, 'route']);
    Route::post('/api/documents/attachfile', [DocumentController::class, 'attachfile']);
    Route::get('/api/documents/getAttachedFiles/{document}', [DocumentController::class, 'getAttachedFiles']);
    Route::get('/api/documents/getAdditionalFiles/{document}', [DocumentController::class, 'getAdditionalFiles']);

    Route::get('/api/documents/file/{document}', [DocumentController::class, 'getDocumentFile']);

    Route::get('/api/documents/transactions/file/{transaction}', [TransactionController::class, 'getTransactionFile']);

    Route::get('/api/settings', [SettingController::class, 'index']);

    Route::post('/api/settings', [SettingController::class, 'update']);

    Route::get('/api/employees', [EmployeeController::class, 'index']);

    Route::get('/api/profile', [ProfileController::class, 'index']);

    Route::put('/api/profile', [ProfileController::class, 'update']);

    Route::post('/api/upload-profile-image', [ProfileController::class, 'uploadImage']);

    Route::post('/api/change-user-password', [ProfileController::class, 'changePassword']);
});

Route::get('{view}', ApplicationController::class)->where('view','(.*)')->middleware('auth');