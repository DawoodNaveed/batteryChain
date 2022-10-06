<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\JoinColumn;

/**
 * Class ModifiedBattery
 * @package App\Entity
 * @ORM\Entity()
 * @ORM\Table()
 */
class ModifiedBattery extends AbstractEntity
{
    /**
     * @var Battery|null
     * Many modified batteries have one Battery. This is the owning side.
     * @ManyToOne(targetEntity="App\Entity\Battery", inversedBy="modifications")
     * @JoinColumn(name="battery_id", referencedColumnName="id")
     */
    private $battery;

    /**
     * @var Manufacturer|null
     * Many modified batteries have one Manufacture. This is the owning side.
     * @ManyToOne(targetEntity="App\Entity\Manufacturer", inversedBy="modifiedBatteries")
     * @JoinColumn(name="manufacturer_id", referencedColumnName="id")
     */
    private $manufacturer;

    /**
     * @var string|null
     * @ORM\Column(name="action", type="string", length=255, nullable="true")
     */
    private $action;

    /**
     * @var User|null
     * Many modified batteries have one Modifier User. This is the owning side.
     * @ManyToOne(targetEntity="App\Entity\User", inversedBy="modifiedBatteries")
     * @JoinColumn(name="modified_by", referencedColumnName="id")
     */
    private $modifiedBy;

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
     * @return Manufacturer|null
     */
    public function getManufacturer(): ?Manufacturer
    {
        return $this->manufacturer;
    }

    /**
     * @param Manufacturer|null $manufacturer
     */
    public function setManufacturer(?Manufacturer $manufacturer): void
    {
        $this->manufacturer = $manufacturer;
    }

    /**
     * @return string|null
     */
    public function getAction(): ?string
    {
        return $this->action;
    }

    /**
     * @param string|null $action
     */
    public function setAction(?string $action): void
    {
        $this->action = $action;
    }

    /**
     * @return User|null
     */
    public function getModifiedBy(): ?User
    {
        return $this->modifiedBy;
    }

    /**
     * @param User|null $modifiedBy
     */
    public function setModifiedBy(?User $modifiedBy): void
    {
        $this->modifiedBy = $modifiedBy;
    }
}