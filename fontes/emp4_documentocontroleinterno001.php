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
<body style='background-color: #CCCCCC; margin-top: 30px'>
<form id="formLiberacaoEmpenho">
  <div class="container">
    <div style="width: 400px;">
        <fieldset>
          <legend class="bold">Controle Interno - Emissão da Nota de Análise</legend>
          <table style="width: 100%">
            <tr>
              <td class="headerLabel">
                <Label for="empenho">
                <?php
                db_ancora('Empenho:', 'js_pesquisa_empenho()', 1);
                ?>
                </Label>
              </td>
              <td>
                <?php
                db_input('empenho', 15, false, true, 'text', 3, null, null, null, null);
                ?>
              </td>
            </tr>
            <tr>
              <td class="headerLabel">
                <label for="nota">
                  Nota:
                </label>
              </td>
              <td>
                <?php
                db_input('nota', 15, false, true, 'text', 3, null, null, null, null);
                ?>
              </td>
            </tr>
          </table>
        </fieldset>

        <p class="text-center">
          <input type="button" id="btnPesquisar" value="Pesquisar" />
          <input type="button" id="btnEmissao" value="Emitir" />
        </p>
      </div>
  </div>
</form>

<script>
  const sUrl = 'emp4_controleinternoliquidacao.RPC.php';

  window.onload = function() {

    reiniciaJanela();
    $('btnEmissao').addEventListener('click', emissaoDocumento);
    $('btnPesquisar').addEventListener('click', reiniciaJanela);
  };

  function reiniciaJanela() {

    $('empenho').value  = '';
    $('nota').value     = '';
    js_pesquisa_empenho();
  }

  function js_pesquisa_empenho() {

    js_OpenJanelaIframe( 'top.corpo',
                         'db_iframe_empempenho',
                         'func_controleinternoliquidacoes.php?filtrar_para_emissao=1&funcao_js=parent.js_mostra_empenho|e60_numemp|e69_codnota|e70_vlrliq',
                         'Pesquisa de Notas', true );
  }

  function js_mostra_empenho(iNumeroEmpenho, iCodigoNota) {

    $('empenho').value = iNumeroEmpenho;
    $('nota').value    = iCodigoNota;
    db_iframe_empempenho.hide();
  }

  function emissaoDocumento() {

    if (empty($F('nota'))) {

      alert('O campo Nota é obrigatório.');
      return;
    }

    var iHeight = (screen.availHeight - 40);
    var iWidth  = (screen.availWidth - 5);
    var sOpcoes = 'width=' + iWidth + ',height=' + iHeight + ',scrollbars=1,location=0';
    var sQuery  = '?iCodigoNota=' + $F('nota');
    var oJanela = window.open("emp4_documentocontroleinterno002.php" + sQuery, '', sOpcoes);

    oJanela.moveTo(0, 0);
  }
</script>

<?php db_menu() ?>
</body>
</html>
