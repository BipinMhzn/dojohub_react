<?php

namespace App\System\Foundation\Traits;

trait AvatarGenerator
{
    public function generateAvatar(string $name): string
    {
        $trimmedName = trim(collect(explode(' ', $name))
            ->map(function ($segment) {
                return mb_substr($segment, 0, 1);
            })->join(' '));
        return 'https://ui-avatars.com/api/?name=' . urlencode($trimmedName) . '&color=1A237E&background=EBF4FF';
    }

}
