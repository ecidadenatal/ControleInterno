<?php
/*
 *     E-cidade Software Publico para Gestao Municipal
 *  Copyright (C) 2014  DBseller Servicos de Informatica
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

require_once "libs/db_stdlib.php";
require_once "libs/db_conecta_plugin.php";
require_once "libs/db_sessoes.php";
require_once "libs/db_utils.php";
require_once "libs/db_app.utils.php";
require_once "dbforms/db_funcoes.php";

$oGet   = db_utils::postMemory($_GET);
$oGet            = db_utils::postMemory($_GET);
$sStyleInputText = 'width:25%;';

$sTituloTipo = " Inicial ";
if ($oGet->liberacaotipo == 2) {
  $sTituloTipo = " Final ";
}

$sFiltroValor = "";
if (!empty($oGet->filtrovalor)) {
  $sFiltroValor = "&filtrarvalor=1";
}

?>
<html xmlns="http://www.w3.org/1999/html">
<head>
  <title>DBSeller Inform&aacute;tica Ltda - P&aacute;gina Inicial</title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
  <?php
  // Includes padrão
  db_app::load("scripts.js, prototype.js, strings.js, datagrid.widget.js, AjaxRequest.js");
  db_app::load("estilos.css, grid.style.css");

  ?>
  <link href="estilos.css" rel="stylesheet" type="text/css">

  <style>
    .headerLabel {
      font-weight: bold;
      width: 80px;
    }

  </style>
</head>
<body class="body-default">
<div class="container">
  <form id="formAprovacaoAnalise">
    <input id="liberacaoTipo" type="hidden" value="<?= $oGet->liberacaotipo?>">
    <fieldset>

      <legend><strong>Controle Interno - Aprovação de Análise</strong></legend>
      <table>
        <tr>
          <td class="bold">
            <label for="exercicio">Exercício</label>
          </td>
          <td>
            <?php
              $exercicio  = db_getsession("DB_anousu");
              $ano_inicio = $exercicio - 2;
              db_select("exercicio", array_combine(range($ano_inicio, $exercicio), range($ano_inicio, $exercicio)), true, 1, "style='width: 80px;'");  
            ?>
          </td>
        </tr>
        <tr>
          <td class="bold">
            <label for="orgao_numero"><?php db_ancora('Órgão:', 'buscarOrgao(true)', $iOpcao, null, 'orgao_numero_ancora'); ?></label>
          </td>
          <td>
            <?php
            $Sorgao_numero = 'Órgão';
            db_input('orgao_numero', 10, 1, true, 'text', $iOpcao, 'onChange="buscarOrgao(false)"');
            db_input('orgao_descricao', 44, 0, true, 'text', 3);
            ?>
          </td>
        </tr>
        <tr>
          <td class="bold">
            <label for="unidade_numero>">
              <?php db_ancora('Unidade:', 'buscarUnidade(true)', $iOpcao, null, 'unidade_numero_ancora'); ?>
            </label>
          </td>
          <td>
            <?php
            $Sunidade_numero = 'Unidade';
            db_input('unidade_numero', 10, 1, true, 'text', $iOpcao, 'onChange="buscarUnidade(false)"');
            db_input('unidade_descricao', 44, 0, true, 'text', 3);
            ?>
          </td>
        </tr>
        <tr>
          <td>
            <label for="analise_numero">
              <?php db_ancora('Análise:', 'buscarAnalise(true)', $iOpcao, null, 'analise_ancora'); ?>
            </label>
          </td>
          <td>
            <?php
            db_input('analise_numero', 10, 1, true, 'text', $iOpcao, 'onChange="buscarAnalise(false)"');
            db_input('analise_descricao', 44, 0, true, 'text', 3);
            ?>
          </td>
        </tr>
        <tr>
          <td id="pesquisaAnalise" style="padding-left: 40%;">
            <input type="button" id="btnPesquisar" value="Pesquisar" onClick="pesquisarAnalise();" />
          </td>
        </tr>
        <tr>
          <td colspan="2">
            <fieldset>
              <legend>Análises</legend>
              <div id='ctnGrid'></div>
            </fieldset>
          </td>
        </tr>
        <tr>
          <td class="headerLabel"><label for="situacao">Liberação:</label></td>
          <td colspan="3">
            <?php
              $oSituacoesControleInterno = db_utils::getDao("controleinternosituacoes");
              $rsSituacoes = $oSituacoesControleInterno->sql_record($oSituacoesControleInterno->sql_query(null, "sequencial, descricao", null, "tipo = 'Diretor'"));
              db_selectrecord('situacao', $rsSituacoes, true, $oGet->liberacaotipo, 'style="' . $sStyleInputText . '"', "", "", "", "", 1);
            ?>
          </td>
        </tr>
      </table>
    </fieldset>
    <p class="text-center">
      <input type="button" id="btnSalvarAprovacao" value="Salvar" />
    </p>
  </form>
</div>
<?php db_menu(); ?>
</body>
</html>
<script type="text/javascript">

  var aAnalises = [];
  const sUrl = 'emp4_controleinternoliquidacao.RPC.php';
  window.onload = function() {
    reiniciaJanela();
    criaGrid();
    $('btnSalvarAprovacao').addEventListener('click', js_salvar);
  };

  /**
   * Faz a busca por orgão.
   * @param {boolean} lMostrar Se deve mostrar a janela para busca ou fazer busca pela chave.
   */
  function buscarOrgao(lMostrar) {

    var sQuerySring = 'funcao_js=parent.retornoOrgao|0|2';
    var sArquivo    = 'func_orcorgao.php';
    var sTituloTela = 'Pesquisar Órgão';

    if (!lMostrar) {
      sQuerySring = 'pesquisa_chave=' + $F('orgao_numero') + '&funcao_js=parent.retornoOrgaoChave';
    }

    js_OpenJanelaIframe('', 'db_iframe_orcorgao', sArquivo +'?' +sQuerySring, sTituloTela, lMostrar);
  }

  /**
   * Faz a busca por unidade.
   * @param {boolean} lMostrar Se deve mostrar a janela para busca ou fazer busca pela chave.
   */
  function buscarUnidade(lMostrar) {

    var iOrgao = $F('orgao_numero');

    if (iOrgao == '') {

      alert("Para selecionar uma unidade, você deve primeiro informar o Órgão.");
      return false;
    }

    var sQuerySring = 'orgao=' + iOrgao + '&funcao_js=parent.retornoUnidade|2|4';
    var sArquivo    = 'func_orcunidade.php';
    var sTituloTela = 'Pesquisar Unidade';

    if (!lMostrar) {
      sQuerySring = 'pesquisa_chave=' + $F('unidade_numero') + '&orgao=' + iOrgao + '&funcao_js=parent.retornoUnidadeChave';
    }

    js_OpenJanelaIframe('', 'db_iframe_orcunidade', sArquivo +'?' +sQuerySring, sTituloTela, lMostrar);
  }

  /**
   * Retorno da busca por órgão usando a chave.
   * @param {string}  sDescricao
   * @param {boolean} lErro
   */
  function retornoOrgaoChave(sDescricao, lErro) {

    $('unidade_numero').value    = '';
    $('unidade_descricao').value = '';
    removerTodasAnalises();
    retornoOrgao($F('orgao_numero'), sDescricao, lErro);
  }

  /**
   * Retorno da busca por unidade usando a chave.
   * @param {string}  sDescricao
   * @param {boolean} lErro
   * @param {string}  sNomeInstituicao
   * @param {int}     iInstituicao
   * @param {int}     iCodigoOrgao
   * @param {int}     iExercicio
   */
  function retornoUnidadeChave(sDescricao, lErro, sNomeInstituicao, iInstituicao, iCodigoOrgao) {

    if (lErro) {
      iExercicio   = '';
    }
    retornoUnidade($F('unidade_numero'), sDescricao, lErro);
  }

  /**
   * Retorno da busca por órgão.
   * @param {int}     iCodigo
   * @param {string}  sDescricao
   * @param {boolean} lErro
   */
  function retornoOrgao(iCodigo, sDescricao, lErro) {

    //Se o valor selecionado for diferente do atual, limpa a grid de análises e a unidade.
    if ($('orgao_numero').value != iCodigo) {

      $('unidade_numero').value    = '';
      $('unidade_descricao').value = '';
      removerTodasAnalises();
    }
    db_iframe_orcorgao.hide();
    retorno('orgao', iCodigo, sDescricao, lErro, false);
  }

  /**
   * Retorno da busca por unidade.
   * @param {int}     iCodigo
   * @param {string}  sDescricao
   * @param {int}     iCodigoOrgao
   * @param {boolean} lErro
   */
  function retornoUnidade(iCodigo, sDescricao, lErro) {

    //Se o valor selecionado for diferente do atual, limpa a grid de análises.
    if ($('unidade_numero').value != iCodigo) {
      removerTodasAnalises();
    }

    if (lErro) {
      iExercicio = '';
    }
    db_iframe_orcunidade.hide();
    retorno('unidade', iCodigo, sDescricao, lErro, false);
  }

  function js_salvar() {
    

    var aAnalisesSelecionadas = oGridAnalises.getSelection();

    var aCodigoAnalises = Array();
    for (i = 0; i < aAnalisesSelecionadas.length; i++) {
      aCodigoAnalises[aCodigoAnalises.length] = aAnalisesSelecionadas[i][2];
    }

    var oParam             = new Object();
    oParam.exec            = "aprovarAnalise";
    oParam.iSituacao       = $F('situacao');
    oParam.aCodigoAnalises = aCodigoAnalises;

    js_divCarregando('Aguarde, salvando aprovação das análises', 'msgBox');
    var oAjax = new Ajax.Request(sUrl,
                                 {
                                   method: 'post',
                                   parameters: 'json='+Object.toJSON(oParam),
                                   onComplete: js_retornoSalvar
                                 }
                               );
  }

  function js_retornoSalvar(oAjax) {
    
    js_removeObj('msgBox');
    var oRetorno = eval("("+oAjax.responseText+")");

    if (oRetorno.status == 1) {

      alert(oRetorno.message.urlDecode());
      reiniciaJanela();
      return true;

    } else {

      alert(oRetorno.message.urlDecode());
      return false;

    }

  }
  /**
   * Limpa todos os campos da tela, inclusive as análises.
   */
  function reiniciaJanela() {

    $('orgao_numero').value      = '';
    $('orgao_descricao').value   = '';
    $('unidade_numero').value    = '';
    $('unidade_descricao').value = '';
    
    removerTodasAnalises();
  }

  function pesquisarAnalise() {

    var iOrgao     = $F('orgao_numero');
    var iUnidade   = $F('unidade_numero');
    var iAnalise   = $F('analise_numero');
    var iExercicio = $('exercicio').value;

    if (iOrgao == '' && iUnidade == '' && iAnalise == '' && iExercicio == '') {
      alert("Ao menos um dos filtros deve ser preenchido.");
      return false;
    }

    var oParam        = new Object();
    oParam.exec       = "getAnalises";
    oParam.iOrgao     = iOrgao;
    oParam.iUnidade   = iUnidade;
    oParam.iAnalise   = iAnalise;
    oParam.iExercicio = iExercicio;

    js_divCarregando('Aguarde, buscando análises', 'msgBox');
    var oAjax = new Ajax.Request(sUrl,
                                   {
                                     method: 'post',
                                     parameters: 'json='+Object.toJSON(oParam),
                                     onComplete: retornoPesquisaAnalise
                                   }
                                 );
   
  }

  function retornoPesquisaAnalise(oAjax) {

    js_removeObj('msgBox');
    var oRetorno = eval("("+oAjax.responseText+")");
    
    if (oRetorno.status == 1) {

      removerTodasAnalises();

      for (i = 0; i < oRetorno.aAnalises.length; i++) {
        //Monta a linha e coloca no array.
        var aLinha = [oRetorno.aAnalises[i].exercicio, 
                      oRetorno.aAnalises[i].codigoanalise, 
                      js_formatar(oRetorno.aAnalises[i].dataanalise, 'd'), 
                      oRetorno.aAnalises[i].orgao, 
                      oRetorno.aAnalises[i].unidade, 
                      oRetorno.aAnalises[i].situacao];
        aAnalises[aAnalises.length] = aLinha;
      }
      /**
       * Após limpar a grid, reinsere cada linha e totaliza os valores do valor total.
       */
      oGridAnalises.clearAll(true);

      for (var iLinhas = 0; iLinhas < aAnalises.length; iLinhas++) {
        oGridAnalises.addRow(aAnalises[iLinhas], false);
      }
      oGridAnalises.renderRows();
      return true;

    } else {

      alert(oRetorno.message.urlDecode());
      return false;

    }

  }

  /**
   * Faz a busca por análises.
   * @param {boolean} lMostrar Se deve mostrar a janela para busca ou fazer busca pela chave.
   */
  function buscarAnalise(lMostrar) {

    //Precisa da unidade, recurso e anexo para realizar a busca das análises.
    var iOrgao   = $F('orgao_numero');
    var iUnidade = $F('unidade_numero');

    var sQueryStringAdicional = 'filtrar_notas_para_diretor=1';

    if (iOrgao != '') {
      var sQueryStringAdicional = sQueryStringAdicional + '&iOrgao=' + iOrgao;
    }

    if (iUnidade != '') {
      var sQueryStringAdicional = sQueryStringAdicional + '&iUnidade=' + iUnidade;
    }

    var sQueryString           = sQueryStringAdicional + '&funcao_js=parent.retornoanalise|0|3|2|6';
    var sArquivo              = 'func_controleinternocredor.php';
    var sTituloTela           = 'Pesquisar Análise';
    if (!lMostrar) {
      sQueryString = 'pesquisa_chave=' + $F('analise_numero') + '&' + sQueryStringAdicional + '&funcao_js=parent.retornoAnalise';
    }
    js_OpenJanelaIframe('', 'db_iframe_analise', sArquivo + '?' + sQueryString, sTituloTela, lMostrar);
  }

  /**
   * Retorno da busca por análise.
   * @param {int}     iCodigo
   * @param {int}     iCodigoEmpenho
   * @param {string}  sDescricao
   * @param {number}  nValor
   * @param {boolean} lErro
   */
  function retornoAnalise(iCodigo, iCodigoEmpenho, sDescricao, nValor, lErro) {
    db_iframe_analise.hide();
    retorno('analise', iCodigo, sDescricao, lErro, false);
  }

  /**
   * Fecha a lookup e preenche as informações de retorno.
   * @param {string}  sCampo            Nome dos campos da âncora.
   * @param {int}     iCodigo           Código referente à âncora.
   * @param {string}  sDescricao        Descrição referente à âncora.
   * @param {boolean} lErro             Se ocorreu erro.
   * @param {boolean} lLimpaAnalises Se deve limpar as análises.
   */
  function retorno(sCampo, iCodigo, sDescricao, lErro, lLimpaAnalises) {

    //Verifica se deve remover todas as análises.
    if (lLimpaAnalises && $(sCampo + '_numero').value != iCodigo) {
      removerTodasAnalises();
    }

    $(sCampo+'_numero').value = iCodigo;
    if (lErro) {
      $(sCampo+'_numero').value = '';
    }
    $(sCampo+'_descricao').value = sDescricao;
  }

  /**
   * Cria uma grid para as análises.
   */
  function criaGrid() {

    oGridAnalises              = new DBGrid("oGridAnalises");
    oGridAnalises.nameInstance = 'oGridAnalises';
    oGridAnalises.setCheckbox(0);
    oGridAnalises.hasTotalizador = true;
    oGridAnalises.allowSelectColumns(true);
    oGridAnalises.setCellWidth(['15%', '15%', '20%', '12%', '12%', '20%']);
    oGridAnalises.setCellAlign(['center', 'center', 'center', 'center', 'center', 'left']);
    oGridAnalises.setHeader(["Exercício", "Código", "Data", "Órgão", "Unidade", "Situação"]);
    oGridAnalises.setHeight(150);
    oGridAnalises.show($('ctnGrid'));
    oGridAnalises.clearAll(true);
    oGridAnalises.renderRows();
  }

  /**
   * Remove todas as análises da grid.
   */
  function removerTodasAnalises() {

    aAnalises = [];
    oGridAnalises.clearAll(true);
    oGridAnalises.renderRows();
  }
  //Chama o método para criação da grid.
  criaGrid();

</script>
