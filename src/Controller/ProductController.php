<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\ProductImage;
use App\Form\ProductType;
use App\Repository\ImageRepository;
use App\Repository\ProductRepository;
use App\Service\ProductImageRemover;
use App\Service\UploadedProductImageSaver;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/product")
 */
class ProductController extends AbstractController
{
    private $entityManager;
    private $logger;
    private $requestStack;
    private $uploadedProductImageSaver;
    private $productImageRemover;

    public function __construct(
        EntityManagerInterface $entityManager,
        UploadedProductImageSaver $uploadedProductImageSaver,
        ProductImageRemover $productImageRemover,
        LoggerInterface $productLogger,
        RequestStack $requestStack
    ) {
        $this->entityManager = $entityManager;
        $this->uploadedProductImageSaver = $uploadedProductImageSaver;
        $this->productImageRemover = $productImageRemover;
        $this->logger = $productLogger;
        $this->requestStack = $requestStack;
    }

    /**
     * @Route("/", name="product_index", methods={"GET"})
     */
    public function index(ProductRepository $productRepository, Request $request): Response
    {
        $request->setLocale('pl');

        return $this->render('product/index.html.twig', [
            'products' => $productRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="product_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $product = new Product();
        $images = $product->getImages()->toArray();
        $form = $this->createForm(ProductType::class, $product, ['images' => $images]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $uploadImages = $form['images']->getData();

            if ($uploadImages) {
                foreach ($uploadImages as $image) {
                    $this->uploadedProductImageSaver->execute($product, $image);
                }
            }

            $this->entityManager->persist($product);
            $this->entityManager->flush();
            $this->addFlash(
                'notice',
                'Product added successfully!'
            );

            return $this->redirectToRoute('product_index');
        }

        return $this->render('product/new.html.twig', [
            'product' => $product,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="product_show", methods={"GET"})
     */
    public function show(Product $product): Response
    {
        $categories = $product->getCategories()->toArray();
        $this->logger->info('Product with ID: ' . $product->getId() . ' shown by ' . $this->requestStack->getCurrentRequest()->getClientIp());

        $images = $product->getImages()->toArray();

        return $this->render('product/show.html.twig', [
            'product' => $product,
            'categories' => $categories,
            'images' => $images,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="product_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Product $product, ImageRepository $imageRepository): Response
    {
        $images = $product->getImages()->toArray();
        $form = $this->createForm(ProductType::class, $product, ['images' => $images]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->logger->info('Product with ID: ' . $product->getId() . ' edited by ' . $this->requestStack->getCurrentRequest()->getClientIp());

            if (isset($request->request->all()['defaultImageId'])) {
                $image = $imageRepository->findOneBy(['id' => $request->request->all()['defaultImageId']]);
                $product->setDefaultImage($image);
            }

            $uploadImages = $form['images']->getData();

            if ($uploadImages) {
                foreach ($uploadImages as $image) {
                    $this->uploadedProductImageSaver->execute($product, $image);
                }
            }

            $this->entityManager->flush();
            $this->addFlash(
                'notice',
                'Product edited successfully!'
            );

            return $this->redirectToRoute('product_index');
        }

        return $this->render('product/edit.html.twig', [
            'product' => $product,
            'form' => $form->createView(),
            'images' => $images,
        ]);
    }

    /**
     * @Route("/{id}", name="product_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Product $product, ImageRepository $imageRepository): Response
    {
        if ($this->isCsrfTokenValid('delete' . $product->getId(), $request->request->get('_token'))) {
            $this->logger->info('Product with ID: ' . $product->getId() . ' deleted by ' . $this->requestStack->getCurrentRequest()->getClientIp());
            $this->entityManager = $this->getDoctrine()->getManager();
            $imagesToDelete = $imageRepository->findBy(['product' => $product]);

            foreach ($imagesToDelete as $image) {
                $this->productImageRemover->execute($image);
            }

            $this->entityManager->flush();

            $this->entityManager->remove($product);
            $this->entityManager->flush();
            $this->addFlash(
                'notice',
                'Product deleted!'
            );
        }

        return $this->redirectToRoute('product_index');
    }

    /**
     * @Route("/deleteimage/{id}", name="delete_image", methods={"DELETE"})
     */
    public function deleteImage(ProductImage $productImage): Response
    {
        $this->productImageRemover->execute($productImage);
        $this->entityManager->flush();

        return $this->redirectToRoute('product_index');
    }
}
