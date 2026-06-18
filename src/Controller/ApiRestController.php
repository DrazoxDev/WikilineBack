<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

use Psr\Log\LoggerInterface;

use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Catalogue\Article;
use App\Entity\Catalogue\Livre;
use App\Entity\Catalogue\Musique;
use App\Entity\Catalogue\Piste;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

use Doctrine\DBAL\Exception\ConstraintViolationException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;


class ApiRestController extends AbstractController
{
	private $entityManager;
	private $logger;
	
	public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)  {
		$this->entityManager = $entityManager;
		$this->logger = $logger;
	}
	
	#[Route('/wp-json/login_check', name: 'login', methods: ['POST'])]
	public function loginAction(Request $request, AuthenticationUtils $authenticationUtils,
		UserProviderInterface $userProvider, JWTTokenManagerInterface $jwtManager) {
		$data = json_decode($request->getContent(), true);
		$email = $data['email'] ?? null;
		$password = $data['password'] ?? null;
		// Vérifier les informations d'identification de l'utilisateur
		$user = $userProvider->loadUserByIdentifier($email);
		if (!$user || !password_verify($password, $user->getPassword())) {
		return new JsonResponse(['error' => 'Invalid credentials'], 401);
		}
		// Générer le token JWT
		$token = $jwtManager->create($user);
		$response = new JsonResponse(['token' => $token]);
		return $response ;
	}
	
    #[Route('/wp-json/wc/v3/products', name: 'list-all-products', methods: ['GET'])]
    public function listAllProducts(): Response
    {
		$query = $this->entityManager->createQuery("SELECT a FROM App\Entity\Catalogue\Article a");
		$articles = $query->getArrayResult();
		return new JsonResponse(
        	$articles,
        	JsonResponse::HTTP_OK // 200
        );
    }

    #[Route('/wp-json/wc/v3/products', name: 'create-a-product', methods: ['POST'])]
    public function createAProduct(Request $request): Response
    {
		$data = json_decode($request->getContent(), true);
	    if (!isset($data['article_type'])) {
            return new JsonResponse(
                ['message' => 'Missing required field: article_type'],
                JsonResponse::HTTP_BAD_REQUEST // 400
            );
        }
		$entity = $this->createEntityFromType($data['article_type']);
        if ($entity === null) {
            return new JsonResponse(
                ['message' => 'Invalid article_type: ' . $data['article_type']],
                JsonResponse::HTTP_BAD_REQUEST // 400
            );
        }
		
		$form = $this->buildForm($entity);
        $form->submit($data);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return new JsonResponse(
                ['message' => 'Invalid data', 'errors' => $this->getFormErrors($form)],
                JsonResponse::HTTP_BAD_REQUEST // 400
            );
        }
		
		try {
            $entity = $form->getData();
            $entity->setId(hexdec(uniqid())); // must be of type int
            $this->entityManager->persist($entity);
            $this->entityManager->flush();
        } catch (UniqueConstraintViolationException $e) {
            return new JsonResponse(
                ['message' => 'Unique constraint violation'],
                JsonResponse::HTTP_UNPROCESSABLE_ENTITY // 422
            );
        }

        return new JsonResponse(
            $this->findArticleAsArray($entity->getId()),
            JsonResponse::HTTP_CREATED, // 201
            ['Content-Location' => '/wp-json/wc/v3/products/' . $entity->getId()]
        );
    }

    #[Route('/wp-json/wc/v3/products/{id}', name: 'retrieve-a-product', methods: ['GET'])]
    public function retrieveAProduct(string $id): Response
    {
		// http://127.0.0.1:8000/wp-json/wc/v3/products/B07KBT4ZRG
		$entity = $this->findArticleEntity($id);

        if (empty($entity)) {
            return new JsonResponse(
                ['message' => 'Resource not found for id ' . $id],
                JsonResponse::HTTP_NOT_FOUND // 404
            );
        }

		return new JsonResponse(
        	$entity,
        	JsonResponse::HTTP_OK // 200
        );
    }
		
	#[Route('/wp-json/wc/v3/products/{id}', name: 'modify-a-product', methods: ['PUT'])]
    public function modifyAProduct(string $id, Request $request): Response
    {
		$entity = $this->findArticleEntity($id);

        if ($entity === null) {
            return new JsonResponse(
                ['message' => 'Resource not found for id ' . $id],
                JsonResponse::HTTP_NOT_FOUND // 404
            );
        }

        $data = json_decode($request->getContent(), true);
        $form = $this->buildForm($entity);
        $form->submit($data);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return new JsonResponse(
                ['message' => 'Invalid data', 'errors' => $this->getFormErrors($form)],
                JsonResponse::HTTP_BAD_REQUEST // 400
            );
        }

        try {
            $this->entityManager->flush();
        } catch (UniqueConstraintViolationException $e) {
            return new JsonResponse(
                ['message' => 'Unique constraint violation'],
                JsonResponse::HTTP_UNPROCESSABLE_ENTITY // 422
            );
        }

        return new JsonResponse(
            $this->findArticleAsArray($id),
            JsonResponse::HTTP_OK, // 200
            ['Content-Location' => '/wp-json/wc/v3/products/' . $id]
        );
    }
	
	/**
     * Instantiates the entity corresponding to the requested type.
     */
    private function createEntityFromType(string $type): ?Article
    {
        return match ($type) {
            'musique' => new Musique(),
            'livre'   => new Livre(),
            default   => null,
        };
    }
	
    /**
     * Builds the form adapted to the entity type.
     */
    private function buildForm(Article $entity): \Symfony\Component\Form\FormInterface
    {
        $builder = $this->createFormBuilder($entity, ['csrf_protection' => false, 'allow_extra_fields' => true]);

        // Common fields for all articles
        $builder
            ->add('titre', TextType::class)
            ->add('prix', NumberType::class)
            ->add('disponibilite', IntegerType::class)
            ->add('image', TextType::class);

        // Type-specific fields
        if ($entity instanceof Musique) {
            $builder
                ->add('artiste', TextType::class)
                ->add('dateDeSortie', TextType::class);
        } elseif ($entity instanceof Livre) {
            $builder
                ->add('auteur', TextType::class)
                ->add('ISBN', TextType::class)
                ->add('nbPages', IntegerType::class)
                ->add('dateDePublication', TextType::class);
        }

        return $builder->getForm();
    }

    /**
     * Returns an article as an array, or an empty array if not found.
     */
    private function findArticleAsArray(string $id): array
    {
        return $this->entityManager
            ->createQuery("SELECT a FROM App\Entity\Catalogue\Article a WHERE a.id = :id")
            ->setParameter('id', $id)
            ->getArrayResult();
    }

    /**
     * Returns the Article entity or null if not found.
     */
    private function findArticleEntity(string $id): ?Article
    {
        return $this->entityManager
            ->getRepository(Article::class)
            ->find($id);
    }

    /**
     * Extracts form errors into a readable array.
     */
    private function getFormErrors(\Symfony\Component\Form\FormInterface $form): array
    {
        $errors = [];
        foreach ($form->getErrors(true) as $error) {
            $errors[] = $error->getMessage();
        }
        return $errors;
    }
}
