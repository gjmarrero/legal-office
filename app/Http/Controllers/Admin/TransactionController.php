<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\Document;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

class TransactionController extends Controller
{
    public function index(){
    }
    
    public function transactions(Document $document){
        $transactions = Transaction::query()
            ->with('employee:id,emp_name')
            ->with('user:id,name')
            ->where('document_id', $document->id)
            ->latest()->paginate()
            ->through(fn ($transaction) => [
                'id' => $transaction->id,
                'date_assigned' => $transaction->formatted_date_assigned,
                'assigned_to' => $transaction->employee,
                'action' => $transaction->action,
                'status' => [
                    'name' => $transaction->status->name,
                    'color' => $transaction->status->color(),
                ],
                'attachment' => $transaction->document_file,
                'routed_by' => $transaction->user,
                'type' => [
                    'name' => $transaction->type?->name,
                    'color' => $transaction->type?->color(),
                ],
            ]);
        return $transactions;
    }

    public function route(){
        $routeOutside = request('routeOutside');
        $file_name = '';
        $validated = request()->validate([
            'document_id' => 'required',
            'employee_id' => 'required',
            'action' => 'required',
        ], [
            'employee_id.required' => "Employee name is required",
        ]);

        if(request()->hasFile('document_file')){
            $file = request()->file('document_file');
            $file_name = time().'_'.'document_file'.'_'.$file->getClientOriginalName();
            $path = 'public/uploads/transaction_documents/'.$file_name;
            Storage::disk('local')->put($path, file_get_contents($file));
        }
        
        if($routeOutside == 1){
            $status = TransactionStatus::COMPLETED;
            $type = TransactionType::RELEASED;
        }else{
            $status = TransactionStatus::PENDING;
            $type = null;
        }
        

        Transaction::create([
            'employee_id' => $validated['employee_id'],
            'document_id' => $validated['document_id'],
            'action' => $validated['action'],
            'document_file' => $file_name,            
            'user_id' => Auth::user()->id,
            'status' => $status,
            'type' => $type,
        ]);
        
        $update_last_transaction = Transaction::where([['document_id',request('document_id')],['type',TransactionType::RECEIVED],['status',TransactionStatus::PENDING],['employee_id',Auth::user()->employee_id]]);
        $update_last_transaction->update([
            'status' => TransactionStatus::COMPLETED,
            'type' => TransactionType::RELEASED,
        ]);

        // dd($routeOutside);
    }

    public function update(Transaction $transaction){
        $file_name = '';
        
        $validated = request()->validate([
            'employee_id' => 'required',
            'action' => 'required',
        ]);

        if(request()->hasFile('document_file')){
            $file = request()->file('document_file');
            $file_name = time().'_'.'document_file'.''.$file->getClientOriginalName();
            $path = 'public/uploads/transaction_documents/'.$file_name;
            Storage::disk('local')->put($path, file_get_contents($file));
        }

        $validated['document_file'] = $file_name;
        $validated['user_id'] = Auth::user()->id;
        $transaction->update($validated);
        return response()->json(['success' => true]);
    }

    public function receive($document_id){
        $receive_document = Transaction::where([['document_id',$document_id],['type',null],['status',TransactionStatus::PENDING],['employee_id',Auth::user()->employee_id]]);
        $receive_document->update([
            'type' => TransactionType::RECEIVED,
        ]);
    }

    // public function release($document_id){
    //     $release_document = Transaction::where([['document_id',$document_id],['type',TransactionType::RECEIVED],['employee_id',Auth::user()->employee_id],['status',TransactionStatus::PENDING]]);
    //     $release_document->update([
    //         'type' => TransactionType::RELEASED,
    //         'status' => TransactionStatus::COMPLETED,
    //     ]);
    // }

    public function getTransactionFile(Transaction $transaction){
        // $downloadpath = Storage::disk('public')->path('uploads/documents/'.$document->document_file);
        // return response()->download($downloadpath);

        $downloadpath = Storage::disk('public')->path('uploads/transaction_documents/'.$transaction->document_file);
        // ob_end_clean();
        return response()->download($downloadpath);
    }
}
