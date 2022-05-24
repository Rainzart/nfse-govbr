<?php

namespace HaDDeR\NfseGovbr\Danfse;

use Exception;
use NFePHP\Common\DOMImproved as Dom;

class Danfse
{
    /**
     * @var Dom
     */
    protected $dom;
    /**
     * @var PdfBase
     */
    private $pdf;
    private $infNfse;
    private $infDecServico;
    /**
     * @var object
     */
    private $wsobj;
    /**
     * @var mixed|string|null
     */
    private $brasao;
    /**
     * @var array
     */
    private $cidades_ibge = [];

    protected $desc = 4; // altura célula descrição
    protected $fdes = 9; // tamanho fonte descrição

    protected $cell = 4; // altura célula dado
    protected $fcel = 10; // tamanho fonte célula
    private $logo;


    /**
     * @throws Exception
     */
    public function __construct($xml, string $logo = null, array $cidades_ibge = [])
    {
        $this->logo = $logo;
        $this->setCidades($cidades_ibge);
        $this->loadDoc($xml);
        $this->pdf = new PdfBase();
        $this->pdf->AddPage();
        $this->pdf->SetAutoPageBreak(true);
        $this->wsobj = $this->loadWsobj($this->infNfse->OrgaoGerador->CodigoMunicipio);
        $this->brasao = realpath(__DIR__ . '/../../storage/images/' . $this->wsobj->brasao);
    }

    /**
     * @throws Exception
     */
    private function loadDoc($xml)
    {
        //        $this->xml = $xml;
        if (!empty($xml)) {
            $this->dom = simplexml_load_string($xml);
            if (!isset($this->dom->Nfse->InfNfse)) {
                throw new Exception('Isso não é uma NFSe.');
            }

            if (isset($this->dom->Nfse)) {
                $this->infNfse = $this->dom->Nfse->InfNfse;
                $this->infDecServico = $this->dom->Nfse->InfNfse->DeclaracaoPrestacaoServico->InfDeclaracaoPrestacaoServico;
            } else {
                $this->infNfse = $this->dom->ListaNfse->CompNfse->Nfse->InfNfse;
                $this->infDecServico = $this->dom->ListaNfse->CompNfse->Nfse->InfNfse->DeclaracaoPrestacaoServico->InfDeclaracaoPrestacaoServico;
            }
            //            dd($this->infNfse, $this->infDecServico);
        }
    }

    public function render($dest = 'I')
    {
        //Linha 208
        $this->cabecalho()
            ->dadosTomador()
            ->dadosIntermediador()
            ->descricaoServicos()
            ->codigoBarras()
            ->protocoloEntrega();
        if (isset($this->dom->NfseCancelamento)) {
            $this->cancelada();
        }

        $nameFile = 'NFSe_' . $this->infNfse->Numero . '_' . $this->infNfse->CodigoVerificacao;
        return $this->pdf->Output($nameFile . '.pdf', $dest);

    }

