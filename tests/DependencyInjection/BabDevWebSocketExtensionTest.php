<?php declare(strict_types=1);

namespace BabDev\WebSocketBundle\Tests\DependencyInjection;

use BabDev\WebSocketBundle\Authentication\Storage\Driver\StorageDriver;
use BabDev\WebSocketBundle\DependencyInjection\BabDevWebSocketExtension;
use BabDev\WebSocketBundle\DependencyInjection\Configuration;
use BabDev\WebSocketBundle\DependencyInjection\Factory\Authentication\SessionAuthenticationProviderFactory;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Symfony\Component\DependencyInjection\Argument\IteratorArgument;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\Reference;

final class BabDevWebSocketExtensionTest extends AbstractExtensionTestCase
{
    public function testContainerIsLoadedWithValidConfiguration(): void
    {
        $uri = 'tcp://127.0.0.1:8080';
        $context = [];
        $origins = ['example.com', 'example.net'];
        $blockedIps = ['192.168.1.1', '10.0.0.0/16'];
        $routerResource = '%kernel.project_dir%/config/websocket_router.php';

        $this->load([
            'server' => [
                'uri' => $uri,
                'context' => $context,
                'allowed_origins' => $origins,
                'blocked_ip_addresses' => $blockedIps,
                'router' => [
                    'resource' => $routerResource,
                ],
            ],
        ]);

        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'babdev_websocket_server.command.run_websocket_server',
            4,
            $uri,
        );

        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'babdev_websocket_server.socket_server.factory.default',
            0,
            $context,
        );

        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'babdev_websocket_server.router',
            1,
            $routerResource,
        );

        foreach ($origins as $origin) {
            $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
                'babdev_websocket_server.server.server_middleware.restrict_to_allowed_origins',
                'allowOrigin',
                [$origin],
            );
        }

        foreach ($blockedIps as $blockedIp) {
            $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
                'babdev_websocket_server.server.server_middleware.reject_blocked_ip_address',
                'blockAddress',
                [$blockedIp],
            );
        }

        $this->assertContainerBuilderHasAlias('babdev_websocket_server.authentication.storage.driver', 'babdev_websocket_server.authentication.storage.driver.in_memory');
        $this->assertContainerBuilderHasAlias(StorageDriver::class, 'babdev_websocket_server.authentication.storage.driver.in_memory');
        $this->assertContainerBuilderNotHasService('babdev_websocket_server.server.server_middleware.initialize_session');
        $this->assertContainerBuilderNotHasService('babdev_websocket_server.server.session.factory');
        $this->assertContainerBuilderNotHasService('babdev_websocket_server.server.session.storage.factory.read_only_native');
    }

    public function testContainerIsLoadedWithSessionAuthenticationProviderConfigured(): void
    {
        $this->load([
            'authentication' => [
                'providers' => [
                    'session' => [
                        'firewalls' => 'main',
                    ],
                ],
            ],
            'server' => [
                'uri' => 'tcp://127.0.0.1:8080',
                'router' => [
                    'resource' => '%kernel.project_dir%/config/websocket_router.php',
                ],
            ],
        ]);

        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'babdev_websocket_server.authentication.authenticator',
            0,
            new IteratorArgument([new Reference('babdev_websocket_server.authentication.provider.session.default')])
        );
    }

    public function testContainerIsLoadedWithPsrCacheAuthenticationStorageConfigured(): void
    {
        $this->load([
            'authentication' => [
                'storage' => [
                    'type' => Configuration::AUTHENTICATION_STORAGE_TYPE_PSR_CACHE,
                    'pool' => 'cache.websocket',
                ],
            ],
            'server' => [
                'uri' => 'tcp://127.0.0.1:8080',
                'router' => [
                    'resource' => '%kernel.project_dir%/config/websocket_router.php',
                ],
            ],
        ]);

        $this->assertContainerBuilderHasAlias('babdev_websocket_server.authentication.storage.driver', 'babdev_websocket_server.authentication.storage.driver.psr_cache');
        $this->assertContainerBuilderHasAlias(StorageDriver::class, 'babdev_websocket_server.authentication.storage.driver.psr_cache');

        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'babdev_websocket_server.authentication.storage.driver.psr_cache',
            0,
            new Reference('cache.websocket')
        );
    }

    public function testContainerIsLoadedWithServiceAuthenticationStorageConfigured(): void
    {
        $this->load([
            'authentication' => [
                'storage' => [
                    'type' => Configuration::AUTHENTICATION_STORAGE_TYPE_SERVICE,
                    'id' => 'app.authentication.storage.driver.custom',
                ],
            ],
            'server' => [
                'uri' => 'tcp://127.0.0.1:8080',
                'router' => [
                    'resource' => '%kernel.project_dir%/config/websocket_router.php',
                ],
            ],
        ]);

        $this->assertContainerBuilderHasAlias('babdev_websocket_server.authentication.storage.driver', 'app.authentication.storage.driver.custom');
        $this->assertContainerBuilderHasAlias(StorageDriver::class, 'app.authentication.storage.driver.custom');
    }

    public function testContainerIsLoadedWithConfiguredSessionFactory(): void
    {
        $this->load([
            'server' => [
                'uri' => 'tcp://127.0.0.1:8080',
                'context' => [],
                'allowed_origins' => [],
                'blocked_ip_addresses' => [],
                'router' => [
                    'resource' => '%kernel.project_dir%/config/websocket_router.php',
                ],
                'session' => [
                    'factory_service_id' => 'session.factory.test',
                ],
            ],
        ]);

        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'babdev_websocket_server.server.server_middleware.initialize_session',
            1,
            'session.factory.test',
        );

        $this->assertContainerBuilderNotHasService('babdev_websocket_server.server.server_middleware.reject_blocked_ip_address');
        $this->assertContainerBuilderNotHasService('babdev_websocket_server.server.server_middleware.restrict_to_allowed_origins');
        $this->assertContainerBuilderNotHasService('babdev_websocket_server.server.session.factory');
        $this->assertContainerBuilderNotHasService('babdev_websocket_server.server.session.storage.factory.read_only_native');
    }

    public function testContainerIsLoadedWithConfiguredSessionStorageFactory(): void
    {
        $this->load([
            'server' => [
                'uri' => 'tcp://127.0.0.1:8080',
                'context' => [],
                'allowed_origins' => [],
                'blocked_ip_addresses' => [],
                'router' => [
                    'resource' => '%kernel.project_dir%/config/websocket_router.php',
                ],
                'session' => [
                    'storage_factory_service_id' => 'session.storage.factory.test',
                ],
            ],
        ]);

        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'babdev_websocket_server.server.server_middleware.initialize_session',
            1,
            'babdev_websocket_server.server.session.factory',
        );

        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'babdev_websocket_server.server.session.factory',
            0,
            'session.storage.factory.test',
        );

        $this->assertContainerBuilderNotHasService('babdev_websocket_server.server.server_middleware.reject_blocked_ip_address');
        $this->assertContainerBuilderNotHasService('babdev_websocket_server.server.server_middleware.restrict_to_allowed_origins');
        $this->assertContainerBuilderNotHasService('babdev_websocket_server.server.session.storage.factory.read_only_native');
    }

    public function testContainerIsLoadedWithConfiguredSessionHandler(): void
    {
        $this->load([
            'server' => [
                'uri' => 'tcp://127.0.0.1:8080',
                'context' => [],
                'allowed_origins' => [],
                'blocked_ip_addresses' => [],
                'router' => [
                    'resource' => '%kernel.project_dir%/config/websocket_router.php',
                ],
                'session' => [
                    'handler_service_id' => 'session.handler.test',
                ],
            ],
        ]);

        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'babdev_websocket_server.server.server_middleware.initialize_session',
            1,
            'babdev_websocket_server.server.session.factory',
        );

        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'babdev_websocket_server.server.session.factory',
            0,
            'babdev_websocket_server.server.session.storage.factory.read_only_native',
        );

        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'babdev_websocket_server.server.session.storage.factory.read_only_native',
            3,
            'session.handler.test',
        );

        $this->assertContainerBuilderNotHasService('babdev_websocket_server.server.server_middleware.reject_blocked_ip_address');
        $this->assertContainerBuilderNotHasService('babdev_websocket_server.server.server_middleware.restrict_to_allowed_origins');
    }

    /**
     * @return ExtensionInterface[]
     */
    protected function getContainerExtensions(): array
    {
        $extension = new BabDevWebSocketExtension();
        $extension->addAuthenticationProviderFactory(new SessionAuthenticationProviderFactory());

        return [
            $extension,
        ];
    }
}
