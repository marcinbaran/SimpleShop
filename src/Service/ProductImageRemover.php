<?php
declare(strict_types=1);

namespace App\Service;

use App\Entity\ProductImage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;

class ProductImageRemover
{
    private $entityManager;
    private $filesystem;
    private $uploadDirectory;

    public function __construct(
        EntityManagerInterface $entityManager,
        Filesystem $filesystem,
        string $uploadDirectory
    ) {
        $this->entityManager = $entityManager;
        $this->filesystem = $filesystem;
        $this->uploadDirectory = $uploadDirectory;
    }

    /**
     * @param ProductImage $productImage
     */
    public function execute(ProductImage $productImage)
    {
        if ($productImage->getProduct()->getDefaultImage() !== null && $this->isDefaultImage($productImage)) {
            $this->setDefaultImageAsNull($productImage);
        }

        $this->removeImageFromDatabase($productImage);
        $this->removeImageFromDirectory($productImage);
    }

    /**
     * @param ProductImage $productImage
     */
    private function removeImageFromDirectory(ProductImage $productImage): void
    {
        $fileDestination = $this->uploadDirectory . '/images/' . $productImage->getFileName();

        if ($this->filesystem->exists($fileDestination)) {
            $this->filesystem->remove($fileDestination);
        }
    }

    /**
     * @param ProductImage $productImage
     */
    private function removeImageFromDatabase(ProductImage $productImage): void
    {
        $this->entityManager->remove($productImage);
    }

    /**
     * @param ProductImage $productImage
     * @return bool
     */
    private function isDefaultImage(ProductImage $productImage): bool
    {
        return $productImage->getProduct()->getDefaultImage()->getId() === $productImage->getId();
    }

    /**
     * @param ProductImage $productImage
     */
    private function setDefaultImageAsNull(ProductImage $productImage): void
    {
        $productImage->getProduct()->setDefaultImage(null);
    }
}
