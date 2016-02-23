create table plugins.liquidacaocompetencia (sequencial integer, 
                                            pagordem integer,
                                            mes integer,
                                            ano integer);

create sequence plugins.liquidacaocompetencia_sequencial_seq;                                              

create table plugins.empenhonotacontroleinternohistoricousuario (sequencial integer, empenhonotacontroleinternohistorico integer, cgm_chefe integer, cgm_diretor integer);

CREATE SEQUENCE plugins.empenhonotacontroleinternohistoricousuario_sequencial_seq INCREMENT 1 MINVALUE 1 MAXVALUE 9223372036854775807 START 1 CACHE 1;
