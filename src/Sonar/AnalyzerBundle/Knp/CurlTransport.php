<?php

namespace Sonar\AnalyzerBundle\Knp;

class CurlTransport
{
    private $logger;
    private $lastElapsedTime;
    private $lastStatusCode;

    public function __construct($logger = null) {
        $this->logger = $logger;
    }

    public function call($url) {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        // call url
        $data = curl_exec($ch);

        $this->log(curl_getinfo($ch), $data);

        return $data;
    }

    private function log($curlinfo, $data) {
        if (null !== $this->logger) {
            $message = sprintf('Called %s, HTTP Status: %s, Total time: %s, data: %s',
                $curlinfo['url'],
                $curlinfo['http_code'],
                $curlinfo['total_time'],
                $data
            );

            $this->logger->debug($message);
        }

        $this->lastElapsedTime = $curlinfo['total_time'];
        $this->lastStatusCode  = $curlinfo['http_code'];
    }

    public function getLastElapsedTime()
    {
        return $this->lastElapsedTime;
    }

    public function getLastStatusCode()
    {
        return $this->lastStatusCode;
    }
}