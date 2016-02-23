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

class ControleInternoDocumentoAnaliseHTML {

  /**
   *
   * @var integer
   */
  private $iCodigoNota;

  /**
   *
   * @var text $html
   */
  private $html;

  /**
   *
   * @param integer $iCodigoNota
   */
  public function __construct($iCodigoNota) {

    $this->iCodigoNota = $iCodigoNota;
  }

  /**
   *
   * @param  integer $iCodigoNota
   * @return stdClass
   */
  public function getDados($iCodigoNota) {

    $oDaoControleInterno = new cl_empenhonotacontroleinterno;

    /**
     * Pega o ultimo movimento feito pelo analista
     */
    $sSqlUsuarioAnalista  = '( select usuario ';
    $sSqlUsuarioAnalista .= 'from plugins.empenhonotacontroleinternohistorico historico ';
    $sSqlUsuarioAnalista .= 'where (situacao = '. ControleInterno::SITUACAO_REJEITADO_ANALISTA . ' or situacao = ' . ControleInterno::SITUACAO_LIBERADO_ANALISTA. ")";
    $sSqlUsuarioAnalista .= 'and empenhonotacontroleinterno = empenhonotacontroleinterno.sequencial ';
    $sSqlUsuarioAnalista .= 'order by historico.data desc, historico.hora desc, historico.sequencial desc limit 1 )';

    /**
     * Pega o ultimo movimento feito pelo chefe
     */
    $sSqlUsuarioChefe  = '( select id_usuario ';
    $sSqlUsuarioChefe .= '  from db_usuacgm ';
    $sSqlUsuarioChefe .= '  inner join plugins.empenhonotacontroleinternohistoricousuario as historicousuario on historicousuario.cgm_chefe = db_usuacgm.cgmlogin';
    $sSqlUsuarioChefe .= '  inner join plugins.empenhonotacontroleinternohistorico as historico on historico.sequencial = historicousuario.empenhonotacontroleinternohistorico ';
    $sSqlUsuarioChefe .= '  inner join plugins.empenhonotacontroleinterno as controleinterno on controleinterno.sequencial = historico.empenhonotacontroleinterno ';
    $sSqlUsuarioChefe .= 'where (historico.situacao not in ('. ControleInterno::SITUACAO_AGUARDANDO_ANALISE . ', ' . ControleInterno::SITUACAO_LIBERADO_AUTOMATICO. ")) ";
    $sSqlUsuarioChefe .= 'and empenhonotacontroleinterno = controleinterno.sequencial ';
    $sSqlUsuarioChefe .= 'and controleinterno.nota = '. $iCodigoNota .' ';
    $sSqlUsuarioChefe .= 'order by historico.data desc, historico.hora desc, historico.sequencial desc limit 1 )';

    /**
     * Pega o ultimo movimento feito pelo diretor
     */
    $sSqlUsuarioDiretor  = '( select id_usuario ';
    $sSqlUsuarioDiretor .= '  from db_usuacgm ';
    $sSqlUsuarioDiretor .= '  inner join plugins.empenhonotacontroleinternohistoricousuario as historicousuario on historicousuario.cgm_diretor = db_usuacgm.cgmlogin';
    $sSqlUsuarioDiretor .= '  inner join plugins.empenhonotacontroleinternohistorico as historico on historico.sequencial = historicousuario.empenhonotacontroleinternohistorico ';
    $sSqlUsuarioDiretor .= '  inner join plugins.empenhonotacontroleinterno as controleinterno on controleinterno.sequencial = historico.empenhonotacontroleinterno ';
    $sSqlUsuarioDiretor .= 'where (historico.situacao not in ('. ControleInterno::SITUACAO_AGUARDANDO_ANALISE . ', ' . ControleInterno::SITUACAO_LIBERADO_AUTOMATICO. ")) ";
    $sSqlUsuarioDiretor .= 'and empenhonotacontroleinterno = controleinterno.sequencial ';
    $sSqlUsuarioDiretor .= 'and controleinterno.nota = '. $iCodigoNota .' ';
    $sSqlUsuarioDiretor .= 'order by historico.data desc, historico.hora desc, historico.sequencial desc limit 1 )';

    /**
     * Ordena pelo ultimo movimento
     */
    $sOrderUltimoMovimento = 'empenhonotacontroleinternohistorico.sequencial desc';

    $sWhere   = "nota = {$iCodigoNota}";


    $sCampos  = 'e150_numeroprocesso as processo_empenho,';
    $sCampos .= 'e60_codemp as numero_empenho,';
    $sCampos .= 'e60_anousu as ano_empenho,';
    $sCampos .= 'nota as sequencial_nota,';
    $sCampos .= 'empenhonotacontroleinternohistorico.data as data_analise,';
    $sCampos .= 'empenhonotacontroleinternohistorico.ressalva,';
    $sCampos .= 'o40_orgao as codigo_orgao,';
    $sCampos .= 'o40_descr as descricao_orgao,';
    $sCampos .= 'o41_unidade as codigo_unidade,';
    $sCampos .= 'o41_descr as descricao_unidade,';
    $sCampos .= 'z01_nome as interessado,';
    $sCampos .= 'empenhonotacontroleinternohistorico.sequencial as sequencial_analise,';
    $sCampos .= "(select nome from db_usuarios where id_usuario = {$sSqlUsuarioDiretor}) as usuario_liberacao_final,";
    $sCampos .= "(select nome from db_usuarios where id_usuario = {$sSqlUsuarioAnalista}) as usuario_liberacao_inicial,";
    $sCampos .= "(select z01_nome from db_usuarios inner join db_usuacgm on db_usuarios.id_usuario = db_usuacgm.id_usuario inner join plugins.usuariocontroladoria u on db_usuacgm.cgmlogin = u.numcgm inner join cgm on db_usuacgm.cgmlogin = z01_numcgm where db_usuarios.id_usuario = {$sSqlUsuarioChefe}) as chefe,";
    $sCampos .= "(select u.cargo from db_usuarios inner join db_usuacgm on db_usuarios.id_usuario = db_usuacgm.id_usuario inner join plugins.usuariocontroladoria u on db_usuacgm.cgmlogin = u.numcgm  where db_usuarios.id_usuario = {$sSqlUsuarioChefe}) as lotacao_chefe, ";
    $sCampos .= "(select u.cargo from db_usuarios inner join db_usuacgm on db_usuarios.id_usuario = db_usuacgm.id_usuario inner join plugins.usuariocontroladoria u on db_usuacgm.cgmlogin = u.numcgm  where db_usuarios.id_usuario = {$sSqlUsuarioDiretor}) as lotacao_liberacao_final,";
    $sCampos .= "(select u.cargo from db_usuarios inner join db_usuacgm on db_usuarios.id_usuario = db_usuacgm.id_usuario inner join plugins.usuariocontroladoria u on db_usuacgm.cgmlogin = u.numcgm  where db_usuarios.id_usuario = {$sSqlUsuarioAnalista}) as lotacao_liberacao_inicial";
    $sCampos .= "";
    $sSql     = $oDaoControleInterno->sql_query_documento_analise($sCampos, $sOrderUltimoMovimento, $sWhere);

    $rsDados  = $oDaoControleInterno->sql_record($sSql);

    if ($oDaoControleInterno->numrows > 0) {
      return db_utils::fieldsMemory($rsDados, 0);
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
    $this->html .= "<div style='position:absolute;top:37;left:504'><span class='ft2'>Processo Nº: {$oDados->processo_empenho}</span></div>";
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
  private function escreverConteudo($oDados) {

    $oDataAnalise = new DBDate($oDados->data_analise);

    $this->html .= "<div style='position:absolute;top:196;left:154'><span class='ft0'>PROCESSO</span></div>";
    $this->html .= "<div style='position:absolute;top:196;left:411'><span class='ft0'>LIQUIDAÇÃO</span></div>";
    $this->html .= "<div style='position:absolute;top:196;left:644'><span class='ft0'>EMPENHO</span></div>";
    $this->html .= "<div style='position:absolute;top:212;left:140'><span class='ft8'>{$oDados->processo_empenho}</span></div>";
    $this->html .= "<div style='position:absolute;top:212;left:430'><span class='ft8'>{$oDados->sequencial_nota}</span></div>";
    $this->html .= "<div style='position:absolute;top:212;left:653'><span class='ft8'> {$oDados->numero_empenho}/{$oDados->ano_empenho}</span></div>";
    $this->html .= "<div style='position:absolute;top:235;left:43'><span class='ft0'>INTERESSADO: </span></div>";
    $this->html .= "<div style='position:absolute;top:235;left:156'><span class='ft8'> {$oDados->interessado}</span></div>";
    $this->html .= "<div style='position:absolute;top:255;left:43'><span class='ft0'>ÓRGÃO DE ORIGEM: </span></div>";
    $this->html .= "<div style='position:absolute;top:255;left:193'><span class='ft8'> {$oDados->codigo_orgao}{$oDados->codigo_unidade} - {$oDados->descricao_unidade}</span></div>";
    $this->html .= "<div style='position:absolute;top:295;left:274'><span class='ft0'>INSTRUÇÃO TÉCNICA Nº {$oDados->sequencial_analise}/{$oDataAnalise->getAno()}-DCI/CGM</span></div>";
    //$this->html .= "<div style='position:absolute;top:281;left:43'><span class='ft5'>{$oDados->ressalva}</span></div>";
    $this->html .= "<div id='ressalva' style='position:absolute;top:311;left:43'><p class='ft5'>{$oDados->ressalva}</p></div>";
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
    $this->html .= "<div style='position:absolute;top:140;left:43'><span class='ft8'>__________________________________________</span></div>";
    $this->html .= "<div style='position:absolute;top:160;left:43'><span class='ft8'>{$oDados->chefe}</span></div>";
    $this->html .= "<div style='position:absolute;top:176;left:100'><span class='ft8'>{$oDados->lotacao_chefe}</span></div>";
    $this->html .= "<div style='position:absolute;top:140;left:492'><span class='ft8'> __________________________________________</span></div>";
    $this->html .= "<div style='position:absolute;top:160;left:557'><span class='ft8'>{$oDados->usuario_liberacao_inicial}</span></div>";
    $this->html .= "<div style='position:absolute;top:176;left:639'><span class='ft8'>{$oDados->lotacao_liberacao_inicial}</span></div>";
    $this->html .= "<div style='position:absolute;top:220;left:492'><span class='ft8'>__________________________________________</span></div>";
    $this->html .= "<div style='position:absolute;top:240;left:557'><span class='ft8'>{$oDados->usuario_liberacao_final}</span></div>";
    $this->html .= "<div style='position:absolute;top:266;left:569'><span class='ft8'>{$oDados->lotacao_liberacao_final}</span></div>";
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

    $oDados = $this->getDados($this->iCodigoNota);

    $this->escreverCabecalho($oDados);
    $this->escreverConteudo($oDados);
    $this->escreverRodape($oDados);

    echo "{$this->html}";
  }

}
