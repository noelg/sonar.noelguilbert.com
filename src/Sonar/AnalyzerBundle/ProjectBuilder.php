<?php

namespace Sonar\AnalyzerBundle;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\PhpProcess;
use Symfony\Component\Yaml\Yaml;

class ProjectBuilder
{
    private $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function build($path)
    {
        $this
            ->checkout($path)
            ->runBeforeScript($path.'/'.$this->config['name'])
            ->addPom($path.'/'.$this->config['name'])
        ;
    }

    public function checkout($path)
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
            '%%SKIP_PHPUNIT%%' => $this->canRunTests($path) ? 'false' : 'true'
        ));

        file_put_contents($path.'/pom.xml', $contents);

        return $this;
    }

    private function runBeforeScript($path)
    {
        if (file_exists($path.'/.travis.yml')) {
            $data = Yaml::parse($path.'/.travis.yml');

            if (isset($data['before_script'])) {
                $process = new Process('cd '.$path.' && '.$data['before_script']);
                $process->setTimeout(300);
                $process->run(function ($type, $data) { echo $data; });

                if (!$process->isSuccessful()) {
                    throw new \RuntimeException('Install: Before script failed : '.
                        $process->getErrorOutput());
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
}