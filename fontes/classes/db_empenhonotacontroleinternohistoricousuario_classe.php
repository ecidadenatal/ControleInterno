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

class cl_empenhonotacontroleinternohistoricousuario extends DAOBasica {

  public function __construct() {
    parent::__construct("plugins.empenhonotacontroleinternohistoricousuario");
  }

  public function sql_query_historicousuario($sCampos = "*", $sWhere = null) {

    $sSql  = " select {$sCampos} ";
    $sSql .= "   from plugins.empenhonotacontroleinternohistoricousuario ";
    $sSql .= "        inner join plugins.empenhonotacontroleinternohistorico on empenhonotacontroleinternohistorico.sequencial = empenhonotacontroleinternohistoricousuario.empenhonotacontroleinternohistorico";
    $sSql .= "        inner join plugins.empenhonotacontroleinterno on empenhonotacontroleinterno.sequencial = empenhonotacontroleinternohistorico.empenhonotacontroleinterno";
    $sSql .= "        inner join protocolo.cgm on cgm.z01_numcgm = empenhonotacontroleinternohistorico.usuario";

    $sWhere = trim($sWhere);
    if (!empty($sWhere)) {
      $sSql .= " where {$sWhere} ";
    }
    return $sSql;
  }

  public function getDadosUsuarioControladoriaPorHistorico($iNota) {
        
    $sSql  = " select empenhonotacontroleinternohistorico.sequencial as codigoHistorico, numcgm, cargo, departamento, lotacao, chefe, diretor ";
    $sSql .= "   from plugins.empenhonotacontroleinterno";
    $sSql .= "        inner join plugins.empenhonotacontroleinternohistorico on empenhonotacontroleinternohistorico.empenhonotacontroleinterno = empenhonotacontroleinterno.sequencial";
    $sSql .= "        inner join db_usuacgm on id_usuario = empenhonotacontroleinternohistorico.usuario";
    $sSql .= "        inner join plugins.usuariocontroladoria on usuariocontroladoria.numcgm = db_usuacgm.cgmlogin";
    $sSql .= "   where empenhonotacontroleinterno.nota = {$iNota}";

    return $sSql;
  }
}
