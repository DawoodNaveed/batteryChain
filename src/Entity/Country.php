<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\OneToMany;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Class Country
 * @package App\Entity
 * @ORM\Entity()
 * @ORM\Table()
 * @Gedmo\SoftDeleteable(fieldName="deletedAt")
 */
class Country extends AbstractEntity
{
    /**
     * @var string|null
     * @ORM\Column(name="name", nullable="true", type="string", length=255)
     */
    protected $name;

    /**
     * @var
     * @ORM\Column(name="zip_code", nullable="true", type="string", length=50)
     */
    protected $zipCode;

    /**
     * @var boolean|null
     * @ORM\Column(name="status", type="boolean", options={"default"=true})
     */
    protected $status;

    /**
     * One Country has many Recyclers.
     * @OneToMany(targetEntity="App\Entity\Recycler", mappedBy="country")
     */
    private $recyclers;

    /**
     * @var \DateTime|null
     * @ORM\Column(name="deleted_at", type="datetime", nullable=true)
     */
    private $deletedAt;

    /**
     * Country constructor.
     */
    public function __construct()
    {
        $this->recyclers = new ArrayCollection();
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string|null $name
     */
    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getZipCode()
    {
        return $this->zipCode;
    }

    /**
     * @param mixed $zipCode
     */
    public function setZipCode($zipCode): void
    {
        $this->zipCode = $zipCode;
    }

    /**
     * @return bool|null
     */
    public function getStatus(): ?bool
    {
        return $this->status;
    }

    /**
     * @param bool|null $status
     */
    public function setStatus(?bool $status): void
    {
        $this->status = $status;
    }

    /**
     * @return Collection|Recycler[]
     */
    public function getRecyclers(): Collection
    {
        return $this->recyclers;
    }

    /**
     * @param Recycler $recycler
     * @return $this
     */
    public function addRecycler(Recycler $recycler): self
    {
        if (!$this->recyclers->contains($recycler)) {
            $this->recyclers[] = $recycler;
        }

        return $this;
    }

    /**
     * @param Recycler $recycler
     * @return $this
     */
    public function removeRecycler(Recycler $recycler): self
    {
        $this->recyclers->removeElement($recycler);

        return $this;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->name;
    }

    /**
     * @return \DateTime|null
     */
    public function getDeletedAt(): ?\DateTime
    {
        return $this->deletedAt;
    }

    /**
     * @param \DateTime|null $deletedAt
     */
    public function setDeletedAt(?\DateTime $deletedAt)
    {
        $this->deletedAt = $deletedAt;
    }
}