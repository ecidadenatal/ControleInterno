create table empenhonotacontroleinternohistorico (
  sequencial serial not null primary key,
  empenhonotacontroleinterno integer,
  ressalva text,
  usuario integer not null,
  situacao integer not null,
  data date not null,
  hora varchar(5) not null,
  constraint empenhonotacontroleinterno_sequencial_fk foreign key (empenhonotacontroleinterno) references empenhonotacontroleinterno
);

create index empenhonotacontroleinternohistorico_empenhonotacontroleinterno_in on empenhonotacontroleinternohistorico(empenhonotacontroleinterno);

alter table empenhonotacontroleinterno drop column resalvaanalista,
                                       drop column resalvadiretor,
                                       drop column usuariodiretor,
                                       drop column usuarioanalista,
                                       drop column liberado,
                                       alter column situacao set not null;