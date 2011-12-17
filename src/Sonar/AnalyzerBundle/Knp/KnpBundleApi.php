<?php

namespace Sonar\AnalyzerBundle\Knp;

class KnpBundleApi
{
    private $transport;

    public function __construct($transport)
    {
        $this->transport = $transport;
    }

    public function getBundles()
    {
        $data = $this->transport->call('http://knpbundles.com/updated?format=json');

        if ($data) {
            $data = json_decode($data, true);

            return $this->addGitRepo($data);
        }

        return array();
    }

    private function addGitRepo($data)
    {
        return array_map(function($value) {
            $value['git'] = sprintf('https://github.com/%s/%s.git', $value['username'], $value['name']);
            return $value;
        }, $data);
    }
}