<?php

namespace LinguaLeo\wti;

class WtiApiRequest
{

    private $resource;
    private $error;
    private $errno;
    private $result;
    private $isRequestRunned = false;

    public function __construct($resource)
    {
        $this->resource = $resource;
    }

    public function __destruct()
    {
        if ($this->resource !== null) {
            curl_close($this->resource);
        }
    }

    public function run()
    {
        $this->isRequestRunned = true;
        $this->result = curl_exec($this->resource);
        if ($this->result === false) {
            $this->error = curl_error($this->resource);
            $this->errno = curl_errno($this->resource);
        }
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
            throw new \Exception("Request must be performed before getting results.");
        }
        return $this->result;
    }

    public function getResult()
    {
        if (!$this->isRequestRunned) {
            throw new \Exception("Request must be performed before getting results.");
        }
        return $this->result ? json_decode($this->result) : null;
    }

} 