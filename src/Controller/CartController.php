<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\Cart\CartInterface;
use App\Service\Mailer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class CartController
 * @package App\Controller
 * @Route("/cart")
 */
class CartController extends AbstractController
{
    private $cart;
    private $mailer;
    /**
     * @var string
     */
    private $adminEmail;

    public function __construct(CartInterface $cart, Mailer $mailer, string $adminEmail)
    {
        $this->cart = $cart;
        $this->mailer = $mailer;
        $this->adminEmail = $adminEmail;
    }

    /**
     * @Route("/", name="cart_index", methods={"GET"})
     */
    public function index(): Response
    {
        $products = $this->cart->getProducts();
        $totalPrice = $this->cart->getTotalPrice();

        return $this->render('cart/index.html.twig', [
            'productsInCart' => $products,
            'totalPrice' => $totalPrice,
        ]);
    }

    /**
     * @Route(name="add_product_to_cart", methods={"POST"})
     */
    public function addProductToCart(Request $request): void
    {
        if ($request->isXmlHttpRequest()) {
            $this->cart->addProduct((int)$request->request->get('id'), (int)$request->request->get('quantity'));
        }
    }

    /**
     * @Route("/cart/delete/{id}", name="remove_product_from_cart", methods={"DELETE"})
     */
    public function removeProductFromCart(int $id): Response
    {
        $this->cart->removeProduct($id);

        return $this->redirectToRoute('cart_index');
    }

    /**
     * @Route("/edit/{id}", name="edit_product_from_cart", methods={"PUT"})
     */
    public function editProductInCart(Request $request, int $id): Response
    {
        $quantity = $request->request->get('quantity');
        $this->cart->editQuantityProduct((int)$id, (int)$quantity);

        return $this->redirectToRoute('cart_index');
    }

    /**
     * @Route("/order/", name="order_cart", methods={"GET"})
     */
    public function order(): Response
    {
        $products = $this->cart->getProducts();

        if (!$products) {
            return $this->redirectToRoute('cart_index');
        }

        $totalPrice = $this->cart->getTotalPrice();
        $emailData = [];

        $emailData = $this->getEmailData($emailData, $products, $totalPrice);

        $this->mailer->sendMail($emailData);
        $this->cart->clearCart();

        return $this->render('cart/order.html.twig', [
            'productsInCart' => $products,
            'totalPrice' => $totalPrice,
        ]);
    }

    /**
     * @param array $emailData
     * @return array
     */
    private function getEmailData(array $emailData, array $products, float $totalPrice): array
    {
        $emailData['from'] = $this->adminEmail;
        $emailData['to'] = $this->getUser()->getUsername();
        $emailData['subject'] = 'Order Completed';
        $emailData['text'] = '<br>';

        foreach ($products as $product) {
            $emailData['text'] .= 'Name: ' . $product['name'] . '&emsp;Quantity: ' . $product['quantity'] . '&emsp;Price: ' .$product['price'];
            $emailData['text'] .= '<br>';
        }

        $emailData['text'] .= '<br><br>Total price: '.$totalPrice;

        return $emailData;
    }
}
