<?php

namespace App\Http\Controllers\Api\Masters;

use App\Http\Controllers\Api\ApiBaseController;
use App\Models\LeadSource;
use App\Traits\MasterApiTrait;
use Illuminate\Http\Request;

class LeadSourceController extends ApiBaseController
{
    use MasterApiTrait;

    protected function model(): string
    {
        return LeadSource::class;
    }

    protected function rules(Request $request, $id = null): array
    {
        return [
            'name' => 'required|string|max:255|unique:lead_sources,name,'.$id,
        ];
    }
}
