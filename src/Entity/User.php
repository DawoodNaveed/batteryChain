<?php

namespace App\Entity;

use App\Repository\UserRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\OneToMany;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=UserRepository::class)
 * @UniqueEntity(fields={"email"}, message="There is already an account with this email")
 * @Gedmo\SoftDeleteable(fieldName="deletedAt")
 */
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank(
     *     message="Field 'First name' should not be empty.",
     *     groups={"registration"}
     * )
     */
    private $firstName;

    /**
     * @var string
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank(
     *     message="Field 'Last name' should not be empty.",
     *     groups={"registration"}
     * )
     */
    private $lastName;

    /**
     * @ORM\Column(type="string", length=180, unique=true)
     */
    private $email;

    /**
     * @ORM\Column(type="json")
     */
    private $roles = [];

    /**
     * @var string The hashed password
     * @ORM\Column(type="string")
     * @Assert\Length(
     *      min = 8,
     *      max = 20,
     *      minMessage = "Your password must be at least {{ limit }} characters long",
     *      maxMessage = "Your password cannot be longer than {{ limit }} characters"
     * )
     * @Assert\Regex(
     *     pattern="/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/",
     *     message="Password must contain one upper case, one lower case, one number and one special character"
     * )
     */
    private $password;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isVerified = false;

    /**
     * @var boolean
     * @ORM\Column(name="status", type="boolean", options={"default"=true})
     */
    protected $status = true;

    /**
     * @var Manufacturer|null
     * @ORM\OneToOne(targetEntity="App\Entity\Manufacturer", mappedBy="user")
     */
    protected $manufacturer;

    /**
     * One User has many batteries.
     * @OneToMany(targetEntity="App\Entity\Battery", mappedBy="currentPossessor")
     */
    protected $batteries;

    /**
     * @var DateTime|null
     * @ORM\Column(
     *     name="created",
     *     type="datetime",
     *     nullable=true
     * )
     * @Gedmo\Timestampable(on="create")
     */
    protected $created;

    /**
     * @var DateTime|null
     * @ORM\Column(
     *     name="updated",
     *     type="datetime",
     *     nullable=true
     * )
     * @Gedmo\Timestampable(on="update")
     */
    protected $updated;

    /**
     * One User has many shipments To.
     * @OneToMany(targetEntity="App\Entity\Shipment", mappedBy="shipmentTo")
     */
    private $shipmentsTo;

    /**
     * One User has many shipments From.
     * @OneToMany(targetEntity="App\Entity\Shipment", mappedBy="shipmentFrom")
     */
    private $shipmentsFrom;

    /**
     * One User has many returns From.
     * @OneToMany(targetEntity="App\Entity\BatteryReturn", mappedBy="returnFrom")
     */
    private $returnsFrom;

    /**
     * @var \DateTime|null
     * @ORM\Column(name="deleted_at", type="datetime", nullable=true)
     */
    private $deletedAt;

    /**
     * User constructor.
     */
    public function __construct() {
        $this->shipmentsTo = new ArrayCollection();
        $this->shipmentsFrom = new ArrayCollection();
        $this->returnsFrom = new ArrayCollection();
        $this->batteries = new ArrayCollection();
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @param string $email
     * @return $this
     */
    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @deprecated since Symfony 5.3, use getUserIdentifier instead
     */
    public function getUsername(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @param $role
     */
    public function addRole($role) {
        $this->roles[] = $role;

        $this->roles = array_unique($this->roles);
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Returning a salt is only needed, if you are not using a modern
     * hashing algorithm (e.g. bcrypt or sodium) in your security.yaml.
     *
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): self
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    /**
     * @return string
     */
    public function getFirstName(): string
    {
        return $this->firstName;
    }

    /**
     * @param string $firstName
     */
    public function setFirstName(string $firstName): void
    {
        $this->firstName = $firstName;
    }

    /**
     * @return string
     */
    public function getLastName(): string
    {
        return $this->lastName;
    }

    /**
     * @param string $lastName
     */
    public function setLastName(string $lastName): void
    {
        $this->lastName = $lastName;
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
     * @return DateTime|null
     */
    public function getCreated(): ?DateTime
    {
        return $this->created;
    }

    /**
     * @param DateTime|null $created
     */
    public function setCreated(?DateTime $created): void
    {
        $this->created = $created;
    }

    /**
     * @return DateTime|null
     */
    public function getUpdated(): ?DateTime
    {
        return $this->updated;
    }

    /**
     * @param DateTime|null $updated
     */
    public function setUpdated(?DateTime $updated): void
    {
        $this->updated = $updated;
    }

    /**
     * @return Collection|Shipment[]
     */
    public function getShipmentsTo(): Collection
    {
        return $this->shipmentsTo;
    }

    /**
     * @param Shipment $shipment
     * @return $this
     */
    public function addShipmentsTo(Shipment $shipment): self
    {
        if (!$this->shipmentsTo->contains($shipment)) {
            $this->shipmentsTo[] = $shipment;
        }

        return $this;
    }

    /**
     * @param Shipment $shipment
     * @return $this
     */
    public function removeShipmentsTo(Shipment $shipment): self
    {
        $this->shipmentsTo->removeElement($shipment);

        return $this;
    }

    /**
     * @return Collection|Shipment[]
     */
    public function getShipmentsFrom(): Collection
    {
        return $this->shipmentsFrom;
    }

    /**
     * @param Shipment $shipment
     * @return $this
     */
    public function addShipmentsFrom(Shipment $shipment): self
    {
        if (!$this->shipmentsFrom->contains($shipment)) {
            $this->shipmentsFrom[] = $shipment;
        }

        return $this;
    }

    /**
     * @param Shipment $shipment
     * @return $this
     */
    public function removeShipmentsFrom(Shipment $shipment): self
    {
        $this->shipmentsFrom->removeElement($shipment);

        return $this;
    }

    /**
     * @return Collection|BatteryReturn[]
     */
    public function getReturnsFrom(): Collection
    {
        return $this->returnsFrom;
    }

    /**
     * @param BatteryReturn $return
     * @return $this
     */
    public function addReturnsFrom(BatteryReturn $return): self
    {
        if (!$this->returnsFrom->contains($return)) {
            $this->returnsFrom[] = $return;
        }

        return $this;
    }

    /**
     * @param BatteryReturn $return
     * @return $this
     */
    public function removeReturnsFrom(BatteryReturn $return): self
    {
        $this->returnsFrom->removeElement($return);

        return $this;
    }

    /**
     * @return Manufacturer|null
     */
    public function getManufacturer(): ?Manufacturer
    {
        return $this->manufacturer;
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
     * @return string|null
     */
    public function getFullName(): ?string
    {
        return $this->firstName . ' ' . $this->lastName;
    }

    /**
     * @return DateTime|null
     */
    public function getDeletedAt(): ?DateTime
    {
        return $this->deletedAt;
    }

    /**
     * @param DateTime|null $deletedAt
     */
    public function setDeletedAt(?DateTime $deletedAt): void
    {
        $this->deletedAt = $deletedAt;
    }
}
