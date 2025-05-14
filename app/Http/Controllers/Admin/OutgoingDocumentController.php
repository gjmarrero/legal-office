<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DocumentAttachmentOutgoing;
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
                'recipient_office' => $document->recipient_office,
                'subject' => $document->subject,
                'content' => $document->content,
                'attachment' => $document->document_file,
                'additional_attachments' => $document->attachments,
                'remarks' => $document->remarks,
                
            ]);
        return $documents;
        // dd($searchByQuery);
                    
    }
    public function store(){
        $file_name = '';

        $validated = request()->validate([
            'recipient' => 'required',
            'recipient_office' => 'required',
            'subject' => 'required',
            'content' => 'required',
            'date_dispatched' => 'required'
        ]);

        if(request()->hasFile('document_file')){
            $file = request()->file('document_file');
            $file_name = time().'_'.$file->getClientOriginalName();
            $path = '/uploads/outgoing/'.$file_name;
            Storage::disk('public')->put($path, file_get_contents($file));
        }

        OutgoingDocument::create([
            'recipient' => $validated['recipient'],
            'recipient_office' => $validated['recipient_office'],
            'date_dispatched' => $validated['date_dispatched'],
            'subject' => $validated['subject'],
            'content' => $validated['content'],
            'remarks' => request('remarks'),
            'document_file' => $file_name,
        ]);
    }

    public function edit(OutgoingDocument $document){
        return($document);
    }

    public function update(OutgoingDocument $document){
        $file_name = '';

        $validated = request()->validate([
            'recipient' => 'required',
            'recipient_office' => 'required',
            'subject' => 'required',
            'content' => 'required',
            'date_dispatched' => 'required'
        ]);

        if(request()->hasFile('document_file')){
            $file = request()->file('document_file');
            $file_name = time().'_'.$file->getClientOriginalName();
            $path = 'public/uploads/outgoing/'.$file_name;
            Storage::disk('public')->put($path, file_get_contents($file));
        }

        $validated['document_file'] = $file_name;

        $document->update($validated);
        
        return response()->json(['success' => true]);
    }

    public function attachFile(OutgoingDocument $document)
    {

        $file_name = '';

        $validated = request()->validate([
            'document_id' => 'required',
        ]);

        if (request()->hasFile('document_file')) {
            $file = request()->file('document_file');
            $file_name = time() . '_' . 'document_file' . '_' . $file->getClientOriginalName();
            $path = 'uploads/outgoing_documents/' . $file_name;
            Storage::disk('public')->put($path, file_get_contents($file));

        }

        DocumentAttachmentOutgoing::create([
            'document_id' => $validated['document_id'],
            'document_file' => $file_name,
        ]);

        return response()->json(['success' => true]);


    }
}
