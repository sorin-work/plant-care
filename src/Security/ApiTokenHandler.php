<?php

namespace App\Security;

use App\Repository\UserRepository;
use SensitiveParameter;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

final class ApiTokenHandler implements AccessTokenHandlerInterface
{
    public function __construct(private readonly UserRepository $users)
    {
    }

    public function getUserBadgeFrom(#[SensitiveParameter] string $accessToken): UserBadge
    {
        $user = $this->users->findOneBy(['apiTokenHash' => hash('sha256', $accessToken)]);
        if (null === $user) {
            throw new BadCredentialsException('Invalid API token.');
        }

        return new UserBadge($user->getUserIdentifier());
    }
}
