<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'date_received' => $this->date_received,

            'client' => $this->client ? [
                'id' => $this->client->id,
                'name' => $this->client->name,
                'office' => $this->client->office,
            ] : null,

            'employee' => $this->employee ? [
                'id' => $this->employee->id,
                'emp_name' => $this->employee->emp_name,
            ] : null,

            'status_info' => [
                'name' => $this->status->name,
                'color' => $this->status->color,
            ],

            'type_label' => $this->type_label,
        ];
    }
}
