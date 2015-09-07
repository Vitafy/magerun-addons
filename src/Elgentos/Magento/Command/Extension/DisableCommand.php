<?php

namespace Elgentos\Magento\Command\Extension;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class DisableCommand extends AbstractMagentoCommand
{

    protected function configure()
    {
        $this
            ->setName('extension:disable')
            ->setDescription('Actually disable an extension')
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
            $moduleDir = $this->getApplication()->getMagentoRootFolder() . DS . 'app' . DS . 'etc' . DS . 'modules';
            $moduleFiles = scandir($moduleDir);
            $moduleFilenames = $moduleNames = array();
            $disabledModuleFilenames = $disabledModuleNames = array();
            foreach ($moduleFiles as $moduleFile) {
                if (strtolower(substr($moduleFile, -4)) == '.xml') {
                    $xml = simplexml_load_file($moduleDir . DS . $moduleFile);
                    $keys = array_keys((array)$xml->modules);
                    $moduleNames[] = $keys[0];
                    $moduleFilenames[] = $moduleFile;
                }
                if (strtolower(substr($moduleFile, -9)) == '.disabled') {
                    $xml = simplexml_load_file($moduleDir . DS . $moduleFile);
                    $keys = array_keys((array)$xml->modules);
                    $disabledModuleNames[] = $keys[0];
                    $disabledModuleFilenames[] = $moduleFile;
                }
            }

            $disableModule = false;
            $name = $input->getArgument('name');
            if (!$name) {
                $moduleIndex = $dialog->select(
                    $output,
                    'Select extension to disable',
                    $moduleNames,
                    0
                );
                $disableModule = true;
            } else {
                if (array_search($name, $moduleNames) !== false) {
                    $moduleIndex = array_search($name, $moduleNames);
                    $disableModule = true;
                } else {
                    // is the module already disabled?
                    $moduleIndex = array_search($name, $disabledModuleNames);
                    if ($moduleIndex !== false) {
                        $output->writeln('<info>Module: ' . $name . ' has already been disabled</info>');
                    } else {
                        $output->writeln('<error>Module: ' . $name . ' Not found please check input </error>');
                    }
                }
            }

            if ($disableModule) {
                exec(
                    'mv ' . $moduleDir . DS . $moduleFilenames[$moduleIndex] . ' ' . $moduleDir . DS
                    . $moduleFilenames[$moduleIndex] . '.disabled'
                );

                $output->writeln('<info>Disabled ' . $moduleNames[$moduleIndex] . '</info>');
            }
        }
    }
}