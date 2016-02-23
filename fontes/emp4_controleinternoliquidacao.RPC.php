<?php
/**
 * E-cidade Software Publico para Gest�o Municipal
 *   Copyright (C) 2015 DBSeller Servi�os de Inform�tica Ltda
 *                          www.dbseller.com.br
 *                          e-cidade@dbseller.com.br
 *   Este programa � software livre; voc� pode redistribu�-lo e/ou
 *   modific�-lo sob os termos da Licen�a P�blica Geral GNU, conforme
 *   publicada pela Free Software Foundation; tanto a vers�o 2 da
 *   Licen�a como (a seu crit�rio) qualquer vers�o mais nova.
 *   Este programa e distribu�do na expectativa de ser �til, mas SEM
 *   QUALQUER GARANTIA; sem mesmo a garantia impl�cita de
 *   COMERCIALIZA��O ou de ADEQUA��O A QUALQUER PROP�SITO EM
 *   PARTICULAR. Consulte a Licen�a P�blica Geral GNU para obter mais
 *   detalhes.
 *   Voc� deve ter recebido uma c�pia da Licen�a P�blica Geral GNU
 *   junto com este programa; se n�o, escreva para a Free Software
 *   Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA
 *   02111-1307, USA.
 *   C�pia da licen�a no diret�rio licenca/licenca_en.txt
 *                                 licenca/licenca_pt.txt
 */
require_once("libs/JSON.php");
require_once("libs/db_stdlib.php");
require_once("libs/db_utils.php");
require_once("libs/db_app.utils.php");
require_once("libs/db_conecta_plugin.php");
require_once("libs/db_sessoes.php");
require_once("std/db_stdClass.php");
require_once("dbforms/db_funcoes.php");

$oJson             = new services_json();
$oParam            = $oJson->decode(str_replace("\\","",$_POST["json"]));

$oRetorno          = new stdClass();
$oRetorno->status  = 1;
$oRetorno->message = "";
$oRetorno->erro    = false;
$sMensagem         = "";

