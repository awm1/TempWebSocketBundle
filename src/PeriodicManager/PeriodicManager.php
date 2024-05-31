<?php declare(strict_types=1);

namespace BabDev\WebSocketBundle\PeriodicManager;

use React\EventLoop\LoopInterface;

interface PeriodicManager
{
    public function getName(): string;

    public function register(LoopInterface $loop): void;

    public function cancelTimers(): void;
}
