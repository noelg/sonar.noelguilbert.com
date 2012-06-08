<?php

namespace Sonar\AnalyzerBundle;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\PhpProcess;
use Symfony\Component\Yaml\Yaml;

class ProjectBuilder
{
    private $config;
    private $beforeScriptSuccessful;

    public function __construct($config)
    {
        $this->config = $config;
        $this->beforeScriptSuccessful = true;
    }

    public function build($path)
    {
        $this
            ->checkout($path);

        if (!$this->isUpdated($path.'/'.$this->config['name'])) {
            return false;
        }

        $this
            ->runBeforeScript($path.'/'.$this->config['name'])
            ->addPom($path.'/'.$this->config['name'])
        ;

        return true;
    }

    public function remove($path)
    {
        $process = new Process('rm -rf '.$path.'/'.$this->config['name']);
        echo $process->getCommandLine()."\n";
        $process->run();
    }

    private function checkout($path)
    {
        echo "INSTALLING ".$this->config['name']."\n";
        $process = new Process(sprintf('git clone %s %s',
            escapeshellarg($this->config['git']),
            escapeshellarg($path.'/'.$this->config['name'])
        ));

        $process->run(function ($type, $data) { echo $data; });

        if (!$process->isSuccessful()) {
            throw new \RuntimeException('Git clone failed : '.$process->getErrorOutput());
        }

        return $this;
    }

    private function addPom($path)
    {
        $contents = file_get_contents(__DIR__.'/Resources/sonar/pom.xml.tpl');

        $contents = strtr($contents, array(
            '%%GROUP_ID%%' => $this->config['username'],
            '%%NAME%%'     => $this->config['name'],
            '%%ARTIFACT_ID%%' => $this->config['name'],
            '%%SKIP_PHPUNIT%%' => $this->canRunTests($path) ? 'false' : 'true',
            '%%VERSION%%' => $this->getVersion($path),
        ));

        file_put_contents($path.'/pom.xml', $contents);

        return $this;
    }

    private function runBeforeScript($path)
    {
        if (file_exists($path.'/.travis.yml')) {
            $data = Yaml::parse($path.'/.travis.yml');

            if (isset($data['before_script'])) {
                if (!is_array($data['before_script'])) {
                    $data['before_script'] = array($data['before_script']);
                }

                foreach ($data['before_script'] as $script) {
                    $process = new Process('cd '.$path.' && '.$script);
                    $process->setTimeout(300);
                    $process->run(function ($type, $data) { echo $data; });

                    if (!$process->isSuccessful()) {
                        $this->beforeScriptSuccessful = false;
                    }
                }
            }
        }

        return $this;
    }

    private function canRunTests($path)
    {
        if (!is_dir($path.'/Tests')) {
            return false;
        }

        if (!file_exists($path.'/phpunit.xml.dist')) {
            return false;
        }

        if (!$this->beforeScriptSuccessful) {
            return false;
        }

        if (!$this->addFallbackAutoloader($path.'/phpunit.xml.dist')) {
            return false;
        }

        return true;
    }

    private function addFallbackAutoloader($phpunitXml)
    {
        $xml = simplexml_load_file($phpunitXml);

        $bootstrap = dirname($phpunitXml) . '/' . $xml['bootstrap'];

        // The bootstrap file does not exists, we can't run the tests
        if (!file_exists($bootstrap)) {
            return false;
        }

        $content = file_get_contents($bootstrap);
        $newPath = sprintf('require_once \'%s\';', realpath(__DIR__.'/../../../app/autoload.php'));

        // The bundle is trying to run tests into a symfony project, change it!
        if (preg_match('#require(../){2,}app/autoload.php["\'];$#i', $content, $match)) {

            $content = str_replace($match[0], $newPath, $content);
        }
        // add the current autoloader as a fallback in case the current autoload is not complete
        else {
            $content .= "\n".$newPath;
        }

        file_put_contents($bootstrap, $content);

        // test if the bootstrap script can be runned
        $process = new PhpProcess($bootstrap);
        $process->run(function ($type, $data) { echo $data; });

        if (!$process->isSuccessful()) {
            return false;
        }

        $xml->filter->whitelist->exclude->directory[] = dirname($phpunitXml).'/vendor';
        $xml->filter->whitelist->exclude->directory[] = dirname($phpunitXml).'/vendors';

        $xml->asXml($phpunitXml);

        return true;
    }

    private function isUpdated($path)
    {
        $currentVersion = $this->getCurrentVersion();
        $version = $this->getVersion($path);

        return $version != $currentVersion;
    }

    private function getCurrentVersion()
    {
        $url = sprintf('http://localhost:9000/api/resources?resource=%s:%s&depth=-1&qualifiers=TRK&format=json',
            $this->config['username'],
            $this->config['name']
        );

        $content = @file_get_contents($url);

        if (!$content) {
            return false;
        }

        $json = json_decode($content, true);

        return isset($json[0]['version']) ? $json[0]['version'] : null;
    }

    private function getVersion($path)
    {
        $process = new Process('cd '.escapeshellarg($path). ' && git log -n1 --format=oneline | awk \'{print $1}\'');
        $process->run();

        return substr($process->getOutput(), 0, 6);
    }
}