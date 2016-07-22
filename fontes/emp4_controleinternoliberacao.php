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
$iOpcao = 1;

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
      <legend><strong>Controle Interno - Liberação de Empenho</strong></legend>
      <table>        
        <tr> 
          <td  align="left" nowrap><strong>Órgão/Unidade: </strong></td>
          <td align="left" nowrap>
            <? 
              db_input("orgao",1,1,true,"text",1, "", "", "", "", 2);  
              db_input("unidade",1,1,true,"text",1, "", "", "", "", 2);  
            ?>
          </td>
        </tr>
        <tr> 
          <td  align="left" nowrap title="<?=$Tz01_numcgm?>"><?db_ancora('<b>Credor:</b>',"js_pesquisa_cgm(true);",1);?></td>
          <td align="left" nowrap>
            <? 
              db_input("z01_numcgm",6,$Iz01_numcgm,true,"text",4,"onchange='js_pesquisa_cgm(false);'");
              db_input("z01_nome2",40,"",true,"text",3);  
            ?>
          </td>
        </tr>
        <tr>
          <td class="headerLabel"><label for="situacao">Liberada:</label></td>
          <td colspan="3">
            <?php
              $oSituacoesControleInterno = db_utils::getDao("controleinternosituacoes");
              $rsSituacoes = $oSituacoesControleInterno->sql_record($oSituacoesControleInterno->sql_query(null, "sequencial, descricao", null, "tipo = 'Analista'"));
              db_selectrecord('situacao', $rsSituacoes, true, $oGet->liberacaotipo, 'style="' . $sStyleInputText . '"', "", "", "", "js_pre_texto();", 1);
            ?>
          </td>
        </tr>
        <tr>
          <td colspan="2">
            <fieldset>
              <legend>Liquidações</legend>
              <div id="lancadorLiquidacao" style="padding-bottom: 5px;">
                <label for="liquidacao_numero">
                  <?php db_ancora('Liquidação:', 'buscarLiquidacao(true)', $iOpcao, null, 'liquidacao_ancora'); ?>
                </label>
                <?php
                $Sliquidacao_numero = 'Liquidação';
                db_input('liquidacao_numero', 10, 1, true, 'text', $iOpcao, 'onChange="buscarLiquidacao(false)"');
                db_input('empenho_codigo', 10, 0, true, 'hidden', 3);
                db_input('liquidacao_descricao', 40, 0, true, 'text', 3);
                db_input('liquidacao_valor', 10, 0, true, 'hidden', 3);
                ?>
                <input type="button" id="btnLancar" value="Lançar" onClick="lancarLiquidacao();" />
              </div>
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
      <input type="button" id="btnSalvar" value="Salvar" />
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

    $('btnSalvar').addEventListener('click', js_salvar);
    $('btnPesquisar').addEventListener('click', reiniciaJanela);
    reiniciaJanela();
  };

  //Coloca no textarea um "pré-texto" definido pela controladoria
  function js_pre_texto() {

    var oComboSituacao = $('situacao');
    var iDiligencia = '<?php echo ControleInterno::SITUACAO_DILIGENCIA ?>';
    var iIrregular  = '<?php echo ControleInterno::SITUACAO_IRREGULAR  ?>';
    var iRessalva   = '<?php echo ControleInterno::SITUACAO_RESSALVA   ?>';
    var iRegular    = '<?php echo ControleInterno::SITUACAO_REGULAR    ?>';
    var sPreTexto   = "";

    switch(oComboSituacao.options[oComboSituacao.selectedIndex].value) {
      case iDiligencia:
        sPreTexto = "De acordo com o que preceitua o art. 17 da Instrução Normativa nº 01/2015 - CGM e após a análise circunstanciada da matéria, propomos baixar o processo em DILIGÊNCIA para que sejam atendidas as exigências abaixo enumeradas, fixando-se o prazo de 10 (dez) dias para o atendimento.";
      break; 
      case iIrregular:
        sPreTexto = "De acordo com o que preceitua o art. 18 da Instrução Normativa nº 01/2015 - CGM e após a análise circunstanciada da matéria evidenciamos o não cumprimento das exigências legais, encontrando-se a presente despesa IRREGULAR, pelos fatos e fundamentos enumerados abaixo:"; 
      break; 
      case iRessalva:
        sPreTexto = "De acordo com o que preceitua o art. 16 da Instrução Normativa nº 01/2015 - CGM e após a análise circunstanciada da matéria, sugerimos o registro regular com RESSALVA da presente despesa, neste Departamento de Controle Interno."; 
      break;
      case iRegular:
        sPreTexto = "De acordo com o que preceitua o art. 15 da Instrução Normativa nº 01/2015 - CGM e após a análise circunstanciada da matéria, sugerimos o registro REGULAR da presente despesa, podendo o processo ser encaminhado à origem para adoção das providências que o caso requer.";
      break;
      default:
        alert("Não foi encontrado um pré-texto para a situação selecionada.");
    }
    tinyMCE.get('ressalva').setContent(sPreTexto);
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

  function js_salvar() {
    
    //utilizando função do próprio TinyMCE para pegar o conteúdo do campo de texto
    var ressalvaText = tinyMCE.get('ressalva').getContent();
    
    var aNotas = [];
    for (var iIndice = 0; iIndice < aLiquidacoes.length; iIndice++) {
      aNotas[iIndice] = aLiquidacoes[iIndice][1];
    }
    var sRessalva    = ressalvaText;
    var iSituacao    = $F('situacao');
    var iNumCgm      = $F('z01_numcgm');

    var oParametro = {
      'exec'      : 'liberarNotaEmpenhoAnalise',
      'aNotas'    : aNotas,
      'sRessalva' : encodeURIComponent(tagString(sRessalva)),
      //'sRessalva' : sRessalva,
      'iSituacao' : iSituacao,
      'iNumCgm'   : iNumCgm
    };

    new AjaxRequest(sUrl, oParametro,
      function (oRetorno, lErro) {

        if (oRetorno.status == 1) {
          alert(oRetorno.message.urlDecode());

          if (oRetorno.erro == false) {
              
            if (confirm("Deseja emitir a nota de análise?")) {
              
              var iHeight = (screen.availHeight - 40);
              var iWidth  = (screen.availWidth - 5);
              var sOpcoes = 'width=' + iWidth + ',height=' + iHeight + ',scrollbars=1,location=0';
              var sQuery  = '?iNumeroInstrucao=' + oRetorno.iCodigoAnalise;
              var oJanela = window.open("emp4_documentocontroleinterno_002_natal.php" + sQuery, '', sOpcoes);
              
              oJanela.moveTo(0, 0);
            }
            reiniciaJanela();
            
          }
          
        }
      }
    ).setMessage(("Atualizando status da nota, aguarde.")).execute();
  }

  /**
   * Limpa todos os campos da tela, inclusive as liquidações.
   */
  function reiniciaJanela() {

    removerTodasLiquidacoes();
    tinyMCE.get('ressalva').setContent('');
    var oComboSituacao = $('situacao');

    oComboSituacao.value            = oComboSituacao.options[1];
    $('orgao').value                = '';
    $('unidade').value              = '';
    $('z01_numcgm').value           = '';
    $('z01_nome2').value            = '';
    $('liquidacao_numero').value    = '';
    $('empenho_codigo').value       = '';
    $('liquidacao_descricao').value = '';
    $('liquidacao_valor').value     = '';

    js_pre_texto();
    js_pesquisa_cgm(true);
  }

  /**
   * Faz a busca por liquidações.
   * @param {boolean} lMostrar Se deve mostrar a janela para busca ou fazer busca pela chave.
   */
  function buscarLiquidacao(lMostrar) {

    var iCredor  = $F('z01_numcgm');
    var iOrgao   = $F('orgao');
    var iUnidade = $F('unidade');

    if (iCredor == '' || iOrgao == '' || iUnidade == '') {

      alert("Para selecionar uma liquidação, você deve preencher todos os filtros.");
      return false;
    }

    var sQuerySringAdicional  = 'filtrar_notas_para_analista=1&iCredor='+ iCredor +'&iOrgao='+ iOrgao +'&iUnidade='+ iUnidade;
    var sQuerySring           = sQuerySringAdicional + '<?php echo $sFiltroValor; ?>&funcao_js=parent.retornoLiquidacao|e69_codnota|e60_numemp|z01_nome|e70_vlrliq';
    var sArquivo              = 'func_controleinternoliquidacoes.php';
    var sTituloTela           = 'Pesquisar Liquidação';
    if (!lMostrar) {
      sQuerySring = 'pesquisa_chave=' + $F('liquidacao_numero') + '&' + sQuerySringAdicional + '<?php echo $sFiltroValor; ?>&funcao_js=parent.retornoLiquidacao';
    }
    js_OpenJanelaIframe('', 'db_iframe_empempenho', sArquivo + '?' + sQuerySring, sTituloTela, lMostrar);
  }

  /**
   * Retorno da busca por liquidação.
   * @param {int}     iCodigo
   * @param {int}     iCodigoEmpenho
   * @param {string}  sDescricao
   * @param {number}  nValor
   * @param {boolean} lErro
   */
  function retornoLiquidacao(iCodigo, iCodigoEmpenho, sDescricao, nValor, lErro) {
    db_iframe_empempenho.hide();
    retorno('liquidacao', iCodigo, sDescricao, nValor, lErro, false);
    $('empenho_codigo').value   = iCodigoEmpenho;
  }

  /**
   * Fecha a lookup e preenche as informações de retorno.
   * @param {string}  sCampo            Nome dos campos da âncora.
   * @param {int}     iCodigo           Código referente à âncora.
   * @param {string}  sDescricao        Descrição referente à âncora.
   * @param {boolean} lErro             Se ocorreu erro.
   * @param {boolean} lLimpaLiquidacoes Se deve limpar as liquidações.
   */
  function retorno(sCampo, iCodigo, sDescricao, nValor, lErro, lLimpaLiquidacoes) {

    //Verifica se deve remover todas as liquidações.
    if (lLimpaLiquidacoes && $(sCampo + '_numero').value != iCodigo) {
      removerTodasLiquidacoes();
    }
    $(sCampo+'_valor').value = nValor;
    $(sCampo+'_numero').value = iCodigo;
    if (lErro) {
      $(sCampo+'_numero').value = '';
    }
    $(sCampo+'_descricao').value = sDescricao;
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

    return "<input type='button' value='Remover' onClick='removerLiquidacao(" + iIndice + ")' />"
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
   * Remove uma liquidação da grid pelo seu indice e remonta o array e a grid.
   * @param {int} iIndice Indice da linha a ser removida.
   */
  function removerLiquidacao(iIndice) {

    var iContador       = 0;
    var nValorLinha     = 0;
    var nTotalRepasse   = 0;
    var aLiquidacaoNova = [];

    /**
     * Monta um novo array pulando o item removido, retotaliza os valores e reimprime a grid.
     */
    oGridLiquidacoes.clearAll(true);
    for (var iLinha = 0; iLinha < aLiquidacoes.length; iLinha++) {

      if (iIndice == iLinha) {
        continue;
      }

      nValorLinha                   = js_strToFloat(aLiquidacoes[iLinha][3]);
      nTotalRepasse                += nValorLinha;
      aLiquidacaoNova[iContador]    = aLiquidacoes[iLinha];
      aLiquidacaoNova[iContador][4] = montaBotaoRemover(iContador);

      oGridLiquidacoes.addRow(aLiquidacoes[iLinha], false);
      iContador++;
    }
    oGridLiquidacoes.renderRows();
    aLiquidacoes = aLiquidacaoNova;

    //Verifica se tem liquidações na grid para saber se libera/bloqueia o campo com o valor do repasse.
    atualizaValorRepasse(nTotalRepasse, (aLiquidacoes.length > 0));
  }

  /**
   * Adiciona uma liquidação na grid.
   */
  function lancarLiquidacao() {

    var iCodigoEmpenho = $F('empenho_codigo');
    var iCodigoNota    = $F('liquidacao_numero');
    var sNomeCredor    = $F('z01_nome2');
    var nValor         = $F('liquidacao_valor');

    //Verifica se a liquidação foi selecionada.
    if (iCodigoNota == '') {

      alert("Selecione uma liquidação para ser lançada.");
      return false;
    }

    if (sNomeCredor == "") {
      alert("Aguarde carregar as informações.");
      return false;
    }
    //Verifica se a liquidação selecionada já está na grid.
    for (var iLinha = 0; iLinha < aLiquidacoes.length; iLinha++) {

      if (aLiquidacoes[iLinha][1] == iCodigoNota) {

        alert("Essa liquidação já foi lançada.");
        return false;
      }
    }
    //Monta a linha e coloca no array.
    var aLinha = [iCodigoEmpenho,
                  iCodigoNota,
                  sNomeCredor,
                  js_formatar(nValor, 'f'),
                  montaBotaoRemover(aLiquidacoes.length)];
    aLiquidacoes[aLiquidacoes.length] = aLinha;
    /**
     * Após limpar a grid, reinsere cada linha e totaliza os valores do valor total.
     */
    oGridLiquidacoes.clearAll(true);
    for (var iLinhas = 0; iLinhas < aLiquidacoes.length; iLinhas++) {

      oGridLiquidacoes.addRow(aLiquidacoes[iLinhas], false);
    }
    oGridLiquidacoes.renderRows();

    $('empenho_codigo').value       = '';
    $('liquidacao_numero').value    = '';
    $('liquidacao_descricao').value = '';
    $('liquidacao_valor').value     = '';
  }

  //Chama o método para criação da grid.
  criaGrid();

</script>
