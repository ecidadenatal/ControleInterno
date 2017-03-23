<?php
/**
 * E-cidade Software Publico para Gestão Municipal
 *   Copyright (C) 2015 DBSeller Serviços de Informática Ltda
 *                          www.dbseller.com.br
 *                          e-cidade@dbseller.com.br
 *   Este programa é software livre; você pode redistribuí-lo e/ou
 *   modificá-lo sob os termos da Licença Pública Geral GNU, conforme
 *   publicada pela Free Software Foundation; tanto a versão 2 da
 *   Licença como (a seu critério) qualquer versão mais nova.
 *   Este programa e distribuído na expectativa de ser útil, mas SEM
 *   QUALQUER GARANTIA; sem mesmo a garantia implícita de
 *   COMERCIALIZAÇÃO ou de ADEQUAÇÃO A QUALQUER PROPÓSITO EM
 *   PARTICULAR. Consulte a Licença Pública Geral GNU para obter mais
 *   detalhes.
 *   Você deve ter recebido uma cópia da Licença Pública Geral GNU
 *   junto com este programa; se não, escreva para a Free Software
 *   Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA
 *   02111-1307, USA.
 *   Cópia da licença no diretório licenca/licenca_en.txt
 *                                 licenca/licenca_pt.txt
 */
class ControleInternoDocumentoAnaliseHTML_novo {
	
	/**
	 *
	 * @var integer
	 */
	private $iInstrucaoTecnica;
	
	/**
	 *
	 * @var text $html
	 */
	private $html;
	
	public $oDados;
	
	/**
	 *
	 * @param integer $iInstrucaoTecnica        	
	 */
	public function __construct($iInstrucaoTecnica) {
		$this->iInstrucaoTecnica = $iInstrucaoTecnica;
		$this->oDados = $this->getDados($iInstrucaoTecnica);
	}
	
