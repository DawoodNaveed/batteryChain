<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class TransactionLog
 * @package App\Entity
 * @ORM\Entity()
 * @ORM\Table()
 */
class TransactionLog extends AbstractEntity
{
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
