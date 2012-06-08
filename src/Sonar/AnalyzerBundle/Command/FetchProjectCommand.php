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

            // FIXME: lazy way, but doesn't need more complex stuff here
            $content = @file_get_contents(sprintf('https://api.github.com/repos/%s/%s/branches', $bundle['username'], $bundle['name']));

            if (!$content) {
                $output->writeln(sprintf('Ignore bundle <comment>%s</comment>: no master head, or not initialized', $bundle['name']));
                continue;
            }

            $content = json_decode($content, true);
            $sha1 = false;
            foreach ($content as $branch)
            {
                if (!$sha1 || $branch['name'] == 'master') {
                    $sha1 = substr($branch['commit']['sha'], 0, 6);
                }
            }

            if (!$sha1) {
                $output->writeln(sprintf('Ignore bundle <comment>%s</comment>: no master head', $bundle['name']));
                continue;
            }

            $headVersion = $this->getCurrentVersion($bundle);

            if ($sha1 == $headVersion) {
                $output->writeln(sprintf('Ignore bundle <comment>%s/%s</comment>: same version (%s)', $bundle['username'], $bundle['name'], $headVersion));
                continue;
            }

            $output->writeln(sprintf(
                'Adding bundle <comment>%s/%s</comment> to the queue',
                $bundle['username'], $bundle['name']
            ));

            $this->getContainer()
                ->get('old_sound_rabbit_mq.create_project_producer')
                ->publish(serialize($msg));
        }
    }

    private function getCurrentVersion($config)
    {
        $url = sprintf('http://localhost:9000/api/resources?resource=%s:%s&depth=-1&qualifiers=TRK&format=json',
            $config['username'],
            $config['name']
        );

        $content = @file_get_contents($url);

        if (!$content) {
            return false;
        }

        $json = json_decode($content, true);

        return isset($json[0]['version']) ? $json[0]['version'] : null;
    }
}