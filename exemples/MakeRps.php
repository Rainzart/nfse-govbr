<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');
include __DIR__ . '/../vendor/autoload.php';

use Carbon\Carbon;
use HaDDeR\NfseGovbr\Rps;
use HaDDeR\NfseGovbr\Tools;
use NFePHP\Common\Certificate;

try {

    bcscale(2);

    $config = new stdClass();
    $config->cnpj = '11111111000199';
    $config->im = '11111'; // Inscrição Municial
    $config->cmun = '4303004'; // Código IBGE - Cachoeira do Sul
    $config->razao = 'Razão Social';
    $config->tpamb = 1; //1 - Produção, 2 - Homologação, Não tem Homologação para Cachoeira do Sul
    $config->validation = false; //Se marcado True dará erro pois o XSD é da Abrasf e o do webservice é diferente

    $configJson = json_encode($config);
    $content = file_get_contents('certs/certificado.pfx');
    $password = 'senha_certificado';
    $cert = Certificate::readPfx($content, $password);

    $tools = new Tools($configJson, $cert);

    $arps = [];

    $std = new stdClass();
    //    $std->version = '2.01';
    $std->lote = '123';
    $std->Rps = new stdClass();
    $std->Rps->DataEmissao = Carbon::now()->format('Y-m-d');
    $std->Rps->Status = '1';    //1 - Normal, 2 - Cancelado

    $std->Rps->IdentificacaoRps = new stdClass();
    $std->Rps->IdentificacaoRps->Numero = 1;
    $std->Rps->IdentificacaoRps->Serie = '1'; //Deve ser string numerico?
    $std->Rps->IdentificacaoRps->Tipo = 1; //1 - RPS, 2 - Nota Fiscal Conjugada (Mista), 3 - Cupom

    $std->Competencia = Carbon::now()->addDay()->format('Y-m-d');

    $std->Servico = new stdClass();
    $std->Servico->IssRetido = 2;//1 - Sim, 2 - Não
    $std->Servico->ItemListaServico = '00.00'; //Consultar na prefeitura
    $std->Servico->CodigoTributacaoMunicipio = '0000000'; //Consultar na prefeitura
    $std->Servico->Discriminacao = 'TESTE DE EMISSÃO NFSe Cachoeira do Sul - Hadder Soft';
//    $std->Servico->CodigoMunicipio = '4303004'; //IBGE
    $std->Servico->CodigoMunicipio = '2408102'; //IBGE
    $std->Servico->CodigoPais = '1058';
    $std->Servico->ExigibilidadeISS = 1; //1 – Exigível, 2 – Não incidência, 3 – Isenção, 4 – Exportação, 5 – Imunidade, 6 – Exigibilidade Suspensa por Decisão Judicial, 7 – Exigibilidade Suspensa por Processo Administrativo
    $std->Servico->MunicipioIncidencia = $config->cmun;

    $std->Servico->Valores = new stdClass();
    $std->Servico->Valores->ValorServicos = '100.00';
    $std->Servico->Valores->Aliquota = 0;
    //    $std->Servico->Valores->ValorIss = bcmul($std->Servico->Valores->ValorServicos, bcdiv($std->Servico->Valores->Aliquota, 100));
    //    $std->Servico->Valores->ValorDeducoes = 0.00;
    //    $std->Servico->Valores->ValorPis = 0.00;
    //    $std->Servico->Valores->ValorCofins = 0.00;
    //    $std->Servico->Valores->ValorInss = 0.00;
    //    $std->Servico->Valores->ValorIr = 0.00;
    //    $std->Servico->Valores->ValorCsll = 0.00;
    //    $std->Servico->Valores->OutrasRetencoes = 0.00;
    //    $std->Servico->Valores->DescontoIncondicionado = 0.00;
    //    $std->Servico->Valores->DescontoCondicionado = 0.00;

    $std->Prestador = new stdClass();
    $std->Prestador->InscricaoMunicipal = $config->im;
    $std->Prestador->CpfCnpj = new stdClass();
    $std->Prestador->CpfCnpj->Cnpj = $config->cnpj;

    $std->Tomador = new stdClass();
    $std->Tomador->RazaoSocial = 'Nome Tomador';
    $std->Tomador->IdentificacaoTomador = new stdClass();
    $std->Tomador->IdentificacaoTomador->CpfCnpj = new stdClass();
    $std->Tomador->IdentificacaoTomador->CpfCnpj->Cpf = '11111111199';
    //    $std->Tomador->IdentificacaoTomador->CpfCnpj->Cnpj = '11111111011199';
    //    $std->Tomador->IdentificacaoTomador->InscricaoMunicipal = '';

    $std->Tomador->Endereco = new stdClass();
    $std->Tomador->Endereco->Endereco = 'Rua Tomador';
    $std->Tomador->Endereco->Numero = '123';
    //    $std->Tomador->Endereco->Complemento = 'Complemento se existir';
    $std->Tomador->Endereco->Bairro = 'Bairro';
    $std->Tomador->Endereco->CodigoMunicipio = '4303004';
    $std->Tomador->Endereco->Uf = 'RS';
    $std->Tomador->Endereco->Cep = '96500000';
    //    $std->Tomador->Endereco->CodigoPais = '1058'; // O código do país do tomador do serviço somente deverá ser informado quando  o município for igual a <9999999>.

    $std->Tomador->Contato = new stdClass();
    $std->Tomador->Contato->Telefone = '51000000000';
    $std->Tomador->Contato->Email = 'email@tomador.com';

    $std->OptanteSimplesNacional = 1; //1 – Sim, 2 – Não
    $std->IncentivoFiscal = 2; //1 – Sim, 2 – Não

    $rps = new Rps($std);
    //    $rps->setFormatOutput(true);
    $response = $tools->gerarNfseEnvio($rps);
    $dom = new DOMDocument();
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = false;
    $dom->loadXML($response);

    if ($dom->getElementsByTagName('GerarNfseResponseResult')->length > 0) {
        $dom->loadXML($dom->getElementsByTagName('GerarNfseResponseResult')->item(0)->nodeValue);

        if ($dom->getElementsByTagName('MensagemRetorno')->length > 0) { //Se Erro
            foreach ($dom->getElementsByTagName('MensagemRetorno') as $key => $value) {
                dump($value->getElementsByTagName('Codigo')->item(0)->nodeValue . ' - ' . $value->getElementsByTagName('Mensagem')->item(0)->nodeValue . ' - ' . $value->getElementsByTagName('Correcao')->item(0)->nodeValue);
            }
        } elseif ($dom->getElementsByTagName('ListaNfse')->length > 0) { //Emitida com sucesso
            $dom->loadXML($dom->getElementsByTagName('ListaNfse')->item(0)->nodeValue);
            dd($dom);
        }
    } else {
        dump('ERRO');
        dd($dom);
    }
} catch (Exception $e) {
    echo $e->getMessage();
}