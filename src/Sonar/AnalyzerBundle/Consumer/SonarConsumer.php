<?php
namespace Sonar\AnalyzerBundle\Consumer;

use Symfony\Component\DependencyInjection\ContainerAware;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use Symfony\Component\Process\Process;

use Sonar\AnalyzerBundle\ProjectBuilder;

class SonarConsumer extends ContainerAware implements ConsumerInterface
{
    public function execute($msg)
    {
        $msg = unserialize($msg);

        $options = array(
            '-Dsonar.jdbc.driverClassName=com.mysql.jdbc.Driver',
            '-D"sonar.jdbc.url=jdbc:mysql://localhost:3306/sonar?useUnicode=true&characterEncoding=utf8"',
            '-Dsonar.jdbc.username=root -Dsonar.jdbc.password=pass'
        );

        $process = new Process(sprintf(
            'cd %s && mvn2 sonar:sonar %s',
            escapeshellarg($msg['path']),
            implode(' ', $options)
        ));
        $process->setTimeout(600);

        echo $process->getCommandLine()."\n";

        $process->run(function ($type, $data) { echo $data; });

        $process = new Process(sprintf('rm -rf %s', escapeshellarg($msg['path'])));
        echo $process->getCommandLine()."\n";
        $process->run(function ($type, $data) { echo $data; });
    }
}