    private function cabecalho()
    {
        $customXDados = 35;
        $this->pdf->SetFont('times', 'B', 15.3);
        $this->pdf->Cell(null, null, 'DANFSE - Documento Auxiliar da Nota Fiscal de Serviço Eletrônica', '', true, 'C');

        $customY = $this->pdf->GetY();
        $this->pdf->Cell(148, 30, '', 'TLBR', false);
        $customX = $this->pdf->GetX();
        $this->pdf->Image($this->logo, 7, 11, 27, 27, '', '', '', false, 300, '', false, false, '');


        $this->pdf->SetFont('Helvetica', 'B', 8.7);
        $this->pdf->setXY($customXDados, 12);
        $this->pdf->Cell(60, null, $this->infNfse->PrestadorServico->RazaoSocial, '', true);

        $endereco = $this->infNfse->PrestadorServico->Endereco->Endereco;
        if ($this->infNfse->PrestadorServico->Endereco->Numero) {
            $endereco .= ', ' . $this->infNfse->PrestadorServico->Endereco->Numero;
        }
        if ($this->infNfse->PrestadorServico->Endereco->Complemento) {
            $endereco .= ' - ' . $this->infNfse->PrestadorServico->Endereco->Complemento;
        }
        $this->pdf->SetFont('Helvetica', '', 7.4);
        $this->pdf->setX($customXDados);
        $this->pdf->Cell(80, null, $endereco, '', true);
        $cep = 'CEP: ' . mask((string)$this->infNfse->PrestadorServico->Endereco->Cep, '#####-###');
        if ($this->infNfse->PrestadorServico->Endereco->Bairro) {
            $cep .= ' - Bairro: ' . $this->infNfse->PrestadorServico->Endereco->Bairro;
        }
        $this->pdf->setX($customXDados);
        $this->pdf->Cell(80, null, $cep, '', true);
        $this->pdf->setX($customXDados);
        $this->pdf->Cell(80, null, 'Município: ' . $this->resolveCidade($this->infNfse->PrestadorServico->Endereco->CodigoMunicipio) . ' - ' . $this->infNfse->PrestadorServico->Endereco->Uf, '', true);
        if ($this->infNfse->PrestadorServico->Contato) {
            if ($this->infNfse->PrestadorServico->Contato->Email) {
                $this->pdf->setX($customXDados);
                $this->pdf->Cell(80, null, 'E-mail: ' . $this->infNfse->PrestadorServico->Contato->Email, '', true);
            }

            if ($this->infNfse->PrestadorServico->Contato->Telefone) {
                $this->pdf->setX($customXDados);
                $this->pdf->Cell(80, null, 'Fone: ' . maskPhone((string)$this->infNfse->PrestadorServico->Contato->Telefone), '', true);
            }
        }

        $this->pdf->SetFont('Helvetica', 'B', 7.4);
        $this->pdf->setX($customXDados);
        $this->pdf->Cell(28, null, 'CNPJ / CPF', '', false);
        $this->pdf->Cell(28, null, 'Inscrição Estadual', '', false);
        $this->pdf->Cell(28, null, 'Inscrição Municipal', '', true);
        $this->pdf->SetFont('Helvetica', '', 7.4);
        $this->pdf->setX($customXDados);
        if (isset($this->infNfse->PrestadorServico->IdentificacaoPrestador->CpfCnpj->Cnpj)) {
            $cpfcnpj = mask((string)$this->infNfse->PrestadorServico->IdentificacaoPrestador->CpfCnpj->Cnpj, '##.###.###/####-##');
        } else {
            $cpfcnpj = mask((string)$this->infNfse->PrestadorServico->IdentificacaoPrestador->CpfCnpj->Cpf, '###.###.###-##');
        }
        $this->pdf->Cell(28, null, $cpfcnpj, '', false);
        $this->pdf->Cell(28, null, ($this->infNfse->PrestadorServico->IdentificacaoPrestador->InscricaoEstadual ?? '****'), '', false);
        $this->pdf->Cell(28, null, ($this->infNfse->PrestadorServico->IdentificacaoPrestador->InscricaoMunicipal ?? '****'), '', true);


        $this->pdf->setXY($customX, $customY);
        $this->pdf->SetFont('Helvetica', '', 6);
        $this->pdf->Cell(60, 5, 'Número da NFS-e', 'TLR', true, 'C');
        $this->pdf->setX($customX);
        $this->pdf->SetFont('Helvetica', 'B', 11);
        $this->pdf->Cell(60, 10, $this->infNfse->Numero, 'LBR', true, 'C');
        $this->pdf->setX($customX);
        $this->pdf->SetFont('Helvetica', '', 6);
        $this->pdf->Cell(30, 5, 'Data do Serviço', 'TLR', false, 'C');
        $this->pdf->Cell(30, 5, 'Código Verificador', 'TLR', true, 'C');
        $this->pdf->setX($customX);
        $this->pdf->SetFont('Helvetica', 'B', 11);
        $this->pdf->Cell(30, 10, date('d/m/Y', strtotime($this->infDecServico->Competencia)), 'LBR', false, 'C');
        $this->pdf->Cell(30, 10, $this->infNfse->CodigoVerificacao, 'LBR', true, 'C');

        $this->pdf->Ln(2);

        $imgCustomX = $this->pdf->GetX();
        $imgCustomY = $this->pdf->GetY();

        $customX = $this->pdf->GetX() + 15;
        $this->pdf->Cell(106, 20, '', 'TLBR', false);
        $this->pdf->Image($this->brasao, $imgCustomX + 1, $imgCustomY + 3, 14, 14);
        $customX2 = $this->pdf->GetX();
        $customY = $this->pdf->GetY();

        $this->pdf->SetFont('Helvetica', 'B', 10.5);
        $this->pdf->setXY($customX, $customY + 2.1);
        $this->pdf->Cell(60, null, 'Prefeitura Municipal de Cachoeira do Sul/RS', '', true);

        $this->pdf->SetFont('Helvetica', 'B', 8);
        $this->pdf->setX($customX);
        $this->pdf->Cell(60, null, 'Secretaria Municipal da Fazenda', '', true);
        $this->pdf->SetFont('Helvetica', '', 8);
        $this->pdf->setX($customX);
        $this->pdf->Cell(60, null, 'Fone: (51) 3724-6038', '', true);
        $this->pdf->setX($customX);
        $this->pdf->Cell(60, null, 'Site: http://cachoeiradosul-portais.govcloud.com.br/NFSe.Portal', '', true, '', false, 'http://cachoeiradosul-portais.govcloud.com.br/NFSe.Portal');

        $this->pdf->setXY($customX2, $imgCustomY);
        $this->pdf->SetFont('Helvetica', '', 8);
        $this->pdf->Cell(34, 10, 'Dt. de Emissão', 'TLRB', false, 'C');
        $this->pdf->Cell(34, 10, 'Exigibilidade ISS', 'TLRB', false, 'C');
        $this->pdf->Cell(34, 10, 'Tributado no Município', 'TLRB', true, 'C');
        $this->pdf->setX($customX2);
        $this->pdf->Cell(34, 10, date('d/m/Y', strtotime($this->infNfse->DataEmissao)), 'LBR', false, 'C');
        $this->pdf->SetFont('Helvetica', '', 6);
        $this->pdf->Cell(34, 10, mb_strtoupper($this->listExigibilidadeIss((int)$this->infDecServico->Servico->ExigibilidadeISS)), 'LBR', false, 'C');
        $this->pdf->Cell(34, 10, $this->resolveCidade($this->infDecServico->Servico->MunicipioIncidencia), 'LBR', true, 'C');
        return $this;
    }

