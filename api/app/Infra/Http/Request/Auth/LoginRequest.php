<?php

namespace App\Infra\Http\Request\Auth;

use App\Core\Application\Auth\Login;
use Illuminate\Foundation\Http\FormRequest;

final class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'The email field is required.',
            'email.email' => 'The email field must be a valid email address.',
            'password.required' => 'The password field is required.',
        ];
    }

    public function toCommand(): Login
    {
        return new Login(
            email: (string) $this->string('email'),
            password: (string) $this->string('password'),
        );
    }
}
