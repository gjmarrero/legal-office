<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Client;

class ClientController extends Controller
{
    public function index(){
        // return Client::latest()->get();

        $clients = Client::query()
            ->when(request('query'), function($query){
                $query->where('name', 'like', '%'. request('query') .'%')
                    ->orWhere('office', 'like', '%'. request('query') .'%');
            })
            ->latest()
            ->paginate(setting('pagination_limit'));

            return $clients;
    }

    public function get_clients(){
        return Client::latest()->get();
    }

    public function store(){
        request()->validate([
            "name" => "required",
            "office" => "required",
        ]);

        return Client::create([
            "name" => request("name"),
            "office" => request("office"),
        ]);
    }

    public function update(Client $client){
        request()->validate([
            'name' => 'required',
            'office' => 'required',
        ]);

        $client->update([
            'name' => request('name'),
            'office' => request('office'),
        ]);

        return $client;
    }

    public function delete(Client $client){
        $client->delete();

        return response()->noContent();
    }

    public function bulkDelete(){
        Client::whereIn('id', request('ids'))->delete();

        return response()->json(['message' => "Clients deleted successfully"]);
    }
}

