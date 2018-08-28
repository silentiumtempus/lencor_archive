<?php

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

    public function setSumErrors($sumErrors)
    {
        $this->sumErrors = $sumErrors;

        return $this;
    }

    /**
     * Get sumErrors
     * @return integer
     */

    public function getSumErrors()
    {
        return $this->sumErrors;
    }
}
