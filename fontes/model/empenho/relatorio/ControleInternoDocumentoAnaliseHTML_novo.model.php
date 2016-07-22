<?php
/**
 * E-cidade Software Publico para Gestão Municipal
 *   Copyright (C) 2015 DBSeller Serviços de Informática Ltda
 *                          www.dbseller.com.br
 *                          e-cidade@dbseller.com.br
 *   Este programa é software livre; você pode redistribuí-lo e/ou
 *   modificá-lo sob os termos da Licença Pública Geral GNU, conforme
 *   publicada pela Free Software Foundation; tanto a versão 2 da
 *   Licença como (a seu critério) qualquer versão mais nova.
 *   Este programa e distribuído na expectativa de ser útil, mas SEM
 *   QUALQUER GARANTIA; sem mesmo a garantia implícita de
 *   COMERCIALIZAÇÃO ou de ADEQUAÇÃO A QUALQUER PROPÓSITO EM
 *   PARTICULAR. Consulte a Licença Pública Geral GNU para obter mais
 *   detalhes.
 *   Você deve ter recebido uma cópia da Licença Pública Geral GNU
 *   junto com este programa; se não, escreva para a Free Software
 *   Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA
 *   02111-1307, USA.
 *   Cópia da licença no diretório licenca/licenca_en.txt
 *                                 licenca/licenca_pt.txt
 */

class ControleInternoDocumentoAnaliseHTML_novo {

  /**
   *
   * @var integer
   */
  private $iInstrucaoTecnica;

  /**
   *
   * @var text $html
   */
  private $html;

  /**
   *
   * @param integer $iInstrucaoTecnica
   */
  public function __construct($iInstrucaoTecnica) {
    $this->iInstrucaoTecnica = $iInstrucaoTecnica;
  }

  /**
   *
   * @param  integer $iInstrucaoTecnica
   * @return stdClass
   */
  public function getDados($iInstrucaoTecnica) {

    $sSql = "
    select
        analise.sequencial as sequencial_analise,
        analise.data_analise as data_analise,
        analise.parecer as parecer,
        lpad(o40_orgao,2,'0') as codigo_orgao,
        o40_descr as descricao_orgao,
        lpad(o41_unidade,2,'0') as codigo_unidade,
        o41_descr as descricao_unidade,
        cgmcredor.z01_numcgm as numcgm,
        cgmcredor.z01_cgccpf as cnpjinteressado,
        cgmcredor.z01_nome as interessado,
        analise.usuario_analise cgm_analista,
        cgmanalista.z01_nome as nome_analista,
        usuanalista.lotacao as lotacao_analista,
        usuanalista.cargo as cargo_analista,
        analise.usuario_diretor_atual as cgm_diretor_atual,
        cgmdiretor.z01_nome as nome_diretor_atual,
        usudiretor.lotacao as lotacao_diretor_atual,
        usudiretor.cargo as cargo_diretor_atual,
        analise.usuario_chefe_atual as cgm_chefe_atual,
        cgmchefe.z01_nome as nome_chefe_atual,
        usuchefe.lotacao as lotacao_chefe_atual,
        usuchefe.cargo as cargo_chefe_atual
    from
        plugins.controleinternocredor analise
        inner join cgm cgmanalista on analise.usuario_analise = cgmanalista.z01_numcgm
         left join plugins.usuariocontroladoria usuanalista on analise.usuario_analise = usuanalista.numcgm
        inner join cgm cgmdiretor on analise.usuario_diretor_atual = cgmdiretor.z01_numcgm
         left join plugins.usuariocontroladoria usudiretor on analise.usuario_diretor_atual = usudiretor.numcgm
         left join cgm cgmchefe on analise.usuario_chefe_atual = cgmchefe.z01_numcgm
         left join plugins.usuariocontroladoria usuchefe on analise.usuario_chefe_atual = usuchefe.numcgm
         left join cgm cgmaprovacao on analise.usuario_aprovacao = cgmaprovacao.z01_numcgm
        inner join plugins.controleinternosituacoes sitanalista on analise.situacao_analise = sitanalista.sequencial
         left join plugins.controleinternosituacoes sitdiretor on analise.situacao_aprovacao = sitdiretor.sequencial
        inner join (
                        select
                                distinct controleinternocredor, o40_orgao, o40_descr, o41_unidade, o41_descr
                        from plugins.controleinternocredor_empenhonotacontroleinterno analiseliquidacao
                                inner join plugins.empenhonotacontroleinterno controlenota on analiseliquidacao.empenhonotacontroleinterno = controlenota.sequencial
                                inner join empnota on controlenota.nota = e69_codnota
                                inner join empempenho on e69_numemp = e60_numemp
                                inner join empempaut on e60_numemp = e61_numemp
                                inner join empautorizaprocesso on e61_autori = e150_empautoriza
                                inner join orcdotacao on e60_coddot = o58_coddot and e60_anousu = o58_anousu
                                inner join orcunidade on o58_orgao = o41_orgao and o58_unidade = o41_unidade and o58_anousu = o41_anousu
                                inner join orcorgao on o58_orgao = o40_orgao and o58_anousu = o40_anousu
                    ) T on analise.sequencial = T.controleinternocredor
        inner join cgm cgmcredor on analise.numcgm_credor = cgmcredor.z01_numcgm
    where analise.sequencial = $iInstrucaoTecnica
	    ";
    //die($sSql);
    $rsDados = pg_exec($sSql);

    if (pg_numrows($rsDados) > 0) {
      return db_utils::fieldsMemory($rsDados, 0);
    }

    throw new Exception("Dados não encontrados");
  }

