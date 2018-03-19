<?php

namespace App\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

trait SumErrorsTrait
{
    /**
     * @ORM\Column(type="smallint", nullable=true)
     * @Assert\Type("smallint")
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