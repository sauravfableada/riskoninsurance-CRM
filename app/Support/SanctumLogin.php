<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class SanctumLogin
{
    public static function findActiveUserByEmail(string $email): ?User
    {
        $query = User::query()->where('email', $email);

        if (Schema::hasColumn('users', 'status')) {
            $query->where('status', 1);
        } else {
            $query->where('is_active', 1);
        }

        return $query->first();
    }

    public static function issueToken(User $user, string $deviceName): string
    {
        return $user->createToken($deviceName)->plainTextToken;
    }

    public static function defaultDeviceName(Request $request, string $fallback = 'web'): string
    {
        $agent = trim((string) $request->userAgent());

        if ($agent === '') {
            return $fallback;
        }

        return mb_substr($agent, 0, 255);
    }

    public static function validateCredentials(string $email, string $password): ?User
    {
        $user = static::findActiveUserByEmail($email);

        if (!$user || !Hash::check($password, $user->password)) {
            return null;
        }

        return $user;
    }
}
