<?php

namespace App\EventListener;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;

/**
 * Limits API calls per token (or per IP without a token).
 */
final class ApiRateLimitListener
{
    public function __construct(
        #[Autowire(service: 'limiter.api')]
        private readonly RateLimiterFactoryInterface $limiter,
    ) {
    }

    // before the firewall, so that brute forcing tokens is limited too
    #[AsEventListener(event: KernelEvents::REQUEST, priority: 20)]
    public function onRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if (!$event->isMainRequest() || !str_starts_with($request->getPathInfo(), '/api/')) {
            return;
        }

        $auth = (string) $request->headers->get('Authorization');
        $key = '' !== $auth ? 'token-'.hash('sha256', $auth) : 'ip-'.$request->getClientIp();
        $limit = $this->limiter->create($key)->consume();

        $request->attributes->set('_api_rate_remaining', $limit->getRemainingTokens());
        if (!$limit->isAccepted()) {
            $retry = max(1, $limit->getRetryAfter()->getTimestamp() - time());
            $event->setResponse(new JsonResponse(['error' => 'Too many requests, slow down a little.'], 429, [
                'Retry-After' => (string) $retry,
            ]));
        }
    }

    #[AsEventListener(event: KernelEvents::RESPONSE)]
    public function onResponse(ResponseEvent $event): void
    {
        $remaining = $event->getRequest()->attributes->get('_api_rate_remaining');
        if (\is_int($remaining)) {
            $event->getResponse()->headers->set('X-RateLimit-Remaining', (string) $remaining);
        }
    }
}
