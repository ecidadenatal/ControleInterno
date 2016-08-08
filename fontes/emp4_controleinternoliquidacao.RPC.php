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

    case 'aprovarAnalise':

      $iSituacao       = $oParam->iSituacao;
      $aCodigoAnalises = $oParam->aCodigoAnalises;

      foreach ($aCodigoAnalises as $iCodigoAnalise) {
        
        // Salva os dados de aprovação na controleinternocredor
        $oUsuario  = new UsuarioSistema(db_getsession('DB_id_usuario'));
        $oUsuarioControladoria = db_utils::fieldsMemory(db_query("select * from plugins.usuariocontroladoria where numcgm = (select cgmlogin from db_usuacgm where id_usuario = {$oUsuario->getCodigo()})"), 0);

        $oDaoControleInternoCredor = db_utils::getDao("controleinternocredor");
        $oDaoControleInternoCredor->aprovarAnalise($iCodigoAnalise, $oUsuarioControladoria->numcgm, date('d/m/Y', db_getsession('DB_datausu')), $iSituacao);
        
        if ($oDaoControleInternoCredor->erro_status == "0") {
          throw new Exception("Erro ao alterar a análise {$iCodigoAnalise}.");
        }

        $oRetorno->message = urlencode("A aprovação das análises foi salva.");
      }      

    break;

    case 'liberarNotaEmpenhoAnalise':

      $sRessalva = db_stdClass::normalizeStringJsonEscapeString($oParam->sRessalva);
      $iSituacao = $oParam->iSituacao;
      $iNumCgm   = $oParam->iNumCgm;
      $aNotas    = $oParam->aNotas;
      $oUsuario  = new UsuarioSistema(db_getsession('DB_id_usuario'));
      $oUsuarioControladoria = db_utils::fieldsMemory(db_query("select * from plugins.usuariocontroladoria where numcgm = (select cgmlogin from db_usuacgm where id_usuario = {$oUsuario->getCodigo()})"), 0);

      $oControleInternoCredor = db_utils::getDao("controleinternocredor");
      $oControleInternoCredor->sequencial            = null;
      $oControleInternoCredor->numcgm_credor         = $iNumCgm;
      $oControleInternoCredor->parecer               = $sRessalva;
      $oControleInternoCredor->usuario_analise       = $oUsuarioControladoria->numcgm;
      $oControleInternoCredor->data_analise          = date('d/m/Y', db_getsession('DB_datausu'));
      $oControleInternoCredor->situacao_analise      = $iSituacao;
      $oControleInternoCredor->usuario_diretor_atual = $oUsuarioControladoria->diretor;
      $oControleInternoCredor->usuario_chefe_atual   = $oUsuarioControladoria->chefe;
      $oControleInternoCredor->usuario_aprovacao     = null;
      $oControleInternoCredor->data_aprovacao        = null;
      $oControleInternoCredor->situacao_aprovacao    = null;
      $oControleInternoCredor->incluir(null);

      for ($i = 0; $i < count($aNotas); $i++) { 
          
        $sMensagemLiberacao = "";
        $oNotaLiquidacao = new NotaLiquidacao($aNotas[$i]);

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
           * Verifica se o elemento do empenho se enquadra na análise automática
           */
          if ( ControleInterno::verificaAnaliseAutomatica($oEmpNota->o56_elemento)) {

            $lUltimaLiquidacao = ($oEmpNota->e60_vlremp - $oEmpNota->e60_vlrliq - $oEmpNota->e60_vlranu) == 0;
            /**
             * Caso não seja a primeira nota de iquidação verifica se a primeira esta liberada
             */
            if ($oEmpNota->e69_codnota != $aNotas[$i]) {

              $oControleInternoPrimeiraLiquidacao = new ControleInternoMovimento($oEmpNota->e69_codnota);

              if ($oControleInternoPrimeiraLiquidacao->getSituacaoFinal() != ControleInterno::SITUACAO_APROVADA) {

                $sMensagem  = "A nota {$aNotas[$i]} se enquadra na regra de análise automática. É necessário fazer a liberação ";
                $sMensagem .= "da primeira nota deste empenho para a liberação das demais.";
                throw new Exception($sMensagem);
              }

            } else if ($oParam->iSituacao == ControleInterno::SITUACAO_APROVADA) {

              /**
               * Caso seja uma liberação do diretor, já autoriza as liquidações pendentes, exceto a última
               */
              for ($iRow = 1; $iRow < $oDaoEmpNota->numrows; $iRow++) {

                $oDadosNota = db_utils::fieldsMemory($rsEmpNota, $iRow);
                $oControleInterno = new ControleInternoMovimento(db_utils::fieldsMemory($rsEmpNota, $iRow)->e69_codnota);

                if ( $oControleInterno->getSituacaoFinal() == ControleInterno::SITUACAO_AGUARDANDO_ANALISE
                  && !($lUltimaLiquidacao && $iRow == $oDaoEmpNota->numrows-1) ) {

                  $oControleInterno->criaAutorizacaoAutomatica();
                }
              }

              $sMensagemLiberacao  = "O empenho se enquadra na regra de análise automática. As próximas análises serão ";
              $sMensagemLiberacao .= "feitas automaticamente após a liquidação do empenho. Caso existam notas pendentes para ";
              $sMensagemLiberacao .= "análise, as mesmas serão liberadas automaticamente.";
            }
          }
        }

        $oControleInterno = new ControleInternoMovimento($aNotas[$i]);
        $oControleInterno->setSituacao($oParam->iSituacao);
        $oControleInterno->setRessalva(db_stdClass::normalizeStringJsonEscapeString($oParam->sRessalva));
        $oControleInterno->setUsuario($oUsuario);
        $oControleInterno->setData(new DBDate(date('d/m/Y', db_getsession('DB_datausu'))));
        $oControleInterno->setHora(date('H:i'));
        $oControleInterno->salvar();

        $oControleCredorNota = db_utils::getDao("controleinternocredor_empenhonotacontroleinterno");
        $oControleCredorNota->empenhonotacontroleinterno = $oControleInterno->getCodigo();
        $oControleCredorNota->controleinternocredor      = $oControleInternoCredor->sequencial;
        $oControleCredorNota->incluir(null);
      }

      $sMensagem = "Liberação efetuada com sucesso.\nAguarde a aprovação para o pagamento.";
      if (in_array($oParam->iSituacao, array(ControleInterno::SITUACAO_DILIGENCIA, ControleInterno::SITUACAO_IRREGULAR))) {
        $sMensagem = "As notas informadas não foram liberadas para pagamento.\nInforme o responsável pelas notas para as devidas providências.";
      }

      $sMensagem = $sMensagem . (!empty($sMensagemLiberacao) ? "\n\n{$sMensagemLiberacao}" : "");

      $oRetorno->iCodigoAnalise = $oControleInternoCredor->sequencial;
      $oRetorno->message = urlencode($sMensagem);

      break;

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
         * Verifica se o elemento do empenho se enquadra na análise automática
         */
        if ( ControleInterno::verificaAnaliseAutomatica($oEmpNota->o56_elemento)) {

          $lUltimaLiquidacao = ($oEmpNota->e60_vlremp - $oEmpNota->e60_vlrliq - $oEmpNota->e60_vlranu) == 0;
          /**
           * Caso não seja a primeira nota de iquidação verifica se a primeira esta liberada
           */
          if ($oEmpNota->e69_codnota != $oParam->iCodigoNota) {

            $oControleInternoPrimeiraLiquidacao = new ControleInternoMovimento($oEmpNota->e69_codnota);

            if ($oControleInternoPrimeiraLiquidacao->getSituacaoFinal() < ControleInterno::SITUACAO_LIBERADO_DIRETOR) {

              $sMensagem  = "Esta nota se enquadra na regra de análise automática. É necessário fazer a liberação ";
              $sMensagem .= "da primeira nota deste empenho para a liberação das demais.";
              throw new Exception($sMensagem);
            }

          } else if ($oParam->iSituacao == ControleInterno::SITUACAO_LIBERADO_DIRETOR) {

            /**
             * Caso seja uma liberação do diretor, já autoriza as liquidações pendentes, exceto a última
             */
            for ($iRow = 1; $iRow < $oDaoEmpNota->numrows; $iRow++) {

              $oDadosNota = db_utils::fieldsMemory($rsEmpNota, $iRow);
              $oControleInterno = new ControleInternoMovimento(db_utils::fieldsMemory($rsEmpNota, $iRow)->e69_codnota);

              if ( $oControleInterno->getSituacaoFinal() == ControleInterno::SITUACAO_AGUARDANDO_ANALISE
                && !($lUltimaLiquidacao && $iRow == $oDaoEmpNota->numrows-1) ) {

                $oControleInterno->criaAutorizacaoAutomatica();
              }
            }

            $sMensagemLiberacao  = "O empenho se enquadra na regra de análise automática. As próximas análises serão ";
            $sMensagemLiberacao .= "feitas automaticamente após a liquidação do empenho. Caso existam notas pendentes para ";
            $sMensagemLiberacao .= "análise, as mesmas serão liberadas automaticamente.";
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

      $sMensagem = "Liberação efetuada com sucesso.\nAguarde a liberação final para o pagamento.";

      if ($oParam->tipo_liberacao == ControleInterno::CONTROLE_DIRETOR) {
        $sMensagem = "Liberação efetuada com sucesso.\nA nota está disponível para pagamento.";
      }

      if (in_array($oParam->iSituacao, array(ControleInterno::SITUACAO_REJEITADO_ANALISTA, ControleInterno::SITUACAO_REJEITADO_DIRETOR))) {
        $sMensagem = "A nota informada não foi liberada para pagamento.\nInforme o responsável pela nota para as devidas providências.";
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

    case 'getAnalises':

       $sCampos = "distinct(plugins.controleinternocredor.sequencial) as codigoAnalise, 
                  extract(year from plugins.controleinternocredor.data_analise) as exercicio, 
                  plugins.controleinternocredor.data_analise as dataAnalise, 
                  o58_orgao as orgao, 
                  o58_unidade as unidade, 
                  plugins.controleinternosituacoes.descricao as situacao";

      $sWhere = "plugins.controleinternocredor.data_aprovacao is null";
      if (!empty($oParam->iOrgao)) {
        $sWhere .= " and o58_orgao = {$oParam->iOrgao}";
      }

      if (!empty($oParam->iUnidade)) {
        $sWhere .= " and o58_unidade = {$oParam->iUnidade}";
      }

      if ($oParam->lFiltrarValor) {
        $sWhere .= " and e60_vlremp <= 80000"; 
      }

      if (!empty($oParam->iAnalise)) {
        $sWhere .= " and plugins.empenhonotacontroleinterno.sequencial = {$oParam->iAnalise}";
      }

      if (!empty($oParam->iExercicio)) {
        $sWhere .= " and extract(year from plugins.controleinternocredor.data_analise) = {$oParam->iExercicio}";
      }

      $oDaoAnalises  = db_utils::getDao("empenhonotacontroleinterno");
      $rsAnalises = $oDaoAnalises->sql_record($oDaoAnalises->sql_query_documento_analise($sCampos, null, $sWhere));

      if ($oDaoAnalises->numrows == 0) {
        
        $oRetorno->message = urlencode("Não foram encontradas análises pendentes de aprovação para os filtros informados.");
        $oRetorno->status  = 0;

      } else{ 
        
        $aAnalises = array();
        for ($i = 0; $i < $oDaoAnalises->numrows; $i++) { 
          $oDaoAnalise = db_utils::fieldsMemory($rsAnalises, $i);

          $oAnalise = new stdClass();
          $oAnalise->codigoanalise = $oDaoAnalise->codigoanalise;
          $oAnalise->exercicio     = $oDaoAnalise->exercicio;
          $oAnalise->dataanalise   = $oDaoAnalise->dataanalise;
          $oAnalise->orgao         = $oDaoAnalise->orgao;
          $oAnalise->unidade       = $oDaoAnalise->unidade;
          $oAnalise->situacao      = utf8_encode($oDaoAnalise->situacao);
          
          $aAnalises[]  = $oAnalise;
        }
        $oRetorno->aAnalises = $aAnalises;
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
