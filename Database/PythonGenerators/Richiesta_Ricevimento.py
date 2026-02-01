create table Richiesta_Ricevimento (
     Codice numeric(10) not null,
     Inserimento TINYINT(1) not null,
     Matricola numeric(10) not null,
     Codice_Ric numeric(10),
     N_Slot numeric(3),
     Ricevimento numeric(10) not null,
     constraint ID_Richiesta_Ricevimento_ID primary key (Codice));

