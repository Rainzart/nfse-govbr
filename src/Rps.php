<?php

namespace HaDDeR\NfseGovbr;

/**
 * Class for RPS construction and validation of data
 *
 */

use DOMException;
use DOMNode;
use NFePHP\Common\DOMImproved as Dom;
use stdClass;


class Rps implements RpsInterface
{
    /**
     * @var stdClass
     */
    public $std;
    /**
     * @var DOMNode
     */
    protected $rps;
    /**
     * @var string
     */
    protected $jsonschema;
    /**
     * @var Dom
     */
    protected $dom;

    /**
     * Constructor
     * @param stdClass|null $std
     * @throws DOMException
     */
    public function __construct(stdClass $std = null)
    {
        $this->init($std);
        $this->dom = new Dom('1.0', 'UTF-8');
        $this->dom->preserveWhiteSpace = false;
        $this->dom->formatOutput = false;
    }

    public function render(stdClass $std = null)
    {
        if ($this->dom->hasChildNodes()) {
            $this->dom = new Dom('1.0', 'UTF-8');
            $this->dom->preserveWhiteSpace = false;
            $this->dom->formatOutput = false;
        }

        $this->init($std);

        $this->rps = $this->dom->createElement('InfDeclaracaoPrestacaoServico');
        $rps_inner = $this->dom->createElement('Rps');

        $identificacaoRps = $this->dom->createElement('IdentificacaoRps');
        $this->dom->addChild(
            $identificacaoRps,
            'Numero',
            $this->std->rps->identificacaorps->numero,
            true
        );
        $this->dom->addChild(
            $identificacaoRps,
            'Serie',
            $this->std->rps->identificacaorps->serie,
            true
        );
        $this->dom->addChild(
            $identificacaoRps,
            'Tipo',
            $this->std->rps->identificacaorps->tipo,
            true
        );
        $rps_inner->appendChild($identificacaoRps);

        $this->rps->appendChild($rps_inner);

        $this->dom->addChild(
            $rps_inner,
            'DataEmissao',
            $this->std->rps->dataemissao,
            true
        );

        $this->dom->addChild(
            $rps_inner,
            'Status',
            $this->std->rps->status,
            true
        );

        $this->dom->addChild(
            $this->rps,
            'Competencia',
            $this->std->competencia,
            true
        );

        $servico = $this->dom->createElement('Servico');
        $valores = $this->dom->createElement('Valores');
        $this->dom->addChild(
            $valores,
            'ValorServicos',
            $this->std->servico->valores->valorservicos,
            true
        );
        if (isset($this->std->servico->valores->valordeducoes)) {
            $this->dom->addChild(
                $valores,
                'ValorDeducoes',
                $this->std->servico->valores->valordeducoes,
                true
            );
        }
        if (isset($this->std->servico->valores->valorpis)) {
            $this->dom->addChild(
                $valores,
                'ValorPis',
                $this->std->servico->valores->valorpis,
                true
            );
        }
        if (isset($this->std->servico->valores->valorcofins)) {
            $this->dom->addChild(
                $valores,
                'ValorCofins',
                $this->std->servico->valores->valorcofins,
                true
            );
        }
        if (isset($this->std->servico->valores->valorinss)) {
            $this->dom->addChild(
                $valores,
                'ValorInss',
                $this->std->servico->valores->valorinss,
                true
            );
        }
        if (isset($this->std->servico->valores->valorir)) {
            $this->dom->addChild(
                $valores,
                'ValorIr',
                $this->std->servico->valores->valorir,
                true
            );
        }
        if (isset($this->std->servico->valores->valorcsll)) {
            $this->dom->addChild(
                $valores,
                'ValorCsll',
                $this->std->servico->valores->valorcsll,
                true
            );
        }
        if (isset($this->std->servico->valores->outrasretencoes)) {
            $this->dom->addChild(
                $valores,
                'OutrasRetencoes',
                $this->std->servico->valores->outrasretencoes,
                false
            );
        }
        if (isset($this->std->servico->valores->valoriss)) {
            $this->dom->addChild(
                $valores,
                'ValorIss',
                $this->std->servico->valores->valoriss,
                true
            );
        }
        if (isset($this->std->servico->valores->aliquota)) {
            $this->dom->addChild(
                $valores,
                'Aliquota',
                $this->std->servico->valores->aliquota,
                false
            );
        }
        if (isset($this->std->servico->valores->descontoincondicionado)) {
            $this->dom->addChild(
                $valores,
                'DescontoIncondicionado',
                $this->std->servico->valores->descontoincondicionado,
                false
            );
        }
        if (isset($this->std->servico->valores->descontocondicionado)) {
            $this->dom->addChild(
                $valores,
                'DescontoCondicionado',
                $this->std->servico->valores->descontocondicionado,
                false
            );
        }
        $servico->appendChild($valores);
        if (isset($this->std->servico->issretido)) {
            $this->dom->addChild(
                $servico,
                'IssRetido',
                $this->std->servico->issretido,
                true
            );
        }
        if (isset($this->std->servico->responsavelretencao)) {
            $this->dom->addChild(
                $servico,
                'ResponsavelRetencao',
                $this->std->servico->responsavelretencao,
                false
            );
        }
        $this->dom->addChild(
            $servico,
            'ItemListaServico',
            $this->std->servico->itemlistaservico,
            true
        );
        $this->dom->addChild(
            $servico,
            'CodigoTributacaoMunicipio',
            $this->std->servico->codigotributacaomunicipio,
            true
        );
        $this->dom->addChild(
            $servico,
            'Discriminacao',
            $this->std->servico->discriminacao,
            true
        );
        $this->dom->addChild(
            $servico,
            'CodigoMunicipio',
            $this->std->servico->codigomunicipio,
            true
        );
        $this->dom->addChild(
            $servico,
            'CodigoPais',
            $this->std->servico->codigopais,
            true
        );
        $this->dom->addChild(
            $servico,
            'ExigibilidadeISS',
            $this->std->servico->exigibilidadeiss,
            true
        );
        if (isset($this->std->servico->municipioincidencia)) {
            $this->dom->addChild(
                $servico,
                'MunicipioIncidencia',
                $this->std->servico->municipioincidencia,
                true
            );
        }
        $this->rps->appendChild($servico);

        $prest = $this->std->prestador;
        $dom_prestador = $this->dom->createElement('Prestador');
        if (!empty($prest->cpfcnpj->cnpj) or !empty($prest->cpfcnpj->cpf)) {
            $node = $this->dom->createElement('CpfCnpj');
            $dom_prestador->appendChild($node);
            $this->dom->addChild(
                $node,
                (!empty($prest->cpfcnpj->cnpj) ? 'Cnpj' : 'Cpf'),
                (!empty($prest->cpfcnpj->cnpj) ? $prest->cpfcnpj->cnpj : $prest->cpfcnpj->cpf),
                true
            );
            $this->rps->appendChild($dom_prestador);
        }

        $this->dom->addChild(
            $dom_prestador,
            'InscricaoMunicipal',
            $prest->inscricaomunicipal,
            true
        );

        $tom = $this->std->tomador;
        $dom_tomador = $this->dom->createElement('Tomador');
        if (!empty($tom->identificacaotomador->cpfcnpj->cnpj) || !empty($tom->identificacaotomador->cpfcnpj->cpf)) {
            $dom_identificacao_tom = $this->dom->createElement('IdentificacaoTomador');
            $node = $this->dom->createElement('CpfCnpj');
            if (!empty($tom->identificacaotomador->cpfcnpj->cnpj)) {
                $this->dom->addChild(
                    $node,
                    'Cnpj',
                    $tom->identificacaotomador->cpfcnpj->cnpj ?? null,
                    true
                );
            } elseif (!empty($tom->identificacaotomador->cpfcnpj->cpf)) {
                $this->dom->addChild(
                    $node,
                    'Cpf',
                    $tom->identificacaotomador->cpfcnpj->cpf ?? null,
                    true
                );
            }
            $dom_identificacao_tom->appendChild($node);
            $dom_tomador->appendChild($dom_identificacao_tom);
        }
        $this->dom->addChild(
            $dom_tomador,
            "RazaoSocial",
            $tom->razaosocial,
            false
        );

        $dom_endereco_tom = $this->dom->createElement('Endereco');
        $this->dom->addChild(
            $dom_endereco_tom,
            'Endereco',
            $tom->endereco->endereco,
            false
        );
        $this->dom->addChild(
            $dom_endereco_tom,
            'Numero',
            $tom->endereco->numero,
            false
        );
        if (isset($tom->endereco->complemento)) {
            $this->dom->addChild(
                $dom_endereco_tom,
                'Complemento',
                $tom->endereco->complemento,
                false
            );
        }
        $this->dom->addChild(
            $dom_endereco_tom,
            'Bairro',
            $tom->endereco->bairro,
            false
        );
        $this->dom->addChild(
            $dom_endereco_tom,
            'CodigoMunicipio',
            $tom->endereco->codigomunicipio,
            false
        );
        $this->dom->addChild(
            $dom_endereco_tom,
            'Uf',
            $tom->endereco->uf,
            false
        );
        if (isset($tom->endereco->codigopais)) {
            $this->dom->addChild(
                $dom_endereco_tom,
                'CodigoPais',
                $tom->endereco->codigopais,
                false
            );
        }
        $this->dom->addChild(
            $dom_endereco_tom,
            'Cep',
            $tom->endereco->cep,
            false
        );
        $dom_tomador->appendChild($dom_endereco_tom);

        $dom_contato_tom = $this->dom->createElement('Contato');
        $this->dom->addChild(
            $dom_contato_tom,
            'Telefone',
            $tom->contato->telefone,
            false
        );
        $this->dom->addChild(
            $dom_contato_tom,
            'Email',
            $tom->contato->email,
            false
        );
        $dom_tomador->appendChild($dom_contato_tom);
        $this->rps->appendChild($dom_tomador);

        $this->dom->addChild(
            $this->rps,
            'OptanteSimplesNacional',
            $this->std->optantesimplesnacional,
            false
        );
        $this->dom->addChild(
            $this->rps,
            'IncentivoFiscal',
            $this->std->incentivofiscal,
            false
        );

        $this->dom->appendChild($this->rps);
        return str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $this->dom->saveXML());
    }

    /**
     * Inicialize properties and valid input
     * @param stdClass|null $rps
     */
    private function init(stdClass $rps = null)
    {
        if (!empty($rps)) {
            $this->std = $this->propertiesToLower($rps);
            if (empty($this->std->version)) {
                $this->std->version = '2.01';
            }
            if (!isset($this->std->lote) or empty($this->std->lote)) {
                $this->std->lote = rand(100, 999);
            }
            //            $ver = str_replace('.', '_', $this->std->version);
            //            $this->jsonschema = realpath("../storage/jsonSchemes/v$ver/rps.schema");
            //            $this->validInputData();
        }
    }

    public function setFormatOutput(bool $formatOutput)
    {
        $this->dom->formatOutput = $formatOutput;
    }

    public function setStd(stdClass $std)
    {
        $this->init($std);
    }

    /**
     * Change properties names of stdClass to lower case
     * @param stdClass $data
     * @return stdClass
     */
    public static function propertiesToLower(stdClass $data)
    {
        $properties = get_object_vars($data);
        $clone = new stdClass();
        foreach ($properties as $key => $value) {
            if ($value instanceof stdClass) {
                $value = self::propertiesToLower($value);
            }
            $nk = strtolower($key);
            $clone->{$nk} = $value;
        }
        return $clone;
    }

    //    /**
    //     * Validation json data from json Schema
    //     * @param stdClass $data
    //     * @return boolean
    //     * @throws \RuntimeException
    //     */
    //    protected function validInputData()
    //    {
    //        if (!is_file($this->jsonschema)) {
    //            return true;
    //        }
    //        $validator = new JsonValid();
    //        $validator->check($this->std, (object)['$ref' => 'file://' . $this->jsonschema]);
    //        if (!$validator->isValid()) {
    //            $msg = "";
    //            foreach ($validator->getErrors() as $error) {
    //                $msg .= sprintf("[%s] %s\n", $error['property'], $error['message']);
    //            }
    //            throw new InvalidArgumentException($msg);
    //        }
    //        return true;
    //    }

}