    private function dadosTomador()
    {
        $tomador = $this->infDecServico->Tomador;
        $this->pdf->SetFont('Helvetica', '', 8.5);
        $this->pdf->setFillColor(191, 191, 191);
        $this->pdf->Cell(136, null, 'TOMADOR DO SERVIÇO', 'TLB', false, 'C', true);
        $customX = $this->pdf->GetX();
        $this->pdf->Cell(null, null, 'Município de Prestação do Serviço', 'TBR', true, 'C', true);
        //Linha 1
        //        $this->pdf->setFillColor(255, 255, 255);
        $this->pdf->SetFont('Helvetica', '', 6);
        $customY = $this->pdf->GetY();
        $this->pdf->Cell(136, null, 'Nome / Razão Social', 'LR', true);
        $this->pdf->SetFont('Helvetica', '', 8);
        $this->pdf->Cell(136, null, $tomador->RazaoSocial, 'LBR', true);

        $endereco_tomador = $tomador->Endereco->Endereco;
        if ($tomador->Endereco->Numero) {
            $endereco_tomador .= ', ' . $tomador->Endereco->Numero;
        }
        if ($tomador->Endereco->Complemento) {
            $endereco_tomador .= ', ' . $tomador->Endereco->Complemento;
        }
        $this->pdf->SetFont('Helvetica', '', 6);
        $this->pdf->Cell(136, null, 'Endereço', 'LR', true);
        $this->pdf->SetFont('Helvetica', '', 8);
        $this->pdf->Cell(136, null, $endereco_tomador, 'LBR', true);

        $this->pdf->SetFont('Helvetica', '', 6);
        $this->pdf->Cell(66, null, 'Cidade', 'LR', false);
        $this->pdf->Cell(8, null, 'UF', 'LR', false);
        $this->pdf->Cell(34, null, 'Fone', 'LR', false);
        $this->pdf->Cell(28, null, 'CEP', 'LR', true);
        $this->pdf->SetFont('Helvetica', '', 8);
        $this->pdf->Cell(66, null, $this->resolveCidade($tomador->Endereco->CodigoMunicipio), 'LBR', false);
        $this->pdf->Cell(8, null, $tomador->Endereco->Uf, 'LBR', false);
        $this->pdf->Cell(34, null, maskPhone((string)$tomador->Contato->Telefone), 'LBR', false);
        $this->pdf->Cell(28, null, mask((string)$tomador->Endereco->Cep, '#####-###'), 'LBR', true);

        $this->pdf->SetFont('Helvetica', '', 6);
        $this->pdf->Cell(136, null, 'Bairro', 'LR', true);
        $this->pdf->SetFont('Helvetica', '', 8);
        $this->pdf->Cell(136, null, $tomador->Endereco->Bairro, 'LBR', true);

        $this->pdf->SetFont('Helvetica', '', 6);
        $this->pdf->Cell(76, null, 'CNPJ / CPF', 'LR', false);
        $this->pdf->Cell(30, null, 'Inscrição Municipal', 'LR', false);
        $this->pdf->Cell(30, null, 'Inscrição Estadual', 'LR', true);
        $this->pdf->SetFont('Helvetica', '', 8);
        if (isset($tomador->IdentificacaoTomador->CpfCnpj->Cnpj)) {
            $cpfcnpj = mask((string)$tomador->IdentificacaoTomador->CpfCnpj->Cnpj, '##.###.###/####-##');
        } else {
            $cpfcnpj = mask((string)$tomador->IdentificacaoTomador->CpfCnpj->Cpf, '###.###.###-##');
        }
        $this->pdf->Cell(76, null, $cpfcnpj, 'LBR', false);
        $this->pdf->Cell(30, null, '', 'LBR', false);
        $this->pdf->Cell(30, null, $tomador->IdentificacaoTomador->InscricaoMunicipal, 'LBR', true);

        $this->pdf->SetFont('Helvetica', '', 6);
        $this->pdf->Cell(136, null, 'E-mail', 'LR', true);
        $this->pdf->SetFont('Helvetica', '', 8);
        $this->pdf->Cell(136, null, $tomador->Contato->Email, 'LBR', false);

        $this->pdf->setXY($customX, $customY);
        $this->pdf->SetFont('Helvetica', '', 10);
        $this->pdf->Cell(null, 37.6, $this->resolveCidade($this->infDecServico->Servico->CodigoMunicipio), 'LRB', true, 'C');
        return $this;
    }

