<?php

namespace App\Entity\Catalogue;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity]
class Musique extends Article
{
    #[ORM\Column(length: 255,name: 'artiste')]
    private ?string $artiste = null;

    #[ORM\Column(length: 255, name: 'date_de_sortie')]
    private ?string $dateDeSortie = null;
	
    #[ORM\OneToMany(targetEntity: "Piste", mappedBy: "musique")]
    private ?Collection $pistes = null;
	
	public function __construct()
	{
		$this->pistes = new ArrayCollection();
	}
	
    public function getArtiste(): ?string
    {
        return $this->artiste;
    }

    public function setArtiste(?string $artiste): static
    {
        $this->artiste = $artiste;

        return $this;
    }

    public function getDateDeSortie(): ?string
    {
        return $this->dateDeSortie;
    }

    public function setDateDeSortie(?string $dateDeSortie): static
    {
        $this->dateDeSortie = $dateDeSortie;

        return $this;
    }
	
    public function getPistes(): ?array
    {
		if($this->pistes == null) {
			return null ;
		}
		else {
			return $this->pistes->toArray();
		}
    }
	
	public function addPiste(Piste $piste): void
	{
		$piste->setMusique($this);
		$this->pistes->add($piste);
	}
}

