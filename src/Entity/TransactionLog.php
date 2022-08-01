<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Class TransactionLog
 * @package App\Entity
 * @ORM\Entity()
 * @ORM\Table()
 * @Gedmo\SoftDeleteable(fieldName="deletedAt")
 */
class TransactionLog extends AbstractEntity
{
    /**
     * @var Battery|null
     * Many Transaction Logs have one Battery. This is the owning side.
     * @ManyToOne(targetEntity="App\Entity\Battery", inversedBy="transactionLogs")
     * @JoinColumn(name="battery_id", referencedColumnName="id")
     */
    private $battery;

    /**
     * @var string|null
     * @ORM\Column(name="transaction_hash", nullable="true", type="string", length=255)
     */
    private $transactionHash;

    /**
     * @var string|null
     * @ORM\Column(name="transaction_type", nullable="true", type="string", length=50)
     */
    private $transactionType;

    /**
     * @var string|null
     * @ORM\Column(name="status", nullable="true", type="string", length=50)
     */
    private $status;

    /**
     * @var string|null
     * @ORM\Column(type="text", unique=false, nullable=true, options={"default"=null})
     */
    private $description;

    /**
     * @var \DateTime|null
     * @ORM\Column(name="deleted_at", type="datetime", nullable=true)
     */
    private $deletedAt;

    /**
     * @var BatteryReturn|null
     * @ORM\OneToOne(targetEntity="App\Entity\BatteryReturn", mappedBy="transactionLog")
     */
    protected $batteryReturn;

    /**
     * @var Shipment|null
     * @ORM\OneToOne(targetEntity="App\Entity\Shipment", mappedBy="transactionLog")
     */
    protected $shipment;

    /**
     * @return BatteryReturn|null
     */
    public function getBatteryReturn(): ?BatteryReturn
    {
        return $this->batteryReturn;
    }

    /**
     * @return Shipment|null
     */
    public function getShipment(): ?Shipment
    {
        return $this->shipment;
    }

    /**
     * @return Battery
     */
    public function getBattery(): Battery
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
     * @return string|null
     */
    public function getTransactionType(): ?string
    {
        return $this->transactionType;
    }

    /**
     * @param string|null $transactionType
     */
    public function setTransactionType(?string $transactionType): void
    {
        $this->transactionType = $transactionType;
    }

    /**
     * @return string|null
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * @param string|null $status
     */
    public function setStatus(?string $status): void
    {
        $this->status = $status;
    }

    /**
     * @return string|null
     */
    public function getTransactionHash(): ?string
    {
        return $this->transactionHash;
    }

    /**
     * @param string|null $transactionHash
     */
    public function setTransactionHash(?string $transactionHash): void
    {
        $this->transactionHash = $transactionHash;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string|null $description
     */
    public function setDescription(?string $description): void
    {
        $this->description = $description;
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
    public function setDeletedAt(?\DateTime $deletedAt): void
    {
        $this->deletedAt = $deletedAt;
    }
}