    private function dadosIntermediador()
    {
        $intermediador = $this->infDecServico->Intermediario;
        $this->pdf->SetFont('Helvetica', '', 8.5);
        $this->pdf->setFillColor(191, 191, 191);
        $this->pdf->Cell(208, null, 'INTERMEDIÁRIO DO SERVIÇO', 'TRBL', true, 'C', true);
        //        $customX = $this->pdf->GetX();
        //Linha 1
        $this->pdf->setFillColor(255, 255, 255);
        $this->pdf->SetFont('Helvetica', '', 6);
        $this->pdf->Cell(106, null, 'Nome / Razão Social', 'LR', false);
        $this->pdf->Cell(46, null, 'CNPJ / CPF', 'LR', false);
        $this->pdf->Cell(56, null, 'Inscrição Municipal', 'LR', true);
        $this->pdf->SetFont('Helvetica', '', 8);
        $this->pdf->Cell(106, null, $intermediador->RazaoSocial ?? '*****', 'LBR', false);
        if (isset($intermediador->IdentificacaoTomador->CpfCnpj->Cnpj)) {
            $cpfcnpj = mask((string)$intermediador->IdentificacaoTomador->CpfCnpj->Cnpj, '##.###.###/####-##');
        } elseif (isset($intermediador->IdentificacaoTomador->CpfCnpj->Cpf)) {
            $cpfcnpj = mask((string)$intermediador->IdentificacaoTomador->CpfCnpj->Cpf, '###.###.###-##');
        }
        $this->pdf->Cell(46, null, $cpfcnpj ?? '*****', 'LBR', false);
        $this->pdf->Cell(56, null, $intermediador->IdentificacaoTomador->InscricaoMunicipal ?? '*****', 'LBR', true);

        $this->pdf->SetFont('Helvetica', '', 6);
        $this->pdf->Cell(136, null, 'E-mail', 'LR', false);
        $this->pdf->Cell(28, null, 'Fone', 'LR', false);
        $this->pdf->Cell(44, null, 'Cidade', 'LR', true);
        $this->pdf->SetFont('Helvetica', '', 8);
        $this->pdf->Cell(136, null, $intermediador->Contato->Email ?? '', 'LBR', false);
        $this->pdf->Cell(28, null, $intermediador->Contato->Telefone ?? '', 'LBR', false);
        $this->pdf->Cell(44, null, $intermediador->Endereco->Cidade ?? '*****', 'LBR', true);

        return $this;
    }

