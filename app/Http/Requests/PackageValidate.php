<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class PackageValidate extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare inputs before validation.
     * Checkboxes in HTML forms submit 'on' when checked and are absent when unchecked.
     * We convert them into true boolean values before validation rules run.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'active' => $this->boolean('active'),
            'is_barman' => $this->boolean('is_barman'),
        ]);
    }

    public function rules(): array
    {
        return [
            'id' => 'nullable|integer|min:0',
            'name' => 'required|string|max:150',
            'order_num' => 'nullable|string|max:20',
            'badge_glasses' => 'nullable|string|max:60',
            'tagline' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:30',
            'light_bg' => 'nullable|string|max:30',
            'price_mode' => 'required|string|in:fixed,per_guest,tiered',
            'min_guests' => 'required|integer|min:1',
            'max_guests' => 'nullable|integer|gte:min_guests',
            'ideal_capacity' => 'nullable|string|max:100',
            'default_guests' => 'nullable|integer|min:1',
            'base_price' => 'nullable|numeric|min:0',
            'description' => 'nullable|string|max:1000',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
            'image_preset' => 'nullable|string|max:255',
            'active' => 'nullable|boolean',
            'is_barman' => 'nullable|boolean',
            'features' => 'nullable', // array | json string
            'tiers' => 'nullable', // array | json string
            'items' => 'nullable', // array | json string
        ];
    }

    public function messages(): array
    {
        return [
            // Required fields
            'name.required' => 'El nombre del paquete es obligatorio.',
            'name.string' => 'El nombre del paquete debe ser texto.',
            'name.max' => 'El nombre del paquete no puede superar los 150 caracteres.',

            'price_mode.required' => 'Debe seleccionar una modalidad de precio.',
            'price_mode.in' => 'La modalidad de precio seleccionada no es válida.',

            'min_guests.required' => 'Debe especificar el número mínimo de invitados.',
            'min_guests.integer' => 'El número mínimo de invitados debe ser un número entero.',
            'min_guests.min' => 'El número mínimo de invitados debe ser al menos 1.',

            // Numerical & range constraints
            'max_guests.integer' => 'El número máximo de invitados debe ser un número entero.',
            'max_guests.gte' => 'El máximo de invitados debe ser mayor o igual al mínimo.',

            'default_guests.integer' => 'Los invitados sugeridos deben ser un número entero.',
            'default_guests.min' => 'Los invitados sugeridos deben ser al menos 1.',

            'base_price.numeric' => 'El precio base debe ser un valor numérico.',
            'base_price.min' => 'El precio base no puede ser negativo.',

            // Booleans & files
            'active.boolean' => 'El estado activo debe ser verdadero o falso.',
            'is_barman.boolean' => 'El campo servicio de barman debe ser verdadero o falso.',

            'image.image' => 'El archivo debe ser una imagen válida (JPG, PNG, WEBP).',
            'image.mimes' => 'La imagen debe ser de tipo JPG, PNG o WEBP.',
            'image.max' => 'La imagen no puede exceder los 4MB.',

            // String lengths
            'order_num.max' => 'El número de orden no puede superar los 20 caracteres.',
            'badge_glasses.max' => 'La insignia de vasos no puede superar los 60 caracteres.',
            'tagline.max' => 'El subtítulo web no puede superar los 255 caracteres.',
            'color.max' => 'El código de color no puede superar los 30 caracteres.',
            'light_bg.max' => 'El fondo claro no puede superar los 30 caracteres.',
            'ideal_capacity.max' => 'La capacidad ideal no puede superar los 100 caracteres.',
            'description.max' => 'La descripción no puede superar los 1000 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre del paquete',
            'price_mode' => 'modalidad de precio',
            'min_guests' => 'mínimo de invitados',
            'max_guests' => 'máximo de invitados',
            'default_guests' => 'invitados sugeridos',
            'base_price' => 'precio base',
            'active' => 'estado activo',
            'is_barman' => 'servicio de barman',
            'image' => 'imagen',
            'description' => 'descripción',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'status' => false,
                'msg' => $validator->errors()->first(),
            ], 422)
        );
    }
}
