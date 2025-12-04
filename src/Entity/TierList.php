<?php

namespace App\Entity;

use App\Repository\TierListRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TierListRepository::class)]
#[ORM\HasLifecycleCallbacks]
class TierList
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: 'tierLists')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $owner = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, Tier>
     */
    #[ORM\OneToMany(targetEntity: Tier::class, mappedBy: 'tierList', orphanRemoval: true, cascade: ['persist'])]
    private Collection $tiers;

    /**
     * @var Collection<int, TierItem>
     */
    #[ORM\OneToMany(targetEntity: TierItem::class, mappedBy: 'tierList')]
    private Collection $items;

    public function __construct()
    {
        $this->tiers = new ArrayCollection();
        $this->items = new ArrayCollection();
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

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * @return Collection<int, Tier>
     */
    public function getTiers(): Collection
    {
        return $this->tiers;
    }

    public function addTier(Tier $tier): static
    {
        if (!$this->tiers->contains($tier)) {
            $this->tiers->add($tier);
            $tier->setTierList($this);
        }

        return $this;
    }

    public function removeTier(Tier $tier): static
    {
        if ($this->tiers->removeElement($tier)) {
            foreach ($tier->getTierItems() as $item) {
                $item->setTier(null);
            }

            // set the owning side to null (unless already changed)
            if ($tier->getTierList() === $this) {
                $tier->setTierList(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, TierItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(TierItem $item): static
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setTierList($this);
        }

        return $this;
    }

    public function removeItem(TierItem $item): static
    {
        if ($this->items->removeElement($item)) {
            // set the owning side to null (unless already changed)
            if ($item->getTierList() === $this) {
                $item->setTierList(null);
            }
        }

        return $this;
    }
}