    private function descricaoServicos()
    {
        $this->pdf->SetFont('Helvetica', '', 6.5);
        $this->pdf->Cell(124, 4, 'DESCRIÇÃO DOS SERVIÇOS', 'TLBR', false, 'C');
        $this->pdf->Cell(24, 4, 'VALOR TOTAL', 'TLBR', false, 'C');
        $this->pdf->Cell(16, 4, 'ALIQ.', 'TLBR', false, 'C');
        $this->pdf->Cell(24, 4, 'VALOR IMPOSTO', 'TLBR', false, 'C');
        $this->pdf->Cell(20, 4, 'RETIDO', 'TLBR', true, 'C');

        $this->pdf->SetFont('Helvetica', '', 8);
        $customX = $this->pdf->GetX();
        $this->pdf->MultiCell(124, 30, $this->infDecServico->Servico->Discriminacao, 'TLBR', 'L', false, false, '', '', true, 0, false, true, 20, 'T', '');
        $this->pdf->MultiCell(24, 30, number_format((float)$this->infNfse->ValoresNfse->ValorLiquidoNfse, 2, ',', '.'), 'TLBR', 'R', false, false, '', '', true, 0, false, true, 20, 'T', '');
        $this->pdf->MultiCell(16, 30, number_format((float)$this->infNfse->ValoresNfse->Aliquota, 2, ',', '.'), 'TLBR', 'R', false, false, '', '', true, 0, false, true, 20, 'T', '');
        $this->pdf->MultiCell(24, 30, number_format((float)$this->infNfse->ValoresNfse->ValorIss, 2, ',', '.'), 'TLBR', 'R', false, false, '', '', true, 0, false, true, 20, 'T', '');
        $this->pdf->MultiCell(20, 30, ((int)$this->infDecServico->Servico->IssRetido == 1 ? 'SIM' : 'NÃO'), 'TLBR', 'C', false, true, '', '', true, 0, false, true, 20, 'T', '');

        $this->pdf->setX($customX);
        $txt = $this->listItensServicos($this->infDecServico->Servico->ItemListaServico);
        $this->pdf->SetFont('Helvetica', '', 8);
        $this->pdf->Cell(104, null, 'Código do Serviço', 'LR', false);
        $this->pdf->Cell(104, null, 'Código NBS', 'LR', true);
        $this->pdf->MultiCell(104, 12, $txt, 'LBR', 'L', false, false);
        $this->pdf->MultiCell(104, 12, '*******', 'LBR', 'L', false, true);

        $this->pdf->setX($customX);
        $this->pdf->SetFont('Helvetica', '', 6);
        $this->pdf->Cell(26, null, 'CIDE', 'LR', false);
        $this->pdf->Cell(26, null, 'COFINS', 'LR', false);
        $this->pdf->Cell(26, null, 'COFINS Importação', 'LR', false);
        $this->pdf->Cell(26, null, 'ICMS', 'LR', false);
        $this->pdf->Cell(26, null, 'IOF', 'LR', false);
        $this->pdf->Cell(26, null, 'IPI', 'LR', false);
        $this->pdf->Cell(26, null, 'PIS/PASEP', 'LR', false);
        $this->pdf->Cell(26, null, 'PIS/PASEP Importação', 'LR', true);
        $this->pdf->SetFont('Helvetica', '', 8);
        $this->pdf->Cell(26, null, number_format((float)$this->infNfse->ValoresNfse->ValorCide, 2, ',', '.') ?? '', 'LBR', false);
        $this->pdf->Cell(26, null, number_format((float)$this->infNfse->ValoresNfse->ValorCofins, 2, ',', '.') ?? '', 'LBR', false);
        $this->pdf->Cell(26, null, number_format((float)$this->infNfse->ValoresNfse->ValorCofinsImportacao, 2, ',', '.') ?? '', 'LBR', false);
        $this->pdf->Cell(26, null, number_format((float)$this->infNfse->ValoresNfse->ValorICMS, 2, ',', '.') ?? '', 'LBR', false);
        $this->pdf->Cell(26, null, number_format((float)$this->infNfse->ValoresNfse->ValorIOF, 2, ',', '.') ?? '', 'LBR', false);
        $this->pdf->Cell(26, null, number_format((float)$this->infNfse->ValoresNfse->ValorIPI, 2, ',', '.') ?? '', 'LBR', false);
        $this->pdf->Cell(26, null, number_format((float)$this->infNfse->ValoresNfse->ValorPis, 2, ',', '.') ?? '', 'LBR', false);
        $this->pdf->Cell(26, null, number_format((float)$this->infNfse->ValoresNfse->ValorPisImportacao, 2, ',', '.') ?? '', 'LBR', true);

        $this->pdf->SetFont('Helvetica', '', 6);
        $this->pdf->Cell(34.66666666666667, null, 'Base Cálculo ISSQN Próprio', 'LR', false);
        $this->pdf->Cell(34.66666666666667, null, 'Valor do ISSQN Próprio', 'LR', false);
        $this->pdf->Cell(34.66666666666667, null, 'Base Cálculo ISSQN Retido', 'LR', false);
        $this->pdf->Cell(34.66666666666667, null, 'Valor do ISSQN Retido', 'LR', false);
        $this->pdf->Cell(34.66666666666667, null, 'Valor Total do ISSQN', 'LR', false);
        $this->pdf->Cell(34.66666666666667, null, 'Valor Dedução/Descontos', 'LR', true);
        $this->pdf->SetFont('Helvetica', '', 8);
        $this->pdf->Cell(34.66666666666667, null, number_format((float)$this->infNfse->ValoresNfse->BaseCalculo, 2, ',', '.') ?? '', 'LBR', false);
        $this->pdf->Cell(34.66666666666667, null, number_format((float)$this->infNfse->ValoresNfse->ValorIss, 2, ',', '.') ?? '', 'LBR', false);
        $this->pdf->Cell(34.66666666666667, null, number_format((float)$this->infNfse->ValoresNfse->ValorBaseISSRetido, 2, ',', '.') ?? '', 'LBR', false);
        $this->pdf->Cell(34.66666666666667, null, number_format((float)$this->infNfse->ValoresNfse->ValorIssRetido, 2, ',', '.') ?? '', 'LBR', false);
        $this->pdf->Cell(34.66666666666667, null, number_format((float)$this->infNfse->ValoresNfse->ValorIss, 2, ',', '.') ?? '', 'LBR', false);
        $this->pdf->Cell(34.66666666666667, null, number_format((float)$this->infNfse->ValoresNfse->ValorDeducoes, 2, ',', '.') ?? '', 'LBR', true);

        $this->pdf->setFillColor(191, 191, 191);
        $this->pdf->SetFont('Helvetica', '', 8);
        $this->pdf->Cell(40, 5.5, 'Valor Total da NFS-e', 'TLBR', false, 'L', true);
        $this->pdf->SetFont('Helvetica', '', 9);
        $this->pdf->Cell(62, 5.5, number_format((float)$this->infNfse->ValoresNfse->BaseCalculo, 2, ',', '.') ?? '', 'LBR', false, 'L');
        $this->pdf->SetFont('Helvetica', '', 8);
        $this->pdf->Cell(40, 5.5, 'Valor Valor Líquido da NFS-e', 'TLBR', false, 'L', true);
        $this->pdf->SetFont('Helvetica', '', 9);
        $this->pdf->Cell(66, 5.5, number_format((float)$this->infNfse->ValoresNfse->ValorLiquidoNfse, 2, ',', '.') ?? '', 'LBR', true, 'L');

        $txtObs = null;
        if ($this->infDecServico->Rps) {
            $txtObs .= 'NFS-e Gerada a Partir do RPS ' . $this->infDecServico->Rps->IdentificacaoRps->Numero;
            $txtObs .= ' | Série: ' . $this->infDecServico->Rps->IdentificacaoRps->Serie;
            //            dd($this->infDecServico->Rps);
            $txtObs .= ' | Emitido em: ' . date('d/m/Y', strtotime($this->infNfse->DataEmissao));
            $txtObs .= ' | Tipo: ' . ($this->infDecServico->Rps->IdentificacaoRps->Tipo == 1 ? 'RPS' : 'NFS-e');
        }
        $txtObs .= str_replace("  ", '', $this->infNfse->OutrasInformacoes);
        $this->pdf->MultiCell(208, 28, $txtObs, 'TLBR', 'L', false, true, '', '', true, 0, false, true, 20, 'T', '');

        return $this;
    }

