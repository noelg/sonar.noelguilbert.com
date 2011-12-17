<?php

namespace Sonar\AnalyzerBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand as Command;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FetchProjectCommand extends Command
{

    protected function configure()
    {
        parent::configure();

        $this
            ->setName('sonar:fetch-projects')
        ;
    }

    /**
     * Executes the current command.
     *
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     *
     * @return integer 0 if everything went fine, or an error code
     *
     * @throws \LogicException When this abstract class is not implemented
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $knpBundleApi = $this->getContainer()->get('sonar_analyzer.knpbundle.api');
        $bundles = $knpBundleApi->getBundles();

        foreach ($bundles as $bundle) {
            $msg = array(
                'name' => $bundle['name'],
                'git' => $bundle['git'],
                'username' => $bundle['username'],
                'version' => $bundle['lastCommitAt']
            );

            $output->writeln(sprintf(
                'Adding bundle <comment>%s/%s</comment> to the queue',
                $bundle['username'], $bundle['name']
            ));

            $this->getContainer()
                ->get('old_sound_rabbit_mq.create_project_producer')
                ->publish(serialize($msg));
        }
    }
}