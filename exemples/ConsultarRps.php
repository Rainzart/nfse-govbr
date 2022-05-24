<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');
include __DIR__ . '/../vendor/autoload.php';

use HaDDeR\NfseGovbr\Tools;
use NFePHP\Common\Certificate;

try {

    $config = new stdClass();
    $config->cnpj = '11111111000199';
    $config->im = '11111'; // Inscrição Municial
    $config->cmun = '4303004'; // Código IBGE - Cachoeira do Sul
    $config->razao = 'Razão Social';
    $config->tpamb = 1; //1 - Produção, 2 - Homologação
    $config->validation = false;

    $configJson = json_encode($config);
    $content = file_get_contents('certs/certificado.pfx');
    $password = 'senha_certificado';
    $cert = Certificate::readPfx($content, $password);

    $tools = new Tools($configJson, $cert);

    $response = $tools->consultarLoteRps('9', '1', '1');
    $dom = new DOMDocument();
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = false;
    $dom->loadXML($response);
    $dom->loadXML($dom->getElementsByTagName('ConsultarNfsePorRpsResponse')->item(0)->nodeValue);

    if ($dom->getElementsByTagName('ConsultarNfseRpsResposta')->length > 0) {
        if ($dom->getElementsByTagName('MensagemRetorno')->length > 0) { //Se Erro
            foreach ($dom->getElementsByTagName('MensagemRetorno') as $key => $value) {
                dump($value->getElementsByTagName('Codigo')->item(0)->nodeValue . ' - ' . $value->getElementsByTagName('Mensagem')->item(0)->nodeValue . ' - ' . $value->getElementsByTagName('Correcao')->item(0)->nodeValue);
            }
        } elseif ($dom->getElementsByTagName('NfseCancelamento')->length > 0) {
            dump('Nota Cancelada');
        }
    }
    dd($dom);

} catch (Exception $e) {
    echo $e->getMessage();
}