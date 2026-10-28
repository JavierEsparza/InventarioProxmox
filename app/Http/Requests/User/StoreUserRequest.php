<?php

namespace App\Http\Requests\User;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    protected function failedValidation(Validator $validator)
    {
        Log::info('Error al crear el usuario: ' . $validator->errors());
        $response = redirect()->route('users.create')->withErrors($validator)->withInput();
        throw new \Illuminate\Validation\ValidationException($validator, $response);
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'username' => 'required|string|min:3|max:255|unique:users,username',
            'role' => 'required|string|exists:roles,name',
            'permissions' => 'array',
            'permissions.*' => 'string|exists:permissions,name',
        ];
    }

    public function attributes()
    {
        return ['name'=>'nombre','email'=>'correo electrónico','password'=>'contraseña','username'=>'nombre de usuario','role'=>'rol','permissions'=>'permisos'];
    }
}
