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
    <legend>Pesquisa de Análises - Controle Interno</legend>
    <table border="0" class="form-container">
      <tr>
        <td>
          <label for="chave_analise">
            <b>Cód. Análise:</b>
          </label>
        </td>
        <td>
          <?php db_input("cod_analise", 8, 3, true, "text", 4, "", "chave_analise"); ?>
        </td>
      </tr>
      <tr>
        <td>
          <label for="chave_orgao">
            <b>Órgão/Unidade:</b>
          </label>
        </td>
        <td>
          <?php db_input("orgao", 4, 1, true, "text", 4, "", "chave_orgao"); ?>
          <?php db_input("unidade", 4, 3, true, "text", 4, "", "chave_unidade"); ?>
        </td>
      </tr>
      <tr>
        <td>
          <b>Exercício:</b>
        </td>
        <td>
          <?php db_input("exercicio", 8, 0, true, "text", 4, "", "chave_exercicio"); ?>
        </td>
      </tr>
    </table>
  </fieldset>
  <table style="margin: 0 auto;">
    <tr>
      <td align="center">
        <input name="pesquisar" type="submit" id="pesquisar2" value="Pesquisar">
        <input name="limpar" type="reset" id="limpar" value="Limpar" >
        <input name="Fechar" type="button" id="fechar" value="Fechar" onClick="parent.db_iframe_analise.hide();">
      </td>
    </tr>
  </table>
</form>
<table class="container">
  <tr>
    <td align="center" valign="top">
      <?php

      $aWhere   = array();

      if (!empty($filtrar_notas_para_analista)) {
        //$aWhere[] =  "(situacao not in (" . ControleInterno::SITUACAO_APROVADA . ", " . ControleInterno::SITUACAO_REJEITADA . ", " . ControleInterno::SITUACAO_LIBERADO_AUTOMATICO . ") or situacao is null)";
        $aWhere[] = "(situacao_analise = " . ControleInterno::SITUACAO_AGUARDANDO_ANALISE . " or situacao_analise is null 
                      or (select situacao_aprovacao from plugins.controleinternocredor
                                              inner join plugins.controleinternocredor_empenhonotacontroleinterno on controleinternocredor = plugins.controleinternocredor.sequencial
                                                                                                                 and empenhonotacontroleinterno = plugins.empenhonotacontroleinterno.sequencial) = ". ControleInterno::SITUACAO_REJEITADA .")";
      }

      if (!empty($filtrar_notas_para_diretor)) {
        $aWhere[] = "(situacao_aprovacao is null and situacao_analise is not null )";
      }

      if (!empty($emiterelatorio)) {
        $aWhere[] = "(situacao_analise is not null )"; 
      }

      if (!empty($iOrgao)) {
        $aWhere[] = "o58_orgao = {$iOrgao}";
      }

      if (!empty($iUnidade)) {
        $aWhere[] = "o58_unidade = {$iUnidade}"; 
      }

      if (!empty($cod_analise)) {
        $aWhere[] = "plugins.controleinternocredor.sequencial = {$cod_analise}";
      }

      if (!empty($orgao)) {
        $aWhere[] = "o58_orgao = {$orgao}";
      }

      if (!empty($unidade)) {
        $aWhere[] = "o58_unidade = {$unidade}";
      }

      if (!empty($exercicio)) {
        $aWhere[] = "extract(year from data_analise) = {$exercicio}";
      }

      $oDaoEmpenhoControleInterno =  new cl_empenhonotacontroleinterno();
      $sWhere   = implode(" and ", $aWhere);

      $sCampos   = " distinct(plugins.controleinternocredor.sequencial) as dl_Código, ";
      $sCampos  .= " plugins.controleinternocredor.data_analise as dl_Data,  ";
      $sCampos  .= " o58_orgao as dl_Órgão, ";
      $sCampos  .= " o58_unidade as dl_Unidade, ";
      $sCampos  .= " (case when situacao_analise = " . ControleInterno::SITUACAO_DILIGENCIA          . " then 'Diligência' ";
      $sCampos  .= "       when situacao_analise = " . ControleInterno::SITUACAO_REGULAR             . " then 'Regular' ";
      $sCampos  .= "       when situacao_analise = " . ControleInterno::SITUACAO_REJEITADA           . " then 'Rejeitado pelo Diretor' ";
      $sCampos  .= "       when situacao_analise = " . ControleInterno::SITUACAO_APROVADA            . " then 'Liberado pelo Diretor' ";
      $sCampos  .= "       when situacao_analise = " . ControleInterno::SITUACAO_LIBERADO_AUTOMATICO . " then 'Liberação Automática' ";
      $sCampos  .= "       when situacao_analise = " . ControleInterno::SITUACAO_RESSALVA            . " then 'Ressalva' ";
      $sCampos  .= "       when situacao_analise = " . ControleInterno::SITUACAO_IRREGULAR           . " then 'Irregular' ";
      $sCampos  .= "       when situacao_analise is null then 'Aguardando análise' end)::varchar as dl_Situação ";
      //comentado porque ao utilizar o html na ressalva, as linhas não apareciam corretamente
      /*$sCampos  .= "(select controlehistorico.ressalva::text
                       from empenhonotacontroleinternohistorico controlehistorico
                      where controlehistorico.empenhonotacontroleinterno = empenhonotacontroleinterno.sequencial
                      order by controlehistorico.data desc, controlehistorico.hora desc, controlehistorico.sequencial desc limit 1) as dl_Ressalva";*/


      $repassa   = array();
      $sOrder    = "plugins.controleinternocredor.sequencial, plugins.controleinternocredor.data_analise desc";
      $sSqlNotas = $oDaoEmpenhoControleInterno->sql_query_documento_analise($sCampos , $sOrder, $sWhere);
      db_lovrot($sSqlNotas, 15, "()", "", $funcao_js, "", "NoMe", $repassa, false); ?>
    </td>
  </tr>
</table>
</body>
</html>