  private function getDadosLiquidacoes($iInstrucaoTecnica) {

     $sSql = "
	select
        e150_numeroprocesso as processo_empenho,
        e60_codemp as numero_empenho,
        e60_anousu as ano_empenho,
        controlenota.nota as sequencial_nota
from
        plugins.controleinternocredor analise
        inner join cgm cgmanalista on analise.usuario_analise = cgmanalista.z01_numcgm
        inner join cgm cgmdiretor on analise.usuario_diretor_atual = cgmdiretor.z01_numcgm
        left join cgm cgmchefe on analise.usuario_chefe_atual = cgmchefe.z01_numcgm
        left join cgm cgmaprovacao on analise.usuario_aprovacao = cgmaprovacao.z01_numcgm
        inner join plugins.controleinternosituacoes sitanalista on analise.situacao_analise = sitanalista.sequencial
        left join plugins.controleinternosituacoes sitdiretor on analise.situacao_aprovacao = sitdiretor.sequencial
        inner join plugins.controleinternocredor_empenhonotacontroleinterno analiseliquidacao on analise.sequencial = analiseliquidacao.controleinternocredor
        inner join plugins.empenhonotacontroleinterno controlenota on analiseliquidacao.empenhonotacontroleinterno = controlenota.sequencial
        inner join empnota on controlenota.nota = e69_codnota
        inner join empempenho on e69_numemp = e60_numemp
        inner join empempaut on e60_numemp = e61_numemp
        inner join empautorizaprocesso on e61_autori = e150_empautoriza
        inner join orcdotacao on e60_coddot = o58_coddot and e60_anousu = o58_anousu
        inner join orcunidade on o58_orgao = o41_orgao and o58_unidade = o41_unidade and o58_anousu = o41_anousu
        inner join orcorgao on o58_orgao = o40_orgao and o58_anousu = o40_anousu
        inner join plugins.controleinternosituacoes sitvalido on controlenota.situacao = sitvalido.sequencial
        inner join cgm cgmcredor on analise.numcgm_credor = cgmcredor.z01_numcgm
where analise.sequencial = $iInstrucaoTecnica ";

    $rsDados = pg_exec($sSql);

    if (pg_numrows($rsDados) > 0) {
      return db_utils::getCollectionByRecord($rsDados, 0);
    }

    throw new Exception("Dados não encontrados");
  }

