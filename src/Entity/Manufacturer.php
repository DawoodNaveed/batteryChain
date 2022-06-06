<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\OneToMany;

/**
 * Class Manufacturer
 * @package App\Entity
 * @ORM\Entity(repositoryClass="App\Repository\ManufacturerRepository")
 * @ORM\Table(name="manufacturer")
 */
class Manufacturer extends AbstractEntity
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
     * @var string|null
     * @ORM\Column(name="country", type="string", length=50, nullable="true")
     */
    private $country;

    /**
     * @var boolean
     * @ORM\Column(name="status", type="boolean", options={"default"=true})
     */
    protected $status = true;

    /**
     * @var User|null
     * @ORM\OneToOne(targetEntity="User", inversedBy="manufacturer", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    private $user;

    /**
     * Many Manufacturers have Many Distributors.
     * @ManyToMany(targetEntity="App\Entity\Distributor", inversedBy="manufacturers")
     * @JoinTable(name="manufacturers_distributors")
     */
    private $distributors;

    /**
     * Many Manufacturers have Many Recyclers.
     * @ManyToMany(targetEntity="App\Entity\Recycler", inversedBy="manufacturers")
     * @JoinTable(name="manufacturers_recyclers")
     */
    private $recyclers;

    /**
     * One Manufacturer has many batteries.
     * @OneToMany(targetEntity="App\Entity\Battery", mappedBy="manufacturer")
     */
    private $batteries;

    /**
     * Manufacturer constructor.
     */
    public function __construct() {
        $this->distributors = new ArrayCollection();
        $this->recyclers = new ArrayCollection();
        $this->batteries = new ArrayCollection();
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
     * @return string|null
     */
    public function getCountry(): ?string
    {
        return $this->country;
    }

    /**
     * @param string|null $country
     */
    public function setCountry(?string $country): void
    {
        $this->country = $country;
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
     * @return Collection|Distributor[]
     */
    public function getDistributors(): Collection
    {
        return $this->distributors;
    }

    /**
     * @param Distributor $distributor
     * @return $this
     */
    public function addDistributor(Distributor $distributor): self
    {
        if (!$this->distributors->contains($distributor)) {
            $this->distributors[] = $distributor;
        }

        return $this;
    }

    /**
     * @param Distributor $distributor
     * @return $this
     */
    public function removeDistributor(Distributor $distributor): self
    {
        $this->distributors->removeElement($distributor);

        return $this;
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
     * @return Collection|Battery[]
     */
    public function getBatteries(): Collection
    {
        return $this->batteries;
    }

    /**
     * @param Battery $battery
     * @return $this
     */
    public function addBattery(Battery $battery): self
    {
        if (!$this->batteries->contains($battery)) {
            $this->batteries[] = $battery;
        }

        return $this;
    }

    /**
     * @param Battery $battery
     * @return $this
     */
    public function removeBattery(Battery $battery): self
    {
        $this->batteries->removeElement($battery);

        return $this;
    }
}
