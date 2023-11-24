<?php

namespace App\Repository;

use App\Entity\ProductCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ProductCategory|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProductCategory|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProductCategory[]    findAll()
 * @method ProductCategory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductCategory::class);
    }

    public function searchProductCategoriesByDescriptionOrName($nameOrDescription) :array
    {
        return $this->createQueryBuilder('pc')
            ->where('pc.name LIKE :query')
            ->orWhere('pc.description LIKE :query')
            ->setParameter('query', '%' . $nameOrDescription . '%')
            ->getQuery()
            ->getResult();
    }

    public function findAllCategories(string $indexedBy = 'id'): array
    {
        return $this->createQueryBuilder('c')
            ->indexBy('c', 'c.' . $indexedBy)
            ->getQuery()
            ->getResult();
    }
}
