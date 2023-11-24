<?php
declare(strict_types=1);

namespace App\Service\Import;

use App\Entity\Product;
use App\Entity\ProductCategory;
use App\Repository\ProductCategoryRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;

class ImportProduct
{
    private const ARRAY_KEY_NEEDED = ['name', 'categories_id'];

    private $countProductsAdded = 0;
    private $countProductsUpdated = 0;
    private $productRepository;
    private $entityManager;
    private $productCategoryRepository;
    private $allProducts;
    private $allCategories;
    private $errors;

    public function __construct(
        ProductRepository $productRepository,
        EntityManagerInterface $entityManager,
        ProductCategoryRepository $productCategoryRepository
    ) {
        $this->productRepository = $productRepository;
        $this->entityManager = $entityManager;
        $this->productCategoryRepository = $productCategoryRepository;
    }

    public function getCountProductsAdded(): int
    {
        return $this->countProductsAdded;
    }

    public function getCountProductsUpdated(): int
    {
        return $this->countProductsUpdated;
    }

    public function getErrors(): ?array
    {
        return $this->errors;
    }

    public function validateDataFromFile(array $dataArray): bool
    {
        try {
            foreach ($dataArray as $row) {
                foreach (self::ARRAY_KEY_NEEDED as $keyNeeded) {
                    if (!array_key_exists($keyNeeded, $row)) {
                        $this->errors['noKeyExist'][] = 'No found key: ' . $keyNeeded;

                        return false;
                    }
                }
            }
        } catch (\Exception $e) {
            $this->errors['otherErrors'][] = $e->getMessage();

            return false;
        }

        return true;
    }

    /**
     * @param string $dataArray[]['name']
     * @param string $dataArray[]['categories_id']
     */
    public function execute(array $dataArray): void
    {
        list($productsToCreate, $productsToUpdate) = $this->prepareData($dataArray);

        foreach ($dataArray as $row) {
            $ids = explode(',', $row['categories_id']);

            if ($this->shouldCreateProduct($row['name'], $productsToCreate)) {
                $this->newProductProcess($row['name'], $ids);
                continue;
            }

            if ($this->shouldUpdateProduct($row['name'], $productsToUpdate)) {
                $this->updateProductProcess($row['name'], $ids);
            }
        }

        $this->entityManager->flush();
    }

    private function newProductProcess(string $name, array $categoriesIds): void
    {
        $product = new Product();
        $product->setName($name);
        $this->addCategoryToProduct($categoriesIds, $product);
        $this->entityManager->persist($product);
        $this->countProductsAdded++;
    }

    private function updateProductProcess(string $productName, array $categoriesIds): void
    {
        foreach ($this->allProducts as $product) {
            if ($product->getName() === $productName) {
                $productToUpdate = $product;
            }
        }

        $oldCategories = $productToUpdate->getCategories();

        foreach ($oldCategories as $oldCategory) {
            $productToUpdate->removeCategory($oldCategory);
        }

        $this->addCategoryToProduct($categoriesIds, $productToUpdate);
        $this->entityManager->persist($productToUpdate);
        $this->countProductsUpdated++;
    }

    private function getCategory(int $categoryId): ?ProductCategory
    {
        if (array_key_exists($categoryId, $this->allCategories)) {
            return $this->allCategories[$categoryId];
        }

        return null;
    }

    private function categoryExist(int $categoryId): bool
    {
        return array_key_exists($categoryId, $this->allCategories);
    }

    /**
     * @param string $name
     * @param array $productsToCreate
     * @return bool
     */
    private function shouldCreateProduct(string $name, array $productsToCreate): bool
    {
        return in_array($name, $productsToCreate, true);
    }

    /**
     * @param string $name
     * @param array $productsToUpdate
     * @return bool
     */
    private function shouldUpdateProduct(string $name, array $productsToUpdate): bool
    {
        return in_array($name, $productsToUpdate, true);
    }

    /**
     * @param array $categoriesIds
     * @param Product $product
     */
    private function addCategoryToProduct(array $categoriesIds, Product $product): void
    {
        foreach ($categoriesIds as $categoryId) {
            if (!$this->categoryExist((int)$categoryId)) {
                $this->errors['categoriesError'][] = 'WARNING: Category with id: ' . $categoryId . ' no exist - skipped';
                continue;
            }

            $product->addCategory($this->getCategory((int)$categoryId));
        }
    }

    /**
     * @param array $dataArray
     * @return array[]
     */
    private function prepareData(array $dataArray): array
    {
        $productsNamesFromFile = [];
        $productsCategoriesIdFromFile = [];
        $productsToCreate = [];
        $productsToUpdate = [];

        foreach ($dataArray as $row['name'] => $value) {
            $productsNamesFromFile[] = $value['name'];
            $productsCategoriesIdFromFile[] = explode(',', $value['categories_id']);
        }

        $this->allCategories = $this->productCategoryRepository->findAllCategories();
        $this->allProducts = $this->productRepository->findBy(['name' => $productsNamesFromFile]);

        foreach ($this->allProducts as $product) {
            if (in_array($product->getName(), $productsNamesFromFile, true)) {
                $productsToUpdate[] = $product->getName();
            }
        }

        foreach ($productsNamesFromFile as $product) {
            if (!in_array($product, $productsToUpdate, true)) {
                $productsToCreate[] = $product;
            }
        }

        return array($productsToCreate, $productsToUpdate);
    }
}
