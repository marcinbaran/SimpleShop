<?php
declare(strict_types=1);

namespace App\Service\Export;

use App\Repository\ProductRepository;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

class CsvProductExporter
{
    private $productRepository;
    private $serializer;
    private $fileSystem;

    public function __construct(
        ProductRepository $productRepository,
        SerializerInterface $serializer,
        FileSystem $fileSystem
    ) {
        $this->productRepository = $productRepository;
        $this->serializer = $serializer;
        $this->fileSystem = $fileSystem;
    }

    public function getProductsToExport(array $productIds): array
    {
        if (empty($productIds)) {
            return $this->productRepository->findAll();
        }

        return $this->productRepository->findBy(['id' => $productIds]);
    }

    public function generateCsv(array $productsToExport, string $fileName): void
    {
        $productsObject = $this->serializer->serialize($productsToExport, 'json', [
            AbstractNormalizer::ATTRIBUTES => [
                'id',
                'name',
                'description',
                'creationDate',
                'lastModificationDate',
                'categories' => ['name']
            ]
        ]);

        $productsArray = json_decode($productsObject, true);
        $dataToExport = '';

        foreach ($productsArray as $product) {
            $dataToExport .= $product['id'] . ',' . $product['name'] . ',' . $product['description'] . ',' . $product['creationDate'] . ',' . $product['lastModificationDate'] . ',';
            foreach ($product['categories'] as $category) {
                $dataToExport .= $category['name'] . ';';
            }
            $dataToExport = substr($dataToExport, 0, -1);
            $dataToExport .= "\n";
        }
        $this->fileSystem->dumpFile('data/' . $fileName, $dataToExport);
    }
}