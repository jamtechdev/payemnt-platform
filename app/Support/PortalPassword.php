<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Validation\Rules\Password;

final class PortalPassword
{
    public static function defaults(): Password
    {
        return Password::min(12)->mixedCase()->numbers()->symbols();
    }
}