    private function codigoBarras()
    {
        $this->pdf->Ln(2);
        $this->pdf->SetFont('Helvetica', 'B', 9);
        $this->pdf->Cell(null, null, 'Para consultar a autenticidade acesse: http://cachoeiradosul-portais.govcloud.com.br/nfse.portal', null, true, 'C', false, 'http://cachoeiradosul-portais.govcloud.com.br/nfse.portal');

        $style = array(
            'position' => '',
            'align' => 'C',
            'stretch' => false,
            'fitwidth' => true,
            'cellfitalign' => 'C',
            'border' => false,
            'hpadding' => 'auto',
            'vpadding' => 'auto',
            'fgcolor' => array(0, 0, 0),
            'bgcolor' => false, //array(255,255,255),
            'text' => true,
            'font' => 'helvetica',
            'fontsize' => 10,
            'stretchtext' => 4
        );

        // PRINT VARIOUS 1D BARCODES
        if (isset($this->infNfse->PrestadorServico->IdentificacaoPrestador->CpfCnpj->Cnpj)) {
            $cpfcnpj = $this->infNfse->PrestadorServico->IdentificacaoPrestador->CpfCnpj->Cnpj;
        } else {
            $cpfcnpj = $this->infNfse->PrestadorServico->IdentificacaoPrestador->CpfCnpj->Cpf;
        }
        $codigo_barras = $this->infNfse->Numero . $this->infNfse->CodigoVerificacao . $cpfcnpj;
        $this->pdf->write1DBarcode($codigo_barras, 'C128', null, '', '', 25, 0.4, $style, 'N');

        $this->pdf->SetFont('Helvetica', '', 10);
        $this->pdf->Cell(null, null, '------------------------------------------------------------------------------------------------------------------------------------', false, true, 'C');
        return $this;
    }

