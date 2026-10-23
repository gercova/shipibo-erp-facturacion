<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class SimulatePublicOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'package_id' => ['required', 'integer', 'exists:packages,id'],
            'guests' => ['required', 'integer', 'min:1'],
            'extras' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'package_id.required' => 'Debe seleccionar un paquete válido.',
            'package_id.exists' => 'El paquete seleccionado no existe en el catálogo.',
            'guests.required' => 'Debe ingresar el número de invitados.',
            'guests.min' => 'El número de invitados debe ser al menos 1.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'status' => false,
            'msg' => $validator->errors()->first(),
            'errors' => $validator->errors(),
            'type' => 'warning',
        ], 422));
    }
}
