<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Member;

class getBalanceRequest extends FormRequest
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
            'providercode' => 'required|string',
            'username' => 'required|string',
            'password' => [
                'required',
                'string',
                'min:7',
                'max:24',
                'regex:/[A-Z]/', 
                'regex:/[a-z]/',
                function ($attribute, $value, $fail) {
                    $sequences = ['123', '234', '345', '456', '567', '678', '789', '890', '012'];
                    foreach ($sequences as $seq) {
                        if (strpos($value, $seq) !== false) {
                            return $fail('The '.$attribute.' must not contain sequential numbers like "123", "234", etc.');
                        }
                    }
                },
            ]
        ];
    }
}
