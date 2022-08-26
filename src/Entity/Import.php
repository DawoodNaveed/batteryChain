<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * Class Import
 * @package App\Entity
 * @ORM\Entity(repositoryClass="App\Repository\ImportRepository")
 * @ORM\Table(name="bulk_import")
 * @Gedmo\SoftDeleteable(fieldName="deletedAt")
 * @Vich\Uploadable
 */
class Import extends AbstractEntity
{
    /**
     * Many batteries have one Manufacture. This is the owning side.
     * @ManyToOne(targetEntity="App\Entity\Manufacturer", inversedBy="imports")
     * @JoinColumn(name="manufacturer_id", referencedColumnName="id")
     * @var Manufacturer|null
     */
    private $manufacturer;

    /**
     * One Bulk Import has many batteries.
     * @OneToMany(targetEntity="App\Entity\Battery", mappedBy="manufacturer")
     */
    private $batteries;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @var string|null
     */
    private $csv;

    /**
     * @Vich\UploadableField(mapping="bulk_import_file", fileNameProperty="csv")
     * @var File|null
     */
    private $csvFile;

    /**
     * @var string|null
     * @ORM\Column(name="status", type="string", nullable="true", length=50)
     */
    private $status;

    /**
     * @var \DateTime|null
     * @ORM\Column(name="deleted_at", type="datetime", nullable=true)
     */
    private $deletedAt;

    /**
     * Import constructor.
     */
    public function __construct()
    {
        $this->batteries = new ArrayCollection();
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

    /**
     * @param File|null $csv
     */
    public function setCsvFile(File $csv = null)
    {
        $this->csvFile = $csv;

        // VERY IMPORTANT:
        // It is required that at least one field changes if you are using Doctrine,
        // otherwise the event listeners won't be called and the file is lost
        if ($csv) {
            // if 'updatedAt' is not defined in your entity, use another property
            $this->updated = new \DateTime('now');
        }
    }

    /**
     * @return string|null
     */
    public function getCsv(): ?string
    {
        return $this->csv;
    }

    /**
     * @param string|null $csv
     */
    public function setCsv(?string $csv): void
    {
        $this->csv = $csv;
    }

    /**
     * @return File|null
     */
    public function getCsvFile(): ?File
    {
        return $this->csvFile;
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
        return $this->csv;
    }

    /**
     * @return string|void|null
     */
    public function serialize(): ?string
    {
        $this->csvFile = base64_encode($this->csvFile);
    }

    /**
     * @param string $serialized
     */
    public function unserialize($serialized)
    {
        $this->csvFile = base64_decode($this->csvFile);
    }
}