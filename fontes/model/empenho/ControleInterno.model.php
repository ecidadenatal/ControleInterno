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

/**
 * Class ControleInterno
 */
abstract class ControleInterno {

  /**
   * Tipos de situacao
   */

  const SITUACAO_AGUARDANDO_ANALISE  = 1;
  const SITUACAO_DILIGENCIA          = 2;
  const SITUACAO_REGULAR             = 3;
  const SITUACAO_REJEITADA           = 4;
  const SITUACAO_APROVADA            = 5;
  const SITUACAO_LIBERADO_AUTOMATICO = 6;
  const SITUACAO_RESSALVA            = 7;
  const SITUACAO_IRREGULAR           = 8;

  const CONTROLE_ANALISTA = 1;
  const CONTROLE_DIRETOR  = 2;

  /**
   * @type integer
   */
  protected $iCodigo;

  /**
   * @type integer
   */
  protected $iCodigoNota;

  /**
   * @type integer
   */
  protected $iSituacaoFinal = self::SITUACAO_AGUARDANDO_ANALISE;


  protected function __construct($iCodigoNota) {

    $oDaoControleInterno  = new cl_empenhonotacontroleinterno();
    $sSqlBuscaControle    = $oDaoControleInterno->sql_query_file(null, "*", null, "nota = {$iCodigoNota}");
    $rsBuscaControle      = $oDaoControleInterno->sql_record($sSqlBuscaControle);
    $this->iCodigoNota    = $iCodigoNota;

    if ($oDaoControleInterno->numrows == 1) {

      $oStdControle         = db_utils::fieldsMemory($rsBuscaControle, 0);
      $this->iCodigo        = $oStdControle->sequencial;
      $this->iSituacaoFinal = $oStdControle->situacao;
    }
  }

  /**
   * Retorna os desdobramentos configurados para utilizaçao
   * na liberaçao automatica
   *
   * @return array
   */
  public static function getDesdobramentos() {

    $oPlugin = new Plugin(null, 'ControleInterno');
    $aConfig = PluginService::getPluginConfig($oPlugin);

    /**
     * Se o arquivo de configuracao nao existir retorna vazio
     */
    if (empty($aConfig)) {
      return array();
    }

    /**
     * Remove espaços em branco
     */
    $aDesdobramentos = array_map(function($sValor) {

      return trim($sValor);
    }, explode(',', $aConfig['Desdobramentos']));

    /**
     * Remove itens vazios
     */
    return array_filter($aDesdobramentos, function($sValor) {

      $sValorSemEspacos = trim($sValor);

      return !empty($sValorSemEspacos);
    });
  }

  /**
   *
   */
  protected function salvar() {

    $oDaoControleInterno = new cl_empenhonotacontroleinterno();
    $oDaoControleInterno->sequencial = $this->iCodigo;
    $oDaoControleInterno->nota       = $this->iCodigoNota;
    $oDaoControleInterno->situacao   = $this->iSituacaoFinal;

    if (empty($this->iCodigo)) {

      $oDaoControleInterno->incluir($this->iCodigo);
      $this->iCodigo = $oDaoControleInterno->sequencial;
    } else {
      $oDaoControleInterno->alterar($this->iCodigo);
    }

    if ($oDaoControleInterno->erro_status == "0") {
      throw new Exception("Não foi possível salvar os dados do controle interno.");
    }
  }

  /**
   * @param $iCodigo
   */
  public function setCodigo($iCodigo) {
    $this->iCodigo = $iCodigo;
  }

  /**
   * @return int
   */
  public function getCodigo() {
    return $this->iCodigo;
  }

  /**
   * @param $iCodigoNota
   */
  public function setCodigoNota($iCodigoNota) {
    $this->iCodigoNota = $iCodigoNota;
  }

  /**
   * @return int
   */
  public function getCodigoNota() {
    return $this->iCodigoNota;
  }

  /**
   * @param $iSituacaoFinal
   *
   * @throws \Exception
   */
  public function setSituacaoFinal($iSituacaoFinal) {
    $this->iSituacaoFinal = $iSituacaoFinal;
  }

  /**
   * Verifica se a liquidação deve passar pela análise automaticamente a partir do seu elemento.
   * @param string $sElemento Elemento da liquidação para ser testado.
   *
   * @return bool
   */
  public static function verificaAnaliseAutomatica($sElemento) {

    $aDesdobramentos = self::getDesdobramentos();

    foreach ($aDesdobramentos as $sDesdobramento) {

      $sDesdobramento = trim($sDesdobramento);
      if ($sDesdobramento == substr($sElemento, 0, strlen($sDesdobramento))) {
        return true;
      }
    }

    return false;
  }

  /**
   * Verifica se o controle interno liberou a nota.
   * @param $iCodigoNota
   * @return bool
   */
  public static function liberado($iCodigoNota) {

    $oControleInterno = new ControleInternoMovimento($iCodigoNota);

    $aLiberacao = array(
      self::SITUACAO_REGULAR,
      self::SITUACAO_RESSALVA,
      self::SITUACAO_APROVADA,
      self::SITUACAO_LIBERADO_AUTOMATICO
    );
    if (in_array($oControleInterno->getSituacaoFinal(), $aLiberacao)) {
      return true;
    }
    return false;
  }
}