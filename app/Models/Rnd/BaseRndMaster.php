<?php

namespace App\Models\Rnd;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

abstract class BaseRndMaster extends Model
{
    use HasFactory;

    abstract public static function label(): string;

    abstract public static function fields(): array;

    /** Columns that together must be unique (case-insensitive); the first field by default. */
    public static function duplicateColumns(): array
    {
        return [static::fields()[0]['name']];
    }

    public static function columns(): array
    {
        return array_map(function (array $field) {
            return [
                'data' => $field['name'],
                'title' => $field['label'],
            ];
        }, static::fields());
    }

    public static function validationRules(): array
    {
        $rules = [];

        foreach (static::fields() as $field) {
            if ($field['type'] === 'multiselect') {
                $fieldRules = [$field['required'] ? 'required' : 'nullable', 'array'];

                if ($field['required']) {
                    $fieldRules[] = 'min:1';
                }

                $rules[$field['name']] = implode('|', $fieldRules);
                $rules[$field['name'] . '.*'] = 'in:' . implode(',', $field['options']);

                continue;
            }

            $fieldRules = [$field['required'] ? 'required' : 'nullable'];

            if ($field['type'] === 'textarea') {
                $fieldRules[] = 'string';
            } elseif ($field['type'] === 'date') {
                $fieldRules[] = 'date';
            } elseif ($field['type'] === 'select') {
                $fieldRules[] = 'in:' . implode(',', $field['options']);
            } else {
                $fieldRules[] = 'string';
                $fieldRules[] = 'max:255';
            }

            $rules[$field['name']] = implode('|', $fieldRules);
        }

        return $rules;
    }
}