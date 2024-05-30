<?php declare(strict_types=1);

namespace BabDev\WebSocketBundle\CacheWarmer;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

/**
 * Decorator for the FrameworkBundle's router cache warmer to ensure the websocket router matcher and generator classes are generated in a unique cache path.
 */
final readonly class RouterCacheWarmer implements CacheWarmerInterface
{
    public function __construct(
        private CacheWarmerInterface $cacheWarmer,
        private string $websocketRouterFolder,
    ) {}

    public function warmUp(string $cacheDir, ?string $buildDir = null): array
    {
        return $this->cacheWarmer->warmUp(
            $cacheDir.'/'.ltrim($this->websocketRouterFolder, '/'),
            null === $buildDir ? null : $buildDir.'/'.ltrim($this->websocketRouterFolder, '/'),
        );
    }

    public function isOptional(): bool
    {
        return true;
    }
}
