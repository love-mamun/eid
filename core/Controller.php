<?php

class Controller
{
    /**
     * Send a JSON response.
     *
     * @param mixed $data The data to encode as JSON.
     * @param int $statusCode The HTTP status code.
     */
    protected function jsonResponse($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        echo json_encode($data);
    }

    /**
     * Get the request body.
     *
     * @return mixed
     */
    protected function getRequestBody()
    {
        return json_decode(file_get_contents('php://input'), true);
    }
}
