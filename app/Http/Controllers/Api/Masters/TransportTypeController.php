<?php

namespace App\Http\Controllers\Api\Masters;

use App\Http\Controllers\Api\ApiBaseController;
use App\Models\TransportType;
use App\Traits\MasterApiTrait;
use Illuminate\Http\Request;

class TransportTypeController extends ApiBaseController
{
    use MasterApiTrait;

    protected function model(): string
    {
        return TransportType::class;
    }

    protected function rules(Request $request, $id = null): array
    {
        return [
            'name' => 'required|string|max:255|unique:transport_types,name,'.$id,
        ];
    }
}
