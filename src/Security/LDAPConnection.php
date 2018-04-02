<?php

namespace App\Security;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class LDAPConnection
 * @package App\Security
 */
class LDAPConnection
{
    protected $container;
    protected $ldap_host;
    protected $ldap_dc;
    protected $ldap_port;
    protected $ldap_version;
    protected $ldap_encryption;
    protected $ldap_dn;
    protected $ldap_password;

    /**
     * LDAPConnection constructor.
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->ldap_host = $this->container->getParameter('ldap.host');
        $this->ldap_dc = $this->container->getParameter('ldap.dc');
        $this->ldap_port = $this->container->getParameter('ldap.port');
        $this->ldap_version = $this->container->getParameter('ldap.version');
        $this->ldap_encryption = $this->container->getParameter('ldap.encryption');
        $this->ldap_user = $this->container->getParameter('ldap.user');
        $this->ldap_password = $this->container->getParameter('ldap.password');
    }

    /**
     * @return string
     */
    public function getLDAPHost() {

        return $this->ldap_host;
    }

    /**
     * @return string
     */
    public function getLDAPDC() {

        return $this->ldap_dc;
    }

    /**
     * @return integer
     */
    public function getLDAPPort() {

        return $this->ldap_port;
    }

    /**
     * @return integer
     */
    public function getLDAPVersion() {

        return $this->ldap_version;
    }

    /**
     * @return string
     */
    public function getLDAPEncryption() {

        return $this->ldap_encryption;
    }

    /**
     * @return string
     */
    public function getLDAPUser() {

        return $this->ldap_user;
    }

    /**
     * @return string
     */
    public function getLDAPPassword() {

        return $this->ldap_password;
    }
}