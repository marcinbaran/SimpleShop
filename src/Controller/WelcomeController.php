<?php
declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class WelcomeController extends AbstractController
{
    public function index(): Response
    {
        $date = date('d-m-Y H:i:s');

        return $this->render('welcome.html.twig', [
           'date' => $date,
        ]);
    }
}