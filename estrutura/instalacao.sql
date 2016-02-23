CREATE SEQUENCE empenhonotacontroleinterno_sequencial_seq
INCREMENT 1
MINVALUE 1
MAXVALUE 9223372036854775807
START 1
CACHE 1;

create table empenhonotacontroleinterno (
  sequencial integer not null primary key,
  nota integer not null,
  resalvaanalista text,
  resalvadiretor text,
  usuariodiretor integer,
  usuarioanalista integer,
  situacao integer,
  liberado boolean default false
);

CREATE  INDEX empenhonotacontroleinterno_nota_in ON empenhonotacontroleinterno(nota);