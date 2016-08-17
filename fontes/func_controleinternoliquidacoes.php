<?php
/*
 *     E-cidade Software Publico para Gestao Municipal
 *  Copyright (C) 2014  DBselller Servicos de Informatica
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

require_once("libs/db_stdlib.php");
require_once("libs/db_conecta_plugin.php");
require_once("libs/db_sessoes.php");
require_once("dbforms/db_funcoes.php");
require_once("classes/db_empempenho_classe.php");
$Se69_nota   = "Nota";
$Se60_numemp = "Seq. Empenho";
?>
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
  <link href="estilos.css" rel="stylesheet" type="text/css">
  <script language="JavaScript" type="text/javascript" src="scripts/scripts.js"></script>
</head>
<body>
<form name="form2" class="container" method="post" action="">
  <fieldset>
    <legend>Pesquisa de Notas - Controle Interno</legend>
    <table border="0" class="form-container">
      <tr>
        <td>
          <label for="chave_e60_codemp">
            <b>Número do Empenho:</b>
          </label>
        </td>
        <td>
          <?php db_input("e60_codemp", 14, 3, true, "text", 4, "", "chave_e60_codemp"); ?>
        </td>
        <td>
          <label for="chave_e60_numemp">
            <b>Seq. Empenho:</b>
          </label>
        </td>
        <td>
          <?php db_input("e60_numemp", 14, 1, true, "text", 4, "", "chave_e60_numemp"); ?>
        </td>
      </tr>
      <tr>
        <td>
          <label for="chave_e71_codord">
            <b>Cód. da OP:</b>
          </label>
        </td>
        <td>
          <?php db_input("e71_codord", 14, 3, true, "text", 4, "", "chave_e71_codord"); ?>
        </td>
      </tr>
      <tr>
        <td>
          <b>Nota Fiscal:</b>
        </td>
        <td>
          <?php db_input("e69_nota", 14, 0, true, "text", 4, "", "chave_e69_nota"); ?>
        </td>
      </tr>
    </table>
  </fieldset>
  <table style="margin: 0 auto;">
    <tr>
      <td align="center">
        <input name="pesquisar" type="submit" id="pesquisar2" value="Pesquisar">
        <input name="limpar" type="reset" id="limpar" value="Limpar" >
        <input name="Fechar" type="button" id="fechar" value="Fechar" onClick="parent.db_iframe_empempenho.hide();">
      </td>
    </tr>
  </table>
</form>
<table class="container">
  <tr>
    <td align="center" valign="top">
      <?php

      $aWhere   = array("e60_instit = ".db_getsession("DB_instit"));
      $aWhere[] = "(e70_vlrliq > 0 and e53_vlrpag = 0)";
      $sWhereDotacoes = PermissaoUsuarioEmpenho::getDotacoesPorAnoDoUsuario(
        db_getsession('DB_id_usuario'),
        db_getsession('DB_anousu'),
        PermissaoUsuarioEmpenho::PERMISSAO_MANUTENCAO_CONSULTA
      );
      if (!empty($sWhereDotacoes)) {
        $aWhere[] =  $sWhereDotacoes;
      }

      if (!empty($filtrar_notas_para_analista)) {
        $aWhere[] = "((not exists (select 1 from plugins.empenhonotacontroleinterno where nota = e69_codnota and situacao != ".ControleInterno::SITUACAO_REJEITADA.")
                      or (select situacao_aprovacao from plugins.controleinternocredor
                                              inner join plugins.controleinternocredor_empenhonotacontroleinterno on controleinternocredor = plugins.controleinternocredor.sequencial
                                                                                                                 and empenhonotacontroleinterno = plugins.empenhonotacontroleinterno.sequencial limit 1) = ". ControleInterno::SITUACAO_REJEITADA ."
                      or (select situacao_aprovacao from plugins.controleinternocredor
                                              inner join plugins.controleinternocredor_empenhonotacontroleinterno on controleinternocredor = plugins.controleinternocredor.sequencial
                                                                                                                 and empenhonotacontroleinterno = plugins.empenhonotacontroleinterno.sequencial
                                              where plugins.controleinternocredor.situacao_analise in (".ControleInterno::SITUACAO_DILIGENCIA.", ".ControleInterno::SITUACAO_IRREGULAR.") limit 1) = ". ControleInterno::SITUACAO_APROVADA ."))";
      }

      if (!empty($chave_e60_codemp)) {

        $arr = explode("/",$chave_e60_codemp);
        $aWhere[]  = "e60_codemp='{$arr[0]}'";
        if (count($arr) == 2  && isset($arr[1]) && $arr[1] != '' ) {
          $aWhere[] = " e60_anousu = ".$arr[1];
        }  elseif (count($arr) == 1) {
          $aWhere [] = " e60_anousu = ".db_getsession("DB_anousu");
        }
      }

      if (!empty($filtrarvalor)) {
        $aWhere[] = "e60_vlremp <= 80000";
      }

      if(!empty($filtrar_para_emissao)) {
        $aWhere[] = "situacao >= " . ControleInterno::SITUACAO_REJEITADA;
      }

      if (!empty($chave_e60_numemp)) {
        $aWhere[] = "e60_numemp = {$chave_e60_numemp}";
      }

      if (!empty($chave_e69_nota)) {
        $aWhere[] = "e69_codnota = {$chave_e69_nota}";
      }

      if (!empty($chave_e71_codord)) {
        $aWhere[] = "e71_codord = {$chave_e71_codord}";
      }

      if (isset($iCredor)) {
        $sWhereCredor  = " exists(select 1 from empautoriza ";
        $sWhereCredor .= "                  inner join empempaut on e61_autori = e54_autori";
        $sWhereCredor .= " where e61_numemp = empempenho.e60_numemp and e54_numcgm = {$iCredor}) ";
        $aWhere[] = $sWhereCredor;
      }

      if (isset($iOrgao) && isset($iUnidade)) {
        $aWhere[] = "o58_orgao = {$iOrgao} and o58_unidade = {$iUnidade}";
      }

      $oDaoEmpenhoControleInterno =  new cl_empenhonotacontroleinterno();
      $sWhere   = implode(" and ", $aWhere);

      $sCampos   = "e60_numemp, e60_codemp, e71_codord, e60_anousu, z01_nome, e69_numero, e69_codnota, e69_codnota as db_e69_codnota, e60_vlremp, e70_vlrliq,";
      $sCampos  .= " (case when situacao = " . ControleInterno::SITUACAO_DILIGENCIA          . " then 'Diligência' ";
      $sCampos  .= "       when situacao = " . ControleInterno::SITUACAO_REGULAR             . " then 'Regular' ";
      $sCampos  .= "       when situacao = " . ControleInterno::SITUACAO_REJEITADA           . " then 'Rejeitado pelo Diretor' ";
      $sCampos  .= "       when situacao = " . ControleInterno::SITUACAO_APROVADA            . " then 'Liberado pelo Diretor' ";
      $sCampos  .= "       when situacao = " . ControleInterno::SITUACAO_LIBERADO_AUTOMATICO . " then 'Liberação Automática' ";
      $sCampos  .= "       when situacao = " . ControleInterno::SITUACAO_RESSALVA            . " then 'Ressalva' ";
      $sCampos  .= "       when situacao = " . ControleInterno::SITUACAO_IRREGULAR           . " then 'Irregular' ";
      $sCampos  .= "       when situacao is null then 'Aguardando análise' end)::varchar as dl_Situação ";
      //comentado porque ao utilizar o html na ressalva, as linhas não apareciam corretamente
      /*$sCampos  .= "(select controlehistorico.ressalva::text
                       from empenhonotacontroleinternohistorico controlehistorico
                      where controlehistorico.empenhonotacontroleinterno = empenhonotacontroleinterno.sequencial
                      order by controlehistorico.data desc, controlehistorico.hora desc, controlehistorico.sequencial desc limit 1) as dl_Ressalva";*/


      $repassa   = array();
      $sOrder    = "e60_anousu desc, e60_codemp::int, situacao desc";
      $sSqlNotas = $oDaoEmpenhoControleInterno->sql_query_empenhos_para_controle_interno($sCampos , $sOrder, $sWhere);

      db_lovrot($sSqlNotas, 15, "()", "", $funcao_js, "", "NoMe", $repassa, false); ?>
    </td>
  </tr>
</table>
</body>
</html>
