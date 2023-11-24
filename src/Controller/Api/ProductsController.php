<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Annotation\Route;


/**
 * Class ApiController
 * @package App\Controller
 * @Route("/api/products", name="rest_api_products")
 */

class ProductsController extends AbstractController
{
    private $serializer;
    private $entityManager;
    private $validator;

    public function __construct(SerializerInterface $serializer, EntityManagerInterface $entityManager, ValidatorInterface $validator){
        $this->serializer = $serializer;
        $this->entityManager = $entityManager;
        $this->validator = $validator;
    }

    /**
     * @Route("/", name="api_show_all_products", methods={"GET"})
     */
    public function showAllProducts(ProductRepository $productRepository): Response
    {
        return new Response($this->serializer->serialize($productRepository->findAll(), 'json', ['groups' => 'list_product']));
    }

    /**
     * @Route("/show/{id}", name="api_show_products", methods={"GET"})
     */
    public function showProducts(ProductRepository $productRepository, $id): JsonResponse
    {
        $product = $productRepository->find($id);

        if (!$product) {
            return $this->json([
                'message' => 'Product not found'
            ], RESPONSE::HTTP_NOT_FOUND);
        }

        $jsonObject = $this->serializer->serialize($product, 'json',
            [AbstractNormalizer::ATTRIBUTES => ['id','name','description', 'creationDate',
                'lastModificationDate', 'categories' => ['id', 'name']]]);

        return new JsonResponse($jsonObject, Response::HTTP_OK, [], true);
    }

    /**
     * @Route("/delete/{id}", name="api_delete_products", methods={"DELETE"})
     */
    public function deleteProducts(ProductRepository $productRepository, $id, Product $product): JsonResponse
    {
        if (!$productRepository->find($id)) {
            return $this->json([
                'message' => 'No product found'
            ], RESPONSE::HTTP_NOT_FOUND);
        }

        $this->deleteProcess($product);

        return $this->json([
            'message' => 'Product deleted'
        ], RESPONSE::HTTP_OK);
    }

    /**
     * @Route("/new", name="api_new_products", methods={"POST"})
     */
    public function newProducts(Request $request): Response
    {
        $productData = $request->getContent();

        try{
            $jsonObject = $this->serializer->deserialize($productData, Product::class, 'json');
            $errors = $this->validator->validate($jsonObject);

            if (count($errors) > 0) {
                return $this->json($errors, Response::HTTP_BAD_REQUEST);
            }

            $this->entityManager->persist($jsonObject);
            $this->entityManager->flush();

            return $this->json([
                'message' => 'Product created'
            ], RESPONSE::HTTP_CREATED);
        }catch (NotEncodableValueException $e) {
            return $this->json([
                'message' => 'Error'
            ], RESPONSE::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @Route("/edit/{id}", name="api_edit_products", methods={"PUT"})
     */
    public function editProducts(Request $request, $id, ProductRepository $productRepository): Response
    {
        $product = $productRepository->find($id);

        if ($product) {
            $this->editProductProcess($request, $product);

            return $this->json([
                'message' => 'Product edited successfully !'
            ], RESPONSE::HTTP_OK);
        }

        return $this->json([
            'message' => 'Product not found'
        ], RESPONSE::HTTP_NOT_FOUND);
    }

    /**
     * @param Product $product
     */
    private function deleteProcess(Product $product): void
    {
        $this->entityManager->remove($product);
        $this->entityManager->flush();
    }

    /**
     * @param Request $request
     * @param Product $product
     */
    private function editProductProcess(Request $request, Product $product): void
    {
        $productData = $request->getContent();
        $this->serializer->deserialize($productData, Product::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $product]);
        $this->entityManager->flush();
    }
}