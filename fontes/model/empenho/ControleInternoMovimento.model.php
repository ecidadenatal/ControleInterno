<?php
/**
 *     E-cidade Software Publico para Gestao Municipal
 *  Copyright (C) 2014  DBSeller Servicos de Informatica
 *                            www.dbseller.com.br
 *                         e-cidade@dbseller.com.br
 *
 *  Este programa e software livre; voce pode redistribui-lo e/ou
 *  modifica-lo sob os termos da Licenca Publica Geral GNU, conforme
 *  publicada pela Free Software Foundation; tanto a versao 2 da
 *  Licenca como (a seu criterio) qualquer versao mais nova.
 *
 *  Este programa e distribuido na expectativa de ser util, mas SEM
 *  QUALQUER GARANTIA; sem mesmo a garantia implicita de
 *  COMERCIALIZACAO ou de ADEQUACAO A QUALQUER PROPOSITO EM
 *  PARTICULAR. Consulte a Licenca Publica Geral GNU para obter mais
 *  detalhes.
 *
 *  Voce deve ter recebido uma copia da Licenca Publica Geral GNU
 *  junto com este programa; se nao, escreva para a Free Software
 *  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA
 *  02111-1307, USA.
 *
 *  Copia da licenca no diretorio licenca/licenca_en.txt
 *                                licenca/licenca_pt.txt
 */
class ControleInternoMovimento extends ControleInterno {

  /**
   * @type integer
   */
  private $iCodigoMovimento;

  /**
   * @type string
   */
  private $sRessalva;

  /**
   * @type UsuarioSistema
   */
  private $oUsuario;

  /**
   * @type integer
   */
  private $iSituacao;

  /**
   * @type DBDate
   */
  private $oData;

  /**
   * @type string
   */
  private $sHora;

  /**
   * @param $iCodigoNota
   */
  public function __construct($iCodigoNota) {
    parent::__construct($iCodigoNota);
  }

  public function salvar() {

    $this->iSituacaoFinal = $this->iSituacao;
    parent::salvar();

    $oDaoMovimentacao = new cl_empenhonotacontroleinternohistorico();
    $oDaoMovimentacao->sequencial = $this->iCodigoMovimento;
    $oDaoMovimentacao->empenhonotacontroleinterno = $this->iCodigo;
    $oDaoMovimentacao->ressalva   = $this->sRessalva;
    $oDaoMovimentacao->situacao   = $this->iSituacao;
    $oDaoMovimentacao->data       = $this->oData->getDate();
    $oDaoMovimentacao->hora       = $this->sHora;
    if ($this->oUsuario != null) {
      $oDaoMovimentacao->usuario = $this->oUsuario->getCodigo();
    }
    $oDaoMovimentacao->incluir($oDaoMovimentacao->sequencial);
    if ($oDaoMovimentacao->erro_status == "0") {
      throw new Exception("Não foi possível incluir a movimentação para a nota {$this->iCodigoNota}.");
    }
    return true;
  }

  /**
   * Salva as informações de Diretor e Chefe de Divisão de um histórico do Controle Interno
   */
  public function salvarHistoricoUsuario($iCodigoNota, $iSituacao){
    
    if (empty($iCodigoNota) || empty($iSituacao)) { 
      return null;  
    }
    
    $oDaoHistoricoUsuario = new cl_empenhonotacontroleinternohistoricousuario();
    $sSqlDadosUsuario = $oDaoHistoricoUsuario->getDadosUsuarioControladoriaPorHistorico($iCodigoNota);
    $rsDadosUsuario   = db_query($sSqlDadosUsuario);
    $oDadosUsuario    = db_utils::fieldsMemory($rsDadosUsuario, 0);

    if (in_array($iSituacao, array(ControleInterno::SITUACAO_REGULAR, ControleInterno::SITUACAO_DILIGENCIA))) {
      
      $oDaoHistoricoUsuario->empenhonotacontroleinternohistorico = $oDadosUsuario->codigohistorico;
      $oDaoHistoricoUsuario->cgm_chefe = $oDadosUsuario->chefe;
      $oDaoHistoricoUsuario->cgm_diretor = $oDadosUsuario->diretor;      
    
    } else if (in_array($iSituacao, array(ControleInterno::SITUACAO_APROVADA, ControleInterno::SITUACAO_REJEITADA))) {
    
      $oDaoHistoricoUsuario->empenhonotacontroleinternohistorico = $oDadosUsuario->codigohistorico;
      $oDaoHistoricoUsuario->cgm_chefe = null;
      $oDaoHistoricoUsuario->cgm_diretor = $oDadosUsuario->diretor;
    
    } else {
    
      return null;
    
    }
    $oDaoHistoricoUsuario->incluir(null);

    if ($oDaoHistoricoUsuario->erro_status == "0") {
      throw new Exception("Não foi possível incluir os dados de usuário do histórico para a nota {$this->iCodigoNota}.");
    }
    return true;
  }

