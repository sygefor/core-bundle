<?php
/**
 * Created by PhpStorm.
 * User: erwan
 * Date: 8/10/17
 * Time: 10:02 AM.
 */

namespace Sygefor\Bundle\CoreBundle\Command;

use Elastica\Document;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class UpdatePasswordsCommand.
 */
class UpdatePasswordsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('passwords:update')
            ->addArgument('file', InputArgument::REQUIRED, 'File password');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // initialize variables
        $i = 0;
        $documents = array();
        $passwordsByStep = 500;
        $filePath = $input->getArgument('file');
        $file = new \SplFileObject($filePath);
        $nbrPasswords = count(file($filePath));
        $esIndex = $this->getContainer()->get('fos_elastica.index.passwords');
        $progress = $this->displayInitialAvancement($output, $nbrPasswords, $passwordsByStep);

        // store password in elasticsearch
        while (!$file->eof()) {
            $password = $file->fgets();
            $documents[] = new Document('', array('password' => $password), 'password');
            if ($i++ % $passwordsByStep === 0 && count($documents) > 0) {
                $esIndex->addDocuments($documents);
                $this->displayAvancement($output, $progress, $nbrPasswords, $i, $passwordsByStep);
                $documents = array();
            }
        }

        // finish work
        if (count($documents) > 0) {
            $esIndex->addDocuments($documents);
        }
        $esIndex->refresh();
        $progress->finish();
        $output->writeln('');
    }

    /**
     * @param OutputInterface $output
     * @param $nbrPasswords
     * @param $passwordsByStep
     *
     * @return null|ProgressBar
     */
    protected function displayInitialAvancement(OutputInterface $output, $nbrPasswords, $passwordsByStep)
    {
        $progress = null;
        $output->writeln($nbrPasswords.' passwords');

        $progress = new ProgressBar($output, ceil($nbrPasswords / $passwordsByStep));
        $progress->start();
        $progress->setFormat('debug');

        return $progress;
    }

    /**
     * @param $output
     * @param $progress
     * @param $nbrPasswords
     * @param $done
     * @param $passwordsByStep
     *
     * @return mixed
     */
    protected function displayAvancement($output, $progress, $nbrPasswords, $done, $passwordsByStep)
    {
        $done += $passwordsByStep;
        if ($done > $nbrPasswords) {
            $done = $nbrPasswords;
        }

        if ($output->isDecorated()) {
            $progress->advance();
        } else {
            $output->writeln($done.' stored on '.$nbrPasswords.'. Memory: '.Helper::formatMemory(memory_get_usage(true)));
        }

        return $done;
    }
}
