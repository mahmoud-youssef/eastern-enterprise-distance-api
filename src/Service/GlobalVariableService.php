<?php

namespace App\Service;

class GlobalVariableService
{
    private $hostBaseUrl;
    private $accessKey;

    public function getHostBaseUrl()
    {
        return $this->hostBaseUrl;
    }

    public function setHostBaseUrl($data)
    {
        $this->hostBaseUrl = $data;
    }

    public function getAccessKey()
    {
        return $this->accessKey;
    }

    public function setAccessKey($data)
    {
        $this->accessKey = $data;
    }
}
