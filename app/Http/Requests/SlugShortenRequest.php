<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class SlugShortenRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'url' => ['required', 'url:http,https'],
      'custom_slug' => ['nullable', 'string', 'max:100'],
      'api_key' => ['nullable', 'string', 'min:8', 'max:255'],
      'expires_at' => ['nullable', 'date', 'after:now'],
    ];
  }

  public function messages(): array
  {
    return [
      'url.required' => 'El campo URL es obligatorio.',
      'url.url' => 'El campo URL debe contener una dirección válida.',
      'custom_slug.string' => 'El slug personalizado debe ser una cadena de texto.',
      'custom_slug.max' => 'El slug personalizado no puede tener más de 100 caracteres.',
      'api_key.string' => 'La API key debe ser una cadena de texto.',
      'api_key.min' => 'La API key debe tener al menos 8 caracteres.',
      'api_key.max' => 'La API key no puede tener más de 255 caracteres.',
      'expires_at.date' => 'La fecha de expiración debe ser una fecha válida.',
      'expires_at.after' => 'La fecha de expiración debe ser posterior a la fecha actual.',
    ];
  }

  public function attributes(): array
  {
    return [
      'url' => 'URL',
      'custom_slug' => 'slug personalizado',
      'api_key' => 'API key',
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
