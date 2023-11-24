<?php
declare(strict_types=1);

namespace App\Service\Cart;

use App\Repository\ProductRepository;
use App\Exception\ProductNotFound;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class SessionCart implements CartInterface
{
    private $session;
    private $productRepository;

    public function __construct(SessionInterface $session, ProductRepository $productRepository)
    {
        $this->session = $session;
        $this->productRepository = $productRepository;
    }

    public function addProduct(int $productId, int $quantity): void
    {
        $productData = [];
        $productsOnCart = [];

        $productData = $this->prepareProductData($productData, $productId, $quantity);

        if (!$productData) {
            throw new ProductNotFound();
        }

        if ($this->session->get('cart')) {
            $productsOnCart = $this->session->get('cart');
        }

        if (!array_key_exists($productData['id'], $productsOnCart)) {
            $productsOnCart[$productData['id']] = $productData;
            $this->session->set('cart', $productsOnCart);
        }
    }

    public function removeProduct(int $productId): void
    {
        $productsOnCart = $this->getProducts();

        if ($productsOnCart[$productId]) {
            unset($productsOnCart[$productId]);
        }

        $this->session->set('cart', $productsOnCart);
    }

    public function editQuantityProduct(int $productId, int $quantity): void
    {
        $productsOnCart = $this->getProducts();

        if ($productsOnCart[$productId]) {
            $productsOnCart[$productId]['quantity'] = $quantity;
            $productsOnCart[$productId]['sum'] = $productsOnCart[$productId]['price'] * $quantity;
        }

        $this->session->set('cart', $productsOnCart);
    }

    public function getProducts(): array
    {
        $products = [];

        if ($this->session->get('cart')) {
            $products = $this->session->get('cart');
        }

        return $products;
    }

    /**
     * @param int $productId
     * @param array $productData
     * @param int $quantity
     * @return array
     */
    private function prepareProductData(array $productData, int $productId, int $quantity): array
    {
        $product = $this->productRepository->find($productId);
        $productData['id'] = $product->getId();
        $productData['name'] = $product->getName();
        $productData['image'] = $product->getDefaultImage()->getFileName();
        $productData['price'] = $product->getPrice();
        $productData['quantity'] = abs($quantity);
        $productData['sum'] = $product->getPrice() * abs((int)$quantity);

        return $productData;
    }

    /**
     * @return float
     */
    public function getTotalPrice(): float
    {
        $totalPrice = 0;
        $products = $this->session->get('cart');

        if (!$products) {
            return $totalPrice;
        }

        foreach ($products as $product => $key) {
            $totalPrice += (float)$key['sum'];
        }

        return (float)$totalPrice;
    }

    public function clearCart(): void
    {
        $this->session->set('cart', []);
    }
}