  /**
   * @return stdClass|null
   */
  public function getUltimoMovimento() {

    if (empty($this->iCodigo)) {
      return null;
    }

    $oDaoRessalva       = new cl_empenhonotacontroleinternohistorico();
    $sSqlBuscaMovimento =
      $oDaoRessalva->sql_query_file(
        null,
        "*",
        "sequencial desc limit 1",
        "empenhonotacontroleinterno = {$this->iCodigo}");

    $rsBuscaMovimento = db_query($sSqlBuscaMovimento);
    if (!$rsBuscaMovimento || pg_num_rows($rsBuscaMovimento) == 0) {
      return null;
    }
    return db_utils::fieldsMemory($rsBuscaMovimento, 0);
  }

  /**
   * @return int
   */
  public function getCodigoMovimento() {
    return $this->iCodigoMovimento;
  }

  /**
   * @param $iCodigo
   */
  public function setCodigoMovimento($iCodigoMovimento) {
    $this->iCodigoMovimento = $iCodigoMovimento;
  }

  /**
   * @return string
   */
  public function getRessalva() {
    return $this->sRessalva;
  }

  /**
   * @param $sObservacao
   */
  public function setRessalva($sRessalva) {
    $this->sRessalva = $sRessalva;
  }

  /**
   * @return \UsuarioSistema
   */
  public function getUsuario() {
    return $this->oUsuario;
  }

  /**
   * @param \UsuarioSistema $oUsuario
   */
  public function setUsuario(UsuarioSistema $oUsuario) {
    $this->oUsuario = $oUsuario;
  }

  /**
   * @return int
   */
  public function getSituacao() {
    return $this->iSituacao;
  }

  /**
   * @return int
   */
  public function getSituacaoFinal() {
    return $this->iSituacaoFinal;
  }

  /**
   * @param $iSituacao
   */
  public function setSituacao($iSituacao) {
    $this->iSituacao = $iSituacao;
  }

  /**
   * @return \DBDate
   */
  public function getData() {
    return $this->oData;
  }

  /**
   * @param $oData
   */
  public function setData(DBDate $oData) {
    $this->oData = $oData;
  }

  /**
   * @return string
   */
  public function getHora() {
    return $this->sHora;
  }

  /**
   * @param $sHora
   */
  public function setHora($sHora) {
    $this->sHora = $sHora;
  }

  /**
   * Cria uma autorização automática do controle interno.
   * @throws Exception
   */
  public function criaAutorizacaoAutomatica() {

    $this->iSituacaoFinal = ControleInterno::SITUACAO_LIBERADO_AUTOMATICO;

    $sRessalva  = "Após a análise da matéria, evidenciamos o cumprimento de exigências legais, podendo o processo ser ";
    $sRessalva .= "encaminhado à origem para adoção das providências que o caso requer.";

    $this->setRessalva($sRessalva);
    $this->setSituacao(ControleInterno::SITUACAO_LIBERADO_AUTOMATICO);
    $this->setData(new DBDate(date('d/m/Y', db_getsession('DB_datausu'))));
    $this->setHora(date('H:i'));
    $this->salvar();
  }
}