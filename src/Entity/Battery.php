<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class Battery
 * @package App\Entity
 * @ORM\Entity(repositoryClass="App\Repository\BatteryRepository")
 * @ORM\Table(name="battery")
 * @Gedmo\SoftDeleteable(fieldName="deletedAt")
 */
class Battery extends AbstractEntity
{
    /**
     * Many batteries have one Manufacture. This is the owning side.
     * @ManyToOne(targetEntity="App\Entity\Manufacturer", inversedBy="batteries")
     * @JoinColumn(name="manufacturer_id", referencedColumnName="id")
     */
    private $manufacturer;

    /**
     * @var string|null
     * @ORM\Column(name="serial_number", type="string", nullable="true", length=255)
     */
    private $serialNumber;

    /**
     * @var BatteryType|null
     * @ManyToOne(targetEntity="App\Entity\BatteryType", inversedBy="batteries")
     * @JoinColumn(name="battery_type_id", referencedColumnName="id")
     */
    private $batteryType;

    /**
     * @var User|null
     * @ManyToOne(targetEntity="App\Entity\User", inversedBy="batteries")
     * @JoinColumn(name="current_possessor_id", referencedColumnName="id")
     */
    private $currentPossessor;

    /**
     * @var double|null
     * @ORM\Column(name="nominal_voltage", type="float", options={"unsigned"=true}, nullable="true")
     * @Assert\Type(
     *     type="double"
     * )
     */
    private $nominalVoltage;

    /**
     * @var double|null
     * @ORM\Column(name="nominal_capacity", type="float", options={"unsigned"=true}, nullable="true")
     * @Assert\Type(
     *     type="double"
     * )
     */
    private $nominalCapacity;

    /**
     * @var double|null
     * @ORM\Column(name="nominal_energy", type="float", options={"unsigned"=true}, nullable="true")
     * @Assert\Type(
     *     type="double"
     * )
     */
    private $nominalEnergy;

    /**
     * @var integer|null
     * @ORM\Column(name="cycle_life", type="integer", options={"unsigned"=true}, nullable="true")
     */
    private $cycleLife;

    /**
     * @var double|null
     * @ORM\Column(name="height", type="float", options={"unsigned"=true}, nullable="true")
     * @Assert\Type(
     *     type="double"
     * )
     */
    private $height;

    /**
     * @var double|null
     * @ORM\Column(name="width", type="float", options={"unsigned"=true}, nullable="true")
     * @Assert\Type(
     *     type="double"
     * )
     */
    private $width;

    /**
     * @var double|null
     * @ORM\Column(name="length", type="float", options={"unsigned"=true}, nullable="true")
     * @Assert\Type(
     *     type="double"
     * )
     */
    private $length;

    /**
     * @var double|null
     * @ORM\Column(name="mass", type="float", options={"unsigned"=true}, nullable="true",)
     * @Assert\Type(
     *     type="double"
     * )
     */
    private $mass;

    /**
     * @var string|null
     * @ORM\Column(name="status", type="string", nullable="true", length=50)
     */
    private $status;

    /**
     * One Battery has many shipments.
     * @OneToMany(targetEntity="App\Entity\Shipment", mappedBy="battery")
     */
    private $shipments;

    /**
     * One Battery has many shipments.
     * @OneToMany(targetEntity="App\Entity\BatteryReturn", mappedBy="battery")
     */
    private $returns;

    /**
     * @var \DateTime|null
     * @ORM\Column(name="deleted_at", type="datetime", nullable=true)
     */
    private $deletedAt;

    /**
     * @var double|null
     * @ORM\Column(name="co2", type="float", nullable=true, options={"default"=0})
     * @Assert\Type(
     *     type="double"
     * )
     */
    private $co2;

    /**
     * @var \DateTime|null
     * @ORM\Column(name="production_date", type="datetime", nullable=true)
     */
    private $productionDate;

    /**
     * @var string|null
     * @ORM\Column(name="cell_type", type="string", nullable=true)
     */
    private $cellType;

