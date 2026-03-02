<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * ApiLoggerSubscriber logs API requests and responses
 */
class ApiLoggerSubscriber implements EventSubscriberInterface
{
    public function __construct(private LoggerInterface $apiLogger)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 256],
            KernelEvents::RESPONSE => ['onKernelResponse', -256],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        // Only log API routes
        if (!str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        $this->apiLogger->info('API request received', [
            'method' => $request->getMethod(),
            'path' => $request->getPathInfo(),
            'query_string' => $request->getQueryString(),
            'ip_address' => $request->getClientIp(),
            'user_agent' => $request->headers->get('User-Agent'),
        ]);
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        // Only log API routes
        if (!str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        $response = $event->getResponse();

        $this->apiLogger->info('API response sent', [
            'method' => $request->getMethod(),
            'path' => $request->getPathInfo(),
            'status_code' => $response->getStatusCode(),
            'content_length' => $response->headers->get('Content-Length'),
        ]);
    }
}

