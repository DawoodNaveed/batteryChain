<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\OneToMany;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Class BatteryType
 * @package App\Entity
 * @ORM\Entity()
 * @ORM\Table(name="battery_type")
 * @Gedmo\SoftDeleteable(fieldName="deletedAt")
 */
class BatteryType extends AbstractEntity
{
    /**
     * One Battery Type has many Batteries.
     * @OneToMany(targetEntity="App\Entity\Battery", mappedBy="batteryType")
     */
    private $batteries;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true, length=50, options={"default"=null})
     */
    private $type;

    /**
     * @var boolean
     * @ORM\Column(name="status", type="boolean", options={"default"=true})
     */
    private $status = true;

    /**
     * @var \DateTime|null
     * @ORM\Column(name="deleted_at", type="datetime", nullable=true)
     */
    private $deletedAt;

    public function __construct()
    {
        $this->created = new \DateTime('now');
        $this->updated = new \DateTime('now');
        $this->batteries = new ArrayCollection();
    }

    /**
     * @return Collection|TransactionLog[]
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

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * @return bool
     */
    public function isStatus(): bool
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
     * @return string
     */
    public function __toString(): string
    {
        return $this->type;
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
