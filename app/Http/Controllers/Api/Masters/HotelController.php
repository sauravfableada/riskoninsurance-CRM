<?php

namespace App\Http\Controllers\Api\Masters;

use App\Http\Controllers\Api\ApiBaseController;
use App\Models\Hotel;
use App\Traits\MasterApiTrait;
use Illuminate\Http\Request;

class HotelController extends ApiBaseController
{
    use MasterApiTrait;

    protected function model(): string
    {
        return Hotel::class;
    }

    protected function rules(Request $request, $id = null): array
    {
        return [
            'name' => 'required|string|max:255',
            'country_id' => 'nullable|exists:countries,id',
            'city_id' => 'nullable|exists:cities,id',
            'star_rating' => 'nullable|integer|min:1|max:5',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'is_active' => 'boolean',
        ];
    }
}
