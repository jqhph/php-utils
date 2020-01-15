<?php

namespace Dcat\Utils\Elasticsearch\Connections;

class HttpConnection extends \Elasticsearch\Connections\Connection
{
    /**
     * Log a successful request
     *
     * @param array $request
     * @param array $response
     * @return void
     */
    public function logRequestSuccess(array $request, array $response): void
    {
        $this->log->debug('Request Body', array($request['body']));
        $this->log->info(
            'Request Success:',
            array(
                'method'    => $request['http_method'],
                'uri'       => $response['effective_url'],
                'port'      => $response['transfer_stats']['primary_port'] ?? null,
                'headers'   => $request['headers'],
                'HTTP code' => $response['status'],
                'duration'  => $response['transfer_stats']['total_time'],
            )
        );
        $this->log->debug('Response', array($response['body']));

        // Build the curl command for Trace.
        $curlCommand = $this->buildCurlCommand($request['http_method'], $response['effective_url'], $request['body']);
        $this->trace->info($curlCommand);
        $this->trace->debug(
            'Response:',
            array(
                'response'  => $response['body'],
                'method'    => $request['http_method'],
                'uri'       => $response['effective_url'],
                'HTTP code' => $response['status'],
                'duration'  => $response['transfer_stats']['total_time'],
            )
        );
    }

    /**
     * Log a failed request
     *
     * @param array $request
     * @param array $response
     * @param \Exception $exception
     *
     * @return void
     */
    public function logRequestFail(array $request, array $response, \Exception $exception): void
    {
        $this->log->debug('Request Body', array($request['body']));

        $this->log->warning(
            'Request Failure:',
            array(
                'method'    => $request['http_method'],
                'uri'       => $response['effective_url'],
                'port'      => $response['transfer_stats']['primary_port'] ?? null,
                'headers'   => $request['headers'],
                'HTTP code' => $response['status'],
                'duration'  => $response['transfer_stats']['total_time'],
                'error'     => $exception->getMessage(),
            )
        );
        $this->log->warning('Response', array($response['body']));

        // Build the curl command for Trace.
        $curlCommand = $this->buildCurlCommand($request['http_method'], $response['effective_url'], $request['body']);
        $this->trace->info($curlCommand);
        $this->trace->debug(
            'Response:',
            array(
                'response'  => $response,
                'method'    => $request['http_method'],
                'uri'       => $response['effective_url'],
                'HTTP code' => $response['status'],
                'duration'  => $response['transfer_stats']['total_time'],
            )
        );
    }

    /**
     * Construct a string cURL command
     */
    protected function buildCurlCommand(string $method, string $uri, ?string $body): string
    {
        if (strpos($uri, '?') === false) {
            $uri .= '?pretty=true';
        } else {
            str_replace('?', '?pretty=true', $uri);
        }

        $curlCommand = 'curl -X' . strtoupper($method);
        $curlCommand .= " '" . $uri . "'";

        if (isset($body) === true && $body !== '') {
            $curlCommand .= " -d '" . $body . "'";
        }

        return $curlCommand;
    }
}
