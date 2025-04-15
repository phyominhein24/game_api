<?php

namespace App\Http\Requests;

use App\Enums\GeneralStatusEnum;
use App\Helpers\Enum;
use Illuminate\Foundation\Http\FormRequest;

class MemberStoreRequest extends FormRequest
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
        $enum = implode(',', (new Enum(GeneralStatusEnum::class))->values());

        return [
            'name' => 'required|string| unique:members,name| max:24 | min:1',
            'email' => 'nullable| email| unique:members,email|unique:users,email|string',
            'phone' => 'required|unique:members,phone|min:9|max:13',
            'password' => [
                'required',
                'string',
                'min:7',
                'max:24',
                'regex:/[A-Z]/', // at least one uppercase
                'regex:/[a-z]/', // at least one lowercase
                function ($attribute, $value, $fail) {
                    $sequences = ['123', '234', '345', '456', '567', '678', '789', '890', '012'];
                    foreach ($sequences as $seq) {
                        if (strpos($value, $seq) !== false) {
                            return $fail('The '.$attribute.' must not contain sequential numbers like "123", "234", etc.');
                        }
                    }
                },
            ],
            'status' => "nullable|in:$enum"
        ];
    }
}
