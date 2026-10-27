<?php

namespace App\Http\Controllers\Api\Masters;

use App\Http\Controllers\Api\ApiBaseController;
use App\Models\TravelType;
use App\Traits\MasterApiTrait;
use Illuminate\Http\Request;

class TravelTypeController extends ApiBaseController
{
    use MasterApiTrait;

    protected function model(): string
    {
        return TravelType::class;
    }

    protected function rules(Request $request, $id = null): array
    {
        return [
            'name' => 'required|string|max:255|unique:travel_types,name,'.$id,
        ];
    }
}
