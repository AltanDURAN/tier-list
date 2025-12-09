<?php

namespace App\Entity;

use App\Repository\TierItemRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TierItemRepository::class)]
#[ORM\Table(name: "tieritem", indexes: [
    new ORM\Index(name: "tieritem_position_idx", columns: ["position"])
])]
class TierItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $image = null;

    #[ORM\Column(nullable: true)]
    private ?int $position = null;

    #[ORM\ManyToOne(inversedBy: 'tierItems')]
    private ?Tier $tier = null;

    #[ORM\ManyToOne(inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false)]
    private ?TierList $tierList = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(string $image): static
    {
        $this->image = $image;

        return $this;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(?int $position): static
    {
        $this->position = $position;

        return $this;
    }

    public function getTier(): ?Tier
    {
        return $this->tier;
    }

    public function setTier(?Tier $tier): static
    {
        $this->tier = $tier;

        return $this;
    }

    public function getTierList(): ?TierList
    {
        return $this->tierList;
    }

    public function setTierList(?TierList $tierList): static
    {
        $this->tierList = $tierList;

        return $this;
    }
}
