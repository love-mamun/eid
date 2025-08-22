<?php

class Router
{
    private $routes = [];

    /**
     * Add a route to the router.
     *
     * @param string $url The URL pattern.
     * @param string $handler The handler (e.g., 'Controller::method').
     * @param string $method The HTTP method (GET, POST, etc.).
     */
    public function add($url, $handler, $method = 'GET')
    {
        $this->routes[] = [
            'pattern' => '#^' . $url . '$#',
            'handler' => $handler,
            'method' => strtoupper($method)
        ];
    }

    /**
     * Dispatch the request to the appropriate handler.
     *
     * @param string $url The requested URL.
     */
    public function dispatch($url)
    {
        foreach ($this->routes as $route) {
            if (preg_match($route['pattern'], $url, $matches) && $_SERVER['REQUEST_METHOD'] === $route['method']) {
                // Remove the full match from the beginning of the array
                array_shift($matches);

                // Call the handler
                $this->callHandler($route['handler'], $matches);
                return;
            }
        }

        // Handle 404 Not Found
        $this->notFound();
    }

    /**
     * Call the handler for the matched route.
     *
     * @param string $handler The handler string (e.g., 'Controller::method').
     * @param array $params The parameters from the URL.
     */
    private function callHandler($handler, $params = [])
    {
        list($controller, $method) = explode('::', $handler);

        if (class_exists($controller) && method_exists($controller, $method)) {
            $controllerInstance = new $controller();
            call_user_func_array([$controllerInstance, $method], $params);
        } else {
            // Handle 500 Internal Server Error
            $this->serverError("Controller or method not found: {$handler}");
        }
    }

    /**
     * Handle 404 Not Found errors.
     */
    private function notFound()
    {
        http_response_code(404);
        echo json_encode(['error' => 'Not Found']);
    }

    /**
     * Handle 500 Internal Server Error.
     * @param string $message The error message.
     */
    private function serverError($message)
    {
        http_response_code(500);
        echo json_encode(['error' => 'Internal Server Error', 'message' => $message]);
    }
}