  /**
   * Escreve o cabeçalho do documento
   *
   * @param stdClass $oDados
   */
  private function escreverCabecalho($oDados) {

    $this->html  = "<html>";
    $this->html .= "<head>";
    $this->html .= "<title>controleinterno_documento_analise_html</title>";
    //estilos
    $this->html .= "<style type='text/css'>";
    $this->html .= "<!--";
    $this->html .= ".ft0{font-style:normal;font-weight:bold;font-size:14px;font-family:Arial;color:#000000;}";
    $this->html .= ".ft1{font-style:normal;font-weight:normal;font-size:13px;font-family:Times New Roman;color:#000000;}";
    $this->html .= ".ft2{font-style:normal;font-weight:normal;font-size:13px;font-family:Arial;color:#000000;}";
    $this->html .= ".ft3{font-style:normal;font-weight:bold;font-size:15px;font-family:Arial;color:#000000;}";
    $this->html .= ".ft4{font-style:normal;font-weight:bold;font-size:13px;font-family:Arial;color:#000000;}";
    $this->html .= ".ft5{font-style:normal;font-weight:normal;font-size:16px;font-family:Arial;color:#000000;}";
    $this->html .= ".ft6{font-style:normal;font-weight:normal;font-size:8px;font-family:Arial;color:#000000;}";
    $this->html .= ".ft7{font-style:normal;font-weight:normal;font-size:10px;font-family:Times New Roman;color:#000000;}";
    $this->html .= ".ft8{font-style:normal;font-weight:normal;font-size:14px;font-family:Arial;color:#000000;}";
    $this->html .= "p { ";
    $this->html .= "  display:block;";
    $this->html .= "  width:750px;";
    $this->html .= "  word-wrap:break-word;";
    $this->html .= "  align: justify;";
    $this->html .= "};";
    $this->html .= "-->";
    $this->html .= "</style>";
    $this->html .= "</head>";

    $this->html .= "<body vlink='#FFFFFF' link='#FFFFFF' bgcolor='#ffffff'>";

    $this->html .= "<div style='position:absolute;top:25;left:30'><img width='85' height='95' src='imagens/files/logologoBrasao.jpg' ALT=''></div>";
    $this->html .= "<div style='position:absolute;top:25;left:130'><span class='ft0'>PREFEITURA MUNICIPAL DO NATAL</span></div>";
    $this->html .= "<div style='position:absolute;top:46;left:130'><span class='ft1'>RUA ULISSES CALDAS, 81</span></div>";
    $this->html .= "<div style='position:absolute;top:62;left:130'><span class='ft1'>NATAL - RN</span></div>";
    $this->html .= "<div style='position:absolute;top:78;left:130'><span class='ft1'>08432324900   -    CNPJ : 08.241.747/0001-43</span></div>";
    $this->html .= "<div style='position:absolute;top:109;left:130'><span class='ft1'>www.natal.rn.gov.br</span></div>";
    $this->html .= "<div style='position:absolute;top:25;left:504'><span class='ft2'>                                             CGM</span></div>";
    $this->html .= "<div style='position:absolute;top:37;left:504'><span class='ft2'>Processo Nº: </span></div>";
    $this->html .= "<div style='position:absolute;top:49;left:504'><span class='ft2'>Folha: </span></div>";
    $this->html .= "<div style='position:absolute;top:61;left:504'><span class='ft2'>Visto: </span></div>";
    $this->html .= "<div style='position:absolute;top:115;left:40'><span>_______________________________________________________________________________________________</span></div>";
    $this->html .= "<div style='position:absolute;top:139;left:274'><span class='ft3'>CONTROLADORIA GERAL DO MUNICÍPIO</span></div>";
    $this->html .= "<div style='position:absolute;top:166;left:298'><span class='ft4'>DEPARTAMENTO DE CONTROLE INTERNO</span></div>";

  }

  /**
   * Escreve o corpo do documento.
   *
   * @param stdClass $oDados
   */
  private function escreverConteudo($oDados, $oDadosLiquidacoes) {

    $oDataAnalise = new DBDate($oDados->data_analise);

    $this->html .= "<div style='position:absolute;top:196;left:154'><span class='ft0'>PROCESSO</span></div>";
    $this->html .= "<div style='position:absolute;top:196;left:411'><span class='ft0'>LIQUIDAÇÃO</span></div>";
    $this->html .= "<div style='position:absolute;top:196;left:644'><span class='ft0'>EMPENHO</span></div>";

    $nTop = 212; 
    foreach ($oDadosLiquidacoes as $oItem) {
      $this->html .= "<div style='position:absolute;top:$nTop;left:140'><span class='ft8'>{$oItem->processo_empenho}</span></div>";
      $this->html .= "<div style='position:absolute;top:$nTop;left:430'><span class='ft8'>{$oItem->sequencial_nota}</span></div>";
      $this->html .= "<div style='position:absolute;top:$nTop;left:653'><span class='ft8'>{$oItem->numero_empenho}/{$oItem->ano_empenho}</span></div>";
      $nTop += 20;
    }

    $nTop += 20;
    $this->html .= "<div style='position:absolute;top:$nTop;left:43'><span class='ft0'>INTERESSADO: </span></div>";
    $this->html .= "<div style='position:absolute;top:$nTop;left:156'><span class='ft8'> {$oDados->interessado}</span></div>";
    $nTop += 20;
    $this->html .= "<div style='position:absolute;top:$nTop;left:43'><span class='ft0'>ÓRGÃO DE ORIGEM: </span></div>";
    $this->html .= "<div style='position:absolute;top:$nTop;left:193'><span class='ft8'> {$oDados->codigo_orgao}.{$oDados->codigo_unidade} - {$oDados->descricao_unidade}</span></div>";
    $nTop += 40;
    $this->html .= "<div style='position:absolute;top:$nTop;left:274'><span class='ft0'>INSTRUÇÃO TÉCNICA Nº {$oDados->sequencial_analise}/{$oDataAnalise->getAno()}-DCI/CGM</span></div>";
    $nTop += 16;
    $this->html .= "<div id='ressalva' style='position:absolute;top:$nTop;left:43'><p class='ft5'>{$oDados->parecer}</p></div>";
  }

