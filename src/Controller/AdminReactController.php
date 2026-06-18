<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;

use Psr\Log\LoggerInterface;

use Doctrine\ORM\EntityManagerInterface;

class AdminReactController extends AbstractController
{
	private EntityManagerInterface $entityManager;
	private LoggerInterface $logger;
	
	public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)  {
		$this->entityManager = $entityManager;
		$this->logger = $logger;
	}

	#[Route('/admin', name: 'adminReact', methods: ['GET'])]
    public function adminReactAction(Request $request, LoggerInterface $logger): Response
    {
		return $this->render('admin.react.html.twig') ;
    }
}
