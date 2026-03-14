<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Errors under /api/ are returned as {"error": "..."} instead of HTML pages.
 */
#[AsEventListener(event: KernelEvents::EXCEPTION, priority: -10)]
final class ApiExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        if (!str_starts_with($event->getRequest()->getPathInfo(), '/api/')) {
            return;
        }

        $exception = $event->getThrowable();
        $status = match (true) {
            $exception instanceof AccessDeniedException => 403,
            $exception instanceof HttpExceptionInterface => $exception->getStatusCode(),
            default => 500,
        };
        $message = match (true) {
            403 === $status => 'You are not allowed to do this.',
            $status >= 500 => 'Something went wrong.',
            default => $exception->getMessage(),
        };

        $event->setResponse(new JsonResponse(['error' => '' === $message ? 'Error' : $message], $status));
    }
}
