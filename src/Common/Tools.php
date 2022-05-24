<?php

namespace HaDDeR\NfseGovbr\Common;

/**
 * Auxiar Tools Class for comunications with NFSe webserver in Nacional Standard
 *
 * @category  NFePHP
 * @package   HaDDeR\NfseGovbr
 * @copyright NFePHP Copyright (c) 2020
 * @license   http://www.gnu.org/licenses/lgpl.txt LGPLv3+
 * @license   https://opensource.org/licenses/MIT MIT
 * @license   http://www.gnu.org/licenses/gpl.txt GPLv3+
 * @author    Roberto L. Machado <linux.rlm at gmail dot com>
 * @link      http://github.com/nfephp-org/sped-nfse-prodam for the canonical source repository
 */

use DOMDocument;
use Exception;
use HaDDeR\NfseGovbr\Common\Soap\SoapCurl;
use HaDDeR\NfseGovbr\Common\Soap\SoapInterface;
use NFePHP\Common\Certificate;

class Tools
{

    protected $config;
    protected $certificate;
    protected $wsobj;
    protected $soap;
    protected $environment;

    /**
     * Constructor
     * @param string $config
     * @param Certificate $cert
     * @throws Exception
     */
    public function __construct(string $config, Certificate $cert)
    {
        $this->config = json_decode($config);
        $this->certificate = $cert;
        $this->wsobj = $this->loadWsobj($this->config->cmun);
        $this->environment = 'homologacao';
        $this->config->validation = $this->config->validation ?? true;
        if ($this->config->tpamb === 1) {
            $this->environment = 'producao';
        }
    }

    /**
     * load webservice parameters
     * @param string $cmun
     * @return object
     * @throws Exception
     */
    protected function loadWsobj(string $cmun)
    {
        $path = realpath(__DIR__ . '/../../storage/urls_webservices.json');
        $urls = json_decode(file_get_contents($path), true);
        if (empty($urls[$cmun])) {
            throw new Exception("Não localizado parâmetros para esse municipio.");
        }
        return (object)$urls[$cmun];
    }

    /**
     * SOAP communication dependency injection
     * @param SoapInterface $soap
     */
    public function loadSoapClass(SoapInterface $soap)
    {
        $this->soap = $soap;
    }

    /**
     * Sign XML passing in content
     * @param string $content
     * @param string $tagname
     * @param string $mark
     * @return string XML signed
     */
    public function sign(string $content, string $tagname, string $mark, $rootname)
    {
        $xml = Signer::sign(
            $this->certificate,
            $content,
            $tagname,
            $mark,
            $rootname
        );
        return $xml;
    }

    /**
     * Send message to webservice
     * @param string $message
     * @param string $operation
     * @return string XML response from webservice
     * @throws Exception
     */
    public function send(string $message, string $method, string $operation)
    {
        $url = $this->wsobj->{$this->environment};
        if (empty($url)) {
            throw new Exception("Não está registrada a URL para o ambiente "
                . "de {$this->environment} desse municipio.");
        }
        $request = $this->createSoapRequest($message, $operation);
        $request = str_replace('<?xml version="1.0"?>', '', $request);
        $request = str_replace("\n", '', $request);
        if (empty($this->soap)) {
            $this->soap = new SoapCurl($this->certificate);
        }
        $msgSize = strlen($request);
        $parameters = [
            //            "Accept-Encoding: gzip, deflate",
            //            "Content-Type: application/soap+xml;charset=UTF-8;",
            "Content-Type: text/xml;charset=UTF-8;",
            "Content-length: $msgSize",
            "SOAPAction: http://tempuri.org/" . $method . "/" . $operation
        ];
        //        $this->soap->setDebugMode(true);
        //        $this->soap->setTemporaryFolder('/var/www/nfse-coplan/exemples/');
        $response = (string)$this->soap->send(
            $operation,
            $url,
            '',
            $request,
            $parameters
        );
        return $this->extractContentFromResponse($response, $operation);
    }

    /**
     * Extract xml response from CDATA outputXML tag
     * @param string $response Return from webservice
     * @param string $operation
     * @return string XML extracted from response
     */
    protected function extractContentFromResponse(string $response, string $operation)
    {
        //verifica se está em modo FAKE
        if (substr($response, 0, 1) == '{') {
            return $response;
        }
        $exceptOperations = ['ConsultaSituacaoLote', 'TesteEnvioLoteRPSAsync'];
        if (in_array($operation, $exceptOperations)) {
            $response = str_replace('&lt;?xml version="1.0" encoding="UTF-8"?&gt;', '', $response);
            $response = str_replace(['&lt;', '&gt;'], ['<', '>'], $response);
        }
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;
        $dom->loadXML($response);
        if (!empty($dom->getElementsByTagName('outputXML')->item(0))) {
            $node = $dom->getElementsByTagName('outputXML')->item(0);
            if (in_array($operation, $exceptOperations)) {
                return $response;
            }
            return $node->textContent;
        }
        return $response;
    }

    /**
     * Build SOAP request
     * @param string $message
     * @param string $operation
     * @return string XML SOAP request
     */
    protected function createSoapRequest(string $message, string $operation)
    {
        $opUpper = mb_strtoupper($operation);
        $opFUpper = ucfirst($operation);
        //        dd($opFUpper);

        $env = "<soapenv:Envelope xmlns:soapenv=\"http://schemas.xmlsoap.org/soap/envelope/\" "
            . "xmlns:tem=\"http://tempuri.org/\">"
            . "<soapenv:Header>"
            . "<tem:cabecalho versao=\"{$this->wsobj->version}\">"
            . "<tem:versaoDados>{$this->wsobj->version}</tem:versaoDados>"
            . "</tem:cabecalho>"
            . "</soapenv:Header>"
            . "<soapenv:Body>"
            . "<tem:{$opFUpper}>"
            . "<tem:xmlEnvio>"
            . "</tem:xmlEnvio>"
            . "</tem:{$opFUpper}>"
            . "</soapenv:Body>"
            . "</soapenv:Envelope>";

        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = false;
        $dom->preserveWhiteSpace = false;
        $dom->loadXML($env);
        //        $node = $dom->getElementsByTagName('nfseCabecMsg')->item(0);
        //        $node->appendChild($dom->createCDATASection('<cabecalho xmlns="http://www.abrasf.org.br/nfse.xsd" versao="' . $this->wsobj->version . '"><versaoDados>' . $this->wsobj->version . '</versaoDados></cabecalho>'));

        $node = $dom->getElementsByTagName('xmlEnvio')->item(0);
        $node->appendChild($dom->createCDATASection($message));
        $env = $dom->saveXML();
        //        header("Content-type: text/xml");
        //        echo $env;
        //        die;

        return $env;
    }
}
