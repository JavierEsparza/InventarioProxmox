<?php


namespace App\MultiAuth;


use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Hash;

class Authenticator
{


    public function attempt(string $username, string $password, string $provider)
    {
        if (!$model = config('auth.providers.' . $provider . '.model')) {
            throw new ModelNotFoundException('No existe modelo configurado para esta acción');
        }
        if (!$user = (new $model)->where('email', $username)->first()) {
            return null;
        }
        if (!$user->activo) {
            return null;
        }
        if (!Hash::check($password, $user->password)) {
            return null;
        }
        return $user;
    }
}