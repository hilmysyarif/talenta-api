<?php

namespace Ianriizky\TalentaApi\Services\Api;

use Illuminate\Http\Client\Response;
use Carbon\Carbon;
use Psr\Http\Message\RequestInterface;

/**
 * @property \Illuminate\Http\Client\PendingRequest $request
 */
trait Employee
{
    /**
     * Create "/employee" GET request to the Talenta api.
     *
     * @param  array|string|null  $query
     */
    protected function getAllEmployee($query = null)
    {
        // Set http client
        $client = new \GuzzleHttp\Client([
            'base_uri' => config('talenta.base_url')
        ]);

        // Set method and path for the request
        $method     = 'GET';
        $path       = '/v2/talenta/v2/employee?' . $query;
        $queryParam = '';
        $headers    = [
        ];

        // Initiate request
        try {
            $response = $client->request($method, $path, [
                'headers'   => array_merge($this->generate_headers($method, $path), $headers),
            ]);


            if($response->getStatusCode() != 200)
            {
                return \Response::json($response->getBody());
            }
            $decodedResponse = json_decode($response->getBody());
            return $decodedResponse;
        } catch (ClientException $e) {
            echo Psr7\Message::toString($e->getRequest());
            echo Psr7\Message::toString($e->getResponse());
            echo PHP_EOL;
        }
    }

    /**
     * Create "/employee/{employee_id}" GET request to the Talenta api.
     *
     * @param  array|string|null  $query
     */
    protected function getEmployee($employee_id)
    {
        // Set http client
        $client = new \GuzzleHttp\Client([
            'base_uri' => config('talenta.base_url')
        ]);

        // Set method and path for the request
        $method     = 'GET';
        $path       = '/v2/talenta/v2/employee/' . $employee_id;
        $queryParam = '';
        $headers    = [
        ];

        // Initiate request
        try {
            $response = $client->request($method, $path, [
                'headers'   => array_merge($this->generate_headers($method, $path), $headers),
            ]);


            if($response->getStatusCode() != 200)
            {
                return \Response::json($response->getBody());
            }
            $decodedResponse = json_decode($response->getBody());
            return $decodedResponse;
        } catch (ClientException $e) {
            echo Psr7\Message::toString($e->getRequest());
            echo Psr7\Message::toString($e->getResponse());
            echo PHP_EOL;
        }
    }

    /**
     * Generate authentication headers based on method and path
     */
    function generate_headers($method, $pathWithQueryParam) {
        $datetime       = Carbon::now()->toRfc7231String();
        $request_line   = "{$method} {$pathWithQueryParam} HTTP/1.1";
        $payload        = implode("\n", ["date: {$datetime}", $request_line]);
        $digest         = hash_hmac('sha256', $payload, config('talenta.hmac_secret'), true);
        $signature      = base64_encode($digest);
        $hmac_secret    = config('talenta.hmac_secret');
        $hmac_username    = config('talenta.hmac_username');

        return [
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
            'Date'          => $datetime,
            'Authorization' => "hmac username=\"{$hmac_username}\", algorithm=\"hmac-sha256\", headers=\"date request-line\", signature=\"{$signature}\""
        ];
    }
}
