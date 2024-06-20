<?php

declare(strict_types=1);

namespace App\Presentation\Actions\Protocols;

use App\Domain\Exceptions\Protocols\HttpSpecializedAdapter;
use App\Domain\Exceptions\Protocols\HttpSpecializedAdapterCustom;
use Core\Http\Exceptions\BaseHttpException;
use Core\Http\Exceptions\HttpBadRequestException;
use Core\Http\Interfaces\ActionInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use React\Promise\PromiseInterface;
use function React\Async\await;

abstract class Action implements ActionInterface
{
    protected Request $request;

    protected Response $response;

    protected array $args;

    public function __construct(protected LoggerInterface $logger)
    {
    }

    /**
     * @throws BaseHttpException
     */
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $this->response = $response;
        $this->args = $args;

        try {
            $response = $this->action($request);

            if ($response instanceof PromiseInterface) {
                $response = await($response);
            }

            assert($response instanceof Response);

            return $response;
        } catch (HttpSpecializedAdapter $httpSpecializedAdapter) {
            throw $httpSpecializedAdapter->wire($request);
        } catch (HttpSpecializedAdapterCustom $httpSpecializedAdapter) {
            throw $httpSpecializedAdapter->wire($request);
        }
    }

    protected function respondWithData(null|array|object $data = null, int $statusCode = 200): Response
    {
        $payload = new ActionPayload($statusCode, $data);

        return $this->respond($payload);
    }

    protected function respond(ActionPayload $payload): Response
    {
        $json = json_encode($payload);
        $this->response->getBody()->write($json);

        return $this->response->withHeader('Content-Type', 'application/json');
    }

    /**
     * @throws HttpBadRequestException
     *
     * @return mixed
     */
    protected function resolveArg(string $name)
    {
        if (!isset($this->args[$name])) {
            throw new class ($name) extends HttpSpecializedAdapterCustom {
                public function __construct(private string $name)
                {
                }

                public function wire(Request $request): BaseHttpException
                {
                    return new HttpBadRequestException($request, sprintf('Could not resolve argument `%s`.', $this->name));
                }
            };
        }

        return $this->args[$name];
    }
}
