<?php

declare(strict_types=1);

namespace Tests\Presentation\Middleware;

use App\Domain\Dto\AccountDto;
use App\Domain\Repositories\AccountRepository;
use App\Infrastructure\Cryptography\BodyTokenCreator;
use App\Infrastructure\Persistence\MemoryRepositories\InMemoryAccountRepository;
use App\Presentation\Handlers\RefreshTokenHandler;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Tests\TestCase;

use function PHPUnit\Framework\assertNotNull;
use function PHPUnit\Framework\assertSame;

/**
 * @internal
 * @coversNothing
 */
class AuthMiddlewareTest extends TestCase
{
    public function setUp(): void
    {
        $this->app = $this->createAppInstance();
        $this->app->group('/api', function ($group) {
            $group->get('/test-auth', function (RequestInterface $request, ResponseInterface $response): ResponseInterface {
                $response->getBody()->write('Works');

                return $response;
            });
        });
        $this->setUpErrorHandler($this->app);
    }

    public function testShouldPassWhenJwtIsProvided()
    {
        self::autowireContainer(AccountRepository::class, new InMemoryAccountRepository());

        $dto = new AccountDto(email: 'mail.com', username: 'user', password: 'pass');
        $repository = $this->getContainer()->get(AccountRepository::class);
        $account = $repository->insert($dto);

        $tokenCreator = new BodyTokenCreator($account);
        $token = $tokenCreator->createToken($_ENV['JWT_SECRET']);

        $request = $this->createRequestWithAuthentication($token);

        $response = $this->app->handle($request);

        assertNotNull($response);
        assertSame($response->getBody()->__toString(), "Works");
        assertSame(200, $response->getStatusCode());
    }

    public function testShouldCallErrorOnJWTErrorHandlerWhenNoRefreshTokenIsProvided()
    {
        $response = $this->app->handle($this->createMockRequest());

        assertNotNull($response);
        assertSame(401, $response->getStatusCode());
    }

    public function testShouldInterceptHttpCookieRefresh()
    {
        $request = $this->createMockRequest();

        $tokenValue = 'any_value';

        $request = $request->withCookieParams([REFRESH_TOKEN => $tokenValue]);

        $mockJwtRefreshHandler = $this->getMockBuilder(RefreshTokenHandler::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        /**
         * @var \Psr\Container\ContainerInterface
         */
        $container = $this->getContainer();

        $container->set(RefreshTokenHandler::class, $mockJwtRefreshHandler);

        $response = $this->app->handle($request);

        assertNotNull($response);
    }

    private function createMockRequest(): ServerRequestInterface
    {
        return $this->createRequest('GET', '/api/test-auth');
    }

    private function createRequestWithAuthentication(string $token)
    {
        $bearer = 'Bearer ' . $token;

        return $this->createRequest(
            'GET',
            '/api/test-auth',
            [
                'HTTP_ACCEPT' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => $bearer,
            ],
        );
    }
}
