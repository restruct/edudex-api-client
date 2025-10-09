<?php

/**
 * HTTP Client interface for making API requests
 *
 * Abstraction layer to allow different HTTP client implementations
 */
interface EduDexClientInterface
{
    /**
     * Make a GET request
     *
     * @param string $path Request path (relative to base URL)
     * @param array $query Query parameters
     * @param array $headers Additional headers
     * @return array Response data
     * @throws EduDexException
     */
    public function get($path, $query = array(), $headers = array());

    /**
     * Make a POST request
     *
     * @param string $path Request path (relative to base URL)
     * @param array $data Request body data
     * @param array $headers Additional headers
     * @return array Response data
     * @throws EduDexException
     */
    public function post($path, $data = array(), $headers = array());

    /**
     * Make a PUT request
     *
     * @param string $path Request path (relative to base URL)
     * @param array $data Request body data
     * @param array $headers Additional headers
     * @return array Response data
     * @throws EduDexException
     */
    public function put($path, $data = array(), $headers = array());

    /**
     * Make a PATCH request
     *
     * @param string $path Request path (relative to base URL)
     * @param array $data Request body data
     * @param array $headers Additional headers
     * @return array Response data
     * @throws EduDexException
     */
    public function patch($path, $data = array(), $headers = array());

    /**
     * Make a DELETE request
     *
     * @param string $path Request path (relative to base URL)
     * @param array $headers Additional headers
     * @return array Response data (may be empty)
     * @throws EduDexException
     */
    public function delete($path, $headers = array());

    /**
     * Make a raw HTTP request
     *
     * @param string $method HTTP method (GET, POST, PUT, PATCH, DELETE)
     * @param string $path Request path (relative to base URL)
     * @param array $options Request options (query, json, headers, etc.)
     * @return array Response data
     * @throws EduDexException
     */
    public function request($method, $path, $options = array());
}
