<?php

namespace App\Infra\Http\Request\Auth;

use App\Core\Application\Auth\SignUp;
use Illuminate\Foundation\Http\FormRequest;

final class SignUpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'The name field is required.',
            'email.required' => 'The email field is required.',
            'email.email' => 'The email field must be a valid email address.',
            'password.required' => 'The password field is required.',
            'password.min' => 'The password field must be at least 8 characters.',
            'password.confirmed' => 'The password confirmation does not match.',
        ];
    }

    public function toCommand(): SignUp
    {
        return new SignUp(
            name: (string) $this->string('name'),
            email: (string) $this->string('email'),
            password: (string) $this->string('password'),
        );
    }
}
