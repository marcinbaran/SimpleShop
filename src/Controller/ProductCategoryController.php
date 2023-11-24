<?php

namespace App\Controller;

use App\Entity\ProductCategory;
use App\Form\ProductCategoryType;
use App\Repository\ProductCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/product/category")
 */
class ProductCategoryController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager){
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/", name="product_category_index", methods={"GET"})
     */
    public function index(ProductCategoryRepository $productCategoryRepository, Request $request): Response
    {
        $request->setLocale('pl');

        return $this->render('productCategory/index.html.twig', [
            'productCategories' => $productCategoryRepository->findAll(),
        ]);
    }

    public function embeddingProductCategory(ProductCategoryRepository $productCategoryRepository): Response
    {
        return $this->render('productCategory/_embedding_categories.html.twig', [
            'productCategories' => $productCategoryRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="product_category_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $productCategory = new ProductCategory();
        $form = $this->createForm(ProductCategoryType::class, $productCategory);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager = $this->getDoctrine()->getManager();
            $this->entityManager->persist($productCategory);
            $this->entityManager->flush();
            $this->addFlash(
                'notice',
                'Category added successfully!'
            );

            return $this->redirectToRoute('product_category_index');
        }

        return $this->render('productCategory/new.html.twig', [
            'productCategory' => $productCategory,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="product_category_show", methods={"GET"})
     */
    public function show(ProductCategory $productCategory): Response
    {
        $products = $productCategory->getProducts()->toArray();

        return $this->render('productCategory/show.html.twig', [
            'productCategory' => $productCategory,
            'products' => $products,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="product_category_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, ProductCategory $productCategory): Response
    {
        $form = $this->createForm(ProductCategoryType::class, $productCategory);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();
            $this->addFlash(
                'notice',
                'Category edited successfully!'
            );

            return $this->redirectToRoute('product_category_index');
        }

        return $this->render('productCategory/edit.html.twig', [
            'productCategory' => $productCategory,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="product_category_delete", methods={"DELETE"})
     */
    public function delete(Request $request, ProductCategory $productCategory): Response
    {
        if ($this->isCsrfTokenValid('delete' . $productCategory->getId(), $request->request->get('_token'))) {
            $this->entityManager = $this->getDoctrine()->getManager();
            $this->entityManager->remove($productCategory);
            $this->entityManager->flush();
            $this->addFlash(
                'notice',
                'Category deleted!'
            );
        }

        return $this->redirectToRoute('product_category_index');
    }
}