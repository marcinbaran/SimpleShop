<?php
declare(strict_types=1);

namespace App\Service;

use App\Entity\ProductImage;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploadedProductImageSaver
{
    private $entityManager;
    private $uploadDirectory;

    public function __construct(EntityManagerInterface $entityManager, string $uploadDirectory)
    {
        $this->entityManager = $entityManager;
        $this->uploadDirectory = $uploadDirectory;
    }

    /**
     * @param Product $product
     * @param array $uploadImages
     * @param string $destination
     */
    public function execute(Product $product, UploadedFile $imageToUpload): void
    {
        $newFilename = $this->generateNewFileName($imageToUpload);

        $this->uploadImage($imageToUpload, $newFilename);
        $this->saveImage($product, $newFilename);
    }

    /**
     * @param UploadedFile $imageToUpload
     * @param string $destination
     * @return string
     */

    private function uploadImage(UploadedFile $imageToUpload, string $newFileName): void
    {
        $destination = $this->uploadDirectory . '/images';
        $imageToUpload->move($destination, $newFileName);
    }

    /**
     * @param string $newFilename
     * @param Product $product
     */
    private function saveImage(Product $product, string $newFilename): void
    {
        $image = new ProductImage();
        $image->setFileName($newFilename);
        $image->setProduct($product);

        $this->entityManager->persist($image);
    }

    /**
     * @param UploadedFile $imageToUpload
     * @return string
     */
    private function generateNewFileName(UploadedFile $imageToUpload): string
    {
        $originalFilename = pathinfo($imageToUpload->getClientOriginalName(), PATHINFO_FILENAME);
        $newFilename = $originalFilename . '_' . md5(uniqid()) . '.' . $imageToUpload->guessExtension();

        return $newFilename;
    }
}
