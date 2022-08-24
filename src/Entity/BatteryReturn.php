<?php

namespace App\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Class BatteryReturn
 * @package App\Entity
 * @ORM\Entity()
 * @ORM\Table(name="battery_return")
 * @Gedmo\SoftDeleteable(fieldName="deletedAt")
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
     * @var Recycler|null
     * Many Returns have one Recycler. This is the owning side.
     * @ManyToOne(targetEntity="App\Entity\Recycler", inversedBy="returnsTo")
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
     * @var \DateTime|null
     * @ORM\Column(name="deleted_at", type="datetime", nullable=true)
     */
    private $deletedAt;

    /**
     * @var TransactionLog|null
     * @ORM\OneToOne(targetEntity="App\Entity\TransactionLog", inversedBy="batteryReturn", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="transaction_log_id", referencedColumnName="id")
     */
    private $transactionLog;

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
     * @return Recycler|null
     */
    public function getReturnTo(): ?Recycler
    {
        return $this->returnTo;
    }

    /**
     * @param Recycler|null $returnTo
     */
    public function setReturnTo(?Recycler $returnTo): void
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

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->battery->getSerialNumber();
    }

    /**
     * @return TransactionLog|null
     */
    public function getTransactionLog(): ?TransactionLog
    {
        return $this->transactionLog;
    }

    /**
     * @param TransactionLog|null $transactionLog
     */
    public function setTransactionLog(?TransactionLog $transactionLog): void
    {
        $this->transactionLog = $transactionLog;
    }
}