<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

use App\Entity\User;

use GuzzleHttp\Client;

use App\Entity\Catalogue\Livre;
use App\Entity\Catalogue\Musique;
use App\Entity\Catalogue\Piste;

use Psr\Log\LoggerInterface;

class AppFixtures extends Fixture
{
	protected $logger;
	protected $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher, LoggerInterface $logger)
    {
        $this->logger = $logger;
		$this->passwordHasher = $passwordHasher;
    }
	
    public function load(ObjectManager $manager): void
    {
		$admin = new User();
		$admin->setEmail('test@test.test');
		$admin->setRoles(['ROLE_ADMIN']);
		$admin->setPassword($this->passwordHasher->hashPassword($admin, 'test'));
		$manager->persist($admin);
		$manager->flush();
		
		if (count($manager->getRepository("App\Entity\Catalogue\Article")->findAll()) == 0) {
			$ebay = new Ebay($this->logger);
			$ebay->setCategory('CDs');
			$keywords = 'U2' ;
			//$ebay->setCategory('Livres');
			//$keywords = 'Harry Potter' ;

			$itemSummaries = $ebay->searchItemSummaries($keywords, 6);
			
			if ($itemSummaries !== false) {
				foreach ($itemSummaries as $itemSummary) {
					// https://developer.ebay.com/api-docs/buy/browse/resources/item/methods/getItem
					// $itemSummary["itemId"] = "v1|167503443270|0"
					$id = explode("|", $itemSummary["itemId"])[1] ;
					if ($ebay->categoryInCategories('Livres', $itemSummary["categories"])) {
						$entityLivre = new Livre();
						$entityLivre->setId((int) $id);
						$entityLivre->setTitre($itemSummary["title"]);
						$entityLivre->setAuteur($ebay->getItem("Auteur", $id));
						$entityLivre->setISBN( $ebay->getItem("ISBN", $id));
						$entityLivre->setDateDePublication( $ebay->getItem("Année de publication", $id));
						$entityLivre->setPrix((float) $itemSummary["price"]["value"]);
						$entityLivre->setDisponibilite(1);
						$entityLivre->setImage($itemSummary["image"]["imageUrl"]);
						$manager->persist($entityLivre);
						$manager->flush();
					}
					if ($ebay->categoryInCategories('CDs', $itemSummary["categories"])) {
						$entityMusique = new Musique();
						$entityMusique->setId((int) $id);
						$entityMusique->setTitre($itemSummary["title"]);
						$entityMusique->setArtiste($ebay->getItem("Artiste", $id));
						$entityMusique->setDateDeSortie( $ebay->getItem("Année de sortie", $id));
						$entityMusique->setPrix((float) $itemSummary["price"]["value"]);
						$entityMusique->setDisponibilite(1);
						$entityMusique->setImage($itemSummary["image"]["imageUrl"]);
						$appleITune = new AppleITune($this->logger);
						$albums = $appleITune->searchAlbumsByArtist($keywords) ;
						foreach ($albums as $album) {
									
							$wordsNameAlbum = $this->extractWords($album->name);
							$wordsTitleEntityMusique = $this->extractWords($entityMusique->getTitre());
							
							$stopWords = [
								// French
								'le', 'la', 'les', 'un', 'une', 'des', 'du', 'de', 'au', 'aux',
								'et', 'ou', 'en', 'dans', 'sur', 'sous', 'avec', 'sans', 'par',
								'pour', 'que', 'qui', 'quoi', 'dont', 'ou', 'car', 'mais', 'donc',
								'or', 'ni', 'ce', 'cet', 'cette', 'ces', 'mon', 'ma', 'mes', 'son',
								'sa', 'ses', 'notre', 'votre', 'leur', 'leurs', 'y', 'il', 'elle',
								'ils', 'elles', 'je', 'tu', 'nous', 'vous', 'on', 'se', 'si', 'ne',
								'pas', 'plus', 'est', 'a', 'd',
								// English
								'the', 'a', 'an', 'and', 'or', 'in', 'on', 'at', 'to', 'of',
								'for', 'by', 'with', 'from', 'is', 'it', 'its', 'this', 'that',
								'as', 'be', 'was', 'are', 'not', 'no',
							];
							
							$excludedWords = array_merge(
								$this->extractWords($keywords),
								$stopWords
							);

							$wordsNameAlbum= array_diff($wordsNameAlbum, $excludedWords);
							$wordsTitleEntityMusique = array_diff($wordsTitleEntityMusique, $excludedWords);

							// Intersection: words present in both
							$common = array_intersect($wordsNameAlbum, $wordsTitleEntityMusique);
							if (count($common) > 0) {
								$tracks = $appleITune->searchTracksByAlbum($album->id) ;
								foreach ($tracks as $track) {
									$entityPiste = new Piste();
									$entityPiste->setTitre($track->name);
									$entityPiste->setMp3($track->preview_url);
									$manager->persist($entityPiste);
									$manager->flush();
									$entityMusique->addPiste($entityPiste) ;
								}
								break;
							}
						}
						$manager->persist($entityMusique);
						$manager->flush();
					}
				}
			}
			$entityLivre = new Livre();
			$entityLivre->setId(55677821);
			$entityLivre->setTitre("Le seigneur des anneaux");
			$entityLivre->setAuteur("J.R.R. TOLKIEN");
			$entityLivre->setISBN("2075134049");
			$entityLivre->setNbPages(736);
			$entityLivre->setDateDePublication("03/10/19");
			$entityLivre->setPrix("8.90");
			$entityLivre->setDisponibilite(1);
			$entityLivre->setImage("images/51O0yBHs+OL._SL140_.jpg");
			$manager->persist($entityLivre);
			$entityLivre = new Livre();
			$entityLivre->setId(55897821);
			$entityLivre->setTitre("Un paradis trompeur");
			$entityLivre->setAuteur("Henning Mankell");
			$entityLivre->setISBN("275784797X");
			$entityLivre->setNbPages(400);
			$entityLivre->setDateDePublication("09/10/14");
			$entityLivre->setPrix("6.80");
			$entityLivre->setDisponibilite(1);
			$entityLivre->setImage("images/71uwoF4hncL._SL140_.jpg");
			$manager->persist($entityLivre);
			$entityLivre = new Livre();
			$entityLivre->setId(56299459);
			$entityLivre->setTitre("Dôme tome 1");
			$entityLivre->setAuteur("Stephen King");
			$entityLivre->setISBN("2212110685");
			$entityLivre->setNbPages(840);
			$entityLivre->setDateDePublication("06/03/13");
			$entityLivre->setPrix("8.90");
			$entityLivre->setDisponibilite(1);
			$entityLivre->setImage("images/719FffADQAL._SL140_.jpg");
			$manager->persist($entityLivre);
			$manager->flush();
		}
    }
	
	private function extractWords(string $text): array {
        $text = strtolower($text);
        $text = iconv('UTF-8', 'ASCII//TRANSLIT', $text); // remove accents
        $text = preg_replace('/[^a-z0-9\s]/', '', $text); // remove punctuation
        return array_filter(explode(' ', $text));          // split into words
    }
}
