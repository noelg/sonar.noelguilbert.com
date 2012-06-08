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

        if (mt_rand(0, 10) == 0) {
            $process = new Process('rm -rf /srv/data01/.pdepend/*');
            $process->run();
        }

        // hardcoded maven parameters, because defaults are not used when the cron is running
        $options = array(
            '-Dsonar.jdbc.driverClassName=com.mysql.jdbc.Driver',
            '-D"sonar.jdbc.url=jdbc:mysql://localhost:3306/sonar?useUnicode=true&characterEncoding=utf8"',
            '-Dsonar.jdbc.username=sonar -Dsonar.jdbc.password=sonar'
        );

        $process = new Process(sprintf(
            'cd %s && mvn2 sonar:sonar %s',
            escapeshellarg($msg['path']),
            implode(' ', $options)
        ));
        $process->setTimeout(600);

        $process->run(function ($type, $data) { echo $data; });

        $process = new Process(sprintf('rm -rf %s', escapeshellarg($msg['path'])));

        $process->run(function ($type, $data) { echo $data; });
    }
}