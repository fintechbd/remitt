<?php

namespace Fintech\Remit\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WalletVerificationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'wallet_no' => ['required', 'string', 'min:5', 'min:255'],
            'wallet_id' => ['nullable', 'string'],
        ];
    }
}
