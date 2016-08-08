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

class cl_empenhonotacontroleinterno extends DAOBasica {

  public function __construct() {
    parent::__construct("plugins.empenhonotacontroleinterno");
  }

  /**
   * Retorna todos os empenhos para verificaчуo do controle interno
   * @param string $sCampos
   * @param        $sOrder
   * @param        $sWhere
   * @return string
   */
  function sql_query_empenhos_para_controle_interno ($sCampos = '*', $sOrder, $sWhere) {

    $sSqlEmpenhos  = " select {$sCampos} ";
    $sSqlEmpenhos .= "  from empnota";
    $sSqlEmpenhos .= "       inner join empempenho   on e60_numemp  = e69_numemp";
    $sSqlEmpenhos .= "       inner join cgm          on z01_numcgm = e60_numcgm";
    $sSqlEmpenhos .= "       inner join orcdotacao   on e60_coddot  = orcdotacao.o58_coddot" ;
    $sSqlEmpenhos .= "                              and e60_anousu  = orcdotacao.o58_anousu";
    $sSqlEmpenhos .= "       inner join empnotaele   on e69_codnota = e70_codnota";
    $sSqlEmpenhos .= "       left join pagordemnota on e71_codnota  = e69_codnota";
    $sSqlEmpenhos .= "                             and e71_anulado is false";
    $sSqlEmpenhos .= "       left join pagordem     on e71_codord  = e50_codord";
    $sSqlEmpenhos .= "       left join pagordemele  on e50_codord  = e53_codord";
    $sSqlEmpenhos .= "       left join plugins.empenhonotacontroleinterno on nota = e69_codnota ";

    if (!empty($sWhere)) {
      $sSqlEmpenhos .= " where {$sWhere} ";
    }

    if (!empty($sOrder)) {
      $sSqlEmpenhos .= " order by {$sOrder}";
    }

    return $sSqlEmpenhos;
  }

  /**
   * Retorna todas as informacoes necessarias para
   * montar o documento de analise
   *
   * @return string
   */
  function sql_query_documento_analise($sCampos = '*', $sOrder = '', $sWhere = '') {

    $aDotacoesUsuario = PermissaoUsuarioEmpenho::getDotacoesUsuario(db_getsession('DB_id_usuario'), db_getsession('DB_anousu'), "M");
    $sDotacoesUsuario = implode(", ", $aDotacoesUsuario);

    $sSql  = " select {$sCampos} ";
    $sSql .= " from plugins.empenhonotacontroleinterno ";
    $sSql .= "    left join plugins.empenhonotacontroleinternohistorico on empenhonotacontroleinterno.sequencial = plugins.empenhonotacontroleinternohistorico.empenhonotacontroleinterno ";
    $sSql .= "   inner join pagordemnota         on e71_codnota = empenhonotacontroleinterno.nota ";
    $sSql .= "   inner join empnota              on e69_codnota = e71_codnota ";
    $sSql .= "   inner join empempenho           on e60_numemp = e69_numemp ";
    $sSql .= "   inner join empempaut            on e60_numemp = e61_numemp  ";
    $sSql .= "   inner join empautoriza          on e54_autori = e61_autori  ";
    $sSql .= "    left join empautorizaprocesso  on e150_empautoriza = e54_autori  ";
    $sSql .= "   inner join orcdotacao           on e60_anousu = o58_anousu and e60_coddot = o58_coddot and o58_coddot in (".$sDotacoesUsuario.")";
    $sSql .= "   inner join orcorgao             on o58_anousu = o40_anousu and o58_orgao = o40_orgao ";
    $sSql .= "   inner join orcunidade           on o58_anousu = o41_anousu and o58_orgao = o41_orgao and o58_unidade = o41_unidade ";
    $sSql .= "   inner join cgm                  on e60_numcgm = z01_numcgm ";
    $sSql .= "    left join db_usuarios          on id_usuario = usuario ";
    $sSql .= "    left join plugins.controleinternocredor_empenhonotacontroleinterno on plugins.controleinternocredor_empenhonotacontroleinterno.empenhonotacontroleinterno = plugins.empenhonotacontroleinterno.sequencial";
    $sSql .= "    left join plugins.controleinternocredor on plugins.controleinternocredor.sequencial = plugins.controleinternocredor_empenhonotacontroleinterno.controleinternocredor";
    $sSql .= "    left join plugins.controleinternosituacoes on plugins.controleinternosituacoes.sequencial = plugins.controleinternocredor.situacao_analise";

    if (!empty($sWhere)) {
      $sSql .= " where {$sWhere} ";
    }

    if (!empty($sOrder)) {
      $sSql .= " order by {$sOrder} ";
    }
    
    return $sSql;
  }

}
