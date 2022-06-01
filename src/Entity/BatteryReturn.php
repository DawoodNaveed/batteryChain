<?php

namespace App\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;

/**
 * Class BatteryReturn
 * @package App\Entity
 * @ORM\Entity()
 * @ORM\Table(name="return")
 */
class BatteryReturn extends AbstractEntity
{
    /**
     * @var Battery|null
     * Many returns have one Battery. This is the owning side.
     * @ManyToOne(targetEntity="App\Entity\Battery", inversedBy="returns")
     * @JoinColumn(name="battery_id", referencedColumnName="id")
     */
    private $battery;

    /**
     * @var User|null
     * Many Returns have one User. This is the owning side.
     * @ManyToOne(targetEntity="App\Entity\User", inversedBy="returnsTo")
     * @JoinColumn(name="return_to", referencedColumnName="id")
     */
    private $returnTo;

    /**
     * @var User|null
     * Many Returns have one User. This is the owning side.
     * @ManyToOne(targetEntity="App\Entity\User", inversedBy="returnsFrom")
     * @JoinColumn(name="return_from", referencedColumnName="id")
     */
    private $returnFrom;

    /**
     * @var DateTime|null
     * @ORM\Column(name="return_date", type="datetime", nullable="true")
     */
    private $returnDate;

    /**
     * @var string|null
     * @ORM\Column(name="address", type="text", nullable="true")
     */
    private $address;

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
     * One Return has many transaction Logs.
     * @OneToMany(targetEntity="App\Entity\TransactionLog", mappedBy="returns")
     */
    private $transactionLogs;

    /**
     * BatteryReturn constructor.
     */
    public function __construct()
    {
        $this->transactionLogs = new ArrayCollection();
    }

    /**
     * @return Battery|null
     */
    public function getBattery(): ?Battery
    {
        return $this->battery;
    }

    /**
     * @param Battery|null $battery
     */
    public function setBattery(?Battery $battery): void
    {
        $this->battery = $battery;
    }

    /**
     * @return User|null
     */
    public function getReturnTo(): ?User
    {
        return $this->returnTo;
    }

    /**
     * @param User|null $returnTo
     */
    public function setReturnTo(?User $returnTo): void
    {
        $this->returnTo = $returnTo;
    }

    /**
     * @return User|null
     */
    public function getReturnFrom(): ?User
    {
        return $this->returnFrom;
    }

    /**
     * @param User|null $returnFrom
     */
    public function setReturnFrom(?User $returnFrom): void
    {
        $this->returnFrom = $returnFrom;
    }

    /**
     * @return DateTime|null
     */
    public function getReturnDate(): ?DateTime
    {
        return $this->returnDate;
    }

    /**
     * @param DateTime|null $returnDate
     */
    public function setReturnDate(?DateTime $returnDate): void
    {
        $this->returnDate = $returnDate;
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
     * @return Collection|TransactionLog[]
     */
    public function getTransactionLogs(): Collection
    {
        return $this->transactionLogs;
    }

    /**
     * @param TransactionLog $transactionLog
     * @return $this
     */
    public function addTransactionLog(TransactionLog $transactionLog): self
    {
        if (!$this->transactionLogs->contains($transactionLog)) {
            $this->transactionLogs[] = $transactionLog;
        }

        return $this;
    }

    /**
     * @param TransactionLog $transactionLog
     * @return $this
     */
    public function removeTransactionLog(TransactionLog $transactionLog): self
    {
        $this->transactionLogs->removeElement($transactionLog);

        return $this;
    }
}