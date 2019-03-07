<?php
declare(strict_types=1);

namespace App\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Trait SumErrorsTrait
 * @package App\Entity\Traits
 */
trait SumErrorsTrait
{
    /**
     * @ORM\Column(type = "smallint", nullable = true)
     * @Assert\Type("smallint")
     * @Gedmo\Versioned()
     * @Serializer\Type("integer")
     */
    protected $sumErrors;

    /**
     * Set sumErrors
     * @param integer $sumErrors
     * @return $this
     */
    public function setSumErrors(int $sumErrors): self
    {
        $this->sumErrors = $sumErrors;

        return $this;
    }

    /**
     * Get sumErrors
     * @return integer
     */
    public function getSumErrors(): ?int
    {
        return $this->sumErrors;
    }
}
