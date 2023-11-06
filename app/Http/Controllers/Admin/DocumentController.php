<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Models\DocumentReset;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
// use League\CommonMark\Node\Block\Document;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;


use App\Models\Document;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function index(){
        $searchQuery = request('query_search');
        $searchbyQuery = request('query_searchby');
        $doc_type = request('query_doc_type');
        $to_do = request('query_to_do');
        $current_user = Auth::user()->employee_id;

        $docWithReset = Document::with('client:id,name,office')
            ->whereHas('document_resets', function ($query) use($searchbyQuery,$searchQuery,$to_do,$current_user,$doc_type){
                $query
                ->when(request('query'), function($query, $selectedStatus){
                    $query->where('status', $selectedStatus);
                })
                ->when(request('query_search'), function($query) use ($searchQuery,$searchbyQuery){
                    $query->when($searchbyQuery === 'client', function ($query) use ($searchQuery){
                        $query->whereHas('client', function($query) use($searchQuery){
                            $query->where('clients.name','like',"%{$searchQuery}%");
                        });
                    });
                    $query->when($searchbyQuery === 'title' || $searchbyQuery === 'description', function($query) use($searchQuery,$searchbyQuery){
                        $query->where($searchbyQuery, 'like', "%{$searchQuery}%");
                    });
                    $query->when($searchbyQuery === 'type', function ($query) use($searchQuery){
                        $query->where('type', $searchQuery);
                    });
                })
                ->when(request('query_to_do'), function($query) use($to_do,$current_user){
                    $query->when($to_do === 'to-receive', function($query) use($current_user){
                        $query->whereHas('transactions', function($query) use($current_user){
                            $query->where([['transactions.status',TransactionStatus::PENDING],['transactions.employee_id',$current_user],['type',null]]);
                        });
                    });
                    $query->when($to_do === 'to-release', function($query) use($current_user){
                        $query->whereHas('transactions', function($query) use($current_user){
                            $query->where([['transactions.status',TransactionStatus::PENDING],['transactions.employee_id',$current_user],['type',TransactionType::RECEIVED]]);
                        });
                    });
                })
                ->when($doc_type === 'cases', function ($query){
                    $query->active()->case();
                })
                ->when($doc_type === 'administrative', function ($query){
                    $query->active()->administrativeCases();
                })
                ->when($doc_type === 'judicial', function ($query) {
                    $query->active()->judicialCases();
                })
                ->when($doc_type === 'quasi', function ($query) {
                    $query->active()->quasiCases();
                })
                ->when($doc_type === 'referrals', function ($query){
                    $query->active()->referral();
                })
                ->when($doc_type==='admin_docs', function ($query){
                    $query->active()->adminDocs();
                })
                ->when($doc_type==='municipal', function ($query){
                    $query->active()->municipalOrdinances();
                })
                ->when($doc_type==='other_referral', function ($query){
                    $query->active()->otherReferrals();
                })
                ->when($doc_type=='provincial', function ($query){
                    $query->active()->provincialOrdinances();
                })
                ->when($doc_type==='code', function ($query) {
                    $query->active()->codes();
                });
            });

            $docWithoutReset = Document::with('client:id,name,office','transactions')
                ->whereDoesntHave('document_resets')
                    ->when(request('query'), function($query, $selectedStatus){
                        $query->where('status', $selectedStatus);
                    })
                    ->when(request('query_search'), function($query) use ($searchQuery,$searchbyQuery){
                        $query->when($searchbyQuery === 'client', function ($query) use ($searchQuery){
                            $query->whereHas('client', function($query) use($searchQuery){
                                $query->where('name','like',"%{$searchQuery}%");
                            });
                        });
                        $query->when($searchbyQuery === 'title' || $searchbyQuery === 'description', function($query) use($searchQuery,$searchbyQuery){
                            $query->where($searchbyQuery, 'like', "%{$searchQuery}%");
                        });
                        $query->when($searchbyQuery === 'type', function ($query) use($searchQuery){
                            $query->where('type', $searchQuery);
                        });
                    })
                    ->when(request('query_to_do'), function($query) use($to_do,$current_user){
                        $query->when($to_do === 'to-receive', function($query) use($current_user){
                            $query->whereHas('transactions', function($query) use($current_user){
                                $query->where([['transactions.status',TransactionStatus::PENDING],['transactions.employee_id',$current_user],['type',null]]);
                            });
                        });
                        $query->when($to_do === 'to-release', function($query) use($current_user){
                            $query->whereHas('transactions', function($query) use($current_user){
                                $query->where([['transactions.status',TransactionStatus::PENDING],['transactions.employee_id',$current_user],['type',TransactionType::RECEIVED]]);
                            });
                        });
                    })
                    ->when($doc_type === 'cases', function ($query){
                        $query->active()->case();
                    })
                    ->when($doc_type === 'administrative', function ($query){
                        $query->active()->administrativeCases();
                    })
                    ->when($doc_type === 'judicial', function ($query) {
                        $query->active()->judicialCases();
                    })
                    ->when($doc_type === 'quasi', function ($query) {
                        $query->active()->case()->quasiCases();
                    })
                    ->when($doc_type === 'referrals', function ($query){
                        $query->active()->referral();
                    })
                    ->when($doc_type==='admin_docs', function ($query){
                        $query->active()->adminDocs();
                    })
                    ->when($doc_type==='municipal', function ($query){
                        $query->active()->municipalOrdinances();
                    })
                    ->when($doc_type==='other_referral', function ($query){
                        $query->active()->otherReferrals();
                    })
                    ->when($doc_type=='provincial', function ($query){
                        $query->active()->provincialOrdinances();
                    })
                    ->when($doc_type==='code', function ($query) {
                        $query->active()->codes();
                    });

                $documents_union = $docWithReset->unionAll($docWithoutReset);

                $documents = $documents_union
                    ->latest()->paginate(setting('pagination_limit'))
                    ->through(fn ($document) => [
                        'id' => $document->id,
                        'date_received' => $document->date_received,
                        'client' => $document->client,
                        'title' => $document->title,
                        'description' => $document->description,
                        'remarks' => $document->remarks,
                        'status' => [
                            'name' => $document->status->name,
                            'color' => $document->status->color(),
                        ],
                        'type' => [
                            'name' => $document->type->name,
                        ],
                        'date_to_count' => $document->date_to_count,
                        // 'last_assigned' => $document->last_transaction,
                        'days_active' => $document->days_active,
                    ]);
        
        // $documents = Document::query()
        //     ->with('client:id,name,office','transactions')
        //     ->when(request('query'), function($query, $selectedStatus){
        //         $query->where('status', $selectedStatus);
        //     })
        //     ->when(request('query_search'), function($query) use ($searchQuery,$searchbyQuery){
        //         $query->when($searchbyQuery === 'client', function ($query) use ($searchQuery){
        //             $query->whereHas('client', function($query) use($searchQuery){
        //                 $query->where('name','like',"%{$searchQuery}%");
        //             });
        //         });
        //         $query->when($searchbyQuery === 'title' || $searchbyQuery === 'description', function($query) use($searchQuery,$searchbyQuery){
        //             $query->where($searchbyQuery, 'like', "%{$searchQuery}%");
        //         });
        //         $query->when($searchbyQuery === 'type', function ($query) use($searchQuery){
        //             $query->where('type', $searchQuery);
        //         });
        //     })
        //     ->when(request('query_to_do'), function($query) use($to_do,$current_user){
        //         $query->when($to_do === 'to-receive', function($query) use($current_user){
        //             $query->whereHas('transactions', function($query) use($current_user){
        //                 $query->where([['transactions.status',TransactionStatus::PENDING],['transactions.employee_id',$current_user],['type',null]]);
        //             });
        //         });
        //         $query->when($to_do === 'to-release', function($query) use($current_user){
        //             $query->whereHas('transactions', function($query) use($current_user){
        //                 $query->where([['transactions.status',TransactionStatus::PENDING],['transactions.employee_id',$current_user],['type',TransactionType::RECEIVED]]);
        //             });
        //         });
        //     })
        //     ->when(request('query_doc_status') === 'past_due', function ($query) use($doc_type){
        //         $query
        //             ->when($doc_type==='administrative'||$doc_type==='judicial'||$doc_type==='quasi'||$doc_type==='cases', function ($query) use($doc_type){
        //                 $query->case()->fifteenDays()->pastDueFifteenDays()                
        //                 ->when($doc_type === 'administrative', function ($query){
        //                     $query->administrativeCases();
        //                 })
        //                 ->when($doc_type === 'judicial', function ($query) {
        //                     $query->judicialCases();
        //                 })
        //                 ->when($doc_type === 'quasi', function ($query) {
        //                     $query->quasiCases();
        //                 });
        //             })
        //             ->when($doc_type==='admin_docs'||$doc_type==='municipal'||$doc_type==='other_referral'||$doc_type==='provincial'||$doc_type==='code'||$doc_type==='referrals', function ($query) use($doc_type){
        //                 $query->referral()
        //                 ->when($doc_type==='admin_docs', function ($query){
        //                     $query->threeDays()->pastDueThreeDays();
        //                 })
        //                 ->when($doc_type==='municipal', function ($query){
        //                     $query->sevenDays()->pastDueSevenDays()->where('type',DocumentType::MUNICIPAL_ORDINANCE);
        //                 })
        //                 ->when($doc_type==='other_referral', function ($query){
        //                     $query->sevenDays()->pastDueSevenDays()->where('type',DocumentType::OTHER_REFERRAL);
        //                 })
        //                 ->when($doc_type=='provincial', function ($query){
        //                     $query->tenDays()->pastDueTenDays()->where('type',DocumentType::PROVINCIAL_ORDINANCE);
        //                 })
        //                 ->when($doc_type==='code', function ($query) {
        //                     $query->tenDays()->pastDueTenDays()->where('type',DocumentType::CODE);
        //                 })
        //                 ->when($doc_type === 'referrals', function ($query) {
        //                     $query->where(function ($query){
        //                         $query->threeDays()->pastDueThreeDays();
        //                     })
        //                     ->orWhere(function ($query) {
        //                         $query->sevenDays()->pastDueSevenDays();
        //                     })
        //                     ->orWhere(function ($query) {
        //                         $query->tenDays()->pastDueTenDays();
        //                     });
        //                 });
        //             })->active();                
        //     })
        //     ->when(request('query_doc_status') === 'near_due', function ($query) use($doc_type){
        //         $query
        //             ->when($doc_type==='administrative'||$doc_type==='judicial'||$doc_type==='quasi'||$doc_type==='cases', function ($query) use($doc_type){
        //                 $query->case()->fifteenDays()->nearDueFifteenDays()
        //                 ->when($doc_type==='administrative', function ($query) {
        //                     $query->administrativeCases();
        //                 })
        //                 ->when($doc_type==='judicial', function ($query) {
        //                     $query->judicialCases();
        //                 })
        //                 ->when($doc_type==='quasi', function ($query) {
        //                     $query->quasiCases();
        //                 });
        //             })
        //             ->when($doc_type==='admin_docs'||$doc_type==='municipal'||$doc_type==='other_referral'||$doc_type==='provincial'||$doc_type==='code'||$doc_type==='referrals', function ($query) use($doc_type){
        //                 $query->referral()
        //                 ->when($doc_type==='admin_docs', function ($query){
        //                     $query->threeDays()->nearDueThreeDays();
        //                 })
        //                 ->when($doc_type==='municipal', function ($query){
        //                     $query->sevenDays()->nearDueSevenDays()->where('type', DocumentType::MUNICIPAL_ORDINANCE);
        //                 })
        //                 ->when($doc_type==='other_referral', function ($query){
        //                     $query->sevenDays()->nearDueSevenDays()->where('type', DocumentType::OTHER_REFERRAL);
        //                 })
        //                 ->when($doc_type==='provincial', function ($query){
        //                     $query->tenDays()->nearDueTenDays()->where('type', DocumentType::PROVINCIAL_ORDINANCE);
        //                 })
        //                 ->when($doc_type==='code', function($query){
        //                     $query->tenDays()->nearDueTenDays()->where('type', DocumentType::CODE);
        //                 })
        //                 ->when($doc_type === 'referrals', function ($query) {
        //                     $query->where(function ($query){
        //                         $query->threeDays()->nearDueThreeDays();
        //                     })
        //                     ->orWhere(function ($query) {
        //                         $query->sevenDays()->nearDueSevenDays();
        //                     })
        //                     ->orWhere(function ($query) {
        //                         $query->tenDays()->nearDueTenDays();
        //                     });
        //                 });
        //             })->active();
                    
        //     })
        //     ->latest()->paginate()
        //     ->through(fn ($document) => [
        //         'id' => $document->id,
        //         'date_received' => $document->date_received,
        //         'client' => $document->client,
        //         'title' => $document->title,
        //         'description' => $document->description,
        //         'remarks' => $document->remarks,
        //         'status' => [
        //             'name' => $document->status->name,
        //             'color' => $document->status->color(),
        //         ],
        //         'type' => [
        //             'name' => $document->type->name,
        //         ],
        //         'date_to_count' => $document->date_to_count,
        //         // 'last_assigned' => $document->last_transaction,
        //         'days_active' => $document->days_active,
        //     ]);
        
        return $documents;
    }

    // public function getOverdueDocuments(){
    //     $searchQuery = request('query_search');
    //     return Document::with('client:id,name,office')
    //         ->when(request('query') === 'all', function($query){
    //             $query->overdue();
    //         })
    //         ->when(request('query') === 'case', function($query){
    //             $query->case();
    //         })
    //         ->when(request('query') === 'referral', function($query){
    //             $query->referral();
    //         })
    //         ->when(request('query_searchby') === 'description', function($query,$searchQuery){
    //             $query->where('title','like',$searchQuery);
    //         })
    //         ->latest()->paginate()
    //         ->through(fn ($document) => [
    //             'days_active' => $document->days_active,
    //             'id' => $document->id,
    //             'date_received' => $document->date_received->format('Y-m-d'),
    //             'client' => $document->client,
    //             'title' => $document->title,
    //             'description' => $document->description,
    //             'remarks' => $document->remarks,
    //             'status' => [
    //                 'name' => $document->status->name,
    //                 'color' => $document->status->color(),
    //             ],
    //         ]);
    // }

    public function store(){
        $file_name = '';

        $validated = request()->validate([
            'client_id' => 'required',
            'document_type' => 'required',
            'title' => 'required',
            'description' => 'required',
            'date_received' => 'required',
        ], [
            'client_id.required' => "Client name is required",
        ]);

        if(request()->hasFile('document_file')){
            $file = request()->file('document_file');
            $file_name = time().'_'.'document_file'.'_'.$file->getClientOriginalName();
            $path = 'public/uploads/documents/'.$file_name;
            Storage::disk('local')->put($path, file_get_contents($file));
        }

        $created_document = Document::create([
            'date_received' => $validated['date_received'],
            'type' => $validated['document_type'],
            'title' => $validated['title'],
            'client_id' => $validated['client_id'],
            'description' => $validated['description'],
            'remarks' => request('remarks'),
            'document_file' => $file_name,
            'status' => DocumentStatus::ACTIVE,
        ]);

        Transaction::create([
            'document_id' => $created_document->id,
            'employee_id' => Auth::user()->employee_id,
            'action' => 'Received',
            'status' => TransactionStatus::PENDING,
            'user_id' => Auth::user()->id,
            'type' => TransactionType::RECEIVED,
        ]);
    }

    public function edit(Document $document){
        return $document;
    }

    public function update(Document $document){
        
        $file_name = '';

        $validated = request()->validate([
            'client_id' => 'required',
            'type' => 'required',
            'title' => 'required',
            'description' => 'required',
            'date_received' => 'required',
        ], [
            'client_id.required' => "Client name is required",
        ]);

        if(request()->hasFile('document_file')){
            $file = request()->file('document_file');
            $file_name = time().'_'.'document_file'.'_'.$file->getClientOriginalName();
            $path = 'public/uploads/documents/'.$file_name;
            Storage::disk('local')->put($path, file_get_contents($file));
        }

        $validated['document_file'] = $file_name;

        $document->update($validated);

        return response() -> json(['success' => true]);
    }

    public function destroy(Document $document){
        $document->delete();

        return response() -> json(['success' => true], 200);
    }

    public function archive(Document $document){
        $setStatus = ([
            'status' => DocumentStatus::ARCHIVED,
        ]);

        $document->update($setStatus);

        // return Document::with('client:id,name,office')
        //     ->where('id',$document->id)
        //     ->latest()->paginate()
        //     ->through(fn ($document) => [
        //         'days_active' => $document->days_active,
        //         'id' => $document->id,
        //         'date_received' => $document->date_received->format('Y-m-d'),
        //         // 'client' => $document->client,
        //         'client' => [
        //             'name' => $document->client->name,
        //             'office' => $document->client->office,
        //         ],
        //         'title' => $document->title,
        //         'description' => $document->description,
        //         'remarks' => $document->remarks,
        //         'status' => [
        //             'name' => $document->status->name,
        //             'color' => $document->status->color(),
        //         ],
        //     ]);
    }

    public function reset(Document $document){
        DocumentReset::create([
            'date_received' => Carbon::now(),
            'document_id' => $document->id,
        ]);

        $setStatus = ([
            'status' => DocumentStatus::ACTIVE
        ]);

        $document->update($setStatus);
        
    }

    public function getDocument(Document $document){
        return Document::with('client:id,name,office')->find($document->id);
    }

    public function getDocumentFile(Document $document){
        $downloadpath = Storage::disk('public')->path('uploads/documents/'.$document->document_file);
        return response()->download($downloadpath);
    }

}
