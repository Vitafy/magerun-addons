<?php

namespace Elgentos\Magento\Command\Extension;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

class EnableCommand extends AbstractMagentoCommand
{

    protected function configure()
    {
        $this
            ->setName('extension:enable')
            ->setDescription('Enable an extension that was disabled through extension:disable')
            ->addArgument('name', InputArgument::OPTIONAL, 'The full module name e.g. Namespace_Module');
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface   $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output);
        if ($this->initMagento()) {
            $dialog = $this->getHelper('dialog');
            $moduleDir = $this->getApplication()->getMagentoRootFolder().DS.'app'.DS.'etc'.DS.'modules';
            $moduleFiles = scandir($moduleDir);
            $moduleFilenames = $moduleNames = array();
            $enabledModuleFilenames = $enabledModuleNames = array();
            foreach ($moduleFiles as $moduleFile) {
                if (strtolower(substr($moduleFile, -9)) == '.disabled') {
                    $xml = simplexml_load_file($moduleDir.DS.$moduleFile);
                    $keys = array_keys((array) $xml->modules);
                    $moduleNames[] = $keys[0];
                    $moduleFilenames[] = $moduleFile;
                }

                if (strtolower(substr($moduleFile, -4)) == '.xml') {
                    $xml = simplexml_load_file($moduleDir.DS.$moduleFile);
                    $keys = array_keys((array) $xml->modules);
                    $enabledModuleNames[] = $keys[0];
                    $enabledModuleFilenames[] = $moduleFile;
                }
            }

            $enableModule = false;
            $name = $input->getArgument('name');
            if (!$name) {
                $moduleIndex = $dialog->select(
                    $output,
                    'Select extension to enable',
                    $moduleNames,
                    0
                );
            } else {
                if (array_search($name, $moduleNames)) {
                    $moduleIndex = array_search($name, $moduleNames);
                    $enableModule = true;
                } else {
                    // is the module already disabled?
                    $moduleIndex = array_search($name, $enabledModuleNames);
                    if ($moduleIndex !== false) {
                        $output->writeln('<info>Module: '.$name.' is enabled</info>');
                    } else {
                        $output->writeln('<error>Module: '.$name.' Not found please check input </error>');
                    }
                }
            }

            if ($enableModule) {
                exec('mv '.$moduleDir.DS.$moduleFilenames[$moduleIndex].' '.$moduleDir.DS.str_replace('.disabled', '', $moduleFilenames[$moduleIndex]));
                $output->writeln('<info>Enabled '.$moduleNames[$moduleIndex].'</info>');
            }
        }
    }
}