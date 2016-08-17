<?php
/*
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

class cl_controleinternocredor extends DAOBasica {

  public function __construct() {
    parent::__construct("plugins.controleinternocredor");
  }

  public function aprovarAnalise($sequencial, $usuario_aprovacao, $data_aprovacao, $situacao_aprovacao) {
  		
  	//Busca os dados da controleinternocredor
    $oDaoControleInternoCredor    = db_utils::getDao("controleinternocredor");
    $rsBuscaControleInternoCredor = $oDaoControleInternoCredor->sql_record($oDaoControleInternoCredor->sql_query_file(null, "*", null, "sequencial = {$sequencial}"));
    if ($oDaoControleInternoCredor->numrows == 0) {
      throw new Exception("A análise de credor {$sequencial} não pôde ser encontrada.");
    }
    //Atualiza os dados de aprovação (usuário, data e situação final)
    $oControleInternoCredor = db_utils::fieldsMemory($rsBuscaControleInternoCredor, 0);
    $oDaoControleInternoCredor->numcgm_credor         = $oControleInternoCredor->numcgm_credor;
    $oDaoControleInternoCredor->parecer               = db_stdClass::normalizeStringJsonEscapeString($oControleInternoCredor->parecer);
    $oDaoControleInternoCredor->usuario_analise       = $oControleInternoCredor->usuario_analise;
    $oDaoControleInternoCredor->data_analise          = $oControleInternoCredor->data_analise;
    $oDaoControleInternoCredor->situacao_analise      = $oControleInternoCredor->situacao_analise;
    $oDaoControleInternoCredor->usuario_diretor_atual = $oControleInternoCredor->usuario_diretor_atual;
    $oDaoControleInternoCredor->usuario_chefe_atual   = $oControleInternoCredor->usuario_chefe_atual;
    $oDaoControleInternoCredor->usuario_aprovacao     = $usuario_aprovacao;
    $oDaoControleInternoCredor->data_aprovacao        = $data_aprovacao;   
    $oDaoControleInternoCredor->situacao_aprovacao    = $situacao_aprovacao;
    $oDaoControleInternoCredor->alterar($oControleInternoCredor->sequencial);
    if ($oDaoControleInternoCredor->erro_status == "0") {
      throw new Exception("Erro ao alterar a análise de credor {$sequencial}.");
    }

    //Busca todos as análises de notas (empenhonotacontroleinterno) na tabela de vínculo
    $oDaoCredorControleInterno    = db_utils::getDao('controleinternocredor_empenhonotacontroleinterno');
    $rsBuscaCredorControleInterno = $oDaoCredorControleInterno->sql_record($oDaoCredorControleInterno->sql_query_file(null, "empenhonotacontroleinterno", null, "controleinternocredor = {$sequencial}"));

    //Faz a alteração da situação de cada uma dessas análises de notas
    for ($i = 0; $i < $oDaoCredorControleInterno->numrows; $i++) { 
	 	$oCredorControleInterno = db_utils::fieldsMemory($rsBuscaCredorControleInterno, $i);
  
  	 	$oDaoControleInterno    = db_utils::getDao('empenhonotacontroleinterno');
  	 	$rsBuscaControleInterno = $oDaoControleInterno->sql_record($oDaoControleInterno->sql_query_file(null, "*", null, "sequencial = {$oCredorControleInterno->empenhonotacontroleinterno}"));		
  	 	if ($oDaoControleInterno->numrows == 0) {
  	 	  throw new Exception("A análise de nota {$oCredorControleInterno->empenhonotacontroleinterno} não pôde ser encontrada.");
  	 	}

      if ($oControleInternoCredor->situacao_analise == ControleInterno::SITUACAO_REGULAR || $oControleInternoCredor->situacao_analise == ControleInterno::SITUACAO_RESSALVA) {
    		$oControleInterno = db_utils::fieldsMemory($rsBuscaControleInterno, 0);
    		$oDaoControleInterno->nota     = $oControleInterno->nota;
    		$oDaoControleInterno->situacao = $situacao_aprovacao;
    		$oDaoControleInterno->alterar($oControleInterno->sequencial);
    		
        if ($oDaoControleInterno->erro_status == "0") {
    		  throw new Exception("Erro ao alterar a análise de nota {$oCredorControleInterno->empenhonotacontroleinterno}.");
    		}
      }
    }
  }

  function getDadosAnalise($iAnalise) {

    $sSql  = " select *, (select sum(e70_vlrliq) from empnotaele where e70_codnota = e69_codnota) as e70_vlrliq ";
    $sSql .= " from plugins.empenhonotacontroleinterno ";
    $sSql .= "   inner join empnota              on e69_codnota = empenhonotacontroleinterno.nota ";
    $sSql .= "   inner join empempenho           on e60_numemp = e69_numemp ";
    $sSql .= "   inner join empempaut            on e60_numemp = e61_numemp ";
    $sSql .= "   inner join empautoriza          on e54_autori = e61_autori ";
    $sSql .= "   inner join plugins.controleinternocredor_empenhonotacontroleinterno on plugins.controleinternocredor_empenhonotacontroleinterno.empenhonotacontroleinterno = plugins.empenhonotacontroleinterno.sequencial";
    $sSql .= "   inner join plugins.controleinternocredor on plugins.controleinternocredor.sequencial = plugins.controleinternocredor_empenhonotacontroleinterno.controleinternocredor";
    $sSql .= "   inner join plugins.controleinternosituacoes on plugins.controleinternosituacoes.sequencial = plugins.controleinternocredor.situacao_analise";
    $sSql .= "   inner join cgm                  on z01_numcgm = e60_numcgm ";
    $sSql .= " where plugins.controleinternocredor.sequencial = $iAnalise";

    return $sSql;
  }

}
