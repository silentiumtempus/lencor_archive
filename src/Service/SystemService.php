<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\Version;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Class SystemService
 * @package App\Service
 */
class SystemService
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getSystemInfo(Request $request)
    {
        $elasticInfo = $this->getElasticSearchInfo();
        if (empty($elasticInfo)) {
            $es_version = 'offline';
            $lucene_version = 'offline';
        } else {
            $es_version = $elasticInfo['version']['number'];
            $lucene_version = $elasticInfo['version']['lucene_version'];
        }
        $data = [
            'server_ip' => $request->getHost(),
            'hostname' => gethostname(),
            'db_platform' => $this->container->get('doctrine')->getConnection()->getDatabasePlatform()->getName(),
            'mysql_server' => $this->container->get('doctrine')->getConnection()->getWrappedConnection()->getServerVersion(),
            'php_version' => PHP_VERSION,
            //'php_version' => phpversion(),
            'ws_version' => $request->server->get('SERVER_SIGNATURE'),
            'ws2php_interface' => PHP_SAPI,
            //'ws2php_interface' => php_sapi_name(),
            'sf_version' => Kernel::VERSION,
            'es_version' => $es_version,
            'lucene_version' => $lucene_version,
            'java_version' => substr(shell_exec('java -version 2>&1'), strlen('java version')),
            'doctrine_version' => Version::VERSION,
            'twig_version' => \Twig_Environment::VERSION,
            'user_agent' => $request->headers->get('User-Agent'),
        ];

        return $data;
    }

    /**
     * @return false|string
     */

    public function getPHPInfo()
    {
        ob_start();
        phpinfo();
        $phpinfo = ob_get_contents();
        ob_end_clean();

        return $phpinfo;
    }

    /**
     * @return mixed
     */

    private function getElasticSearchInfo()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'localhost:9200');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $info = json_decode(curl_exec($ch), true);

        return $info;
    }

}