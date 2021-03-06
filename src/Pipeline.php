<?php
declare(strict_types=1);

namespace Shoot\Shoot;

use Psr\Http\Message\ServerRequestInterface;

/**
 * The processing pipeline of Shoot. Holds the middleware that enables Shoot's functionality. It's called from the Twig
 * extension.
 */
final class Pipeline
{
    /** @var callable */
    private $middleware;

    /** @var ServerRequestInterface|null */
    private $request;

    /**
     * Constructs an instance of Pipeline. Takes the middleware that enables Shoot's functionality. Middleware is
     * executed in the same order as given.
     *
     * @param MiddlewareInterface[] $middleware
     */
    public function __construct(array $middleware = [])
    {
        $this->middleware = $this->chainMiddleware($middleware);
    }

    /**
     * Sets the HTTP request context while executing the given callback. Any templates should be rendered within this
     * callback. Returns the result returned by the callback (if any).
     *
     * @param ServerRequestInterface $request
     * @param callable               $callback
     *
     * @return mixed
     */
    public function withRequest(ServerRequestInterface $request, callable $callback)
    {
        try {
            $this->request = $request;

            return $callback();
        } finally {
            $this->request = null;
        }
    }

    /**
     * @param View $view
     *
     * @return void
     *
     * @internal
     */
    public function process(View $view): void
    {
        if ($this->request === null) {
            throw new MissingRequestException('Cannot process a view without a request set. This method should be called from the callback passed to Pipeline::withRequest');
        }

        call_user_func($this->middleware, $view);
    }

    /**
     * @param MiddlewareInterface[] $middleware
     *
     * @return callable
     */
    private function chainMiddleware(array $middleware): callable
    {
        $middleware = array_reverse($middleware);

        return array_reduce($middleware, function (callable $next, MiddlewareInterface $middleware): callable {
            return function (View $view) use ($middleware, $next): View {
                return $middleware->process($view, $this->request, $next);
            };
        }, function (View $view): View {
            $view->render();

            return $view;
        });
    }
}
