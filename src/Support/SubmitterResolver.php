<?php

declare(strict_types=1);

namespace HotReloadStudios\Triage\Support;

use Illuminate\Database\Eloquent\Model;

final class SubmitterResolver
{
    public function resolve(string $email): ?Model
    {
        /** @var class-string<Model> $userModelClass */
        $userModelClass = config('triage.user_model');

        /** @var Model|null $user */
        $user = $userModelClass::where('email', $email)->first();

        return $user;
    }

    public function resolveId(string $email): ?string
    {
        $user = $this->resolve($email);

        if ($user === null) {
            return null;
        }

        return (string) $user->getKey();
    }
}
