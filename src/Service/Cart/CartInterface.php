<?php

namespace App\Service\Cart;

use App\Entity\Product;

interface CartInterface
{
    /**
     * @param int $productId
     * @param int $quantity
     * @throws \ProductNotFound
     */
    public function addProduct(int $productId, int $quantity): void;

    /**
     * @param int $productId
     */
    public function removeProduct(int $productId): void;

    /**
     * @param int $productId
     */
    public function editQuantityProduct(int $productId, int $quantity): void;

    /**
     * @param array
     * [
     *      3 => ['id' => 3, 'name' => string, 'price' => float, ...]
     * ]
     */
    public function getProducts(): array;
    public function clearCart(): void;
}