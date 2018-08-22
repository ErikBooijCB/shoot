<?php
declare(strict_types=1);

namespace Shoot\Shoot\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Shoot\Shoot\MiddlewareInterface;
use Shoot\Shoot\View;

/**
 * Logs each view processed by the pipeline to a PSR-3 compliant logger.
 */
final class LoggingMiddleware implements MiddlewareInterface
{
    /** @var LoggerInterface */
    private $logger;

    /**
     * @param LoggerInterface $logger A PSR-3 compliant logger.
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param View                   $view    The view to be processed by this middleware.
     * @param ServerRequestInterface $request The current HTTP request being handled.
     * @param callable               $next    The next middleware to call.
     *
     * @return View The processed view.
     */
    public function process(View $view, ServerRequestInterface $request, callable $next): View
    {
        /** @var View $view */
        $startTime = microtime(true);
        $view = $next($view);
        $endTime = microtime(true);

        $presentationModel = $view->getPresentationModel();

        $fields = [
            'presentation_model' => $presentationModel->getName(),
            'time_taken' => sprintf("%f seconds", $endTime - $startTime),
            'variables' => $presentationModel->getVariables(),
        ];

        $this->logger->debug($view->getName(), $fields);

        return $view;
    }
}