	/**
	 *
	 * @param integer $iInstrucaoTecnica        	
	 * @return stdClass
	 */
	public function getDados($iInstrucaoTecnica) {
		
		$sSqlDados = " select analise.sequencial as sequencial_analise,
                              analise.data_analise as data_analise,
                              analise.parecer as parecer,
                              lpad(o40_orgao,2,'0') as codigo_orgao,
                              o40_descr as descricao_orgao,
                              lpad(o41_unidade,2,'0') as codigo_unidade,
                              o41_descr as descricao_unidade,
                              cgmcredor.z01_numcgm as numcgm,
                              cgmcredor.z01_cgccpf as cnpjinteressado,
                              cgmcredor.z01_nome as interessado,
                              analise.usuario_analise cgm_analista,
                              cgmanalista.z01_nome as nome_analista,
                              usuanalista.lotacao as lotacao_analista,
                              usuanalista.cargo as cargo_analista,
                              analise.usuario_diretor_atual as cgm_diretor_atual,
                              cgmdiretor.z01_nome as nome_diretor_atual,
                              usudiretor.lotacao as lotacao_diretor_atual,
                              usudiretor.cargo as cargo_diretor_atual,
                              analise.usuario_chefe_atual as cgm_chefe_atual,
                              cgmchefe.z01_nome as nome_chefe_atual,
                              usuchefe.lotacao as lotacao_chefe_atual,
                              usuchefe.cargo as cargo_chefe_atual
                         from plugins.controleinternocredor analise
                               left join cgm cgmanalista                              on analise.usuario_analise       = cgmanalista.z01_numcgm
                               left join plugins.usuariocontroladoria usuanalista     on analise.usuario_analise       = usuanalista.numcgm
                               left join cgm cgmdiretor                               on analise.usuario_diretor_atual = cgmdiretor.z01_numcgm
                               left join plugins.usuariocontroladoria usudiretor      on analise.usuario_diretor_atual = usudiretor.numcgm
                               left join cgm cgmchefe                                 on analise.usuario_chefe_atual   = cgmchefe.z01_numcgm
                               left join plugins.usuariocontroladoria usuchefe        on analise.usuario_chefe_atual   = usuchefe.numcgm
                               left join cgm cgmaprovacao                             on analise.usuario_aprovacao     = cgmaprovacao.z01_numcgm
                              inner join plugins.controleinternosituacoes sitanalista on analise.situacao_analise      = sitanalista.sequencial
                               left join plugins.controleinternosituacoes sitdiretor  on analise.situacao_aprovacao    = sitdiretor.sequencial
                              inner join ( select distinct controleinternocredor, o40_orgao, o40_descr, o41_unidade, o41_descr
                                             from plugins.controleinternocredor_empenhonotacontroleinterno analiseliquidacao
                                                  inner join plugins.empenhonotacontroleinterno controlenota on analiseliquidacao.empenhonotacontroleinterno = controlenota.sequencial
                                                  inner join empnota                                         on controlenota.nota                            = e69_codnota
                                                  inner join empempenho                                      on e69_numemp                                   = e60_numemp
                                                  inner join empempaut                                       on e60_numemp                                   = e61_numemp
                                                  inner join orcdotacao                                      on e60_coddot                                   = o58_coddot 
                                                                                                            and e60_anousu                                   = o58_anousu
                                                  inner join orcunidade                                      on o58_orgao                                    = o41_orgao 
                                                                                                            and o58_unidade                                  = o41_unidade 
                                                                                                            and o58_anousu                                   = o41_anousu
                                                  inner join orcorgao                                        on o58_orgao                                    = o40_orgao 
                                                                                                            and o58_anousu                                   = o40_anousu
                                       ) T on analise.sequencial = T.controleinternocredor
                           inner join cgm cgmcredor on analise.numcgm_credor = cgmcredor.z01_numcgm
                       where analise.sequencial = {$iInstrucaoTecnica}";
		$rsDados = pg_exec ( $sSqlDados );
		if (pg_numrows ( $rsDados ) > 0) {
			
			$oDados = new stdClass ();
			$oDados = db_utils::fieldsMemory ( $rsDados, 0 );
			
			$sSqlLiquidacoes = "select distinct e150_numeroprocesso as processo_empenho,
    	                               e60_codemp as numero_empenho,
    	                               e60_anousu as ano_empenho,
    	                               controlenota.nota as sequencial_nota
    	                          from plugins.controleinternocredor analise
    	                               left join cgm cgmanalista                                                            on analise.usuario_analise                      = cgmanalista.z01_numcgm
    	                               left join cgm cgmdiretor                                                             on analise.usuario_diretor_atual                = cgmdiretor.z01_numcgm
    	                                left join cgm cgmchefe                                                               on analise.usuario_chefe_atual                  = cgmchefe.z01_numcgm
    	                                left join cgm cgmaprovacao                                                           on analise.usuario_aprovacao                    = cgmaprovacao.z01_numcgm
    	                               inner join plugins.controleinternosituacoes sitanalista                               on analise.situacao_analise                     = sitanalista.sequencial
    	                                left join plugins.controleinternosituacoes sitdiretor                                on analise.situacao_aprovacao                   = sitdiretor.sequencial
    	                               inner join plugins.controleinternocredor_empenhonotacontroleinterno analiseliquidacao on analise.sequencial                           = analiseliquidacao.controleinternocredor
    	                               inner join plugins.empenhonotacontroleinterno controlenota                            on analiseliquidacao.empenhonotacontroleinterno = controlenota.sequencial
    	                               inner join empnota                                                                    on controlenota.nota                            = e69_codnota
    	                               inner join empempenho                                                                 on e69_numemp                                   = e60_numemp
    	                               inner join empempaut                                                                  on e60_numemp                                   = e61_numemp
    	                                left join empautorizaprocesso                                                        on e61_autori                                   = e150_empautoriza
    	                               inner join orcdotacao                                                                 on e60_coddot                                   = o58_coddot 
    	                                                                                                                    and e60_anousu                                   = o58_anousu
    	                               inner join orcunidade                                                                 on o58_orgao                                    = o41_orgao 
    	                                                                                                                    and o58_unidade                                  = o41_unidade 
    	                                                                                                                    and o58_anousu                                   = o41_anousu
    	                               inner join orcorgao                                                                   on o58_orgao                                    = o40_orgao 
    	                                                                                                                    and o58_anousu                                   = o40_anousu
    	                               inner join plugins.controleinternosituacoes sitvalido                                 on controlenota.situacao                        = sitvalido.sequencial
    	                               inner join cgm cgmcredor                                                              on analise.numcgm_credor                        = cgmcredor.z01_numcgm
    	                         where analise.sequencial = $iInstrucaoTecnica ";
			
			$rsDadosLiquidacoes = pg_exec ( $sSqlLiquidacoes );
			if (pg_numrows ( $rsDadosLiquidacoes ) > 0) {
				$oDados->liquidacoes = db_utils::getCollectionByRecord ( $rsDadosLiquidacoes, 0 );
			}
			
			return $oDados;
			
		} else {
		  throw new Exception ( "Dados não encontrados" );
		}
	}
	
