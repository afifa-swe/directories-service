<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class StoreBudgetHolderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tin' => 'required|string',
            'name' => 'required|string',
            'region' => 'required|string',
            'district' => 'required|string',
            'address' => 'required|string',
            'phone' => 'required|string',
            'responsible' => 'required|string',
            'created_by' => 'nullable|uuid',
            'updated_by' => 'nullable|uuid',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $response = response()->json([
            'message' => 'Ошибка валидации',
            'data' => $validator->errors(),
            'timestamp' => now()->toIso8601String(),
            'success' => false,
        ], 422);

        throw new ValidationException($validator, $response);
    }
}
