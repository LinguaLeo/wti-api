<?php

namespace LinguaLeo\wti;

use LinguaLeo\wti\Exception\WtiApiException;

class WtiApiRequest
{

    private $resource;
    private $error;
    private $errno;
    private $result;
    private $headers;
    private $isRequestRunned = false;

    public function __construct($resource)
    {
        $this->resource = $resource;
    }

    public function run()
    {
        $this->isRequestRunned = true;
        $result = curl_exec($this->resource);

        $header_size = curl_getinfo($this->resource, CURLINFO_HEADER_SIZE);
        $this->headers = $this->prepareHeaders(substr($result, 0, $header_size));
        $this->result = substr($result, $header_size);

        if ($this->result === false) {
            $this->error = curl_error($this->resource);
            $this->errno = curl_errno($this->resource);
        }
    }

    private function prepareHeaders ($headersString) {
        $headersArray = explode("\n", $headersString);
        // Remove first header, HTTP code
        array_shift($headersArray);

        $headersAssoc = [];

        foreach ($headersArray as $header) {
            if ($header === '') {
                continue;
            }
            preg_match('~^([^:]*)\:(.*)$~', $header, $matches);
            if (count($matches) == 3) {
                $headersAssoc[$matches[1]] = trim($matches[2]);
            }
        }

        return $headersAssoc;
    }

    public function getError()
    {
        return $this->error;
    }

    public function getErrno()
    {
        return $this->errno;
    }

    public function getRawResult()
    {
        if (!$this->isRequestRunned) {
            throw new WtiApiException("Request must be performed before getting results.");
        }
        return $this->result;
    }

    public function getResult($assoc = false)
    {
        if (!$this->isRequestRunned) {
            throw new WtiApiException("Request must be performed before getting results.");
        }
        return $this->result ? json_decode($this->result, $assoc) : null;
    }

    public function getHeaders () {
        if (!$this->isRequestRunned) {
            throw new WtiApiException("Request must be performed before getting results.");
        }
        return $this->headers;
    }

} 