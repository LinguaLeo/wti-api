<?php

namespace LinguaLeo\wti;

class WtiRequestBuilder
{

    const API_URL = "https://webtranslateit.com/api";

    private $apiKey;
    private $endpoint;
    private $method;
    private $params = [];
    private $resource;
    private $jsonEncodeParams = true;

    public function __construct($apiKey, $resource)
    {
        $this->apiKey = $apiKey;
        $this->resource = $resource;
    }

    public function setEndpoint($endpoint)
    {
        $this->endpoint = $endpoint;
        return $this;
    }

    public function setMethod($method)
    {
        $this->method = $method;
        return $this;
    }

    public function setParams(array $params)
    {
        $this->params = $params;
        return $this;
    }

    public function setJsonEncodeParams($flag)
    {
        $this->jsonEncodeParams = $flag;
        return $this;
    }

    public function build()
    {
        if ($this->method !== RequestMethod::GET) {
            $params = $this->jsonEncodeParams ? json_encode($this->params) : $this->params;
            curl_setopt($this->resource, CURLOPT_POST, true);
            curl_setopt($this->resource, CURLOPT_POSTFIELDS, $params);
        }
        curl_setopt($this->resource, CURLOPT_URL, $this->buildRequestUrl());
        curl_setopt($this->resource, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->resource, CURLOPT_CUSTOMREQUEST, $this->method);
        if ($this->jsonEncodeParams) {
            curl_setopt($this->resource, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        } else if ($this->method !== RequestMethod::GET) {
            if (isset($params['file'])) {
                curl_setopt($this->resource, CURLOPT_HTTPHEADER, array('Content-Type: multipart/form-data'));
            } else {
                curl_setopt($this->resource, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
            }
        }
        return new WtiApiRequest($this->resource);
    }

    private function buildRequestUrl()
    {
        $requestUrl = self::API_URL . "/projects/" . $this->apiKey;
        if ($this->endpoint !== null) {
            $requestUrl .= "/" . $this->endpoint;
        }
        if ($this->method === RequestMethod::GET) {
            $requestUrl .= ".json";
            if ($this->params) {
                $requestUrl .= "?" . $this->buildUrlParams();
            }
        }
        return $requestUrl;
    }

    private function buildUrlParams()
    {
        $params = array_filter($this->params, function($e) { return !is_null($e); });
        return $params ? http_build_query($params) : [];
    }

} 