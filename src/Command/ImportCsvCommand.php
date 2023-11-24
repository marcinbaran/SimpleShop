<?php
declare(strict_types=1);

namespace App\Command;

use App\Service\Import\CsvDataConverter;
use App\Service\Import\ImportProduct;
use App\Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ImportCsvCommand extends Command
{
    protected static $defaultName = 'app:import-csv';

    private $importProduct;
    private $csvDataConverter;

    public function __construct(string $name = null, ImportProduct $importProduct, CsvDataConverter $csvDataConverter)
    {
        parent::__construct($name);
        $this->importProduct = $importProduct;
        $this->csvDataConverter = $csvDataConverter;
    }

    public function configure()
    {
        $this
            ->addArgument('filename', InputArgument::REQUIRED, 'File name to read data');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $io = new SymfonyStyle($input, $output);
            $filename = $input->getArgument('filename');
            $rows = $this->csvDataConverter->execute($filename);

            $this->importProduct->validateDataFromFile($rows);
            $errors = $this->importProduct->getErrors();

            if (!$errors) {
                $this->importProduct->execute($rows);
                $io->success('Added: ' . $this->importProduct->getCountProductsAdded() . '   Updated: ' . $this->importProduct->getCountProductsUpdated());

                return Command::SUCCESS;
            }

            foreach ($errors as $error) {
                $io->writeln($error);
            }

            return Command::FAILURE;
        } catch (Exception $e) {
            $output->writeln($e->getMessage());

            return Command::FAILURE;
        }
    }
}