    private function protocoloEntrega()
    {
        //        $this->pdf->Ln(0.5);
        $customXBloco1 = $this->pdf->GetX();
        $customYBloco1 = $this->pdf->GetY();
        $this->pdf->Cell(100, 30, '', 'TLBR', false);
        $customXBloco2 = $this->pdf->GetX();
        $customYBloco2 = $this->pdf->GetY();
        $this->pdf->Cell(50, 30, '', 'TLBR', false);
        $customXBloco3 = $this->pdf->GetX();
        $customYBloco3 = $this->pdf->GetY();
        $this->pdf->Cell(58, 30, '', 'TLBR', true);

        $this->pdf->setXY($customXBloco1, $customYBloco1);
        $this->pdf->SetFont('Helvetica', '', 8);
        $this->pdf->Cell(100, 6, 'Recebi(emos) de', '', true);
        $this->pdf->Cell(100, null, $this->infNfse->PrestadorServico->RazaoSocial, '', true);
        $this->pdf->setY($customYBloco1 + 15);
        $this->pdf->Cell(100, null, 'os serviços constantes da Nota Fiscal Eletrônica indicada ao lado.', '', true);
        $this->pdf->Ln();
        $this->pdf->Cell(40, null, '____/____/________', '', false, 'C');
        $this->pdf->Cell(60, null, '___________________________________', '', true, 'C');
        $this->pdf->Cell(40, null, 'Data', '', false, 'C');
        $this->pdf->Cell(60, null, 'Identificação e assinatura do recebedor', '', true, 'C');

        $this->pdf->setXY($customXBloco2, $customYBloco2);
        $this->pdf->Cell(50, 6, $this->infNfse->Numero, '', true, 'C');
        $this->pdf->setX($customXBloco2);
        $this->pdf->Cell(50, null, 'Número da NFS-e', '', true, 'C');
        $this->pdf->setX($customXBloco2);
        $this->pdf->Cell(50, 6, 'Competência', '', true, 'C');
        $this->pdf->setX($customXBloco2);
        $this->pdf->Cell(50, null, date('d/m/Y', strtotime($this->infDecServico->Competencia)), '', true, 'C');
        $this->pdf->setX($customXBloco2);
        $this->pdf->Cell(50, 6, 'NFS-e', '', true, 'C');
        $this->pdf->setX($customXBloco2);
        $this->pdf->Cell(50, null, $this->infNfse->CodigoVerificacao, '', true, 'C');

        $this->pdf->setXY($customXBloco3, $customYBloco3 + 5);
        $this->pdf->Cell(58, null, 'Número de Controle do Município', '', true, 'C');

        $this->pdf->setY($customYBloco1 + 35);
        $this->pdf->SetFont('Helvetica', 'B', 9);
        $this->pdf->Cell(null, null, 'Para consultar a autenticidade acesse: http://cachoeiradosul-portais.govcloud.com.br/nfse.portal', null, true, 'C', false, 'http://cachoeiradosul-portais.govcloud.com.br/nfse.portal');
    }

