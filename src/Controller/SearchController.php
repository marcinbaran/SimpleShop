<?php

namespace App\Controller;

use App\Form\SearchType;
use App\Repository\ProductCategoryRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SearchController extends AbstractController
{
    /**
     * @Route("/search", name="search")
     */
    public function index(): Response
    {
        $form = $this->createForm(SearchType::class);

        return $this->render('search/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/submit_search", name="submit_search", methods={"GET"})
     */
    public function submitForm(
        Request $request,
        ProductRepository $productRepository,
        ProductCategoryRepository $productCategoryRepository
    ) :Response {
        $descriptionOrName = $request->query->get('search')['query'];

        if (empty($descriptionOrName)) {
            return $this->redirectToRoute('search');
        }

        $products = $productRepository->searchProductsByDescriptionOrName($descriptionOrName);
        $categories = $productCategoryRepository->searchProductCategoriesByDescriptionOrName($descriptionOrName);

        return $this->render('search/show.html.twig', [
            'products' => $products,
            'categories' => $categories,
        ]);
    }
}