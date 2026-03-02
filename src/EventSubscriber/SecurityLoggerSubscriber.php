<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;
use Symfony\Component\Security\Http\SecurityEvents;

/**
 * SecurityLoggerSubscriber logs security-related events
 */
class SecurityLoggerSubscriber implements EventSubscriberInterface
{
    public function __construct(private LoggerInterface $securityLogger)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SecurityEvents::INTERACTIVE_LOGIN => ['onInteractiveLogin'],
            LogoutEvent::class => ['onLogout'],
        ];
    }

    public function onInteractiveLogin(InteractiveLoginEvent $event): void
    {
        $user = $event->getAuthenticationToken()->getUser();
        $request = $event->getRequest();

        $this->securityLogger->info('User login successful', [
            'username' => $user->getUserIdentifier(),
            'ip_address' => $request->getClientIp(),
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s'),
        ]);
    }

    public function onLogout(LogoutEvent $event): void
    {
        $token = $event->getToken();

        if (null !== $token) {
            $user = $token->getUser();
            $request = $event->getRequest();

            $this->securityLogger->info('User logout', [
                'username' => $user->getUserIdentifier(),
                'ip_address' => $request->getClientIp(),
                'timestamp' => (new \DateTime())->format('Y-m-d H:i:s'),
            ]);
        }
    }
}