    public function traco()
    {
        $this->pdf->Ln(0.5);
        $this->pdf->SetFont('Calibri', '', 6.5);
        $this->pdf->Cell(85, 3, 'Gerado Por:', '', false, 'C', false, '', false, true);
        $this->pdf->Cell(87, 3, 'Impresso Por:', '', true, 'C', false, '', false, true);
        //        $this->pdf->Cell(98.5, 5.5, 'Gerado Por:', 'TLBR', true, 'L', false, '', false, true);

        $this->pdf->SetFont('Calibri', '', 25);
        $this->pdf->Cell(0, 1, str_pad('-', 83, ' -', STR_PAD_RIGHT), '', true, '', false, '', false, true);

        return $this;
    }


    /**
     * @throws Exception
     */
    public function loadWsobj(string $cmun)
    {
        $path = realpath(__DIR__ . "/../../storage/urls_webservices.json");
        $urls = json_decode(file_get_contents($path), true);
        if (empty($urls[$cmun])) {
            throw new Exception("Não localizado parâmetros para esse municipio.");
        }
        return (object)$urls[$cmun];
    }

    public function setCidades(array $cidades)
    {
        $this->cidades_ibge = $cidades;
    }

    private function resolveCidade(string $codigoMunicipio)
    {
        return $this->cidades_ibge[$codigoMunicipio] ?? $codigoMunicipio;
    }

    private function cancelada()
    {
        $customXOriginal = $this->pdf->GetX();
        $customYOriginal = $this->pdf->GetY();
        $customX = ($this->pdf->getPageWidth() / 2) / 2;
        $customY = 210;

        $this->pdf->SetAlpha(0.5);
        $this->pdf->setTextColor(150, 150, 150);
        $this->pdf->SetFont('Helvetica', '', 85);
        $this->pdf->SetY($customY);
        $this->pdf->SetX($customX);
        $this->pdf->StartTransform();
        $this->pdf->Rotate(60);
        $this->pdf->Cell(0, 0, 'Cancelada', '', 1, 'C', false);
        $this->pdf->StopTransform();
        $this->pdf->setXY($customXOriginal, $customYOriginal);
        $this->pdf->setTextColor(0, 0, 0);
        $this->pdf->SetAlpha(1);
        return $this;
    }

    private function listItensServicos($id)
    {
        $id = (string)$id;
        $dados = [
            '01.04' => '01.04 - Elaboração de programas de computadores, inclusive de jogos eletrônicos, independentemente da arquitetura construtiva da máquina em que o programa será executado, incluindo tablets, smartphones e congêneres.',
            '10.02' => '10.02 - Agenciamento, corretagem ou intermediação de títulos em geral, valores mobiliários e contratos quaisquer.',
        ];
        return (!empty($id) and array_key_exists($id, $dados)) ? $dados[$id] : $id;
        //        if (!empty($id)) {
        //            return $dados[$id];
        //        } else {
        //            return $id;
        //        }
    }

    private function listExigibilidadeIss(int $id)
    {
        $dados = [
            1 => 'Exigível',
            2 => 'Não incidência',
            3 => 'Isenção',
            4 => 'Exportação',
            5 => 'Imunidade',
            6 => 'Exigibilidade Suspensa por Decisão Judicial',
            7 => 'Exigibilidade Suspensa por Processo Administrativo',
        ];
        if (!empty($id)) {
            return $dados[$id];
        } else {
            return $id;
        }
    }


}