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

class ControleInternoDocumentoAnalise {

  /**
   *
   * @var integer
   */
  private $iCodigoNota;

  /**
   *
   * @var PDFDocument
   */
  private $oPdf;

  /**
   *
   * @param integer $iCodigoNota
   */
  public function __construct($iCodigoNota) {

    $this->iCodigoNota = $iCodigoNota;
    $this->oPdf        = new PDFDocument;
    $this->iAltura     = 4;
    $this->iLargura    = $this->oPdf->getAvailWidth() - 10;
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

    $this->oPdf->addHeaderDescription("                                            CGM");
    $this->oPdf->addHeaderDescription("Processo Nº: {$oDados->processo_empenho}");
    $this->oPdf->addHeaderDescription("Folha: ");
    $this->oPdf->addHeaderDescription("Visto: ");
    $this->oPdf->AddPage();

    $this->oPdf->SetFontSize(10);
    $this->oPdf->setBold(true);
    $this->oPdf->Cell($this->iLargura, $this->iAltura, 'CONTROLADORIA GERAL DO MUNICÍPIO', 0, 1, 'C');
    $this->oPdf->SetFontSize(8);
    $this->oPdf->Cell($this->iLargura, $this->iAltura, 'DEPARTAMENTO DE CONTROLE INTERNO', 0, 1, 'C');
    $this->oPdf->setBold(false);
    $this->oPdf->SetFontSize(9);
    $this->oPdf->Ln($this->iAltura);
  }

  /**
   * Escreve despacho, local, data e assinaturas.
   *
   * @param stdClass $oDados
   */
  private function escreverRodape($oDados) {

    /**
     * Verifica se tem espaço antes de escrever
     */
    if ($this->oPdf->getAvailHeight() < 70) {
      $this->oPdf->AddPage();
    }

    $oDataAnalise = new DBDate($oDados->data_analise);
    $oInstituicao = InstituicaoRepository::getInstituicaoPrefeitura();
    $sCidade      = mb_convert_case($oInstituicao->getMunicipio(), MB_CASE_TITLE);

    $this->oPdf->setBold(true);
    $this->oPdf->MultiCell($this->iLargura, $this->iAltura, "DESPACHO", 0, "C");
    $this->oPdf->setBold(false);
    $this->oPdf->MultiCell($this->iLargura, $this->iAltura, "De acordo com a informação acima, encaminhamos o processo ao órgão de origem para providências.", 0, "C");
    $this->oPdf->Ln($this->iAltura * 2);

    $this->oPdf->Cell($this->iLargura, $this->iAltura, "{$sCidade}, {$oDataAnalise->dataPorExtenso()}", 0, 1, 'C');
    $this->oPdf->Ln($this->iAltura * 2);

    if (!empty($oDados->chefe)) {
      $this->oPdf->MultiCell(100, $this->iAltura, "__________________________________________", 0, 'L');
      $this->oPdf->MultiCell(100, $this->iAltura, $oDados->chefe, 0, 'L');
      $this->oPdf->MultiCell($this->iLargura, $this->iAltura, $oDados->lotacao_chefe, 0, 'L');
    }

    $this->oPdf->MultiCell($this->iLargura, $this->iAltura, "__________________________________________", 0, 'R');
    if (!empty($oDados->usuario_liberacao_inicial)) {
      $this->oPdf->MultiCell($this->iLargura, $this->iAltura, $oDados->usuario_liberacao_inicial, 0, 'R');
    }
    $this->oPdf->MultiCell($this->iLargura, $this->iAltura, $oDados->lotacao_liberacao_inicial, 0, 'R');
    $this->oPdf->Ln($this->iAltura * 2);

    $this->oPdf->MultiCell($this->iLargura, $this->iAltura, "__________________________________________", 0, 'R');
    //if (!empty($oDados->usuario_liberacao_final)) {
      
      $this->oPdf->MultiCell($this->iLargura, $this->iAltura, $oDados->usuario_liberacao_final, 0, 'R');
      //$this->oPdf->MultiCell($this->iLargura, $this->iAltura, "JANICE MONTEIRO DA SILVA", 0, 'R');
    //}
      $this->oPdf->MultiCell($this->iLargura, $this->iAltura, $oDados->lotacao_liberacao_final, 0, 'R');    
      //$this->oPdf->MultiCell($this->iLargura, $this->iAltura, "Diretora de Controle Interno", 0, 'R');
  }

  /**
   * Escreve o corpo do documento.
   *
   * @param stdClass $oDados
   */
  private function escreverConteudo($oDados) {

    $oDataAnalise = new DBDate($oDados->data_analise);

    $this->oPdf->setBold(true);
    $this->oPdf->Cell($this->iLargura * 0.40, $this->iAltura, 'PROCESSO', 'TBLR', 0, 'C');
    $this->oPdf->Cell($this->iLargura * 0.30, $this->iAltura, 'LIQUIDAÇÃO', 'TBLR', 0, 'C');
    $this->oPdf->Cell($this->iLargura * 0.30, $this->iAltura, 'EMPENHO', 'TBLR', 1, 'C');
    $this->oPdf->setBold(false);
    $this->oPdf->Cell($this->iLargura * 0.40, $this->iAltura, $oDados->processo_empenho, 'BLR', 0, 'C');
    $this->oPdf->Cell($this->iLargura * 0.30, $this->iAltura, $oDados->sequencial_nota, 'BLR', 0, 'C');
    $this->oPdf->Cell($this->iLargura * 0.30, $this->iAltura, "{$oDados->numero_empenho}/{$oDados->ano_empenho}", 'BLR', 1, 'C');

    $this->oPdf->setBold(true);
    $this->oPdf->Cell($this->iLargura * 0.15 , $this->iAltura, "INTERESSADO: ", 0, 0);
    $this->oPdf->setBold(false);
    $this->oPdf->Cell($this->iLargura * 0.2 , $this->iAltura, $oDados->interessado, 0, 1);

    $this->oPdf->setBold(true);
    $this->oPdf->Cell($this->iLargura * 0.2, $this->iAltura, "ÓRGÃO DE ORIGEM: ", 0, 0);
    $this->oPdf->setBold(false);
    $this->oPdf->Cell($this->iLargura * 0.2, $this->iAltura, "{$oDados->codigo_orgao}.".str_pad($oDados->codigo_unidade, 2, "0", STR_PAD_LEFT)." - {$oDados->descricao_unidade}", 0, 1);
    $this->oPdf->Ln($this->iAltura);

    $this->oPdf->setBold(true);
    $this->oPdf->Cell($this->iLargura, $this->iAltura, "INSTRUÇÃO TÉCNICA Nº {$oDados->sequencial_analise}/{$oDataAnalise->getAno()}-DCI/CGM", 0, 1, 'C');
    $this->oPdf->setBold(false);
    $this->oPdf->MultiCell($this->iLargura, $this->iAltura, $oDados->ressalva);
    $this->oPdf->Ln($this->iAltura);

  }

  /**
   * Emite o documento.
   */
  public function emitir() {

    $oDados = $this->getDados($this->iCodigoNota);

    $this->oPdf->SetLeftMargin(10);
    $this->oPdf->Open();
    $this->oPdf->AliasNbPages();
    $this->oPdf->SetAutoPageBreak(true, 12);
    $this->oPdf->SetFillcolor(235);
    $this->oPdf->SetFont('arial', '', 6);

    $this->escreverCabecalho($oDados);
    $this->escreverConteudo($oDados);
    $this->escreverRodape($oDados);

    $this->oPdf->showPDF('controleinterno_documento_analise.pdf');
  }

}
