<?php
/**
 * Created by PhpStorm.
 * User: Vinegar
 * Date: 017 17.02.17
 * Time: 21:17
 */

namespace AppBundle\Entity;

use FOS\UserBundle\Model\User as DefaultUSer;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class User
 * @package AppBundle\Entity
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
