<?php
/**
 * @file        installerBaseDeDonneesDoctrine3.php
 * @description Installation de la base de données d'un projet Symfony
 * @author      Alain Corbière <alain.corbiere@univ-lemans.fr>
 * @version     1.0.0
 * @since       22/04/2026
 *
 * @copyright   Copyright (c) 2026
 * @license     Propriétaire — usage non autorisé sans accord écrit
 *
 * Ce fichier est protégé par le droit d'auteur et ne peut être
 * reproduit, distribué ou modifié sans autorisation explicite.
 *
 * @requires    Doctrine ORM ^3.0
 * @requires    driver pdo_mysql >= 8.0
 */

header("Content-type: text/plain") ;
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
ob_end_clean();
ob_implicit_flush(true);

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} elseif (file_exists(dirname(__DIR__) . '/vendor/autoload.php')) {
    require_once dirname(__DIR__) . '/vendor/autoload.php';
} else {
    die('Erreur : fichier vendor/autoload.php introuvable (ni dans ' . __DIR__ . ' ni dans ' . dirname(__DIR__) . ')');
}

use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\DBAL\DriverManager;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Psr\Log\NullLogger;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Finder\Finder;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasher;
use App\Entity\User;

// ── 1. Environnement ──────────────────────────────────────────────────────────
(new Dotenv())->load(__DIR__ . '/.env');

$databaseUrl = $_ENV['DATABASE_URL'] ?? null;
if (!$databaseUrl) {
    die('DATABASE_URL n\'est pas défini dans .env');
}

// ── 2. Connexion ──────────────────────────────────────────────────────────────
$parsedUrl = parse_url($databaseUrl);
$scheme    = $parsedUrl['scheme'] ?? '';

$driverMap = [
    'mysql'  => 'pdo_mysql',
];

if (!isset($driverMap[$scheme])) {
    die("Driver '{$scheme}' non supporté. Utilisez mysql.");
}

$dbParams = [
        'driver'   => 'pdo_mysql',
        'user'     => $parsedUrl['user'],
        'password' => $parsedUrl['pass'],
        'dbname'   => trim($parsedUrl['path'], '/'),
        'host'     => $parsedUrl['host'],
        'port'     => $parsedUrl['port'] ?? 3306,
        'charset'  => 'utf8mb4',
    ];

// ── 3. EntityManager ──────────────────────────────────────────────────────────
$config = ORMSetup::createAttributeMetadataConfiguration(
    [__DIR__ . '/src/Entity'],
    true
);
$config->setNamingStrategy(new UnderscoreNamingStrategy());

$connection    = DriverManager::getConnection($dbParams, $config);
$entityManager = new EntityManager($connection, $config);

// ── 4. Schéma ─────────────────────────────────────────────────────────────────
echo("Suppression du schéma...\n");
flush();
$schemaTool = new SchemaTool($entityManager);
$metadatas  = $entityManager->getMetadataFactory()->getAllMetadata();

if (empty($metadatas)) {
    die('Aucune entité trouvée dans src/Entity');
}

$schemaTool->dropDatabase();
$schemaTool->createSchema($metadatas);
echo("Schéma créé\n");
flush();

// ── 5. Fixtures ───────────────────────────────────────────────────────────────
$passwordHasherFactory = new PasswordHasherFactory([
    User::class => ['algorithm' => 'auto'],
]);
$userPasswordHasher = new UserPasswordHasher($passwordHasherFactory);

$loader = new Loader();
$finder = (new Finder())
    ->files()
    ->in(__DIR__ . '/src/DataFixtures')
    ->name('*Fixtures.php');

foreach ($finder as $file) {
    $className   = 'App\\DataFixtures\\' . $file->getBasename('.php');
    $reflection  = new \ReflectionClass($className);
    $constructor = $reflection->getConstructor();
    $args        = [];

    foreach ($constructor?->getParameters() ?? [] as $param) {
        $type   = $param->getType()?->getName();
        $args[] = match (true) {
            is_a($type, UserPasswordHasher::class, true) => $userPasswordHasher,
            is_a($type, 'Psr\Log\LoggerInterface', true) => new NullLogger(),
            $param->isOptional()                         => $param->getDefaultValue(),
            default                                      => null,
        };
    }

    $loader->addFixture($reflection->newInstanceArgs($args));
    echo("Fixture chargée : {$className}\n");
	flush();
}

// ── 6. Exécution ──────────────────────────────────────────────────────────────
(new ORMExecutor($entityManager))->execute($loader->getFixtures(), true);

echo("\nBase de données installée avec succès !\n");