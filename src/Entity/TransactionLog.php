<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;

/**
 * Class TransactionLog
 * @package App\Entity
 * @ORM\Entity()
 * @ORM\Table()
 */
class TransactionLog extends AbstractEntity
{
    /**
     * @var Shipment|null
     * Many Transaction Logs have one Shipment. This is the owning side.
     * @ManyToOne(targetEntity="App\Entity\Shipment", inversedBy="transactionLogs")
     * @JoinColumn(name="shipment_id", referencedColumnName="id")
     */
    private $shipment;

    /**
     * @var BatteryReturn|null
     * Many Transaction Logs have one Return. This is the owning side.
     * @ManyToOne(targetEntity="App\Entity\BatteryReturn", inversedBy="transactionLogs")
     * @JoinColumn(name="return_id", referencedColumnName="id")
     */
    private $returns;

    /**
     * @var string|null
     * @ORM\Column(name="transaction_hash", nullable="true", type="string", length=255)
     */
    private $transactionHash;

    /**
     * @var string|null
     * @ORM\Column(type="text", unique=false, nullable=true, options={"default"=null})
     */
    private $description;

    /**
     * @return Shipment|null
     */
    public function getShipment(): ?Shipment
    {
        return $this->shipment;
    }

    /**
     * @param Shipment|null $shipment
     */
    public function setShipment(?Shipment $shipment): void
    {
        $this->shipment = $shipment;
    }

    /**
     * @return BatteryReturn|null
     */
    public function getReturns(): ?BatteryReturn
    {
        return $this->returns;
    }

    /**
     * @param BatteryReturn|null $returns
     */
    public function setReturns(?BatteryReturn $returns): void
    {
        $this->returns = $returns;
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
}
