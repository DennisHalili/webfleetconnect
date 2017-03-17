<?php
namespace WebFleetConnect;

use Carbon\Carbon;
use GuzzleHttp;
use GuzzleHttp\Handler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use WebFleetConnectException;

class WebFleetConnectAPI
{
    protected static $instance;

    protected $client;

    public function __construct()
    {
        $this->setClient([
            'account'       => config('tomtom.account'),
            'username'      => config('tomtom.username'),
            'password'      => config('tomtom.password'),
            'apikey'        => config('tomtom.apikey'),
            'lang'          => 'en',
            'outputformat'  => 'json',
            'useISO8601'    => true
        ]);
    }

    public static function getInstance()
    {
        return static::$instance ?: new static;
    }

    protected function setClient(array $queries)
    {
        $stack = HandlerStack::create(new Handler\CurlHandler());

        foreach ($queries as $key => $value) {
            $stack->push($this->queryValueMiddleware($key, $value));
        }

        $this->client = new GuzzleHttp\Client([
            'base_uri'  => config('tomtom.base_url'),
            'handler'   => $stack
        ]);

        return $this;
    }

    protected function queryValueMiddleware($key, $value)
    {
        return Middleware::mapRequest(function (RequestInterface $request) use ($key, $value) {
            return $request->withUri(Uri::withQueryValue($request->getUri(), $key, $value));
        });
    }

    public function request($action, array $data = [], $cleanData = false, $debug = false)
    {
        if ($cleanData) $this->cleanData($data);

        $query = array_merge(['action' => $action], $data);

        $response = $this->client->get('', ['query' => $query]);

        if ($debug) $this->checkForErrors($response);

        return $this->formatResponse($response);
    }

    protected function cleanData(array &$data)
    {
        foreach ($data as $key => $value) {
            if (empty($value)) unset($data[$key]);
        }

        return $data;
    }

    protected function checkForErrors(ResponseInterface $response)
    {
        $headers = $response->getHeaders();

        if (array_key_exists('X-Webfleet-Errorcode', $headers)) {
            throw new WebFleetConnectException(
                $headers['X-Webfleet-Errormessage'][0],
                floatval($headers['X-Webfleet-Errorcode'][0])
            );
        }

        return $response;
    }

    /**
     * Format the response. Obviously.
     * 
     * @param Psr\Http\Message\ResponseInterface $response
     * @return array
     */
    protected function formatResponse(ResponseInterface $response)
    {
        return json_decode($response->getBody()->getContents());
    }

    /**
     * Every method calls will try to request to our endpoint.
     */
    public function __call($method, $args)
    {
        return $this->request($method, ...$args);
    }
}