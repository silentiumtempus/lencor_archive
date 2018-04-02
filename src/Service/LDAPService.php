<?php

namespace App\Service;
use App\Model\LDAPConnectionModel;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Ldap\Ldap;
use Symfony\Component\Ldap\Entry as Entry;

/**
 * Class LDAPService
 * @package App\Service
 */
class LDAPService
{
    protected $container;

    /**
     * LDAPService constructor.
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return LDAPConnectionModel
     */
    public function createLDAPConnectionModel() {

        return new LDAPConnectionModel($this->container);
    }

    /**
     * @param LDAPConnectionModel $LDAPConnectionModel
     * @return Ldap
     */
    public function createLDAPConnection(LDAPConnectionModel $LDAPConnectionModel)
    {

        return Ldap::create('ext_ldap', array(
            'host' => $LDAPConnectionModel->getLDAPHost(),
            'port' => $LDAPConnectionModel->getLDAPPort(),
            'version' => $LDAPConnectionModel->getLDAPVersion(),
            'encryption' => $LDAPConnectionModel->getLDAPEncryption()
        ));
    }

    /**
     * @param LDAPConnectionModel $LDAPConnectionModel
     * @return Ldap
     */
    private function prepareConnection(LDAPConnectionModel $LDAPConnectionModel) {

        $ldap = $this->createLDAPConnection($LDAPConnectionModel);
        $ldap->bind($LDAPConnectionModel->getLDAPUser(), $LDAPConnectionModel->getLDAPPassword());

        return $ldap;
    }

    /**
     * @param $username
     * @return array
     */
    public function findLDAPUserByUserName($username) {

        $LDAPConnectionModel = $this->createLDAPConnectionModel();
        $ldap = $this->prepareConnection($LDAPConnectionModel);

        return $this->findUser($ldap, $LDAPConnectionModel->getLDAPDC(), $username);
    }

    /**
     * @param Ldap $ldap
     * @param string $dc
     * @param string $username
     * @return mixed|Entry
     */
    public function findUser(Ldap $ldap, string $dc, string $username)
    {
        $query = $ldap->query($dc, '(&(uid='.$username.'))');
        $resultList = $query->execute();

        return $resultList[0];
    }

}