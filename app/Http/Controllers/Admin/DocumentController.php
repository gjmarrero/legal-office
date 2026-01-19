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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Models\DocumentAttachment;
use Webklex\PDFMerger\Facades\PDFMergerFacade as PDFMerger;
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
        $perPage = request('per_page', 10);

        $filters = [
            'type' => request('filter_type'),
            'date_received' => request('filter_date_received'),
            'client' => request('filter_client'),
            'office' => request('filter_office'),
            'title' => request('filter_title'),
            'description' => request('filter_description'),
            'employee' => request('filter_employee'),
        ];

        $query = Document::with(['client', 'employee']);

        // RESET FILTER
        $resetFilter = request('query_to_do'); // with_reset, without_reset

        if ($resetFilter === 'with_reset') {
            $query->whereHas('document_resets');
        }

        if ($resetFilter === 'without_reset') {
            $query->whereDoesntHave('document_resets');
        }

        // APPLY COLUMN FILTERS
        $query
            ->when($filters['type'], function ($q) use ($filters) {
                $search = strtolower($filters['type']);

                $matched = collect(DocumentType::cases())
                    ->first(
                        fn($case) =>
                        str_contains(strtolower($case->label()), $search) ||
                        str_contains(strtolower($case->name), $search)
                    );

                if ($matched) {
                    $q->where('type', $matched->value);
                } else {
                    $q->whereRaw('0=1'); // no results
                }
            })
            ->when($filters['date_received'], fn($q) => $q->where('date_received', 'like', "%{$filters['date_received']}%"))
            ->when($filters['title'], fn($q) => $q->where('title', 'like', "%{$filters['title']}%"))
            ->when($filters['description'], fn($q) => $q->where('description', 'like', "%{$filters['description']}%"))
            ->when(
                $filters['client'],
                fn($q) =>
                $q->whereHas(
                    'client',
                    fn($c) =>
                    $c->where('name', 'like', "%{$filters['client']}%")
                )
            )
            ->when(
                $filters['office'],
                fn($q) =>
                $q->whereHas(
                    'client',
                    fn($c) =>
                    $c->where('office', 'like', "%{$filters['office']}%")
                )
            )
            ->when(
                $filters['employee'],
                fn($q) =>
                $q->whereHas(
                    'employee',
                    fn($e) =>
                    $e->where('emp_name', 'like', "%{$filters['employee']}%")
                )
            );

        // SEARCH BAR FILTER
        $search = request('query');
        $searchBy = request('query_searchby');

        if ($search && $searchBy) {
            if ($searchBy === 'id') {
                $query->where('id', $search);
            } elseif ($searchBy === 'client') {
                $query->whereHas('client', fn($c) =>
                    $c->where('name', 'like', "%{$search}%"));
            } elseif ($searchBy === 'employee') {
                $query->whereHas('employee', fn($e) =>
                    $e->where('emp_name', 'like', "%{$search}%"));
            } else {
                $query->where($searchBy, 'like', "%{$search}%");
            }
        }

        //DASHBOARD FILTERS
        $doc_type = request('query_doc_type');
        $query_type = request('query_type');
        $to_do = request('query_to_do');
        $query_doc_status = request('query_doc_status');
        $current_user = Auth::user()->employee_id;

        $query->when(request('query_type'), function ($query) use ($doc_type, $query_type, $query_doc_status, $to_do, $current_user) {
            $query->when($query_doc_status === 'near_due', function ($query) use ($doc_type) {
                $query->active()
                    ->when($doc_type === 'case', function ($query) {
                        $query->case()->nearDueFifteenDays();
                    })
                    ->when($doc_type === 'administrative', function ($query) {
                        $query->administrativeCases()->nearDueFifteenDays();
                    })
                    ->when($doc_type === 'judicial', function ($query) {
                        $query->judicialCases()->nearDueFifteenDays();
                    })
                    ->when($doc_type === 'quasi', function ($query) {
                        $query->quasiCases()->nearDueFifteenDays();
                    })
                    ->when($doc_type === 'admin_docs', function ($query) {
                        $query->adminDocs()->nearDueThreeDays();
                    })->when($doc_type === 'other_referral', function ($query) {
                        $query->otherReferrals()->nearDueSevenDays();
                    })->when($doc_type === 'municipal', function ($query) {
                        $query->municipalOrdinances()->nearDueSevenDays();
                    })->when($doc_type === 'provincial', function ($query) {
                        $query->provincialOrdinances()->nearDueTenDays();
                    })->when($doc_type === 'code', function ($query) {
                        $query->codes()->nearDueTenDays();
                    })->when($doc_type === 'ctrc', function ($query) {
                        $query->ctrcs()->nearDueSevenDays();
                    })->when($doc_type === 'referral', function ($query) {
                        $query->where(function ($q) {
                            $q->where(function ($x) {
                                $x->adminDocs()->nearDueThreeDays();
                            })
                                ->orWhere(function ($x) {
                                    $x->otherReferrals()->nearDueSevenDays();
                                })
                                ->orWhere(function ($x) {
                                    $x->municipalOrdinances()->nearDueSevenDays();
                                })
                                ->orWhere(function ($x) {
                                    $x->provincialOrdinances()->nearDueTenDays();
                                })
                                ->orWhere(function ($x) {
                                    $x->codes()->nearDueTenDays();
                                })
                                ->orWhere(function ($x) {
                                    $x->ctrcs()->nearDueSevenDays();
                                });

                        });
                    });

            })
                ->when($query_doc_status === 'past_due', function ($query) use ($doc_type) {
                    $query->active()
                        ->when($doc_type === 'case', function ($query) {
                            return $query->case()->pastDueFifteenDays();
                        })
                        ->when($doc_type === 'administrative', function ($query) {
                            return $query->administrativeCases()->pastDueFifteenDays();
                        })
                        ->when($doc_type === 'judicial', function ($query) {
                            return $query->judicialCases()->pastDueFifteenDays();
                        })
                        ->when($doc_type === 'quasi', function ($query) {
                            return $query->quasiCases()->pastDueFifteenDays();
                        })
                        ->when($doc_type === 'admin_docs', function ($query) {
                            return $query->adminDocs()->pastDueThreeDays();
                        })
                        ->when($doc_type === 'other_referral', function ($query) {
                            return $query->otherReferrals()->pastDueSevenDays();
                        })
                        ->when($doc_type === 'municipal', function ($query) {
                            return $query->municipalOrdinances()->pastDueSevenDays();
                        })
                        ->when($doc_type === 'provincial', function ($query) {
                            return $query->provincialOrdinances()->pastDueTenDays();
                        })
                        ->when($doc_type === 'code', function ($query) {
                            return $query->codes()->pastDueTenDays();
                        })
                        ->when($doc_type === 'ctrc', function ($query) {
                            return $query->ctrcs()->pastDueSevenDays();
                        })
                        ->when($doc_type === 'referral', function ($query) {
                            return $query->where(function ($q) {
                                $q->orWhere(function ($x) {
                                    return $x->adminDocs()->pastDueThreeDays();
                                })
                                    ->orWhere(function ($x) {
                                        return $x->otherReferrals()->pastDueSevenDays();
                                    })
                                    ->orWhere(function ($x) {
                                        return $x->municipalOrdinances()->pastDueSevenDays();
                                    })
                                    ->orWhere(function ($x) {
                                        return $x->provincialOrdinances()->pastDueTenDays();
                                    })
                                    ->orWhere(function ($x) {
                                        return $x->codes()->pastDueTenDays();
                                    })
                                    ->orWhere(function ($x) {
                                        return $x->ctrcs()->pastDueSevenDays();
                                    });
                            });
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
                ->when($query_type === 'all', function ($query) use ($doc_type) {
                    $query
                        ->when($doc_type === 'referrals', function ($query) {
                            $query->referral();
                        })
                        ->when($doc_type === 'code', function ($query) {
                            $query->codes();
                        })
                        ->when($doc_type === 'municipal', function ($query) {
                            $query->municipalOrdinances();
                        })
                        ->when($doc_type === 'provincial', function ($query) {
                            $query->provincialOrdinances();
                        })
                        ->when($doc_type === 'other_referral', function ($query) {
                            $query->otherReferrals();
                        })
                        ->when($doc_type === 'ctrc', function ($query) {
                            $query->ctrcs();
                        })
                        ->when($doc_type === 'admin_docs', function ($query) {
                            $query->adminDocs();
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
                        });
                });

        });
        $query->orderBy('created_at', 'desc');

        $paginator = $query->paginate($perPage);

        return $this->shortenLinks($paginator, 1);

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
            'employee_id' => 'nullable'
        ], [
            'client_id.required' => "Client name is required",

        ]);

        if (request()->hasFile('document_file')) {
            $file = request()->file('document_file');
            $original_filename = $file->getClientOriginalName();
            $sanitized_filename = str_replace(' ', '_', $original_filename);
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
            'employee_id' => $validated['employee_id'],
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
            'employee_id' => 'nullable'
        ]);

        if (request()->hasFile('document_file')) {
            $file = request()->file('document_file');
            $original_filename = $file->getClientOriginalName();
            $sanitized_filename = str_replace(' ', '_', $original_filename);
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
                // 'status' => [
                //     'name' => $doc->status->name,
                // ],
                'status_info' => [
                    'name' => $document->status->label(),
                    'color' => $document->status->color(),
                ],
                'last_assigned' => $document->last_assignment,
                'last_transaction_type' => $document->last_transaction_type,
                'type' => $doc->type->name,
                'days_active' => $doc->days_active,
                'document_file' => $doc->document_file,
                'employee' => $doc->employee?->emp_name,
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
            $sanitized_filename = str_replace(' ', '_', $original_filename);
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

    public function getAdditionalFiles(Document $document)
    {
        $additional_files = DB::table('document_attachments')->select('id', 'document_file')->where('document_id', $document->id)->get();

        return $additional_files;
    }

    public function getAttachedFiles(Document $document)
    {

        $main_file = DB::table('documents')->select('document_file', 'updated_at', DB::raw("'main' as file_type"))->where([['id', $document->id], ['document_file', '<>', NULL], ['document_file', '<>', '']]);

        $transaction_files = DB::table('transactions')->select('document_file', 'updated_at', DB::raw("'transaction' as file_type"))->where([['document_id', $document->id], ['document_file', '<>', null], ['document_file', '<>', '']]);

        $additional_files = DB::table('document_attachments')->select('document_file', 'updated_at', DB::raw("'additional' as file_type"))->where([['document_id', $document->id], ['document_file', '<>', null], ['document_file', '<>', '']]);

        $all_files = $main_file->union($transaction_files)->union($additional_files)->orderBy('updated_at')->get();

        $pdfVersion = "1.4";

        $original_filename = "";

        foreach ($all_files as $attached_file) {
            $original_filename = $attached_file->document_file;

            $current_file = ($attached_file->file_type === 'transaction')
                ? Storage::disk("public")->path('uploads/transaction_documents/' . $attached_file->document_file)
                : Storage::disk("public")->path('uploads/documents/' . $attached_file->document_file);

            $sanitized_filename = str_replace(' ', '-', $original_filename);
            $converted_file = Storage::disk("public")->path('uploads/documents/converted/' . $sanitized_filename);

            if (!file_exists(dirname($converted_file))) {
                mkdir(dirname($converted_file), 0777, true);
            }

            $gsPath = 'C:\\Program Files\\gs\\gs10.05.1\\bin\\gswin64c.exe';
            $command = "\"$gsPath\" -sDEVICE=pdfwrite -dCompatibilityLevel=$pdfVersion -dNOPAUSE -dBATCH -sOutputFile=\"{$converted_file}\" \"{$current_file}\"";

            exec($command, $output, $returnVar);

            if ($returnVar !== 0) {
                throw new \Exception("Ghostscript conversion failed for {$current_file}: " . implode("\n", $output));
            }
        }

        // $clean_filenames = [];
        $oMerger = PDFMerger::init();
        foreach ($all_files as $converted) {
            $original_filename = $converted->document_file;
            $sanitized_file = str_replace(' ', '-', $original_filename);
            $oMerger->addPDF(Storage::disk('public')->path('uploads/documents/converted/' . $sanitized_file));
            // $clean_filenames[] = $sanitized_file;
        }
        $oMerger->merge();

        $merged_file = 'merged_' . $document->id . '.pdf';

        $oMerger->save(Storage::disk('public')->path('uploads/merged/merged_' . $document->id . '.pdf'));

        foreach ($all_files as $delete_file) {
            unlink(Storage::disk("public")->path('uploads/documents/converted/' . str_replace(' ', '-', $delete_file->document_file)));
        }

        return $merged_file;
        // dd($clean_filenames);
    }

    public function getDocumentFile(Document $document)
    {
        $downloadpath = Storage::disk('public')->path('uploads/documents/' . $document->document_file);
        return response()->download($downloadpath);
    }

    private function shortenLinks($paginator, $onEachSide = 1)
    {
        $current = $paginator->currentPage();
        $last = $paginator->lastPage();

        $pages = [];

        // Always show first page
        $pages[] = 1;

        // Left ellipsis
        if ($current - $onEachSide > 2) {
            $pages[] = '...';
        }

        // Middle range
        for ($i = max(2, $current - $onEachSide); $i <= min($last - 1, $current + $onEachSide); $i++) {
            $pages[] = $i;
        }

        // Right ellipsis
        if ($current + $onEachSide < $last - 1) {
            $pages[] = '...';
        }

        // Always show last page
        if ($last > 1) {
            $pages[] = $last;
        }

        // Recreate "links" array
        $links = [];

        // Previous
        $links[] = [
            'url' => $paginator->previousPageUrl(),
            'label' => '&laquo; Previous',
            'active' => false,
        ];

        foreach ($pages as $page) {
            $links[] = [
                'url' => $page === '...' ? null : $paginator->url($page),
                'label' => (string) $page,
                'active' => $page === $current,
            ];
        }

        // Next
        $links[] = [
            'url' => $paginator->nextPageUrl(),
            'label' => 'Next &raquo;',
            'active' => false,
        ];

        // Replace original links
        $paginator->setCollection($paginator->getCollection());
        $paginator->links = $links;

        return $paginator;
    }

    public function deleteAdditionalFile($fileId)
    {
        $attachment = DocumentAttachment::where('id', $fileId)->first();

        if (!$attachment) {
            return response()->json([
                'message' => 'Attachment not found'
            ], 404);
        }

        $filePath = 'uploads/documents/' . $attachment->document_file;
        if (
            $attachment->document_file &&
            Storage::disk('public')->exists($filePath)
        ) {

            Storage::disk('public')->delete($filePath);
        }

        $attachment->delete();

        return response()->json([
            'message' => 'Attachment deleted successfully'
        ]);
    }

    public function deleteDocumentFile($documentId)
    {
        $document = Document::where('id', $documentId)->first();

        if ($document->document_file) {

            $filePath = 'uploads/documents/' . $document->document_file;

            if (Storage::disk('public')->exists($filePath)) {
                Storage::disk('public')->delete($filePath);
            }

            $document->update([
                'document_file' => null
            ]);
        }

        return response()->json([
            'message' => 'Document file removed successfully'
        ]);
    }

}
