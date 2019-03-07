<?php
declare(strict_types=1);

namespace App\Entity\Traits;
/**
 * Trait SlugTrait
 * @package App\Entity\Traits
 */
trait SlugTrait
{
    /**
     * Set slug
     * @param string $slug
     * @return $this
     */
    public function setSlug(string $slug = null): self
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * Get slug
     * @return string
     */
    public function getSlug(): ?string
    {
        return $this->slug;
    }
}
