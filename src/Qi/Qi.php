<?php

namespace App\Qi;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class Qi
{
    private $baseUrl;
    private $username;
    private $password;
    private $overrideCertificateAuthorityFile;
    private $sslCertificateAuthorityFile;

    public function __construct(ParameterBagInterface $params)
    {
        $qiApi = $params->get('qi_api');
        $this->baseUrl = $qiApi['url'];
        $this->username = $qiApi['username'];
        $this->password = $qiApi['password'];

        $this->overrideCertificateAuthorityFile = $params->get('override_certificate_authority');
        $this->sslCertificateAuthorityFile = $params->get('ssl_certificate_authority_file');
    }

    public function getAllObjects()
    {
        $objects = array();

        $objsJson = $this->get($this->baseUrl . '/get/object');
        $objs = json_decode($objsJson);
        $count = $objs->count;
        $records = $objs->records;
        foreach($records as $record) {
            $id = explode(' - ', $record->name);
            $objects[$id[0]] = $record;
        }
        for($i = 0; $i < ($count + 499) / 500 - 1; $i++) {
            $objsJson = $this->get($this->baseUrl . '/get/object/_offset/' . (($i + 1) * 500));
            $objs = json_decode($objsJson);
            $records = $objs->records;
            foreach($records as $record) {
                $id = explode(' - ', $record->name);
                $objects[$id[0]] = $record;
            }
        }

        return $objects;
    }

    public function get($url)
    {
        echo $url . PHP_EOL;
        $ch = curl_init();
        if ($this->overrideCertificateAuthorityFile) {
            curl_setopt($ch,CURLOPT_CAINFO, $this->sslCertificateAuthorityFile);
            curl_setopt($ch,CURLOPT_CAPATH, $this->sslCertificateAuthorityFile);
        }
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $this->username . ':' . $this->password);

        $resultJson = curl_exec($ch);
        if($resultJson === false) {
            echo 'HTTP error: ' . curl_error($ch) . PHP_EOL;
        } else if (!curl_errno($ch)) {
            switch ($http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE)) {
                case 200:  # OK
                    break;
                default:
                    echo 'HTTP error ' .  $http_code . ': ' . $resultJson . PHP_EOL;
                    break;
            }
        }
        curl_close($ch);
        return $resultJson;
    }
}