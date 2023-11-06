<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Enums\DocumentType;
use App\Models\Document;

class DocumentTypeController extends Controller
{
    public function getDocumentType(){
        $cases = DocumentType::cases();
        return collect($cases)->map(function($type){
            return [
                'name' => $type->name,
                'value' => $type->value,
                'timeline' =>DocumentType::from($type->value)->timeline(),
            ];
        });
    }
}
