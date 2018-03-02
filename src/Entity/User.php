<?php

namespace App\Entity;

use FOS\UserBundle\Model\User as DefaultUSer;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class User
 * @package App\Entity
 * @ORM\Entity
 * @ORM\Table(name="main_users")
 */

class User extends DefaultUSer
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
}
