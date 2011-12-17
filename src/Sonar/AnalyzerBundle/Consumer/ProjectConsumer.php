<?php
namespace Sonar\AnalyzerBundle\Consumer;

use Symfony\Component\DependencyInjection\ContainerAware;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use Symfony\Component\Process\PhpProcess;

use Sonar\AnalyzerBundle\ProjectBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
class ProjectConsumer extends ContainerAware implements ConsumerInterface
{
    public function execute($msg)
    {
        $msg = unserialize($msg);

        $builder = new ProjectBuilder($msg);

        $builder->build('/tmp');

        $msg = array('path' => '/tmp/'.$msg['name']);

        $this->container
                ->get('old_sound_rabbit_mq.analyze_project_producer')
                ->publish(serialize($msg));
    }

}