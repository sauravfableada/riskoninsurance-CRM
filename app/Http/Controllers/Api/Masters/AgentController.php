<?php

namespace App\Http\Controllers\Api\Masters;

use App\Http\Controllers\Api\ApiBaseController;
use App\Models\Agent;
use App\Traits\MasterApiTrait;
use Illuminate\Http\Request;

class AgentController extends ApiBaseController
{
    use MasterApiTrait;

    protected function model(): string
    {
        return Agent::class;
    }

    protected function rules(Request $request, $id = null): array
    {
        return [
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255|unique:agents,email,'.$id,
            'phone' => 'nullable|string|max:20',
            'country_id' => 'nullable|exists:countries,id',
            'city_id' => 'nullable|exists:cities,id',
            'type' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ];
    }
}
