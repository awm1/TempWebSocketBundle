<?php declare(strict_types=1);

namespace BabDev\WebSocketBundle\Authentication\Exception;

use BabDev\WebSocket\Server\WebSocketException;

class AuthenticationException extends \RuntimeException implements WebSocketException {}
