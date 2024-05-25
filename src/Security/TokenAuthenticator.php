<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Uid\Uuid;

readonly class TokenAuthenticator implements AuthenticatorInterface, AuthenticationEntryPointInterface
{
    public function __construct(private UserRepository $userRepository)
    {
    }

    public function supports(Request $request): ?bool
    {
        return $request->query->has('token');
    }

    public function getCredentials(Request $request)
    {
        return [
            'token' => $request->query->get('token'),
        ];
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        $apiToken = $credentials['token'];

        if (null === $apiToken) {
            return null;
        }

        return $userProvider->loadUserByUsername($apiToken);
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        // In case you need to check if token is valid, you can do it here.
        // For simplicity, we'll assume token is always valid.
        return true;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new Response('Authentication Failed.', Response::HTTP_FORBIDDEN);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey): ?Response
    {
        // on success, let the request continue
        return null;
    }

    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        // no need to implement this method, as it's not used
        return new Response('Auth required', Response::HTTP_UNAUTHORIZED);
    }

    public function supportsRememberMe(): false
    {
        return false;
    }

    public function authenticate(Request $request): Passport
    {
        $token = $request->query->get('token');

        if (!$token) {
            throw new CustomUserMessageAuthenticationException('Token not provided');
        }

        try {
            Uuid::fromString($token);
        } catch (\Throwable) {
            throw new CustomUserMessageAuthenticationException('Invalid token format');
        }
        $admin = $this->userRepository->findOneBy(['token' => $token]);

        if ($admin === null) {
            throw new CustomUserMessageAuthenticationException('Invalid token');
        }

        $badge = new UserBadge($token, function () use ($admin) {
            return $admin;
        });

        return new SelfValidatingPassport($badge);
    }

    public function createToken(Passport $passport, string $firewallName): TokenInterface
    {
        /** @var $user User */
        $user = $passport->getUser();

        $token = $user->getToken();

        if ($token !== null) {
            return new Token($token, $user, $user->getRoles());
        }

        throw new AuthenticationCredentialsNotFoundException('No authentication token found.');
    }
}
