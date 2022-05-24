<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');
include __DIR__ . '/../vendor/autoload.php';

use HaDDeR\NfseGovbr\Danfse\Danfse;

try {
    //Considerando que praticamente nenhuma prefeitura implementa a tag de nome da cidade, uma das soluções é informar os códigos IBGEs envolvidos
    $cidades = [
        '4303004' => 'Cachoeira do Sul',
        '2408102' => 'Natal/RN',
    ];

    $xml = file_get_contents('xml/emitida-cancelada.xml');
    $pdf = new Danfse($xml, '', $cidades);
//    header('Content-Type: application/pdf');
    return $pdf->render();

} catch (Exception $e) {
    echo $e->getMessage();
}