try {

  db_inicio_transacao();
  switch ($oParam->exec) {

    case 'liberarNotaEmpenho':

      $sMensagemLiberacao = "";
      $oNotaLiquidacao = new NotaLiquidacao($oParam->iCodigoNota);

      /* [SolicitacaoRepasse] - Extensao */

      $oDaoEmpNota = new cl_empnota();

      $aWhere = array(
        "e69_numemp = {$oNotaLiquidacao->getNumeroEmpenho()}",
        "e70_valor <> e70_vlranu",
        "e70_valor = e70_vlrliq"
      );
      $sSqlEmpNota = $oDaoEmpNota->sql_query_valor_financeiro( null,
                                                               "e69_codnota, o56_elemento, e60_vlremp, e60_vlrliq, e60_vlranu",
                                                               "e69_codnota",
                                                               implode(' and ', $aWhere) );
      $rsEmpNota = $oDaoEmpNota->sql_record($sSqlEmpNota);

      if ($oDaoEmpNota->numrows > 0) {

        $oEmpNota = db_utils::fieldsMemory($rsEmpNota, 0);

        /**
         * Verifica se o elemento do empenho se enquadra na an�lise autom�tica
         */
        if ( ControleInterno::verificaAnaliseAutomatica($oEmpNota->o56_elemento)) {

          $lUltimaLiquidacao = ($oEmpNota->e60_vlremp - $oEmpNota->e60_vlrliq - $oEmpNota->e60_vlranu) == 0;

          /**
           * Caso n�o seja a primeira nota de iquida��o verifica se a primeira esta liberada
           */
          if ($oEmpNota->e69_codnota != $oParam->iCodigoNota) {

            $oControleInternoPrimeiraLiquidacao = new ControleInternoMovimento($oEmpNota->e69_codnota);

            if ($oControleInternoPrimeiraLiquidacao->getSituacaoFinal() < ControleInterno::SITUACAO_LIBERADO_DIRETOR) {

              $sMensagem  = "Esta nota se enquadra na regra de an�lise autom�tica. � necess�rio fazer a libera��o ";
              $sMensagem .= "da primeira nota deste empenho para a libera��o das demais.";
              throw new Exception($sMensagem);
            }

          } else if ($oParam->iSituacao == ControleInterno::SITUACAO_LIBERADO_DIRETOR) {

            /**
             * Caso seja uma libera��o do diretor, j� autoriza as liquida��es pendentes, exceto a �ltima
             */
            for ($iRow = 1; $iRow < $oDaoEmpNota->numrows; $iRow++) {

              $oDadosNota = db_utils::fieldsMemory($rsEmpNota, $iRow);
              $oControleInterno = new ControleInternoMovimento(db_utils::fieldsMemory($rsEmpNota, $iRow)->e69_codnota);

              if ( $oControleInterno->getSituacaoFinal() == ControleInterno::SITUACAO_AGUARDANDO_ANALISE
                && !($lUltimaLiquidacao && $iRow == $oDaoEmpNota->numrows-1) ) {

                $oControleInterno->criaAutorizacaoAutomatica();
              }
            }

            $sMensagemLiberacao  = "O empenho se enquadra na regra de an�lise autom�tica. As pr�ximas an�lises ser�o ";
            $sMensagemLiberacao .= "feitas automaticamente ap�s a liquida��o do empenho. Caso existam notas pendentes para ";
            $sMensagemLiberacao .= "an�lise, as mesmas ser�o liberadas automaticamente.";
          }
        }
      }

      $oControleInterno = new ControleInternoMovimento($oParam->iCodigoNota);
      $oControleInterno->setSituacao($oParam->iSituacao);
      $oControleInterno->setRessalva(db_stdClass::normalizeStringJsonEscapeString($oParam->sRessalva));
      $oControleInterno->setUsuario(new UsuarioSistema(db_getsession('DB_id_usuario')));
      $oControleInterno->setData(new DBDate(date('d/m/Y', db_getsession('DB_datausu'))));
      $oControleInterno->setHora(date('H:i'));
      $oControleInterno->salvar();

      if (!in_array($oParam->iSituacao, array(ControleInterno::SITUACAO_LIBERADO_AUTOMATICO, ControleInterno::SITUACAO_AGUARDANDO_ANALISE))) {
        $oControleInterno->salvarHistoricoUsuario($oParam->iCodigoNota, $oParam->iSituacao);
      }

      $sMensagem = "Libera��o efetuada com sucesso.\nAguarde a libera��o final para o pagamento.";

      if ($oParam->tipo_liberacao == ControleInterno::CONTROLE_DIRETOR) {
        $sMensagem = "Libera��o efetuada com sucesso.\nA nota est� dispon�vel para pagamento.";
      }

      if (in_array($oParam->iSituacao, array(ControleInterno::SITUACAO_REJEITADO_ANALISTA, ControleInterno::SITUACAO_REJEITADO_DIRETOR))) {
        $sMensagem = "A nota informada n�o foi liberada para pagamento.\nInforme o respons�vel pela nota para as devidas provid�ncias.";
      }

      $sMensagem = $sMensagem . (!empty($sMensagemLiberacao) ? "\n\n{$sMensagemLiberacao}" : "");

      $oRetorno->message = urlencode($sMensagem);

      break;

    case 'getUltimoMovimento';

      $oControleInterno    = new ControleInternoMovimento($oParam->iCodigoNota);
      $oRetorno->movimento = $oControleInterno->getUltimoMovimento();
      if (!empty($oRetorno->movimento)) {
        $oRetorno->movimento->ressalva = urlencode($oRetorno->movimento->ressalva);
      }

      break;
  }
  db_fim_transacao(false);
} catch (Exception $eErro) {

  db_inicio_transacao(true);
  $oRetorno->erro   = true;
  $oRetorno->message = urlencode($eErro->getMessage());
}
echo $oJson->encode($oRetorno);
