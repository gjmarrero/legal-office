<?php
namespace App\Http\Controllers\Admin;

// require_once __DIR__ . '/vendor/autoload.php';

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
use App\Models\DocumentAttachment;
use Webklex\PDFMerger\Facades\PDFMergerFacade as PDFMerger;
// use GrofGraf\LaravelPDFMerger\PDFMerger;
// use Softplaceweb\PdfMerger\Facades\PdfMerger;
use Symfony\Component\Filesystem\Filesystem,
Xthiago\PDFVersionConverter\Converter\GhostscriptConverterCommand,
Xthiago\PDFVersionConverter\Converter\GhostscriptConverter;
use Symfony\Component\Process\Process;


use App\Models\Document;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function index()
    {
        $searchQuery = request('query_search');
        $searchbyQuery = request('query_searchby');
        $doc_type = request('query_doc_type');
        $to_do = request('query_to_do');
        $current_user = Auth::user()->employee_id;
        $query_type = request('query_type');

        $docWithReset = Document::with('client:id,name,office')
            ->whereHas('document_resets', function ($query) use ($searchbyQuery, $searchQuery, $to_do, $current_user, $doc_type, $query_type) {
                $query
                    ->when(request('query'), function ($query, $selectedStatus) {
                        $query->where('status', $selectedStatus);
                    })
                    ->when(request('query_search'), function ($query) use ($searchQuery, $searchbyQuery) {
                        $query->when($searchbyQuery === 'client', function ($query) use ($searchQuery) {
                            $query->whereHas('client', function ($query) use ($searchQuery) {
                                $query->where('clients.name', 'like', "%{$searchQuery}%");
                            });
                        });
                        $query->when($searchbyQuery === 'title' || $searchbyQuery === 'description', function ($query) use ($searchQuery, $searchbyQuery) {
                            $query->where($searchbyQuery, 'like', "%{$searchQuery}%");
                        });
                        $query->when($searchbyQuery === 'type', function ($query) use ($searchQuery) {
                            $query->where('type', $searchQuery);
                        });
                    })
                    ->when(request('query_to_do'), function ($query) use ($to_do, $current_user) {
                        $query->when($to_do === 'to-receive', function ($query) use ($current_user) {
                            $query->whereHas('transactions', function ($query) use ($current_user) {
                                $query->where([['transactions.status', TransactionStatus::PENDING], ['transactions.employee_id', $current_user], ['type', null]]);
                            });
                        });
                        $query->when($to_do === 'to-release', function ($query) use ($current_user) {
                            $query->whereHas('transactions', function ($query) use ($current_user) {
                                $query->where([['transactions.status', TransactionStatus::PENDING], ['transactions.employee_id', $current_user], ['type', TransactionType::RECEIVED]]);
                            });
                        });
                    })
                    ->when(request('query_type'), function ($query) use ($doc_type, $query_type) {
                        $query
                            ->when($query_type === 'active', function ($query) {
                                $query->active();
                            })
                            ->when($query_type === 'all', function ($query) {
                                $query->userCount();
                            })
                            ->when($doc_type === 'cases', function ($query) {
                                $query->case();
                            })
                            ->when($doc_type === 'administrative', function ($query) {
                                $query->administrativeCases();
                            })
                            ->when($doc_type === 'judicial', function ($query) {
                                $query->judicialCases();
                            })
                            ->when($doc_type === 'quasi', function ($query) {
                                $query->quasiCases();
                            })
                            ->when($doc_type === 'referrals', function ($query) {
                                $query->referral();
                            })
                            ->when($doc_type === 'admin_docs', function ($query) {
                                $query->adminDocs();
                            })
                            ->when($doc_type === 'municipal', function ($query) {
                                $query->municipalOrdinances();
                            })
                            ->when($doc_type === 'other_referral', function ($query) {
                                $query->otherReferrals();
                            })
                            ->when($doc_type == 'provincial', function ($query) {
                                $query->provincialOrdinances();
                            })
                            ->when($doc_type === 'code', function ($query) {
                                $query->codes();
                            })
                            ->when($doc_type === 'notary', function ($query) {
                                $query->notaries();
                            });
                    });
            });
        $docWithoutReset = Document::with('client:id,name,office', 'transactions')
            ->whereDoesntHave('document_resets')
            ->when(request('query'), function ($query, $selectedStatus) {
                $query->where('status', $selectedStatus);
            })
            ->when(request('query_search'), function ($query) use ($searchQuery, $searchbyQuery) {
                $query->when($searchbyQuery === 'client', function ($query) use ($searchQuery) {
                    $query->whereHas('client', function ($query) use ($searchQuery) {
                        $query->where('name', 'like', "%{$searchQuery}%");
                    });
                });
                $query->when($searchbyQuery === 'title' || $searchbyQuery === 'description', function ($query) use ($searchQuery, $searchbyQuery) {
                    $query->where($searchbyQuery, 'like', "%{$searchQuery}%");
                });
                $query->when($searchbyQuery === 'type', function ($query) use ($searchQuery) {
                    $query->where('type', $searchQuery);
                });
            })
            ->when(request('query_to_do'), function ($query) use ($to_do, $current_user) {
                $query->when($to_do === 'to-receive', function ($query) use ($current_user) {
                    $query->whereHas('transactions', function ($query) use ($current_user) {
                        $query->where([['transactions.status', TransactionStatus::PENDING], ['transactions.employee_id', $current_user], ['type', null]]);
                    });
                });
                $query->when($to_do === 'to-release', function ($query) use ($current_user) {
                    $query->whereHas('transactions', function ($query) use ($current_user) {
                        $query->where([['transactions.status', TransactionStatus::PENDING], ['transactions.employee_id', $current_user], ['type', TransactionType::RECEIVED]]);
                    });
                });
            })
            ->when(request('query_type'), function ($query) use ($doc_type, $query_type) {
                $query
                    ->when($query_type === 'all', function ($query) {
                        $query->userCount();
                    })
                    ->when($query_type === 'active', function ($query) {
                        $query->active();
                    })
                    ->when($doc_type === 'cases', function ($query) {
                        $query->case();
                    })
                    ->when($doc_type === 'administrative', function ($query) {
                        $query->administrativeCases();
                    })
                    ->when($doc_type === 'judicial', function ($query) {
                        $query->judicialCases();
                    })
                    ->when($doc_type === 'quasi', function ($query) {
                        $query->quasiCases();
                    })
                    ->when($doc_type === 'referrals', function ($query) {
                        $query->referral();
                    })
                    ->when($doc_type === 'admin_docs', function ($query) {
                        $query->adminDocs();
                    })
                    ->when($doc_type === 'municipal', function ($query) {
                        $query->municipalOrdinances();
                    })
                    ->when($doc_type === 'other_referral', function ($query) {
                        $query->otherReferrals();
                    })
                    ->when($doc_type == 'provincial', function ($query) {
                        $query->provincialOrdinances();
                    })
                    ->when($doc_type === 'code', function ($query) {
                        $query->codes();
                    })
                    ->when($doc_type === 'notary', function ($query) {
                        $query->notaries();
                    });
            });

        $documents_union = $docWithReset->unionAll($docWithoutReset);

        $documents = $documents_union
            ->latest()->paginate(setting('pagination_limit'))
            ->through(fn($document) => [
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
                'last_assigned' => $document->last_assignment,
                'last_transaction_type' => $document->last_transaction_type,
                'days_active' => $document->days_active,
                'additional_attachments' => $document->attachments,
            ]);


        return $documents;

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

    public function store()
    {
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

        if (request()->hasFile('document_file')) {
            $file = request()->file('document_file');
            $original_filename = $file->getClientOriginalName();
            $sanitized_filename = str_replace(' ','_',$original_filename);
            $file_name = time() . '_' . 'document_file' . '_' . $sanitized_filename;
            $path = 'uploads/documents/' . $file_name;
            Storage::disk('public')->put($path, file_get_contents($file));
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

    public function edit(Document $document)
    {
        return $document;
    }

    public function update(Document $document)
    {

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

        if (request()->hasFile('document_file')) {
            $file = request()->file('document_file');
            $original_filename = $file->getClientOriginalName();
            $sanitized_filename = str_replace(' ','_',$original_filename);
            $file_name = time() . '_' . 'document_file' . '_' . $sanitized_filename;
            $path = 'public/uploads/documents/' . $file_name;
            Storage::disk('public')->put($path, file_get_contents($file));
        }

        $validated['document_file'] = $file_name;

        $document->update($validated);

        return response()->json(['success' => true]);
    }

    public function destroy(Document $document)
    {
        $document->delete();

        return response()->json(['success' => true], 200);
    }

    public function archive(Document $document)
    {
        $validated = request()->validate([
            'remarks' => 'required',
        ]);

        $setStatus = ([
            'status' => DocumentStatus::ARCHIVED,
        ]);

        $document->update($setStatus);

        $setTransactionStatus = ([
            'status' => TransactionStatus::COMPLETED,
            'remarks' => $validated['remarks'],
        ]);

        $update_last_transaction = Transaction::where('document_id', $document->id)->orderBy('id', 'desc')->first();

        $update_last_transaction->update($setTransactionStatus);

        return Document::with('client:id,name,office')->where('id', $document->id)->limit(1)->get()
            ->map(fn($doc) => [
                'id' => $doc->id,
                'date_received' => $doc->date_received,
                'client' => [
                    'name' => $doc->client->name,
                    'office' => $doc->client->office,
                ],
                'title' => $doc->title,
                'description' => $doc->description,
                'remarks' => $doc->remarks,
                'status' => [
                    'name' => $doc->status->name,
                    'color' => $doc->status->color(),
                ],
                'last_assigned' => $document->last_assignment,
                'last_transaction_type' => $document->last_transaction_type,
                'type' => $doc->type->name,
                'days_active' => $doc->days_active,
            ]);

    }

    public function reset(Document $document)
    {
        DocumentReset::create([
            'date_received' => Carbon::now(),
            'document_id' => $document->id,
        ]);

        $setStatus = ([
            'status' => DocumentStatus::ACTIVE
        ]);

        $document->update($setStatus);

    }

    public function getDocument(Document $document)
    {
        $document = Document::with('client:id,name,office')->where('id', $document->id)->limit(1)->get()
            ->map(fn($doc) => [
                'id' => $doc->id,
                'date_received' => $doc->date_received,
                'client' => [
                    'name' => $doc->client->name,
                    'office' => $doc->client->office,
                ],
                'title' => $doc->title,
                'description' => $doc->description,
                'remarks' => $doc->remarks,
                'status' => [
                    'name' => $doc->status->name,
                ],
                'last_assigned' => $document->last_assignment,
                'last_transaction_type' => $document->last_transaction_type,
                'type' => $doc->type->name,
                'days_active' => $doc->days_active,
                'document_file' => $doc->document_file,
            ]);

        return $document;

    }

    public function attachfile(Document $document)
    {

        $file_name = '';

        $validated = request()->validate([
            'document_id' => 'required',
        ]);

        if (request()->hasFile('document_file')) {
            $file = request()->file('document_file');
            $original_filename = $file->getClientOriginalName();
            $sanitized_filename = str_replace(' ','_',$original_filename);
            $file_name = time() . '_' . 'document_file' . '_' . $sanitized_filename;
            $path = 'uploads/documents/' . $file_name;
            Storage::disk('public')->put($path, file_get_contents($file));

        }

        DocumentAttachment::create([
            'document_id' => $validated['document_id'],
            'document_file' => $file_name,
        ]);

        return response()->json(['success' => true]);


    }

    public function getAdditionalFiles(Document $document){
        $additional_files = DB::table('document_attachments')->select('document_file')->where('document_id', $document->id)->get();

        return $additional_files;
    }

    public function getAttachedFiles(Document $document)
    {

        $main_file = DB::table('documents')->select('document_file','updated_at',DB::raw("'main' as file_type"))->where([['id', $document->id],['document_file', '<>', NULL],['document_file','<>','']]);

        $transaction_files = DB::table('transactions')->select('document_file','updated_at',DB::raw("'transaction' as file_type"))->where([['document_id', $document->id],['document_file','<>',null],['document_file','<>','']]);

        $additional_files = DB::table('document_attachments')->select('document_file','updated_at',DB::raw("'additional' as file_type"))->where([['document_id', $document->id],['document_file','<>',null],['document_file','<>','']]);

        $all_files = $main_file->union($transaction_files)->union($additional_files)->orderBy('updated_at')->get();        

        $pdfVersion = "1.4";

        $original_filename = "";
        
        foreach ($all_files as $attached_file) {
            $original_filename = $attached_file->document_file;
            
            if($attached_file->file_type === 'transaction'){
                $current_file = Storage::disk("public")->path('uploads/transaction_documents/'.$attached_file->document_file);
            }else{
                $current_file = Storage::disk("public")->path('uploads/documents/'.$attached_file->document_file);
            }
            $sanitized_currentfile = str_replace(' ', '-', $current_file);
            $sanitized_filename = str_replace(' ','-', $original_filename);
            
            $converted_file = Storage::disk("public")->path('uploads/documents/converted/'.$sanitized_filename);

<<<<<<< HEAD
            $gsPath = 'C:\\Program Files\\gs\\gs10.05.1\\bin\\gswin64c.exe';
            $command = "\"$gsPath\" -sDEVICE=pdfwrite -dCompatibilityLevel=$pdfVersion -o -dNOPAUSE -dBATCH -sOutputFile=\"{$converted_file}\" \"{$sanitized_currentfile}\"";
            exec($command);


            // exec("gswin64c -sDEVICE=pdfwrite -dCompatibilityLevel=$pdfVersion -o -dNOPAUSE -dBATCH -sOutputFile=$converted_file $current_file");
=======
            exec("gswin64c -sDEVICE=pdfwrite -dCompatibilityLevel=$pdfVersion -o -dNOPAUSE -dBATCH -sOutputFile=$converted_file $current_file");
>>>>>>> 9b4a72b3e9bae2598cc38aee6504600c75b9dcfb

        }
        // $clean_filenames = [];
        $oMerger = PDFMerger::init();
        foreach ($all_files as $converted){
            $original_filename = $converted->document_file;
            $sanitized_file = str_replace(' ','-',$original_filename);
            $oMerger->addPDF(Storage::disk('public')->path('uploads/documents/converted/'.$sanitized_file));
            // $clean_filenames[] = $sanitized_file;
        }        
        $oMerger->merge();

        $merged_file = 'merged_'.$document->id.'.pdf';

        $oMerger->save(Storage::disk('public')->path('uploads/merged/merged_'.$document->id.'.pdf'));

        foreach ($all_files as $delete_file){
            unlink(Storage::disk("public")->path('uploads/documents/converted/'.str_replace(' ','-',$delete_file->document_file)));
        }

        return $merged_file;
        // dd($clean_filenames);
    }

    public function getDocumentFile(Document $document)
    {
        $downloadpath = Storage::disk('public')->path('uploads/documents/' . $document->document_file);
        return response()->download($downloadpath);
    }

}