	/**
	 * Escreve o cabeçalho do documento
	 *
	 * @param stdClass $oDados        	
	 */
	private function escreverCabecalho() {
		
		$oInstituicao = InstituicaoRepository::getInstituicaoSessao ();
		$sCidade = mb_convert_case ( $oInstituicao->getMunicipio (), MB_CASE_TITLE );
		
		$this->html = "<html>";
		$this->html .= "<head>";
		$this->html .= "<title></title>";
                $this->html .= "<meta http-equiv='Content-Type' content='text/html; charset='>";
		
		// estilos
		$this->html .= "<style type='text/css'>";
		$this->html .= "<!--";
		$this->html .= ".ft0{font-style:normal;font-weight:bold;font-size:14px;font-family:Arial;color:#000000;}";
		$this->html .= ".ft1{font-style:normal;font-weight:normal;font-size:13px;font-family:Times New Roman;color:#000000;}";
		$this->html .= ".ft2{font-style:normal;font-weight:bold;font-size:15px;font-family:Arial;color:#000000;}
				table {
				  font-size:16px;font-family:Arial;
				}
                                @media print{
                                               table{ page-break-inside:auto; }
                                            }";
		$this->html .= "-->";
		$this->html .= "</style>";
		$this->html .= "</head>";
		$this->html .= "<body onload='window.print();'>";
		
		/*
		 * Cabeçalho
		 */
		$this->html .= "<table width='750'>
  	                  <tr>
  	                    <td rowspan='6' width='15%'><img width='85' height='95' src='imagens/files/logologoBrasao.jpg' ALT=''></td>
  	                    <td><span class='ft0'>{$oInstituicao->getDescricao()}</span></td>
  	                  </tr>
  	                    <tr>
  	                    <td><span class='ft1'>{$oInstituicao->getLogradouro()}, {$oInstituicao->getNumero()}</span></td>
  	                  </tr>
  	                    <tr>
  	                    <td><span class='ft1'>{$oInstituicao->getMunicipio()} - {$oInstituicao->getUf()} </span></td>
  	                  </tr>
  	                    <tr>
  	                    <td><span class='ft1'>{$oInstituicao->getTelefone()}   -    CNPJ : " . db_formatar ( $oInstituicao->getCNPJ (), "cnpj" ) . "</span></td>
  	                  </tr>
  	                  <tr>
  	                    <td></td>
  	                  </tr>
  	                  <tr>
  	                    <td><span class='ft1'>{$oInstituicao->getSite()}</span></td>
  	                  </tr>
  	                  <tr>
  	                    <td colspan='2'><hr></hr></td>
  	                  </tr>
  	                </table>
  	
  	                <table width='750'>
  	                  <tr>
  	                    <td colspan='2' align='center'><span class='ft2'>CONTROLADORIA GERAL DO MUNICÍPIO</span></td>
		              </tr>
		              <tr>
		                <td colspan='2' align='center'><span class='ft2'>DEPARTAMENTO DE CONTROLE INTERNO</span></td>
		              </tr>
		              <tr><td>&nbsp;</td></tr>
  	                </table>
  	                <br>";
	}
	
