<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OutgoingDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class OutgoingDocumentController extends Controller
{

    public function index(){
        $searchQuery = request('query_search');
        $searchByQuery = request('query_searchby');
        $current_user = Auth::user()->employee_id;

        $documents = OutgoingDocument::query()
            ->when($searchByQuery === 'content', function($query) use ($searchQuery){
                $query->where('content','like',"%{$searchQuery}%");
            })
            ->when($searchByQuery === 'subject', function($query) use ($searchQuery){
                $query->where('subject','like',"%{$searchQuery}%");
            })
            ->when(($searchByQuery === 'all'), function($query) use ($searchQuery){
                $query->where('content','like',"%{$searchQuery}%")
                    ->orWhere('subject','like',"%{$searchQuery}%");
            })
            ->latest()->paginate(10)
            ->through(fn ($document) => [
                'id' => $document->id,
                'date_dispatched' => $document->date_dispatched,
                'recipient' => $document->recipient,
                'subject' => $document->subject,
                'content' => $document->content,
                'attachment' => $document->document_file,
                'remarks' => $document->remarks,
                
            ]);
        return $documents;
        // dd($searchByQuery);
                    
    }
    public function store(){
        $file_name = '';

        $validated = request()->validate([
            'recipient' => 'required',
            'subject' => 'required',
            'content' => 'required',
            'date_dispatched' => 'required'
        ]);

        if(request()->hasFile('document_file')){
            $file = request()->file('document_file');
            $file_name = time().'_'.$file->getClientOriginalName();
            $path = 'public/uploads/outgoing/'.$file_name;
            Storage::disk('local')->put($path, file_get_contents($file));
        }

        $created_document = OutgoingDocument::create([
            'recipient' => $validated['recipient'],
            'date_dispatched' => $validated['date_dispatched'],
            'subject' => $validated['subject'],
            'content' => $validated['content'],
            'remarks' => request('remarks'),
            'document_file' => $file_name,
        ]);
    }
}
