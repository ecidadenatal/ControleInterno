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
$iOpcao = 3;

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
  <script src="scripts/tinymce/tinymce.min.js"></script>
  <script>tinymce.init({ selector:'textarea',
                         menubar: false,
                         statusbar: false,
                         width: 550,
                         height: 300,
                         plugins: 'table textcolor colorpicker paste ',
                         toolbar: [
                          'undo redo | styleselect | bold italic underline | bullist numlist | forecolor backcolor | alignleft aligncenter alignright | paste | table'
                         ],
                         language: 'pt_BR' });</script>
  <script type="text/javascript" src="scripts/widgets/DBAbas.widget.js"></script>
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
  <form id="formLiberacaoEmpenho">
    <input id="liberacaoTipo" type="hidden" >
    <fieldset>
      <legend><strong>Controle Interno - Desaprovação de Análise</strong></legend>
      <table>
        <tr>
          <td>
            <label for="analise_numero">
              <strong><?php db_ancora('Análise:', 'buscarAnalise(true)', $iOpcao, null, 'analise_ancora'); ?></strong>
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
          <td  align="left" nowrap><strong>Órgão/Unidade: </strong></td>
          <td align="left" nowrap>
            <? 
              db_input("orgao",1,1,true,"text",22, "", "", "", "", 2);  
              db_input("unidade",1,1,true,"text",22, "", "", "", "", 2);  
            ?>
          </td>
        </tr>
        <tr> 
          <td  align="left" nowrap title="<?=$Tz01_numcgm?>"><b>Credor:</b></td>
          <td align="left" nowrap>
            <? 
              db_input("z01_numcgm",6,$Iz01_numcgm,true,"text",22,"onchange='js_pesquisa_cgm(false);'");
              db_input("z01_nome2",40,"",true,"text",3);  
            ?>
          </td>
        </tr>
        <tr>
          <td class="headerLabel"><label for="situacao">Situação:</label></td>
          <td colspan="3">
            <?php
              $oSituacoesControleInterno = db_utils::getDao("controleinternosituacoes");
              $rsSituacoes = $oSituacoesControleInterno->sql_record($oSituacoesControleInterno->sql_query(null, "sequencial, descricao", null, "tipo = 'Analista'"));
              db_selectrecord('situacao', $rsSituacoes, true, $oGet->liberacaotipo, 'style="' . $sStyleInputText . '"', "", "", "", "", 1);
            ?>
          </td>
        </tr>
        <tr>
          <td colspan="2">
            <fieldset>
              <legend>Liquidações</legend>
              <div id='ctnGrid'></div>
            </fieldset>
          </td>
        </tr>
        <tr>
          <td colspan='2'>
            <fieldset>
              <legend><label for="ressalva">Ressalva</label></legend>
              <?php
              $Smotivo = "ressalva";
              db_textarea('ressalva', 3, 40, false, true, 'text', $iOpcao, ' class="field-size-max" ');
              ?>
            </fieldset>
          </td>
        </tr>
      </table>
    </fieldset>
    <p class="text-center">
      <input type="button" id="btnDesaprovar" value="Desaprovar" />
      <input type="button" id="btnPesquisar" value="Pesquisar" />
    </p>
  </form>
