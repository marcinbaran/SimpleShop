<?php
declare(strict_types=1);

namespace App\Service\Import;

use Symfony\Component\Serializer\SerializerInterface;

class CsvDataConverter
{
    private $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    public function execute(string $filename): array
    {
        return $this->serializer->decode(file_get_contents($filename), 'csv');
    }
}