<?php

namespace App\Http\Controllers\Api\Masters;

use App\Http\Controllers\Api\ApiBaseController;
use App\Models\RoomCategory;
use App\Traits\MasterApiTrait;
use Illuminate\Http\Request;

class RoomCategoryController extends ApiBaseController
{
    use MasterApiTrait;

    protected function model(): string
    {
        return RoomCategory::class;
    }

    protected function rules(Request $request, $id = null): array
    {
        return [
            'name' => 'required|string|max:255|unique:room_categories,name,'.$id,
        ];
    }
}
