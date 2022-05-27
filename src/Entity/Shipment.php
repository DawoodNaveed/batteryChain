<?php

namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;

/**
 * Class Shipment
 * @package App\Entity
 * @ORM\Entity()
 * @ORM\Table()
 */
class Shipment extends AbstractEntity
{
    /**
     * @var Battery|null
     * Many shipments have one Batter. This is the owning side.
     * @ManyToOne(targetEntity="App\Entity\Battery", inversedBy="shipments")
     * @JoinColumn(name="battery_id", referencedColumnName="id")
     */
    private $battery;

    /**
     * @var User|null
     * Many shipments have one User. This is the owning side.
     * @ManyToOne(targetEntity="App\Entity\User", inversedBy="shipmentsTo")
     * @JoinColumn(name="shipment_to", referencedColumnName="id")
     */
    private $shipmentTo;

    /**
     * @var User|null
     * Many shipments have one Batter. This is the owning side.
     * @ManyToOne(targetEntity="App\Entity\User", inversedBy="shipmentsFrom")
     * @JoinColumn(name="shipment_from", referencedColumnName="id")
     */
    private $shipmentFrom;

    /**
     * @var DateTime|null
     * @ORM\Column(name="shipment_date", type="datetime", nullable="true")
     */
    private $shipmentDate;

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
    public function getShipmentTo(): ?User
    {
        return $this->shipmentTo;
    }

    /**
     * @param User|null $shipmentTo
     */
    public function setShipmentTo(?User $shipmentTo): void
    {
        $this->shipmentTo = $shipmentTo;
    }

    /**
     * @return User|null
     */
    public function getShipmentFrom(): ?User
    {
        return $this->shipmentFrom;
    }

    /**
     * @param User|null $shipmentFrom
     */
    public function setShipmentFrom(?User $shipmentFrom): void
    {
        $this->shipmentFrom = $shipmentFrom;
    }

    /**
     * @return DateTime|null
     */
    public function getShipmentDate(): ?DateTime
    {
        return $this->shipmentDate;
    }

    /**
     * @param DateTime|null $shipmentDate
     */
    public function setShipmentDate(?DateTime $shipmentDate): void
    {
        $this->shipmentDate = $shipmentDate;
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
}
