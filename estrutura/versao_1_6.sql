create table plugins.controleinternocredor (
sequencial integer,
numcgm_credor integer,
parecer text,
usuario_analise integer,
data_analise date,
situacao_analise integer,
usuario_diretor_atual integer,
usuario_chefe_atual integer,
usuario_aprovacao integer,
data_aprovacao date,
situacao_aprovacao integer
);

CREATE SEQUENCE plugins.controleinternocredor_sequencial_seq
INCREMENT 1
MINVALUE 1
MAXVALUE 9223372036854775807
START 1
CACHE 1;

create table plugins.controleinternocredor_empenhonotacontroleinterno (
sequencial integer,
controleinternocredor integer,
empenhonotacontroleinterno integer
);

CREATE SEQUENCE plugins.controleinternocredor_empenhonotacontroleinterno_sequencial_seq
INCREMENT 1
MINVALUE 1
MAXVALUE 9223372036854775807
START 1
CACHE 1;

create table plugins.controleinternosituacoes (
sequencial integer,
descricao text,
tipo text 
);

insert into plugins.controleinternosituacoes select 1, 'Aguardando Análise', null;
insert into plugins.controleinternosituacoes select 2, 'Diligência', 'Analista';
insert into plugins.controleinternosituacoes select 3, 'Regular', 'Analista';
insert into plugins.controleinternosituacoes select 4, 'Rejeitada pelo Diretor', 'Diretor';
insert into plugins.controleinternosituacoes select 5, 'Aprovada pelo Diretor', 'Diretor';
insert into plugins.controleinternosituacoes select 6, 'Liberação Automática', null;
insert into plugins.controleinternosituacoes select 7, 'Ressalva', 'Analista';
insert into plugins.controleinternosituacoes select 8, 'Irregular', 'Analista';