</div>
<?php db_menu(); ?>
</body>
</html>
<script type="text/javascript">

  var aLiquidacoes = [];
  const sUrl = 'emp4_controleinternoliquidacao.RPC.php';

  window.onload = function() {

    $('btnDesaprovar').addEventListener('click', js_desaprovar);
    $('btnPesquisar').addEventListener('click', reiniciaJanela);
    reiniciaJanela();
  };

  function js_desaprovar() {
    
    var iAnalise     = $F('analise_numero');
    var aNotas = [];
    for (var iIndice = 0; iIndice < aLiquidacoes.length; iIndice++) {
      aNotas[iIndice] = aLiquidacoes[iIndice][1];
    }

    var oParametro = {
      'exec'      : 'desaprovarAnalise',
      'iAnalise'  : iAnalise,
      'aNotas'    : aNotas
    };

    if(confirm("Deseja mesmo desaprovar a análise "+iAnalise+"?")) {
      new AjaxRequest(sUrl, oParametro,
        function (oRetorno, lErro) {

          if (oRetorno.status == 1) {
            alert(oRetorno.message.urlDecode());

            if (oRetorno.erro == false) {
              
              reiniciaJanela();
              
            }
            
          }
        }
      ).setMessage(("Desaprovando análise, aguarde.")).execute();
    }
  }

  /**
   * Limpa todos os campos da tela, inclusive as liquidações.
   */
  function reiniciaJanela() {

    removerTodasLiquidacoes();
    tinyMCE.get('ressalva').setContent('');
    var oComboSituacao = $('situacao');

    oComboSituacao.value            = oComboSituacao.options[1];
    oComboSituacao.disabled         = true;
    $('orgao').value                = '';
    $('unidade').value              = '';
    $('z01_numcgm').value           = '';
    $('z01_nome2').value            = '';

    buscarAnalise(true);
  }

  /**
   * Cria uma grid para as liquidações.
   */
  function criaGrid() {

    oGridLiquidacoes              = new DBGrid("oGridLiquidacoes");
    oGridLiquidacoes.nameInstance = 'oGridLiquidacoes';
    oGridLiquidacoes.setCellAlign(['center', 'center', 'left', 'right', 'center']);
    oGridLiquidacoes.setHeader(["Empenho", "Nota", 'Credor', 'Valor', 'Ações']);
    oGridLiquidacoes.setCellWidth(['15%', '15%', '40%', '15%', '15%']);
    //oGridLiquidacoes.setHeight(150);
    oGridLiquidacoes.show($('ctnGrid'));
    oGridLiquidacoes.clearAll(true);
    oGridLiquidacoes.renderRows();
  }

  /**
   * Retorna a string do botão.
   * @param iIndice {int} Indice da linha para o botão.
   */
  function montaBotaoRemover(iIndice) {

    return "<input type='button' disabled value='Remover'/>"
  }

  /**
   * Remove todas as liquidações da grid.
   */
  function removerTodasLiquidacoes() {

    aLiquidacoes = [];
    oGridLiquidacoes.clearAll(true);
    oGridLiquidacoes.renderRows();
  }

  /**
   * Faz a busca por análises.
   * @param {boolean} lMostrar Se deve mostrar a janela para busca ou fazer busca pela chave.
   */
  function buscarAnalise(lMostrar) {

    var sQueryString          = 'filtro_desaprovacao=1&funcao_js=parent.retornoAnalise|0|2|3|4|5|6';
    var sArquivo              = 'func_controleinternocredor.php';
    var sTituloTela           = 'Pesquisar Análise';
    if (!lMostrar) {
      sQueryString = 'pesquisa_chave=' + $F('analise_numero') + '&' + sQueryStringAdicional + '&funcao_js=parent.retornoAnalise';
    }
    js_OpenJanelaIframe('', 'db_iframe_analise', sArquivo + '?' + sQueryString, sTituloTela, lMostrar);
  }

  /**
   * Retorno da busca por análise.
   */
  function retornoAnalise(iCodigo, iOrgao, iUnidade, sDescricao, iSituacao, iCredor, lErro) {
    
    db_iframe_analise.hide();
    
    $('analise_numero').value = iCodigo;
    if (lErro) {
      $('analise_numero').value = '';
    }
    $('orgao').value   = iOrgao;
    $('unidade').value = iUnidade;
    $('analise_descricao').value = sDescricao;
    $('z01_numcgm').value = iCredor;
    $('situacao').value = iSituacao;
    listaLiquidacaoesAnalise();
    js_pesquisa_cgm(false);
  }

  function js_pesquisa_cgm(mostra){
    if(mostra==true){
      js_OpenJanelaIframe('top.corpo','func_nome',
                          'func_cgm.php?funcao_js=parent.js_mostracgm1|z01_numcgm|z01_nome',
                          'Pesquisa',true);
    }else{
       if($F('z01_numcgm') != ''){ 
          js_OpenJanelaIframe('top.corpo','func_nome',
                              'func_cgm.php?pesquisa_chave='+$F('z01_numcgm')+'&funcao_js=parent.js_mostracgm',
                              'Pesquisa',false);
       }else{
         $('z01_nome2').value = ''; 
       }
    }
  }
  function js_mostracgm(erro,chave){
    
    $('z01_nome2').value = chave; 
    if(erro==true){ 
      $('z01_numcgm').value = ''; 
      $('z01_numcgm').focus(); 
    }
  }
  function js_mostracgm1(chave1,chave2){
    $('z01_numcgm').value = chave1;  
    $('z01_nome2').value  = chave2;
    func_nome.hide();
  }

  function listaLiquidacaoesAnalise() {
    
    var iAnalise = $F('analise_numero');

    var oParam = {
      'exec'      : 'buscarDetalhesAnalise',
      'iAnalise'  : iAnalise
    };

    js_divCarregando('Aguarde, buscando dados da análise', 'msgBox');
    var oAjax = new Ajax.Request(sUrl,
                                 {
                                   method: 'post',
                                   parameters: 'json='+Object.toJSON(oParam),
                                   onComplete: retornaLiquidacoesAnalise
                                 }
                               );
  }

  function retornaLiquidacoesAnalise(oAjax) {

    js_removeObj('msgBox');
    var oRetorno = eval("("+oAjax.responseText+")");

    if (oRetorno.status == 1) {
      tinyMCE.get('ressalva').setContent(oRetorno.sParecer);
      
      for (i = 0; i < oRetorno.aLiquidacoes.length; i++) {

        //Monta a linha e coloca no array.
        var aLinha = [oRetorno.aLiquidacoes[i].iCodigoEmpenho,
                      oRetorno.aLiquidacoes[i].iCodigoNota,
                      oRetorno.aLiquidacoes[i].sNomeCredor,
                      js_formatar(oRetorno.aLiquidacoes[i].nValor, 'f'),
                      montaBotaoRemover(aLiquidacoes.length)];
        aLiquidacoes[aLiquidacoes.length] = aLinha;
      }
      oGridLiquidacoes.clearAll(true);
      for (var iLinhas = 0; iLinhas < aLiquidacoes.length; iLinhas++) {

        oGridLiquidacoes.addRow(aLiquidacoes[iLinhas], false);
      }
      oGridLiquidacoes.renderRows();
    } 
  }
  //Chama o método para criação da grid.
  criaGrid();

</script>
