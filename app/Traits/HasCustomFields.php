<?php

namespace App\Traits;

use App\Models\CustomField;
use App\Models\CustomFieldValue;

trait HasCustomFields
{
    public function customFieldValues()
    {
        return $this->morphMany(CustomFieldValue::class, 'model');
    }

    public function getCustomFieldValue($fieldName)
    {
        $field = CustomField::where('module', class_basename($this))
            ->where('name', $fieldName)
            ->first();

        if (!$field) return null;

        $value = $this->customFieldValues()
            ->where('custom_field_id', $field->id)
            ->first();

        return $value ? $value->value : null;
    }

    public function saveCustomFields(array $values)
    {
        $fields = CustomField::where('module', class_basename($this))
            ->whereIn('name', array_keys($values))
            ->get();

        foreach ($fields as $field) {
            $this->customFieldValues()->updateOrCreate(
                ['custom_field_id' => $field->id],
                ['value' => $values[$field->name]]
            );
        }
    }
}
