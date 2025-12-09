<?php

namespace App\Entity;

use App\Repository\TierRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TierRepository::class)]
#[ORM\Table(name: "tier", indexes: [
    new ORM\Index(name: "tier_position_idx", columns: ["position"])
])]
class Tier
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column]
    private ?int $position = null;

    /**
     * @var Collection<int, TierItem>
     */
    #[ORM\OneToMany(targetEntity: TierItem::class, mappedBy: 'tier')]
    private Collection $tierItems;

    #[ORM\ManyToOne(inversedBy: 'tiers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?TierList $tierList = null;

    #[ORM\Column(length: 7, nullable: true)]
    private ?string $color = null;

    public function __construct()
    {
        $this->tierItems = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

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

    /**
     * @return Collection<int, TierItem>
     */
    public function getTierItems(): Collection
    {
        return $this->tierItems;
    }

    public function addTierItem(TierItem $tierItem): static
    {
        if (!$this->tierItems->contains($tierItem)) {
            $this->tierItems->add($tierItem);
            $tierItem->setTier($this);
        }

        return $this;
    }

    public function removeTierItem(TierItem $tierItem): static
    {
        if ($this->tierItems->removeElement($tierItem)) {
            // set the owning side to null (unless already changed)
            if ($tierItem->getTier() === $this) {
                $tierItem->setTier(null);
            }
        }

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

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): static
    {
        $this->color = $color;

        return $this;
    }
}
