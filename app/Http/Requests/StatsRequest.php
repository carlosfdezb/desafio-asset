<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StatsRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  protected function prepareForValidation(): void
  {
    $this->merge([
      'slug' => $this->route('slug'),
      'api_key' => $this->header('X-API-Key'),
    ]);
  }

  public function rules(): array
  {
    return [
      'slug' => ['required', 'string', 'max:100'],
      'api_key' => ['nullable', 'string', 'min:8', 'max:255'],
    ];
  }

  public function messages(): array
  {
    return [
      'slug.required' => 'El slug es obligatorio.',
      'slug.string' => 'El slug debe ser una cadena de texto.',
      'slug.max' => 'El slug no puede tener más de 100 caracteres.',
      'api_key.string' => 'La API key debe ser una cadena de texto.',
      'api_key.min' => 'La API key debe tener al menos :min caracteres.',
      'api_key.max' => 'La API key no debe exceder :max caracteres.',
    ];
  }

  protected function failedValidation(Validator $validator): void
  {
    throw new HttpResponseException(
      response()->json([
        'message' => 'Los datos enviados no son válidos.',
        'errors' => $validator->errors(),
      ], 422)
    );
  }
}
