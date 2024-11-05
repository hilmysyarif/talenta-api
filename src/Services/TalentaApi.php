<?php

namespace Ianriizky\TalentaApi\Services;

use BadMethodCallException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Traits\Macroable;
use RuntimeException;
use Illuminate\Http\JsonResponse;
use stdClass;

/**
 * @see https://documenter.getpostman.com/view/12246328/TWDZHvj1
 */
class TalentaApi
{
    use Macroable {
        __call as macroCall;
    }
    use Api\Employee;
    use Concerns\HandleAuthentication;

    /**
     * List of escaped method when __call() is called.
     *
     * @var array<int, string>
     */
    public static $escapedMethods = [
        'createRequestInstance',
        'sendRequestToTalenta',

        // Concerns\HandleAuthentication
        'isRequestAuthenticated',
        'authenticateRequest',
        'getCookiesFromLogin',
        'getCredentials',
        'reattemptLoginWhenUnauthorized',
    ];

    /**
     * Instance of \Illuminate\Http\Client\PendingRequest to make the request.
     *
     * @var \Illuminate\Http\Client\PendingRequest
     */
    protected PendingRequest $request;

    /**
     * Create a new instance class.
     *
     * @param  array  $config
     * @param  string|bool|null  $sslVerify
     * @return void
     */
    public function __construct(protected array $config, $sslVerify = null)
    {
        $this->createRequestInstance(
            $sslVerify, Arr::except($config['guzzle_options'], 'verify')
        );

        $this->reattemptLoginWhenUnauthorized(
            $config['request_retry_times'],
            $config['request_retry_sleep']
        );
    }

    /**
     * Create Laravel HTTP client request instance.
     *
     * @param  string|bool|null  $sslVerify
     * @param  array  $options
     * @return void
     */
    protected function createRequestInstance($sslVerify = null, array $options)
    {
        $this->request = Http::baseUrl($this->config['base_url'])->withOptions($options);

        if (! is_null($sslVerify)) {
            $this->request->withOptions(['verify' => $sslVerify]);
        }
    }

    /**
     * Send a request to the Talenta api based on the given method and parameters.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return stdClass|\GuzzleHttp\Psr7\Response
     *
     * @throws \RuntimeException
     */
    protected function sendRequestToTalenta(string $method, array $parameters = []): stdClass|Response|JsonResponse
    {
        $response = $this->{$method}(...$parameters);

        // if (! $response instanceof Response) {
        //     throw new RuntimeException(sprintf(
        //         'The return value from method %s::%s must be an instance of %s class.',
        //         static::class, $method, Response::class
        //     ));
        // }

        // Use throw() method to make sure that it's always throw an exception
        // when the given response is error.
        return $response;
    }

    /**
     * Dynamically handle calls to the class.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     *
     * @throws \BadMethodCallException
     */
    public function __call($method, $parameters)
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }

        if (in_array($method, static::$escapedMethods, true)) {
            throw new BadMethodCallException(sprintf(
                'Method %s::%s is in the escaped method list.', static::class, $method
            ));
        }

        if (! $this->isRequestAuthenticated()) {
            $this->authenticateRequest();
        }

        return $this->sendRequestToTalenta($method, $parameters);
    }
}
