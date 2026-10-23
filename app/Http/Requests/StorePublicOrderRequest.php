<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StorePublicOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            '_hp_website' => ['nullable', 'max:0'],
            'nombre' => [
                'required',
                'string',
                'min:2',
                'max:100',
                'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s\.\'-]{2,100}$/u',
            ],
            'telefono' => [
                'required',
                'string',
                'regex:/^(\+?51)?9\d{8}$/',
            ],
            'consent' => ['accepted'],
            'package_id' => ['required', 'integer', 'exists:packages,id'],
            'guests' => ['required', 'integer', 'min:1'],
            'fecha_evento' => ['required', 'date', 'after_or_equal:today'],
            'event_type' => ['nullable', 'string', 'max:100'],
            'extras' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            '_hp_website.max' => 'Solicitud inválida detectada.',
            'nombre.required' => 'Por favor ingrese su nombre para identificarse.',
            'nombre.min' => 'El nombre debe contener al menos 2 caracteres.',
            'nombre.max' => 'El nombre no puede exceder los 100 caracteres.',
            'nombre.regex' => 'El nombre contiene caracteres no permitidos.',
            'telefono.required' => 'Debe ingresar un número de teléfono celular.',
            'telefono.regex' => 'Ingrese un número celular peruano válido (9 dígitos, comenzando con 9).',
            'consent.accepted' => 'Debe aceptar la cláusula de tratamiento de datos personales (Ley N° 29733) para continuar.',
            'package_id.required' => 'Debe seleccionar un paquete de coctelería.',
            'package_id.exists' => 'El paquete seleccionado no existe en el catálogo.',
            'guests.required' => 'Especifique el número estimado de invitados.',
            'guests.min' => 'El número de invitados debe ser al menos 1.',
            'fecha_evento.required' => 'Indique la fecha tentativa de su evento.',
            'fecha_evento.after_or_equal' => 'La fecha del evento debe ser hoy o una fecha posterior.',
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
