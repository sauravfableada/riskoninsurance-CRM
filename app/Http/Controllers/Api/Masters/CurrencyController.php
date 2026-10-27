<?php

namespace App\Http\Controllers\Api\Masters;

use App\Http\Controllers\Api\ApiBaseController;
use App\Models\Currency;
use App\Traits\MasterApiTrait;
use Illuminate\Http\Request;

class CurrencyController extends ApiBaseController
{
    use MasterApiTrait;

    protected function model(): string
    {
        return Currency::class;
    }

    protected function rules(Request $request, $id = null): array
    {
        return [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:currencies,code,'.$id,
            'symbol' => 'nullable|string|max:10',
            'exchange_rate' => 'nullable|numeric',
        ];
    }
}
