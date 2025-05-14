<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Enums\DocumentStatus;
use App\Models\Document;

class DocumentStatusController extends Controller
{
    public function getStatusWithCount(){
        $searchQuery = request('query_search');
        $searchbyQuery = request('query_searchby');

        $cases = DocumentStatus::cases();
        return collect($cases)->map(function($status) use($searchQuery,$searchbyQuery){
            return [
                'name' => $status->name,
                'value' => $status->value,
                'count' => Document::where('status', $status->value)
                ->when(request('query_search'), function($query) use ($searchQuery,$searchbyQuery){
                            $query->when($searchbyQuery === 'client', function ($query) use ($searchQuery){
                                $query->whereHas('client', function($query) use($searchQuery){
                                    $query->where('name','like',"%{$searchQuery}%");
                                });
                            });
                            $query->when($searchbyQuery === 'title' || $searchbyQuery === 'description', function($query) use($searchQuery,$searchbyQuery){
                                $query->where($searchbyQuery, 'like', "%{$searchQuery}%");
                            });                
                        })->count(),
                'color' =>DocumentStatus::from($status->value)->color(),
            ];
        });
    }
}
