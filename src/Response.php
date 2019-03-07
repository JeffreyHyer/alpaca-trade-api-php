<?php

namespace Alpaca;

use GuzzleHttp\Psr7\Response as ResponseInterface;

class Response {

    protected $code = 0;

    protected $reason = "";

    protected $response = [];

    public function __construct(ResponseInterface $response)
    {
        $this->code = $response->getStatusCode();
        $this->reason = $response->getReasonPhrase();
        $this->response = $this->_parseResponse($response);
    }

    public function getCode()
    {
        return $this->code;
    }

    public function getReason()
    {
        return $this->reason;
    }

    public function getResponse()
    {
        return $this->response;
    }

    private function _parseResponse(ResponseInterface $response)
    {
        if ($response->hasHeader('Content-Type')) {
            $contentType = $response->getHeader('Content-Type');
            
            if (is_array($contentType)) {
                $contentType = $contentType[0];
            }

            // JSON
            if (strpos($contentType, "application/json") !== false) {
                return json_decode($response->getBody()->getContents());
            }
        }

        return $response->getBody()->getContents();
    }
}