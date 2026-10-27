<?php

namespace App\Http\Controllers\Api\Masters;

use App\Http\Controllers\Api\ApiBaseController;
use App\Models\Country;
use App\Traits\MasterApiTrait;
use Illuminate\Http\Request;

class CountryController extends ApiBaseController
{
    use MasterApiTrait;

    protected function model(): string
    {
        return Country::class;
    }

    protected function rules(Request $request, $id = null): array
    {
        return [
            'name' => 'required|string|max:255|unique:countries,name,'.$id,
            'iso_code' => 'required|string|min:2|max:3',
            'is_active' => 'boolean',
        ];
    }
}