  /**
   * Escreve despacho, local, data e assinaturas.
   *
   * @param stdClass $oDados
   */
  private function escreverRodape($oDados) {

    $oDataAnalise = new DBDate($oDados->data_analise);
    $oInstituicao = InstituicaoRepository::getInstituicaoPrefeitura();
    $sCidade      = mb_convert_case($oInstituicao->getMunicipio(), MB_CASE_TITLE);
    
    $this->html .= "<div id='rodape' style='position:relative;top:450;'>";
    $this->html .= "<div style='position:absolute;top:0;left:378'><span class='ft0'>DESPACHO</span></div>";
    $this->html .= "<div style='position:absolute;top:18;left:80'><p class='ft5'>De acordo com a informação acima, encaminhamos o processo ao órgão de origem para providências.</p></div>";
    $this->html .= "<div style='position:absolute;top:70;left:325'><span class='ft8'>{$sCidade}, {$oDataAnalise->dataPorExtenso()}</span></div>";
    if ($oDados->nome_analista != "") {
      //$this->html .= "<div style='position:absolute;top:140;left:492'><span class='ft8'> __________________________________________</span></div>";
      $this->html .= "<div style='position:absolute;top:140;left:43'><span class='ft8'>Analista: {$oDados->nome_analista}</span></div>";
      //$this->html .= "<div style='position:absolute;top:176;left:639'><span class='ft8'>{$oDados->cargo_analista}</span></div>";
    }
    if ($oDados->nome_chefe_atual != "") {
      //$this->html .= "<div style='position:absolute;top:140;left:43'><span class='ft8'>__________________________________________</span></div>";
      $this->html .= "<div style='position:absolute;top:160;left:43'><span class='ft8'>Chefe: {$oDados->nome_chefe_atual}</span></div>";
      //$this->html .= "<div style='position:absolute;top:176;left:100'><span class='ft8'>{$oDados->cargo_chefe_atual}</span></div>";
    }
    if ($oDados->nome_diretor_atual != "") {
      $this->html .= "<div style='position:absolute;top:220;left:492'><span class='ft8'>__________________________________________</span></div>";
      $this->html .= "<div style='position:absolute;top:240;left:557'><span class='ft8'>{$oDados->nome_diretor_atual}</span></div>";
      $this->html .= "<div style='position:absolute;top:266;left:569'><span class='ft8'>{$oDados->cargo_diretor_atual}</span></div>";
    }
    $this->html .= "</div>";
    $this->html .= "<script language='javascript'>";
    $this->html .= "window.onload = function initJS(){";
    $this->html .= "  var fimRessalva = document.getElementById('ressalva');";
    $this->html .= "  var fimRodape = fimRessalva.offsetTop + fimRessalva.offsetHeight + 30;";
    $this->html .= "  document.getElementById('rodape').style.top = fimRodape+'px';";
    $this->html .= "  window.print();";
    $this->html .= "}";
    $this->html .= "</script>";

    $this->html .= "</body>";
    $this->html .= "</html>";

  }

  /**
   * Emite o documento.
   */
  public function emitir() {

    $oDados = $this->getDados($this->iInstrucaoTecnica);
    $oDadosLiquidacoes = $this->getDadosLiquidacoes($this->iInstrucaoTecnica);

    $this->escreverCabecalho($oDados);
    $this->escreverConteudo($oDados, $oDadosLiquidacoes);
    $this->escreverRodape($oDados);

    echo "{$this->html}";
  }

}
