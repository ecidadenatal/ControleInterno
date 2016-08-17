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
          <b>CNPJ:</b>
        </td>
        <td>
          <?php db_input("cnpj", 15, 0, true, "text", 4, "", "chave_cnpj"); ?>
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
      <tr>
        <td>
          <b>Intervalo:</b>
        </td>
        <td>
          <?php db_inputdata("periodo_inicial", null, null,null,true,'text',1); ?> à <?php db_inputdata("periodo_final", null, null, null,true,'text',1); ?>
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

      if (!empty($filtro_alteracao)) {
        $aWhere[] = "(situacao_aprovacao is null and situacao_analise is not null )";
      }

      if (!empty($filtrarvalor)) {
        $aWhere[] = "e60_vlremp <= 80000";
      }

      if (!empty($iOrgao)) {
        $aWhere[] = "o58_orgao = {$iOrgao}";
      }

      if (!empty($iUnidade)) {
        $aWhere[] = "o58_unidade = {$iUnidade}"; 
      }

      if (isset($pesquisa_chave) && $pesquisa_chave != "") {
        $chave_analise = $pesquisa_chave;
      }
      if (!empty($chave_analise)) {
        $aWhere[] = "plugins.controleinternocredor.sequencial = {$chave_analise}";
      }

      if (!empty($chave_cnpj)) {
        $aWhere[] = "numcgm_credor in (select z01_numcgm from cgm where z01_cgccpf = '{$chave_cnpj}')";
      }

      if (!empty($chave_orgao)) {
        $aWhere[] = "o58_orgao = {$chave_orgao}";
      }

      if (!empty($chave_unidade)) {
        $aWhere[] = "o58_unidade = {$chave_unidade}";
      }

      if (!empty($chave_exercicio)) {
        $aWhere[] = "extract(year from data_analise) = {$chave_exercicio}";
      }

      if (!empty($periodo_inicial)) {
        $aWhere[] = " data_analise >= '".implode("-",array_reverse(explode("/",$periodo_inicial)))."'";
      }

      if (!empty($periodo_final)) {
        $aWhere[] = " data_analise <= '".implode("-",array_reverse(explode("/",$periodo_final)))."'";
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
      if (!empty($filtro_alteracao)) {
        $sCampos  .= ",      situacao_analise,";
        $sCampos  .= "       numcgm_credor";
      }
      $sCampos  .= ", (select z01_cgccpf from cgm where z01_numcgm = numcgm_credor limit 1) as dl_CNPJ";

      $repassa   = array();
      $sOrder    = "plugins.controleinternocredor.sequencial, plugins.controleinternocredor.data_analise desc";
      $sSqlNotas = $oDaoEmpenhoControleInterno->sql_query_documento_analise($sCampos , $sOrder, $sWhere);

      if (!isset($pesquisa_chave)) {
        db_lovrot($sSqlNotas, 15, "()", "", $funcao_js, "", "NoMe", $repassa, false); 
      } else {
        if ($pesquisa_chave!=null && $pesquisa_chave!="") {
          $rsAnalise = $oDaoEmpenhoControleInterno->sql_record($sSqlNotas);
          if ($oDaoEmpenhoControleInterno->numrows > 0) {
            $oDados = db_utils::fieldsMemory($rsAnalise, 0);
            echo "<script>".$funcao_js."($oDados->sequencial, null, null, null ,false);</script>";
          } else {
	          echo "<script>".$funcao_js."('Chave(".$pesquisa_chave.") não Encontrado',null, null, null, true);</script>";
          }         
        } else {
          echo "<script>".$funcao_js."('',false);</script>";
        }
      }
?>
    </td>
  </tr>
</table>
</body>
</html>
