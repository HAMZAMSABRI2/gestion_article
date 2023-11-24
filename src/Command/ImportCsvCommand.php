<?php

// src/Command/ImportCsvCommand.php
namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Article;

class ImportCsvCommand extends Command
{
    private $entityManager;
    private $params;

    public function __construct(EntityManagerInterface $entityManager, ParameterBagInterface $params)
    {
        parent::__construct();

        $this->entityManager = $entityManager;
        $this->params = $params;
    }

    protected function configure()
    {
        $this
            ->setName('app:import-csv')
            ->setDescription('Import CSV file into the database')
            ->addArgument('folder', InputArgument::REQUIRED, 'Path to the folder containing the CSV file');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $folder = $input->getArgument('folder');
        $csvFile = $folder . '/articles.csv';

        if (!file_exists($csvFile)) {
            $output->writeln('Le fichier CSV n\'existe pas dans le dossier spécifié.');
            return Command::FAILURE;
        }

        $output->writeln('Importation du fichier CSV...');

        $articles = $this->parseCsv($csvFile);

        foreach ($articles as $articleData) {
            $article = $this->entityManager->getRepository(Article::class)
                ->findOneBy(['reference' => $articleData['reference']]);

            if (!$article) {
                $article = new Article();
            }

            $article->setReference($articleData['reference']);
            $article->setDesignation($articleData['designation']);
            $article->setQuantite((int)$articleData['quantite']);
            $article->setPrix((float)$articleData['prix']);

            $this->entityManager->persist($article);
        }

        $this->entityManager->flush();

        $output->writeln('Importation terminée avec succès.');
        return Command::SUCCESS;
    }

    private function parseCsv($csvFile)
    {
        $articles = [];
        $handle = fopen($csvFile, 'r');

        if ($handle) {
            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                $articles[] = [
                    'reference' => $data[0],
                    'designation' => $data[1],
                    'quantite' => $data[2],
                    'prix' => $data[3],
                ];
            }
            fclose($handle);
        }

        return $articles;
    }
}

