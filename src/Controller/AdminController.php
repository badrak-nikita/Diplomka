<?php

namespace App\Controller;

use App\Entity\Service;
use App\Repository\ServiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AdminController extends AbstractController
{
    #[Route('/admin', name: 'admin_home')]
    public function index(): Response
    {
        return $this->render('admin/dashboard/index.html.twig');
    }

    #[Route('/admin/services', name: 'admin_services')]
    public function showServices(ServiceRepository $serviceRepository): Response
    {
        $services = $serviceRepository->findAll();

        return $this->render('admin/services/index.html.twig', [
            'services' => $services,
        ]);
    }

    #[Route('/admin/services/create', name: 'admin_services_create')]
    public function createServices(Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            $serviceName = $request->request->get('serviceName');
            $duration = $request->request->get('duration');
            $price = $request->request->get('price');

            if (!$serviceName) {
                return $this->redirectToRoute('admin_services_create');
            }

            $service = new Service();
            $service->setServiceName($serviceName);
            $service->setDuration($duration ?: null);
            $service->setPrice($price !== null ? (float)$price : null);

            $entityManager->persist($service);
            $entityManager->flush();

            return $this->redirectToRoute('admin_services');
        }

        return $this->render('admin/services/create.html.twig');
    }

    #[Route('/admin/services/edit/{id}', name: 'admin_services_edit')]
    public function editService(int $id, Request $request, EntityManagerInterface $entityManager, ServiceRepository $serviceRepository): Response
    {
        $service = $serviceRepository->find($id);

        if (!$service) {
            throw $this->createNotFoundException('Послуга не знайдена');
        }

        if ($request->isMethod('POST')) {
            $serviceName = $request->request->get('serviceName');
            $duration = $request->request->get('duration');
            $price = $request->request->get('price');

            if (!$serviceName) {
                return $this->redirectToRoute('admin_services_edit', ['id' => $id]);
            }

            $service->setServiceName($serviceName);
            $service->setDuration($duration ?: null);
            $service->setPrice($price !== null ? (float)$price : null);

            $entityManager->flush();

            return $this->redirectToRoute('admin_services');
        }

        return $this->render('admin/services/edit.html.twig', [
            'service' => $service,
        ]);
    }

    #[Route('/admin/services/delete/{id}', name: 'admin_services_delete', methods: ['POST'])]
    public function deleteService(int $id, EntityManagerInterface $entityManager, ServiceRepository $serviceRepository): Response
    {
        $service = $serviceRepository->find($id);

        if (!$service) {
            throw $this->createNotFoundException('Послуга не знайдена');
        }

        $entityManager->remove($service);
        $entityManager->flush();

        return $this->redirectToRoute('admin_services');
    }

    #[Route('/admin/orders', name: 'admin_orders')]
    public function showOrders(): Response
    {
        return $this->render('admin/orders/index.html.twig');
    }
}
