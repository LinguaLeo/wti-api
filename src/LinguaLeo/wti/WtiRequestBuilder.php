<?php

namespace LinguaLeo\wti;

class WtiRequestBuilder
{

    const API_URL = "https://webtranslateit.com/api";

    private $apiKey;
    private $endpoint;
    private $method;
    private $params = [];
    private $jsonEncodeParams = true;

    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
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
        $ch = curl_init();
        $requestUrl = self::API_URL . "/projects/" . $this->apiKey;
        if ($this->endpoint !== null) {
            $requestUrl .= "/" . $this->endpoint;
        }
        if ($this->method === RequestMethod::GET) {
            $requestUrl .= ".json";
            if ($this->params) {
                $requestUrl .= "?" . $this->buildUrlParams();
            }
        } else {
            $params = $this->jsonEncodeParams ? json_encode($this->params) : $this->params;
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        }
        curl_setopt($ch, CURLOPT_URL, $requestUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $this->method);
        if ($this->jsonEncodeParams) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        }
        return new WtiRequest($ch);
    }

    private function buildUrlParams()
    {
        $params = array_filter($this->params, function($e) { return !is_null($e); });
        return $params ? http_build_query($params) : [];
    }

} 