<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ProductImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'excel' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'excel.required' => 'Seleccione un archivo de hoja de cálculo (.xlsx).',
            'excel.file'     => 'El archivo enviado no es válido.',
            'excel.mimes'    => 'El archivo debe estar en formato Excel (.xlsx o .xls).',
            'excel.max'      => 'El archivo no debe exceder los 10 MB.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'status' => false,
            'msg'    => $validator->errors()->first(),
            'errors' => $validator->errors(),
            'type'   => 'warning',
        ], 422));
    }
}
