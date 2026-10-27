<?php

namespace App\Http\Controllers;

use App\Models\CustomField;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomFieldController extends Controller
{
    public function index(Request $request)
    {
        $module = $request->get('module');
        $modules = $this->getModules();
        
        $query = CustomField::query();
        if ($module) {
            $query->where('module', $module);
        }
        
        $fields = $query->orderBy('module')->orderBy('sort_order')->get();
        return view('crm.settings.custom_fields.index', compact('fields', 'module', 'modules'));
    }

    private function getModules()
    {
        return [
            'Customer', 'Followup', 'Lead', 'Invoice', 'Ticket', 
            'Task', 'Project', 'Service', 'Deal', 'Report', 
            'Meeting', 'Pipeline', 'Stage', 'Product'
        ];
    }

    public function create(Request $request)
    {
        $module = $request->get('module', 'Lead');
        return view('crm.settings.custom_fields.create', compact('module'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'module' => ['required', 'string'],
            'fields' => ['required', 'array', 'min:1'],
            'fields.*.type' => ['required', 'string', 'in:text,number,date,select,textarea'],
            'fields.*.label' => ['required', 'string', 'max:255'],
            'fields.*.name' => ['required', 'string', 'max:50', 'alpha_dash'],
            'fields.*.options' => ['nullable', 'string'],
            'fields.*.is_required' => ['nullable', 'boolean'],
            'fields.*.is_active' => ['nullable', 'boolean'],
            'fields.*.sort_order' => ['nullable', 'integer'],
        ]);

        $module = $request->get('module');
        $fieldsData = $request->get('fields');

        DB::transaction(function () use ($module, $fieldsData) {
            foreach ($fieldsData as $field) {
                if (isset($field['options']) && $field['type'] === 'select') {
                    $field['options'] = array_map('trim', explode(',', $field['options']));
                }
                
                $field['module'] = $module;
                $field['is_required'] = isset($field['is_required']) ? (bool)$field['is_required'] : false;
                $field['is_active'] = isset($field['is_active']) ? (bool)$field['is_active'] : true;
                $field['sort_order'] = isset($field['sort_order']) ? (int)$field['sort_order'] : 0;

                CustomField::create($field);
            }
        });

        return redirect()->route('settings.custom-fields.index', ['module' => $module])
            ->with('success', count($fieldsData) . ' custom fields created successfully.');
    }

    public function edit(CustomField $customField)
    {
        return view('crm.settings.custom_fields.edit', compact('customField'));
    }

    public function update(Request $request, CustomField $customField)
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'options' => ['nullable', 'string'],
            'is_required' => ['boolean'],
            'sort_order' => ['integer'],
            'is_active' => ['boolean'],
        ]);

        if (isset($data['options'])) {
            $data['options'] = array_map('trim', explode(',', $data['options']));
        }

        $customField->update($data);

        return redirect()->route('settings.custom-fields.index', ['module' => $customField->module])
            ->with('success', 'Custom field updated successfully.');
    }

    public function destroy(CustomField $customField)
    {
        $module = $customField->module;
        $customField->delete();

        return redirect()->route('settings.custom-fields.index', ['module' => $module])
            ->with('success', 'Custom field deleted successfully.');
    }
}