	/**
	 * Escreve o corpo do documento.
	 *
	 * @param stdClass $oDados        	
	 */
	private function escreverConteudo() {
		
		$oDataAnalise = new DBDate ( $this->oDados->data_analise );
		
		$this->html .= "<table border=0 width='750'>
				          <tr>
				            <td>";
		
		       /* Liquidacoes */
		       $this->html .= "<table width='100%' align='center'>
		                         <tr>
		                           <td align='center' width='33%'> <b>PROCESSO</b>   </td>
		                           <td align='center' width='33%'> <b>LIQUIDAÇÃO</b> </td>
		                           <td align='center' width='33%'> <b>EMPENHO</b>    </td>
		                         </tr>";
		       foreach ( $this->oDados->liquidacoes as $oItem ) {
		       	$this->html .= "<tr>
		       	                  <td align='center' width='33%'>{$oItem->processo_empenho}</td>
		       	                  <td align='center' width='33%'>{$oItem->sequencial_nota}</td>
		       	                  <td align='center' width='33%'>{$oItem->numero_empenho}/{$oItem->ano_empenho}</td>
		       	                </tr>";
		       }		
		       $this->html .= "</table>";
		       
		$this->html .= "    </td>
		                  </tr>
				          <tr><td>&nbsp;</td></tr>
		                  <tr>
		                    <td>";
		                    

		       $this->html .= "<table>
		       		              <tr>
		       		                <td> <b>INTERESSADO:</b> </td>
		       		                <td> {$this->oDados->interessado} </td>
		                          </tr>
		       		              <tr>
		       		                <td> <b>ÓRGÃO DE ORIGEM: </b></td>
		                            <td> {$this->oDados->codigo_orgao}.{$this->oDados->codigo_unidade} - {$this->oDados->descricao_unidade} </td>
		       		              </tr>
		       		              <tr><td>&nbsp;</td></tr>
		                          <tr>
		                            <td colspan='2' align='center'> <b>INSTRUÇÃO TÉCNICA Nº {$this->oDados->sequencial_analise}/{$oDataAnalise->getAno()}-DCI/CGM </b> </td>
		                          </tr>
		                          <tr><td>&nbsp;</td></tr>
		                          <tr>
		                            <td colspan='2' align='justify'>{$this->oDados->parecer}</td>
		                          </tr>";
		       
		$this->html .= "    </td>
		                  </tr>
		                </table>";
		
	}
	
	/**
	 * Escreve despacho, local, data e assinaturas.
	 *
	 * @param stdClass $oDados        	
	 */
	private function escreverRodape() {
		
		$oDataAnalise = new DBDate ( $this->oDados->data_analise );
		$oInstituicao = InstituicaoRepository::getInstituicaoPrefeitura ();
		$sCidade = mb_convert_case ( $oInstituicao->getMunicipio (), MB_CASE_TITLE );
		
		$this->html .= "<br><table width='750'>
				          <tr>
				            <td>";
				
		/* assinaturas */
		$this->html .= "      <table width='100%'>
				                <tr>";
				
				if ($this->oDados->nome_chefe_atual != "") {
				  $this->html .= "<td align='center' width=50%>
				  		            __________________________________ <br>
				  		            {$this->oDados->nome_chefe_atual} <br>
				  		            {$this->oDados->cargo_chefe_atual}
				  		          </td>";
		        }
		
		        if ($this->oDados->nome_analista != "") {
		          $this->html .= "<td align='center' width=50%>
		                            __________________________________ <br>
		                            {$this->oDados->nome_analista} <br>
		                            {$this->oDados->cargo_analista}
		                          </td>";
		        }
		$this->html .= "        </tr>
				              </table>
				            </td>
				          </tr>
				          <tr><td>&nbsp;</td></tr>
				          <tr><td>&nbsp;</td></tr>
				          <tr>
				            <td>";
		
		/*despacho*/
		$this->html .= "      <table>
				                <tr>
				                  <td align='center'><b>DESPACHO</b></td>
				                </tr>
				                <tr>
				                  <td>De acordo com a informação acima, encaminhamos o processo ao órgão de origem para providências.</td>
				                </tr>
				                <tr><td>&nbsp;</td></tr>
				                <tr><td>&nbsp;</td></tr>
				                <tr>
				                 <td align='center'>{$sCidade}, {$oDataAnalise->dataPorExtenso()} </td>
		                        </tr>
				              </table>
				            </td>
				          </tr>"; 
		
		if ($this->oDados->nome_diretor_atual != "") {
			
			$this->html .= "<tr><td>&nbsp;</td></tr>
			                <tr><td>&nbsp;</td></tr>
			                <table width='100%'>
					          <tr>
					            <td width='50%'></td>
					            <td align='center' width='50%'>
					               __________________________________ <br>
					               {$this->oDados->nome_diretor_atual} <br>
			                       {$this->oDados->cargo_diretor_atual}
					            </td>
					          </tr>
					        </table>";
		}
					
		$this->html .= "    </td>
				          </tr>
				        </table>";
	}
	
	/**
	 * Emite o documento.
	 */
	public function emitir() {
		
		$this->escreverCabecalho();
		$this->escreverConteudo();
		$this->escreverRodape();
		
		echo "{$this->html}";
	}
}
