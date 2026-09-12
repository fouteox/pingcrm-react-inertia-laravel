<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

final class UserRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $user = $this->route('user');

        return [
            'first_name' => ['required', 'string', 'max:25'],
            'last_name' => ['required', 'string', 'max:25'],
            'email' => [
                'required',
                'string',
                'max:50',
                'email',
                Rule::unique('users')->ignore($user),
            ],
            'password' => [Rule::when($user !== null, 'sometimes'), 'required', 'string', Password::defaults()],
            'owner' => ['required', 'boolean'],
        ];
    }
}
