<?php
declare(strict_types=1);

namespace App\Command;

use App\Repository\ProductRepository;
use App\Service\Export\CsvProductExporter;
use http\Exception;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class ExportCsvCommand extends Command
{
    protected static $defaultName = 'app:export-csv';

    private $productRepository;
    private $serializer;
    private $fileSystem;
    private $csvProductExporter;

    public function __construct(
        string $name = null,
        ProductRepository $productRepository,
        SerializerInterface $serializer,
        FileSystem $fileSystem,
        CsvProductExporter $csvProductExporter
    ) {
        parent::__construct($name);
        $this->productRepository = $productRepository;
        $this->serializer = $serializer;
        $this->fileSystem = $fileSystem;
        $this->csvProductExporter = $csvProductExporter;
    }

    public function configure()
    {
        $this
            ->addArgument('filename', InputArgument::REQUIRED, 'File name to save data')
            ->addArgument('ids', InputArgument::OPTIONAL | InputArgument::IS_ARRAY, 'Product IDs');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $fileName = $input->getArgument('filename');
        $productIds = $input->getArgument('ids');

        try {
            $productsToExport = $this->csvProductExporter->getProductsToExport($productIds);
            $this->csvProductExporter->generateCsv($productsToExport , $fileName);

            return Command::SUCCESS;
        } catch (Exception $e) {
            echo 'Error';

            return Command::FAILURE;
        }
    }
}