    /**
     * @var string|null
     * @ORM\Column(name="module_type", type="string", nullable=true)
     */
    private $moduleType;

    /**
     * @var double|null
     * @ORM\Column(name="acid_volume", type="float", nullable=true, options={"default"=0})
     * @Assert\Type(
     *     type="double"
     * )
     */
    private $acidVolume;

    /**
     * @var string|null
     * @ORM\Column(name="tray_number", type="string", nullable=true)
     */
    private $trayNumber;

    /**
     * @var boolean|null
     * @ORM\Column(name="blockchain_secured", type="boolean", nullable=true, options={"default"=false})
     */
    private $blockchainSecured;

    /**
     * @var boolean|null
     * @ORM\Column(name="is_bulk_import", type="boolean", nullable=true, options={"default"=false})
     */
    private $isBulkImport;

    /**
     * One Battery has many transaction Logs.
     * @OneToMany(targetEntity="App\Entity\TransactionLog", mappedBy="battery")
     */
    private $transactionLogs;

    /**
     * Battery constructor.
     */
    public function __construct() {
        $this->shipments = new ArrayCollection();
        $this->returns = new ArrayCollection();
        $this->transactionLogs = new ArrayCollection();
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
     * @return $this
     */
    public function setManufacturer(?Manufacturer $manufacturer): self
    {
        $this->manufacturer = $manufacturer;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getSerialNumber(): ?string
    {
        return $this->serialNumber;
    }

    /**
     * @param string|null $serialNumber
     */
    public function setSerialNumber(?string $serialNumber): void
    {
        $this->serialNumber = $serialNumber;
    }

    /**
     * @return BatteryType|null
     */
    public function getBatteryType(): ?BatteryType
    {
        return $this->batteryType;
    }

    /**
     * @param BatteryType|null $batteryType
     */
    public function setBatteryType(?BatteryType $batteryType): void
    {
        $this->batteryType = $batteryType;
    }

    /**
     * @return User|null
     */
    public function getCurrentPossessor(): ?User
    {
        return $this->currentPossessor;
    }

    /**
     * @param User|null $currentPossessor
     */
    public function setCurrentPossessor(?User $currentPossessor): void
    {
        $this->currentPossessor = $currentPossessor;
    }

    /**
     * @return float|null
     */
    public function getNominalVoltage(): ?float
    {
        return $this->nominalVoltage;
    }

    /**
     * @param float|null $nominalVoltage
     */
    public function setNominalVoltage(?float $nominalVoltage): void
    {
        $this->nominalVoltage = $nominalVoltage;
    }

    /**
     * @return float|null
     */
    public function getNominalCapacity(): ?float
    {
        return $this->nominalCapacity;
    }

    /**
     * @param float|null $nominalCapacity
     */
    public function setNominalCapacity(?float $nominalCapacity): void
    {
        $this->nominalCapacity = $nominalCapacity;
    }

    /**
     * @return float|null
     */
    public function getNominalEnergy(): ?float
    {
        return $this->nominalEnergy;
    }

    /**
     * @param float|null $nominalEnergy
     */
    public function setNominalEnergy(?float $nominalEnergy): void
    {
        $this->nominalEnergy = $nominalEnergy;
    }

    /**
     * @return int|null
     */
    public function getCycleLife(): ?int
    {
        return $this->cycleLife;
    }

    /**
     * @param int|null $cycleLife
     */
    public function setCycleLife(?int $cycleLife): void
    {
        $this->cycleLife = $cycleLife;
    }

    /**
     * @return float|null
     */
    public function getHeight(): ?float
    {
        return $this->height;
    }

    /**
     * @param float|null $height
     */
    public function setHeight(?float $height): void
    {
        $this->height = $height;
    }

    /**
     * @return float|null
     */
    public function getWidth(): ?float
    {
        return $this->width;
    }

    /**
     * @param float|null $width
     */
    public function setWidth(?float $width): void
    {
        $this->width = $width;
    }

    /**
     * @return float|null
     */
    public function getLength(): ?float
    {
        return $this->length;
    }

    /**
     * @param float|null $length
     */
    public function setLength(?float $length): void
    {
        $this->length = $length;
    }

    /**
     * @return float|null
     */
    public function getMass(): ?float
    {
        return $this->mass;
    }

    /**
     * @param float|null $mass
     */
    public function setMass(?float $mass): void
    {
        $this->mass = $mass;
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
     * @return Collection|Shipment[]
     */
    public function getShipments(): Collection
    {
        return $this->shipments;
    }

    /**
     * @param Shipment $shipment
     * @return $this
     */
    public function addShipment(Shipment $shipment): self
    {
        if (!$this->shipments->contains($shipment)) {
            $this->shipments[] = $shipment;
        }

        return $this;
    }

    /**
     * @param Shipment $shipment
     * @return $this
     */
    public function removeShipment(Shipment $shipment): self
    {
        $this->shipments->removeElement($shipment);

        return $this;
    }

    /**
     * @return Collection|BatteryReturn[]
     */
    public function getReturns(): Collection
    {
        return $this->returns;
    }

    /**
     * @param BatteryReturn $return
     * @return $this
     */
    public function addReturns(BatteryReturn $return): self
    {
        if (!$this->returns->contains($return)) {
            $this->returns[] = $return;
        }

        return $this;
    }

    /**
     * @param BatteryReturn $return
     * @return $this
     */
    public function removeReturns(BatteryReturn $return): self
    {
        $this->returns->removeElement($return);

        return $this;
    }

    /**
     * @return string|null
     */
    public function __toString(): ?string
    {
        return $this->serialNumber;
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
     * @return float|null
     */
    public function getCo2(): ?float
    {
        return $this->co2;
    }

    /**
     * @param float|null $co2
     */
    public function setCo2(?float $co2): void
    {
        $this->co2 = $co2;
    }

    /**
     * @return \DateTime|null
     */
    public function getProductionDate(): ?\DateTime
    {
        return $this->productionDate;
    }

    /**
     * @param \DateTime|null $productionDate
     */
    public function setProductionDate(?\DateTime $productionDate): void
    {
        $this->productionDate = $productionDate;
    }

    /**
     * @return string|null
     */
    public function getCellType(): ?string
    {
        return $this->cellType;
    }

    /**
     * @param string|null $cellType
     */
    public function setCellType(?string $cellType): void
    {
        $this->cellType = $cellType;
    }

    /**
     * @return string|null
     */
    public function getModuleType(): ?string
    {
        return $this->moduleType;
    }

    /**
     * @param string|null $moduleType
     */
    public function setModuleType(?string $moduleType): void
    {
        $this->moduleType = $moduleType;
    }

    /**
     * @return float|null
     */
    public function getAcidVolume(): ?float
    {
        return $this->acidVolume;
    }

    /**
     * @param float|null $acidVolume
     */
    public function setAcidVolume(?float $acidVolume): void
    {
        $this->acidVolume = $acidVolume;
    }

    /**
     * @return string|null
     */
    public function getTrayNumber(): ?string
    {
        return $this->trayNumber;
    }

    /**
     * @param string|null $trayNumber
     */
    public function setTrayNumber(?string $trayNumber): void
    {
        $this->trayNumber = $trayNumber;
    }

    /**
     * @return boolean|null
     */
    public function getBlockchainSecured(): ?bool
    {
        return $this->blockchainSecured;
    }

    /**
     * @param boolean|null $blockchainSecured
     */
    public function setBlockchainSecured(?bool $blockchainSecured): void
    {
        $this->blockchainSecured = $blockchainSecured;
    }

    /**
     * @return bool|null
     */
    public function getIsBulkImport(): ?bool
    {
        return $this->isBulkImport;
    }

    /**
     * @param bool|null $isBulkImport
     */
    public function setIsBulkImport(?bool $isBulkImport): void
    {
        $this->isBulkImport = $isBulkImport;
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
