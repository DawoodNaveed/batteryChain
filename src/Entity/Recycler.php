<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\ManyToOne;

/**
 * Class Recycler
 * @package App\Entity
 * @ORM\Entity()
 * @ORM\Table()
 */
class Recycler extends AbstractEntity
{
    /**
     * @var string|null
     * @ORM\Column(name="name", type="string", length=255, nullable="true")
     */
    private $name;

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
     * @var User|null
     * @ORM\OneToOne(targetEntity="User", inversedBy="recycler", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    private $user;

    /**
     * Many Recyclers have Many Manufacturers.
     * @ManyToMany(targetEntity="App\Entity\Manufacturer", mappedBy="recyclers")
     */
    private $manufacturers;

    /**
     * Recycler constructor.
     */
    public function __construct() {
        $this->manufacturers = new ArrayCollection();
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
     * @return User|null
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * @param User|null $user
     */
    public function setUser(?User $user): void
    {
        $this->user = $user;
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
}
