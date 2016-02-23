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

require_once "libs/db_stdlib.php";
require_once "libs/db_conecta_plugin.php";
require_once "libs/db_sessoes.php";
require_once "libs/db_utils.php";
require_once "libs/db_app.utils.php";
require_once "dbforms/db_funcoes.php";

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
  <script src="scripts/tinymce/tinymce.min.js"></script>
  <script>tinymce.init({ selector:'textarea',
                         menubar: false,
                         statusbar: false,
                         width: 700,
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
<body style='background-color: #CCCCCC; margin-top: 30px'>
<form id="formLiberacaoEmpenho">
  <input id="liberacaoTipo" type="hidden" value="<?= $oGet->liberacaotipo?>">
  <div class="container">
    <div style="width: 600px;">

      <div id="ctnCadastroAtividade">
        <fieldset>
          <legend class="bold">Controle Interno - Liberação <?=$sTituloTipo?> de Empenho</legend>
          <table style="width: 100%">
            <tr>
              <td class="headerLabel">
                <Label for="empenho">
                <?php
                db_ancora('Empenho:', 'js_pesquisa_empenho()', $oGet->liberacaotipo);
                ?>
                </Label>
              </td>
              <td colspan="3">
                <?php
                db_input('empenho', 15, false, true, 'text', 3, null, null, null, $sStyleInputText);
                ?>
              </td>
            </tr>
            <tr>
              <td class="headerLabel">
                <label for="nota">
                  Nota:
                </label>
              </td>
              <td colspan="3">
                <?php
                db_input('nota', 15, false, true, 'text', 3, null, null, null, $sStyleInputText);
                ?>
              </td>
            </tr>
            <tr>
              <td class="headerLabel">
                <label for="valor">
                  Valor:
                </label>
              </td>
              <td colspan="3">
                <?php
                db_input('valor', 15, false, true, 'text', 3, null, null, null, $sStyleInputText);
                ?>
              </td>
            </tr>
            <tr>
              <td class="headerLabel"><label for="situacao">Liberada:</label></td>
              <td colspan="3">
                <?php
                $aLiberada = array(
                  ControleInterno::SITUACAO_REJEITADO_DIRETOR  => "Não",
                  ControleInterno::SITUACAO_LIBERADO_DIRETOR   => "Sim"
                );
                if ($oGet->liberacaotipo == ControleInterno::CONTROLE_ANALISTA) {

                  $aLiberada = array(
                    ControleInterno::SITUACAO_REJEITADO_ANALISTA => "Não",
                    ControleInterno::SITUACAO_LIBERADO_ANALISTA  => "Sim"
                  );
                }

                db_select('situacao', $aLiberada, true, $oGet->liberacaotipo, 'style="' . $sStyleInputText . '"');
                ?>
              </td>
            </tr>
            <tr>
              <td colspan="2">
                <fieldset>
                  <legend><label for="ressalva">Ressalva:</label></legend>
                  <?
                  db_textarea('ressalva', 5, 80, '', true, 'text', '', "")
                  ?>
                </fieldset>
              </td>
            </tr>
          </table>
        </fieldset>
        <p class="text-center">
          <input type="button" id="btnSalvarAtividade" value="Salvar" />
          <input type="button" id="btnPesquisar" value="Pesquisar" />
        </p>
      </div>
    </div>
  </div>
</form>

<script>

  const sUrl = 'emp4_controleinternoliquidacao.RPC.php';
  window.onload = function() {

    reiniciaJanela();
    $('btnSalvarAtividade').addEventListener('click', js_salvar);
    $('btnPesquisar').addEventListener('click', reiniciaJanela);
  };

  function reiniciaJanela() {

    var oComboSituacao = $('situacao');
    $('empenho').value  = '';
    $('nota').value     = '';
    $('valor').value    = '';
    $('ressalva').value = '';
    oComboSituacao.value = oComboSituacao.options[1];

    js_pesquisa_empenho();
  }

  function js_salvar() {
    //utilizando função do próprio TinyMCE para pegar o conteúdo do campo de texto
    var ressalvaText   = tinyMCE.get('ressalva').getContent();

    var sResalva       = ressalvaText;//$F('ressalva')
    var iSituacao      = $F('situacao');
    var iCodigoNota    = $F('nota');
    var iLiberacaoTipo = $F('liberacaoTipo');

    if (!$F('empenho')) {
      alert("Campo empenho é de preenchimento obrigatório.");
      return;
    }

    var oParametro = {
      'exec'           : 'liberarNotaEmpenho',
      'iCodigoNota'    : iCodigoNota,
      'sRessalva'      : encodeURIComponent(tagString(sResalva)),
      'iSituacao'      : iSituacao,
      'tipo_liberacao' : iLiberacaoTipo
    };

    new AjaxRequest(sUrl, oParametro,
      function (oRetorno, lErro) {

        if (oRetorno.status == 1) {
          getUltimoMovimento();
          alert(oRetorno.message.urlDecode());

          if (oRetorno.erro == false) {
              
          	if (confirm("Deseja emitir a nota de análise?")) {
              	
          	  var iHeight = (screen.availHeight - 40);
          	  var iWidth  = (screen.availWidth - 5);
                var sOpcoes = 'width=' + iWidth + ',height=' + iHeight + ',scrollbars=1,location=0';
                var sQuery  = '?iCodigoNota=' + iCodigoNota;
                var oJanela = window.open("emp4_documentocontroleinterno002.php" + sQuery, '', sOpcoes);
                
                oJanela.moveTo(0, 0);
                
          	}
          	
          }
          
        }
      }
    ).setMessage(("Atualizando status da nota, aguarde.")).execute();
  }

  function js_pesquisa_empenho() {

    var sNomeParametroFiltro = 'filtrar_notas_para_analista';

    if ($F('liberacaoTipo') == 2) {
      sNomeParametroFiltro = 'filtrar_notas_para_diretor';
    }
    js_OpenJanelaIframe( 'top.corpo',
                         'db_iframe_empempenho',
                         'func_controleinternoliquidacoes.php?' + sNomeParametroFiltro + '=1<?php echo $sFiltroValor; ?>&funcao_js=parent.js_mostra_empenho|e60_numemp|e69_codnota|e70_vlrliq',
                         'Pesquisa de Notas Pendentes', true );
  }

  function js_mostra_empenho(iNumeroEmpenho, iCodigoNota, nValor) {

    $('empenho').value = iNumeroEmpenho;
    $('nota').value    = iCodigoNota;
    $('valor').value   = js_formatar(nValor, 'f');
    getUltimoMovimento();
    db_iframe_empempenho.hide();
  }

  function getUltimoMovimento() {

    //$('ressalva').value           = '';
    tinyMCE.get('ressalva').setContent('');

    new AjaxRequest(
      sUrl,
      {'exec' : 'getUltimoMovimento', 'iCodigoNota' : $F('nota')},
      function (oRetorno, lErro) {

        if (oRetorno.movimento == null) {
          return false;
        }

        //$('ressalva').value = oRetorno.movimento.ressalva.urlDecode();
        tinyMCE.get('ressalva').setContent(oRetorno.movimento.ressalva.urlDecode());

        var aSituacoes = new Array();

        for (var iIndice = 0; iIndice < $('situacao').options.length; iIndice++) {
          aSituacoes.push( $('situacao').options[iIndice].value );
        }

        if (aSituacoes.indexOf(oRetorno.movimento.situacao) != -1) {
          $('situacao').value = oRetorno.movimento.situacao;
        } else if (oRetorno.movimento.situacao > Math.max(...aSituacoes)) {
          $('situacao').value = Math.max(...aSituacoes);
        }

      }
    ).execute();
  }

</script>

<?php db_menu() ?>
</body>
</html>
