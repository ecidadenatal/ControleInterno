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

    case 'aprovarAnalise':

      $iSituacao       = $oParam->iSituacao;
      $aCodigoAnalises = $oParam->aCodigoAnalises;

      foreach ($aCodigoAnalises as $iCodigoAnalise) {
        
        // Salva os dados de aprova��o na controleinternocredor
        $oUsuario  = new UsuarioSistema(db_getsession('DB_id_usuario'));
        $oUsuarioControladoria = db_utils::fieldsMemory(db_query("select * from plugins.usuariocontroladoria where numcgm = (select cgmlogin from db_usuacgm where id_usuario = {$oUsuario->getCodigo()})"), 0);

        $oDaoEmpNotaControleInterno = db_utils::getDao("empenhonotacontroleinterno");
        $oDaoControleInternoCredor = db_utils::getDao("controleinternocredor");
        $oDaoControleInternoCredor->aprovarAnalise($iCodigoAnalise, $oUsuarioControladoria->numcgm, date('d/m/Y', db_getsession('DB_datausu')), $iSituacao);
        
        if ($oDaoControleInternoCredor->erro_status == "0") {
          throw new Exception("Erro ao alterar a an�lise {$iCodigoAnalise}.");
        }

        $rsNotas = $oDaoControleInternoCredor->sql_record($oDaoControleInternoCredor->getDadosAnalise($iCodigoAnalise));

        for ($i=0; $i < $oDaoControleInternoCredor->numrows; $i++) {

          $oNota = db_utils::fieldsMemory($rsNotas, $i);

          $sMensagemLiberacao = "";
          $oNotaLiquidacao = new NotaLiquidacao($oNota->e69_codnota);
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

          /*
          @todo verificar essa logica pois nao faz sentido
          $rsEmpNotaControleInterno = $oDaoEmpNotaControleInterno->sql_record($oDaoEmpNotaControleInterno->sql_query_file(null, "*", null, "nota = {$oNota->e69_codnota}"));
          $oEmpNotaControleInterno  = db_utils::fieldsMemory($rsEmpNotaControleInterno, 0);
          if ($iSituacao == ControleInterno::SITUACAO_APROVADA) {
            $oDaoEmpNotaControleInterno->nota = $oEmpNotaControleInterno->nota;
            $oDaoEmpNotaControleInterno->situacao = $oParam->iSituacao;
            $oDaoEmpNotaControleInterno->alterar($oEmpNotaControleInterno->sequencial);
          } 
          */
          
          if ($oDaoEmpNota->numrows > 0) {

            $oEmpNota = db_utils::fieldsMemory($rsEmpNota, 0);

            if ( ControleInterno::verificaAnaliseAutomatica($oEmpNota->o56_elemento)) {

              //Pega situa��o da an�lise para ver se � dilig�ncia, regular, ressalva ou irregular
              $rsSituacaoInicial = db_query("SELECT situacao_analise
                                      FROM plugins.controleinternocredor 
                                          INNER JOIN plugins.controleinternocredor_empenhonotacontroleinterno ON controleinternocredor = plugins.controleinternocredor.sequencial
                                          INNER JOIN plugins.empenhonotacontroleinterno ON plugins.empenhonotacontroleinterno.sequencial = empenhonotacontroleinterno
                                      WHERE nota = {$oEmpNota->e69_codnota} ORDER BY plugins.empenhonotacontroleinterno.sequencial DESC limit 1");
              $iSituacaoInicial  = db_utils::fieldsMemory($rsSituacaoInicial, 0)->situacao_analise;
              
              $lUltimaLiquidacao = ($oEmpNota->e60_vlremp - $oEmpNota->e60_vlrliq - $oEmpNota->e60_vlranu) == 0;
              /**
               * Caso n�o seja a primeira nota de iquida��o verifica se a primeira esta liberada
               */
              if ($oEmpNota->e69_codnota != $oNota->e69_codnota) {
                $oControleInternoPrimeiraLiquidacao = new ControleInternoMovimento($oEmpNota->e69_codnota);
                if ($oControleInternoPrimeiraLiquidacao->getSituacaoFinal() != ControleInterno::SITUACAO_APROVADA && in_array($iSituacaoInicial, array(ControleInterno::SITUACAO_REGULAR, ControleInterno::SITUACAO_RESSALVA))) {

                  $sMensagem  = "Esta nota se enquadra na regra de an�lise autom�tica. � necess�rio fazer a libera��o ";
                  $sMensagem .= "da primeira nota deste empenho para a libera��o das demais.";
                  throw new Exception($sMensagem);
                }

              } else if ($oParam->iSituacao == ControleInterno::SITUACAO_APROVADA && in_array($iSituacaoInicial, array(ControleInterno::SITUACAO_REGULAR, ControleInterno::SITUACAO_RESSALVA))) {
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
        }
      }     

      if ($iSituacao == ControleInterno::SITUACAO_APROVADA) {
        $oRetorno->message = urlencode("An�lises aprovadas.". (!empty($sMensagemLiberacao) ? "\n\n{$sMensagemLiberacao}" : ""));
      } else {
        $oRetorno->message = urlencode("An�lises rejeitadas.");
      }

      break;

    case 'desaprovarAnalise':

      //Codigo da plugins.empenhonotacontroleinterno, que ser� desaprovada
      $iCodigoAnalise = $oParam->iAnalise;
      $aNotas = $oParam->aNotas;
      $oDaoControleInternoCredor = db_utils::getDao("controleinternocredor");
      $oDaoControleDesaprovacao = db_utils::getDao("controleinternocredordesaprovacao");
      $oUsuario  = new UsuarioSistema(db_getsession('DB_id_usuario'));
      $oUsuarioControladoria = db_utils::fieldsMemory(db_query("select * from plugins.usuariocontroladoria where numcgm = (select cgmlogin from db_usuacgm where id_usuario = {$oUsuario->getCodigo()})"), 0);
            
      for ($i=0; $i < count($aNotas); $i++) { 
        if (SolicitacaoRepasseFinanceiro::notaTemSolicitacao($aNotas[$i])) {
          throw new BusinessException("N�o � poss�vel desaprovar a libera��o. A Nota de Liquida��o j� possui uma Solicita��o de Repasse Financeiro.");
        }
      }

      //Busca os dados da an�lise
      $rsControleInternoCredor   = $oDaoControleInternoCredor->sql_record($oDaoControleInternoCredor->sql_query_file($iCodigoAnalise, "*", "", ""));
      $oControleInternoCredor    = db_utils::fieldsMemory($rsControleInternoCredor, 0);

      $sSqlEmpenhoNotaControleInterno = "update plugins.empenhonotacontroleinterno set situacao = situacao_analise
                                           from plugins.controleinternocredor_empenhonotacontroleinterno
                                                inner join plugins.controleinternocredor on controleinternocredor.sequencial = controleinternocredor_empenhonotacontroleinterno.controleinternocredor
                                          where empenhonotacontroleinterno.sequencial = empenhonotacontroleinterno
                                            and controleinternocredor = {$iCodigoAnalise}";
      if (!db_query($sSqlEmpenhoNotaControleInterno)) {
      	throw new Exception("Erro realizando antera��o na situa��o da nota de liqui��o");
      }
      
      //Salva os dados na tabela de desaprova��o de an�lise
      $oDaoControleDesaprovacao->controleinternocredor = $iCodigoAnalise;
      $oDaoControleDesaprovacao->usuario_aprovacao     = $oControleInternoCredor->usuario_aprovacao;
      $oDaoControleDesaprovacao->data_aprovacao        = $oControleInternoCredor->data_aprovacao;
      $oDaoControleDesaprovacao->situacao_aprovacao    = $oControleInternoCredor->situacao_aprovacao;
      $oDaoControleDesaprovacao->data_desaprovacao     = date('d/m/Y', db_getsession('DB_datausu'));
      //Coloquei pra colocar o CGM da DBSeller em casos onde a desaprova��o seja feita pelo suporte
      $oDaoControleDesaprovacao->usuario_desaprovacao  = db_getsession('DB_id_usuario') == "1" ? "19" : $oUsuarioControladoria->numcgm;
      $oDaoControleDesaprovacao->incluir(null);  
      
      if ($oDaoControleDesaprovacao->erro_status == "0") {
        throw new Exception("Erro ao desaprovar a an�lise {$iCodigoAnalise}.");
      }
      //Altera os dados de aprova��o da an�lise
      $oDaoControleInternoCredor->numcgm_credor         = $oControleInternoCredor->numcgm_credor;
      $oDaoControleInternoCredor->parecer               = $oControleInternoCredor->parecer;
      $oDaoControleInternoCredor->usuario_analise       = $oControleInternoCredor->usuario_analise;
      $oDaoControleInternoCredor->data_analise          = $oControleInternoCredor->data_analise;
      $oDaoControleInternoCredor->situacao_analise      = $oControleInternoCredor->situacao_analise;
      $oDaoControleInternoCredor->usuario_diretor_atual = $oControleInternoCredor->usuario_diretor_atual;
      $oDaoControleInternoCredor->usuario_chefe_atual   = $oControleInternoCredor->usuario_chefe_atual;
      $oDaoControleInternoCredor->usuario_aprovacao     = null;
      $oDaoControleInternoCredor->data_aprovacao        = null;
      $oDaoControleInternoCredor->situacao_aprovacao    = null;
      $oDaoControleInternoCredor->alterar($oControleInternoCredor->sequencial);

      if ($oDaoControleInternoCredor->erro_status == "0") {
        throw new Exception("Erro ao atualizar os dados de aprova��o da an�lise.");
      }
      $oRetorno->message = urlencode("An�lise desaprovada com sucesso.");
      
    break;

    case 'liberarNotaEmpenhoAnalise':

      $sRessalva = db_stdClass::normalizeStringJsonEscapeString($oParam->sRessalva);
      $iSituacao = $oParam->iSituacao;
      $iNumCgm   = $oParam->iNumCgm;
      $aNotas    = $oParam->aNotas;
      $oUsuario  = new UsuarioSistema(db_getsession('DB_id_usuario'));
      $oUsuarioControladoria = db_utils::fieldsMemory(db_query("select * from plugins.usuariocontroladoria where numcgm = (select cgmlogin from db_usuacgm where id_usuario = {$oUsuario->getCodigo()})"), 0);
      $oDaoEmpenhoControleInterno =  new cl_empenhonotacontroleinterno();

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

        $sWhere = "e69_codnota = {$aNotas[$i]} and ((not exists (select 1 from plugins.empenhonotacontroleinterno where nota = e69_codnota and situacao != ".ControleInterno::SITUACAO_REJEITADA.")
                      or (select situacao_aprovacao from plugins.controleinternocredor
                                              inner join plugins.controleinternocredor_empenhonotacontroleinterno on controleinternocredor = plugins.controleinternocredor.sequencial
                                                                                                                 and empenhonotacontroleinterno = plugins.empenhonotacontroleinterno.sequencial order by controleinternocredor.sequencial desc limit 1) = ". ControleInterno::SITUACAO_REJEITADA ."
                      or (select situacao_aprovacao from plugins.controleinternocredor
                                              inner join plugins.controleinternocredor_empenhonotacontroleinterno on controleinternocredor = plugins.controleinternocredor.sequencial
                                                                                                                 and empenhonotacontroleinterno = plugins.empenhonotacontroleinterno.sequencial
                                              where plugins.controleinternocredor.situacao_analise in (".ControleInterno::SITUACAO_DILIGENCIA.", ".ControleInterno::SITUACAO_IRREGULAR.") order by controleinternocredor.sequencial desc limit 1) = ". ControleInterno::SITUACAO_APROVADA ."))";
        $rsControleInterno = $oDaoEmpenhoControleInterno->sql_record($oDaoEmpenhoControleInterno->sql_query_empenhos_para_controle_interno("*", "", $sWhere));

        if($oDaoEmpenhoControleInterno->numrows == 0) {
          throw new BusinessException("A nota {$aNotas[$i]} j� se encontra numa an�lise pendente de aprova��o.");
        }
          
        if (SolicitacaoRepasseFinanceiro::notaTemSolicitacao($aNotas[$i])) {
          throw new BusinessException("N�o � poss�vel alterar a libera��o. A Nota de Liquida��o j� possui uma Solicita��o de Repasse Financeiro.");
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

      $sMensagem = "Libera��o efetuada com sucesso.\nAguarde a aprova��o para o pagamento.";
      if (in_array($oParam->iSituacao, array(ControleInterno::SITUACAO_DILIGENCIA, ControleInterno::SITUACAO_IRREGULAR))) {
        $sMensagem = "As notas informadas n�o foram liberadas para pagamento.\nInforme o respons�vel pelas notas para as devidas provid�ncias.";
      }

      $oRetorno->iCodigoAnalise = $oControleInternoCredor->sequencial;
      $oRetorno->message = urlencode($sMensagem);

      break;

    //RETIRAR ESTE CASE QUANDO N�O FOR MAIS NECESS�RIO APROVAR AN�LISES ANTIGAS
    case 'liberarNotaEmpenho':

      $sMensagemLiberacao = "";
      $oNotaLiquidacao = new NotaLiquidacao($oParam->iCodigoNota);

      if (SolicitacaoRepasseFinanceiro::notaTemSolicitacao($oParam->iCodigoNota)) {
        throw new BusinessException("N�o � poss�vel alterar a libera��o. A Nota de Liquida��o j� possui uma Solicita��o de Repasse Financeiro.");
      }
        

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

          //Pega situa��o da an�lise para ver se � dilig�ncia, regular, ressalva ou irregular
          $rsSituacaoInicial = $oDaoEmpNota->sql_record("SELECT situacao_analise
                                  FROM plugins.controleinternocredor 
                                      INNER JOIN plugins.controleinternocredor_empenhonotacontroleinterno ON controleinternocredor = plugins.controleinternocredor.sequencial
                                      INNER JOIN plugins.empenhonotacontroleinterno ON plugins.empenhonotacontroleinterno.sequencial = empenhonotacontroleinterno
                                  WHERE nota = {$oEmpNota->e69_codnota} ORDER BY plugins.empenhonotacontroleinterno.sequencial DESC limit 1");
          $iSituacaoInicial  = db_utils::fieldsMemory($rsSituacaoInicial, 0)->situacao_analise;
          $lUltimaLiquidacao = ($oEmpNota->e60_vlremp - $oEmpNota->e60_vlrliq - $oEmpNota->e60_vlranu) == 0;
          /**
           * Caso n�o seja a primeira nota de iquida��o verifica se a primeira esta liberada
           */
          if ($oEmpNota->e69_codnota != $oParam->iCodigoNota) {

            $oControleInternoPrimeiraLiquidacao = new ControleInternoMovimento($oEmpNota->e69_codnota);
            if ($oControleInternoPrimeiraLiquidacao->getSituacaoFinal() < ControleInterno::SITUACAO_APROVADA) {

              $sMensagem  = "Esta nota se enquadra na regra de an�lise autom�tica. � necess�rio fazer a libera��o ";
              $sMensagem .= "da primeira nota deste empenho para a libera��o das demais.";
              throw new Exception($sMensagem);
            }

          } else if ($oParam->iSituacao == ControleInterno::SITUACAO_APROVADA 
                      && ($iSituacaoInicial == ControleInterno::SITUACAO_REGULAR
                           || $iSituacaoInicial == ControleInterno::SITUACAO_RESSALVA)) {
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

      if (in_array($oParam->iSituacao, array(ControleInterno::SITUACAO_DILIGENCIA, ControleInterno::SITUACAO_IRREGULAR, ControleInterno::SITUACAO_REJEITADA))) {
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

      //Campo referente a analise do credor
      if (!empty($oParam->iAnalise)) {
        $sWhere .= " and plugins.controleinternocredor.sequencial = {$oParam->iAnalise}";
      }

      if (!empty($oParam->iExercicio)) {
        $sWhere .= " and extract(year from plugins.controleinternocredor.data_analise) = {$oParam->iExercicio}";
      }

      $oDaoAnalises  = db_utils::getDao("empenhonotacontroleinterno");
      $rsAnalises = $oDaoAnalises->sql_record($oDaoAnalises->sql_query_documento_analise($sCampos, null, $sWhere));

      if ($oDaoAnalises->numrows == 0) {
        
        $oRetorno->message = urlencode("N�o foram encontradas an�lises pendentes de aprova��o para os filtros informados.");
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

    case 'buscarDetalhesAnalise':
      
      $oDaoControleInternoCredor = db_utils::getDao("controleinternocredor");
      $rsLiquidacoes = $oDaoControleInternoCredor->sql_record($oDaoControleInternoCredor->getDadosAnalise($oParam->iAnalise));
      $aLiquidacoes = array();

      if($oDaoControleInternoCredor->numrows == 0) {
        $oRetorno->status  = 0;
        throw new BusinessException("N�o foram encontrados dados referentes � an�lise {$oParam->iAnalise}.");
      }

      for ($i=0; $i < $oDaoControleInternoCredor->numrows; $i++) { 
        $oDaoLiquidacoes = db_utils::fieldsMemory($rsLiquidacoes, $i);
        $oLiquidacao = new stdClass();

        $oLiquidacao->iCodigoEmpenho = $oDaoLiquidacoes->e60_numemp;
        $oLiquidacao->iCodigoNota    = $oDaoLiquidacoes->e69_codnota;
        $oLiquidacao->sNomeCredor    = $oDaoLiquidacoes->z01_nome;
        $oLiquidacao->nValor         = $oDaoLiquidacoes->e70_vlrliq;

        $aLiquidacoes[] = $oLiquidacao;
        $oRetorno->sParecer = $oDaoLiquidacoes->parecer;
      }

      $oRetorno->aLiquidacoes = $aLiquidacoes;

      break;

    case 'alterarNotaEmpenhoAnalise':

      $oDaoControleInternoCredor  = db_utils::getDao("controleinternocredor");
      $oDaoControleCredorEmpNota  = db_utils::getDao("controleinternocredor_empenhonotacontroleinterno");
      $oDaoEmpenhoControleInterno = db_utils::getDao("empenhonotacontroleinterno");
      $oDaoEmpenhoControleInternoHistorico = db_utils::getDao("empenhonotacontroleinternohistorico");
      $oUsuario  = new UsuarioSistema(db_getsession('DB_id_usuario'));

      $rsControleInternoCredor = $oDaoControleInternoCredor->sql_record($oDaoControleInternoCredor->sql_query_file($oParam->iAnalise, "*", "", ""));
      $aLiquidacoesAnterior = db_utils::getCollectionByRecord($oDaoEmpenhoControleInterno->sql_record($oDaoEmpenhoControleInterno->sql_query_documento_analise("distinct nota", "", "plugins.controleinternocredor.sequencial = {$oParam->iAnalise}")));
      $aLiquidacoesAtual    = $oParam->aNotas;

      if ($oDaoControleInternoCredor->numrows == 1) {
        //Atualiza os dados da an�lise
        $oControleInternoCredor = db_utils::fieldsMemory($rsControleInternoCredor, 0);

        $oDaoControleInternoCredor->numcgm_credor         = $oControleInternoCredor->numcgm_credor;
        $oDaoControleInternoCredor->parecer               = db_stdClass::normalizeStringJsonEscapeString($oParam->sRessalva);
        $oDaoControleInternoCredor->usuario_analise       = $oControleInternoCredor->usuario_analise;
        $oDaoControleInternoCredor->data_analise          = date('d/m/Y', db_getsession('DB_datausu'));
        $oDaoControleInternoCredor->situacao_analise      = $oParam->iSituacao;
        $oDaoControleInternoCredor->usuario_diretor_atual = $oControleInternoCredor->usuario_diretor_atual;
        $oDaoControleInternoCredor->usuario_chefe_atual   = $oControleInternoCredor->usuario_chefe_atual;
        $oDaoControleInternoCredor->usuario_aprovacao     = null;
        $oDaoControleInternoCredor->data_aprovacao        = null;
        $oDaoControleInternoCredor->situacao_aprovacao    = null;
        $oDaoControleInternoCredor->alterar($oControleInternoCredor->sequencial);
        //Verifica se foram inclu�das liquida��es novas
        for ($i = 0; $i < count($aLiquidacoesAtual); $i++) { 
          //Se o c�digo da liquida��o do array das liquida��es atuais n�o estiver no das liquida��es que est�o no banco, � porque a liquida��o � nova
          //Faz o mesmo processo que quando salva a an�lise
          $notaNova = true;
          foreach ($aLiquidacoesAnterior as $oLiquidacao) {
            if ($aLiquidacoesAtual[$i] == $oLiquidacao->nota) {
              $notaNova = false;
            }
          }
          if($notaNova) {
            $sWhere = "e69_codnota = {$aLiquidacoesAtual[$i]} and ((not exists (select 1 from plugins.empenhonotacontroleinterno where nota = e69_codnota and situacao != ".ControleInterno::SITUACAO_REJEITADA.")
                      or (select situacao_aprovacao from plugins.controleinternocredor
                                              inner join plugins.controleinternocredor_empenhonotacontroleinterno on controleinternocredor = plugins.controleinternocredor.sequencial
                                                                                                                 and empenhonotacontroleinterno = plugins.empenhonotacontroleinterno.sequencial order by controleinternocredor.sequencial desc limit 1) = ". ControleInterno::SITUACAO_REJEITADA ."
                      or (select situacao_aprovacao from plugins.controleinternocredor
                                              inner join plugins.controleinternocredor_empenhonotacontroleinterno on controleinternocredor = plugins.controleinternocredor.sequencial
                                                                                                                 and empenhonotacontroleinterno = plugins.empenhonotacontroleinterno.sequencial
                                              where plugins.controleinternocredor.situacao_analise in (".ControleInterno::SITUACAO_DILIGENCIA.", ".ControleInterno::SITUACAO_IRREGULAR.") order by controleinternocredor.sequencial desc limit 1) = ". ControleInterno::SITUACAO_APROVADA ."))";
            $rsControleInterno = $oDaoEmpenhoControleInterno->sql_record($oDaoEmpenhoControleInterno->sql_query_empenhos_para_controle_interno("*", "", $sWhere));

            if($oDaoEmpenhoControleInterno->numrows == 0) {
              throw new BusinessException("A nota {$aLiquidacoesAtual[$i]} j� se encontra numa an�lise pendente de aprova��o.");
            }
              
            $sMensagemLiberacao = "";
            $oNotaLiquidacao = new NotaLiquidacao($aLiquidacoesAtual[$i]);

            if (SolicitacaoRepasseFinanceiro::notaTemSolicitacao($aLiquidacoesAtual[$i])) {
              throw new BusinessException("N�o � poss�vel alterar a libera��o. A Nota de Liquida��o j� possui uma Solicita��o de Repasse Financeiro.");
            }

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
                if ($oEmpNota->e69_codnota != $aLiquidacoesAtual[$i]) {

                  $oControleInternoPrimeiraLiquidacao = new ControleInternoMovimento($oEmpNota->e69_codnota);

                  if ($oControleInternoPrimeiraLiquidacao->getSituacaoFinal() != ControleInterno::SITUACAO_APROVADA) {

                    $sMensagem  = "A nota {$aLiquidacoesAtual[$i]} se enquadra na regra de an�lise autom�tica. � necess�rio fazer a libera��o ";
                    $sMensagem .= "da primeira nota deste empenho para a libera��o das demais.";
                    throw new Exception($sMensagem);
                  }

                } else if ($oParam->iSituacao == ControleInterno::SITUACAO_APROVADA) {

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

            $oControleInterno = new ControleInternoMovimento($aLiquidacoesAtual[$i]);
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
          } else {
            $rsEmpNotaControleInterno = $oDaoEmpenhoControleInterno->sql_record($oDaoEmpenhoControleInterno->sql_query_file(null, "*", null, "nota = {$aLiquidacoesAtual[$i]}"));
            $oEmpNotaControleInterno = db_utils::fieldsMemory($rsEmpNotaControleInterno, 0);
            $oDaoEmpenhoControleInterno->nota = $oEmpNotaControleInterno->nota;
            $oDaoEmpenhoControleInterno->situacao = $oParam->iSituacao;
            $oDaoEmpenhoControleInterno->alterar($oEmpNotaControleInterno->sequencial);
          }
        }
        //Verifica se foram exclu�das liquida��es
        for ($i = 0; $i < count($aLiquidacoesAnterior); $i++) { 
          //Se a liquida��o do array das liquida��es do banco n�o estiver no array das liquida��es que vieram da tela, � porque ela foi exclu�da da an�lise
          if (!in_array($aLiquidacoesAnterior[$i]->nota, $aLiquidacoesAtual)) {
            $oDaoControleCredorEmpNota->excluir(null, "empenhonotacontroleinterno = (select sequencial from plugins.empenhonotacontroleinterno where nota = {$aLiquidacoesAnterior[$i]->nota})");
            $oDaoEmpenhoControleInternoHistorico->excluir(null, "empenhonotacontroleinterno = (select sequencial from plugins.empenhonotacontroleinterno where nota = {$aLiquidacoesAnterior[$i]->nota})");
            $oDaoEmpenhoControleInterno->excluir(null, "nota = {$aLiquidacoesAnterior[$i]->nota}");  
          }
        }

      } else {
        $oRetorno->erro = true;
        $oRetorno->status = 0;
        throw new BusinessException("An�lise {$oParam->iAnalise} n�o encontrada.");
      }
      $oRetorno->message = urlencode("Altera��o efetuada com sucesso.");

      break;

      case 'excluirNotaEmpenhoAnalise':

        if (isset($oParam->iAnalise)) {

          $oDaoControleInternoCredor  = db_utils::getDao("controleinternocredor");
          $oDaoControleCredorEmpNota  = db_utils::getDao("controleinternocredor_empenhonotacontroleinterno");
          $oDaoEmpenhoControleInterno = db_utils::getDao("empenhonotacontroleinterno");
          $oDaoEmpenhoControleInternoHistorico = db_utils::getDao("empenhonotacontroleinternohistorico");
          //Pega os sequenciais de todas as an�lises de nota (empenhonotacontroleinterno) vinculadas � an�lise (controleinternocredor)
          $rsEmpNotaControleInterno = $oDaoEmpenhoControleInterno->sql_record($oDaoEmpenhoControleInterno->sql_query_file(null, 
                                                                                                                   "sequencial", 
                                                                                                                   "", 
                                                                                                                   "sequencial in (select empenhonotacontroleinterno 
                                                                                                                                   from plugins.controleinternocredor_empenhonotacontroleinterno
                                                                                                                                   where controleinternocredor = {$oParam->iAnalise})")); 
          for ($i = 0; $i < $oDaoEmpenhoControleInterno->numrows; $i++) { 
            $oEmpNotaControleInterno = db_utils::fieldsMemory($rsEmpNotaControleInterno, $i);
            //Apaga todos os hist�ricos (empenhonotacontroleinternohistorico) vinculados a an�lise de nota (empenhonotacontroleinterno)
            $oDaoEmpenhoControleInternoHistorico->excluir(null, "empenhonotacontroleinterno = {$oEmpNotaControleInterno->sequencial}");
            //Apaga a an�lise de nota (empenhonotacontroleinterno)
            $oDaoEmpenhoControleInterno->excluir(null, "sequencial = $oEmpNotaControleInterno->sequencial");  
          }       
          //Apaga todos os dados de v�nculo entre a empenhonotacontroleinterno e a controleinternocredor
          $oDaoControleCredorEmpNota->excluir(null, "controleinternocredor = {$oParam->iAnalise}");
          //Apaga a an�lise de credor (controleinternocredor)
          $oDaoControleInternoCredor->excluir(null, "sequencial = {$oParam->iAnalise}");
          $oRetorno->message = urlencode("Exclus�o efetuada com sucesso.");

        } else {
          $oRetorno->erro = true;
          $oRetorno->message = urlencode("Informe uma an�lise.");
          $oRetorno->status = 1;
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

