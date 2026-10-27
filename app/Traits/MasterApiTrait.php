<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;

trait MasterApiTrait
{
    /**
     * Get the model class for the master module
     */
    abstract protected function model(): string;

    /**
     * Get validation rules for store/update
     */
    abstract protected function rules(Request $request, $id = null): array;

    public function index()
    {
        $data = $this->model()::all();
        return $this->success($data);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules($request));
        $item = $this->model()::create($validated);
        return $this->success($item, 'Created successfully', 201);
    }

    public function show($id)
    {
        $item = $this->model()::findOrFail($id);
        return $this->success($item);
    }

    public function update(Request $request, $id)
    {
        $item = $this->model()::findOrFail($id);
        $validated = $request->validate($this->rules($request, $id));
        $item->update($validated);
        return $this->success($item, 'Updated successfully');
    }

    public function destroy($id)
    {
        $item = $this->model()::findOrFail($id);
        $item->delete();
        return $this->success(null, 'Deleted successfully');
    }
}
