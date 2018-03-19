<?php

namespace App\Entity\Traits;

trait SlugTrait
{
    /**
     * Set slug
     * @param string $slug
     * @return $this
     */

    public function setSlug($slug)
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * Get slug
     * @return string
     */

    public function getSlug()
    {
        return $this->slug;
    }
}