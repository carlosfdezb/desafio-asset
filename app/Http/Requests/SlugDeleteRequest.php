<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class SlugDeleteRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'api_key' =>  ['nullable', 'string', 'min:8', 'max:255'],
    ];
  }

  public function messages(): array
  {
    return [
      'api_key.min' => 'La API key debe tener al menos :min caracteres.',
      'api_key.max' => 'La API key no debe exceder :max caracteres.',
      'api_key.string' => 'La API key debe ser una cadena de texto.',
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
