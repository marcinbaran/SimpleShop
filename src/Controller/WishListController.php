<?php

namespace App\Controller;

use App\Entity\Product;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class WishListController extends AbstractController
{
    private const PRODUCTS_IDS_ON_WISHLIST = 'productsIdsOnWishList';
    private const ROUTE_TO_WISHLIST = 'wishList';
    private const MAX_PRODUCTS_ON_WISHLIST = 5;

    private $session;

    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    /**
     * @Route("/wishlist", name="wishList", methods={"GET"})
     */
    public function index(): Response
    {
        $productsIdsOnWishList = $this->session->get(self::PRODUCTS_IDS_ON_WISHLIST);

        return $this->render('wishList/index.html.twig', [
            'productsIdOnWishList' => $productsIdsOnWishList,
        ]);
    }

    /**
     * @Route("/wishlist/add/{id}", name="add_to_wish_list", methods={"POST"})
     */
    public function addWishList(Product $product): Response
    {
        if (!($this->session->get(self::PRODUCTS_IDS_ON_WISHLIST))) {
            $this->session->set(self::PRODUCTS_IDS_ON_WISHLIST, []);
        }

        $productsIdsOnWishListArray = $this->session->get(self::PRODUCTS_IDS_ON_WISHLIST);

        if ($this->hasMaxProductsInWishList($productsIdsOnWishListArray)) {
            $this->addFlash(
                'notice',
                'WishList can contain 5 products'
            );

            return $this->redirectToRoute(self::ROUTE_TO_WISHLIST);
        }

        $productId = $product->getId();

        if (!$this->isProductOnWishList($productId, $productsIdsOnWishListArray)) {
            $this->addProductToWishList($productsIdsOnWishListArray, $productId);
        }

        return $this->redirectToRoute(self::ROUTE_TO_WISHLIST);
    }

    /**
     * @Route("/wishlist/remove/{id}", name="remove_product_from_wish_list", methods={"DELETE"})
     */
    public function removeProductFromWishList(Product $product): Response
    {
        $productsIdsOnWishListArray = $this->session->get(self::PRODUCTS_IDS_ON_WISHLIST);
        $productIdToRemove = $product->getId();

        if ($this->isProductOnWishList($productIdToRemove, $productsIdsOnWishListArray)) {
            $this->processRemoveProduct($productIdToRemove, $productsIdsOnWishListArray);

            return $this->redirectToRoute(self::ROUTE_TO_WISHLIST);
        }

        $this->addFlash(
            'notice',
            'Error while removing product!'
        );

        return $this->redirectToRoute(self::ROUTE_TO_WISHLIST);
    }

    /**
     * @Route("/wishlist/clear", name="clear_wish_list", methods={"DELETE"})
     */
    public function clearWishList(): Response
    {
        $this->session->clear();

        return $this->redirectToRoute(self::ROUTE_TO_WISHLIST);
    }

    /**
     * @param $productsIdsOnWishListArray
     * @return bool
     */
    private function hasMaxProductsInWishList($productsIdsOnWishListArray): bool
    {
        return count($productsIdsOnWishListArray) > self::MAX_PRODUCTS_ON_WISHLIST - 1;
    }

    /**
     * @param int|null $productIdToRemove
     * @param $productsIdsOnWishListArray
     */
    private function processRemoveProduct(?int $productIdToRemove, $productsIdsOnWishListArray): void
    {
        $elementPositionInArray = array_search($productIdToRemove, $productsIdsOnWishListArray);
        unset($productsIdsOnWishListArray[$elementPositionInArray]);
        $this->session->set(self::PRODUCTS_IDS_ON_WISHLIST, $productsIdsOnWishListArray);
    }

    /**
     * @param int|null $productId
     * @param $productsIdsOnWishListArray
     * @return bool
     */
    private function isProductOnWishList(?int $productId, $productsIdsOnWishListArray): bool
    {
        return in_array($productId, $productsIdsOnWishListArray);
    }

    /**
     * @param $productsIdsOnWishListArray
     * @param int|null $productId
     * @return mixed
     */
    private function addProductToWishList($productsIdsOnWishListArray, ?int $productId): void
    {
        array_push($productsIdsOnWishListArray, $productId);
        $this->session->set(self::PRODUCTS_IDS_ON_WISHLIST, $productsIdsOnWishListArray);
    }
}
