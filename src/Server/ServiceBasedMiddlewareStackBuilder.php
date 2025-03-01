<?php declare(strict_types=1);

namespace BabDev\WebSocketBundle\Server;

use BabDev\WebSocket\Server\ServerMiddleware;
use BabDev\WebSocketBundle\DependencyInjection\Compiler\BuildMiddlewareStackCompilerPass;
use BabDev\WebSocketBundle\Exception\MiddlewareNotConfigured;

/**
 * The service based middleware stack builder provides the middleware stack created by the
 * {@see BuildMiddlewareStackCompilerPass}.
 */
final readonly class ServiceBasedMiddlewareStackBuilder implements MiddlewareStackBuilder
{
    public function __construct(private ?ServerMiddleware $middleware = null) {}

    /**
     * @throws MiddlewareNotConfigured if the middleware stack is not properly configured
     */
    public function build(): ServerMiddleware
    {
        if (!$this->middleware instanceof ServerMiddleware) {
            throw new MiddlewareNotConfigured(\sprintf('The middleware stack is not configured. Ensure your %s instances have the "babdev_websocket_server.server_middleware" service tag or implement your own middleware stack builder.', ServerMiddleware::class));
        }

        return $this->middleware;
    }
}
