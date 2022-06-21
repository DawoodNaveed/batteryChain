<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Class Recycler
 * @package App\Entity
 * @ORM\Entity()
 * @ORM\Table()
 * @UniqueEntity(fields={"email"}, message="There is already a recycler with this email")
 * @Gedmo\SoftDeleteable(fieldName="deletedAt")
 */
class Recycler extends AbstractEntity
{
    /**
     * @var string|null
     * @ORM\Column(name="name", type="string", length=255, nullable="true")
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=180, unique=true)
     */
    private $email;

    /**
     * @var string|null
     * @ORM\Column(name="address", type="text", nullable="true")
     */
    private $address;

    /**
     * @var string|null
     * @ORM\Column(name="contact", type="string", length=50, nullable="true")
     */
    private $contact;

    /**
     * @var string|null
     * @ORM\Column(name="city", type="string", length=50, nullable="true")
     */
    private $city;

    /**
     * @var Country|null
     * Many Recyclers have one Country. This is the owning side.
     * @ManyToOne(targetEntity="App\Entity\Country", inversedBy="recyclers")
     * @JoinColumn(name="country_id", referencedColumnName="id")
     */
    private $country;

    /**
     * @var boolean
     * @ORM\Column(name="status", type="boolean", options={"default"=true})
     */
    protected $status = true;

    /**
     * Many Recyclers have Many Manufacturers.
     * @ManyToMany(targetEntity="App\Entity\Manufacturer", mappedBy="recyclers")
     */
    private $manufacturers;

    /**
     * One Recycler has many returns.
     * @OneToMany(targetEntity="App\Entity\BatteryReturn", mappedBy="returnTo")
     */
    private $returnsTo;

    /**
     * @var \DateTime|null
     * @ORM\Column(name="deleted_at", type="datetime", nullable=true)
     */
    private $deletedAt;

    /**
     * Recycler constructor.
     */
    public function __construct() {
        $this->manufacturers = new ArrayCollection();
        $this->returnsTo = new ArrayCollection();
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
     * @return string|null
     */
    public function getAddress(): ?string
    {
        return $this->address;
    }

    /**
     * @param string|null $address
     */
    public function setAddress(?string $address): void
    {
        $this->address = $address;
    }

    /**
     * @return string|null
     */
    public function getContact(): ?string
    {
        return $this->contact;
    }

    /**
     * @param string|null $contact
     */
    public function setContact(?string $contact): void
    {
        $this->contact = $contact;
    }

    /**
     * @return string|null
     */
    public function getCity(): ?string
    {
        return $this->city;
    }

    /**
     * @param string|null $city
     */
    public function setCity(?string $city): void
    {
        $this->city = $city;
    }

    /**
     * @return bool
     */
    public function getStatus(): bool
    {
        return $this->status;
    }

    /**
     * @param bool $status
     */
    public function setStatus(bool $status): void
    {
        $this->status = $status;
    }

    /**
     * @return Collection
     */
    public function getManufacturers(): Collection
    {
        return $this->manufacturers;
    }

    /**
     * @param Manufacturer $manufacturer
     * @return $this
     */
    public function addManufacturer(Manufacturer $manufacturer): self
    {
        if (!$this->manufacturers->contains($manufacturer)) {
            $this->manufacturers[] = $manufacturer;
            $manufacturer->addRecycler($this);
        }
        return $this;
    }

    /**
     * @param Manufacturer $manufacturer
     * @return $this
     */
    public function removeManufacturer(Manufacturer $manufacturer): self
    {
        if ($this->manufacturers->removeElement($manufacturer)) {
            $manufacturer->removeRecycler($this);
        }
        return $this;
    }

    /**
     * @return $this
     */
    public function removeAllManufacturers(): self
    {
        /** @var Manufacturer $manufacturer */
        foreach ($this->manufacturers as $manufacturer) {
            $manufacturer->removeRecycler($this);
        }

        return $this;
    }

    /**
     * @return Country|null
     */
    public function getCountry(): ?Country
    {
        return $this->country;
    }

    /**
     * @param Country|null $country
     */
    public function setCountry(?Country $country): void
    {
        $this->country = $country;
    }

    /**
     * @return string|null
     */
    public function __toString(): ?string
    {
        return $this->name;
    }

    /**
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @param string $email
     * @return $this
     */
    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return Collection|BatteryReturn[]
     */
    public function getReturnsTo(): Collection
    {
        return $this->returnsTo;
    }

    /**
     * @param BatteryReturn $return
     * @return $this
     */
    public function addReturnsTo(BatteryReturn $return): self
    {
        if (!$this->returnsTo->contains($return)) {
            $this->returnsTo[] = $return;
        }

        return $this;
    }

    /**
     * @param BatteryReturn $return
     * @return $this
     */
    public function removeReturnsTo(BatteryReturn $return): self
    {
        $this->returnsTo->removeElement($return);

        return $this;
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
