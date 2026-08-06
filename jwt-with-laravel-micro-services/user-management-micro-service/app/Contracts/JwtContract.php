<?php

namespace App\Contracts;

use App\Models\User;

interface JwtContract
{
    public function generateToken(User $user);

    public function validateToken(string $token);

    public function decodeToken(string $token);
}
