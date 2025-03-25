<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PriceController extends AbstractController
{
    #[Route('/prices', name: 'app_prices')]
    public function index(): Response
    {
        return $this->render('home/price.html.twig');
    }
}
