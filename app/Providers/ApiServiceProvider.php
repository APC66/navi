<?php

namespace App\Providers;

use App\Http\Controllers\Api\CalendarController;
use App\Http\Controllers\Api\CancellationController;
use App\Http\Controllers\Api\SeedController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\CartController;
use Illuminate\Support\ServiceProvider;
use ReflectionMethod;
use WP_REST_Request;

class ApiServiceProvider extends ServiceProvider
{
    public function register()
    {
        add_action('rest_api_init', [$this, 'registerRoutes']);
    }

    public function registerRoutes()
    {
        $this->registerApiRoute('GET', 'seeds', [SeedController::class, 'index']);
        $this->registerApiRoute('GET', 'seeds/(?P<id>[\d]+)', [SeedController::class, 'show']);

        $this->registerApiRoute('GET', 'calendar/events', [CalendarController::class, 'index']);
        $this->registerApiRoute('POST', 'booking/add-to-cart', [CartController::class, 'addToCart']);

        // ROUTES ANNULATION
        $this->registerApiRoute('GET', 'cancellation/analyze', [CancellationController::class, 'analyze']);
        $this->registerApiRoute('POST', 'cancellation/confirm', [CancellationController::class, 'confirm']);
        $this->registerApiRoute('POST', 'cancellation/reschedule', [CancellationController::class, 'reschedule']);

        $this->registerApiRoute('GET', 'cruises/search', [SearchController::class, 'search']);

    }

    protected function registerApiRoute(string $method, string $route, array $callback)
    {
        register_rest_route('radicle/v1', $route, [
            'methods' => $method,
            'callback' => function (WP_REST_Request $request) use ($callback) {
                $controller = app($callback[0]);
                $method = $callback[1];

                $params = array_merge(
                    $request->get_url_params(),
                    $request->get_query_params(),
                    $request->get_json_params() ?: [],
                    $request->get_body_params()
                );

                return $controller->$method(...$this->extractMethodParams($callback, $params, $request));
            },
            'permission_callback' => function () use ($route) {
                // Protection Admin pour l'annulation
                if (strpos($route, 'cancellation') !== false) {
                    return current_user_can('edit_posts');
                }

                return '__return_true';
            },
        ]);
    }

    protected function extractMethodParams(array $callback, array $params, WP_REST_Request $requestOriginal): array
    {
        $reflection = new ReflectionMethod($callback[0], $callback[1]);
        $methodParams = [];

        foreach ($reflection->getParameters() as $param) {
            $name = $param->getName();
            $type = $param->getType();

            if ($name === 'request' || ($type && $type->getName() === WP_REST_Request::class)) {
                $methodParams[] = $requestOriginal;

                continue;
            }

            if (isset($params[$name])) {
                $methodParams[] = $params[$name];
            } elseif ($param->isDefaultValueAvailable()) {
                $methodParams[] = $param->getDefaultValue();
            } else {
                $methodParams[] = null;
            }
        }

        return $methodParams;
    }
}
