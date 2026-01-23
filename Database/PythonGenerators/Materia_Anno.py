materie = [(1001, "Microeconomia", 0),
(1002, "Macroeconomia", 0),
(1003, "Gestione Aziendale", 0),
(1004, "Turismo Sostenibile", 0),
(1005, "Marketing Turistico", 0),
(1006, "Finanza Aziendale", 1),
(1007, "Microeconomia", 2),
(1008, "Analisi Matematica I", 2),
(1009, "Fisica I", 2),
(1010, "Chimica", 2),
(1011, "Biologia", 2),
(1012, "Ottimizzazione dei Processi", 3),
(1013, "Gestione Industriale", 3),
(1014, "Progettazione Meccanica", 3),
(1015, "Analisi Matematica I", 4),
(1016, "Fisica I", 4),
(1017, "Programmazione Base", 5),
(1018, "Ottimizzazione dei Processi", 5),
(1019, "Gestione Industriale", 5),
(1020, "Progettazione Meccanica", 5),
(1021, "Programmazione Avanzata", 5),
(1022, "Basi di Dati", 5),
(1023, "Machine Learning", 5),
(1024, "Analisi dei Dati", 5),
(1025, "Robotica Industriale", 5),
(1026, "Architettura", 5),
(1027, "Design dei Prodotti", 5),
(1028, "Pianificazione Urbana", 5),
(1029, "Programmazione Base", 6),
(1030, "Infermieristica", 6),
(1031, "Medicina e Chirurgia", 6),
(1032, "Architettura", 7),
(1033, "Programmazione Base", 7),
(1034, "Algoritmi e Strutture Dati", 7),
(1035, "Sistemi Intelligenti", 7),
(1036, "Progettazione Edile", 7),
(1037, "Strutture Civili", 7),
(1038, "Innovazione Industriale", 7),
(1039, "Pianificazione Territoriale", 7),
(1040, "Tecnologie per l'Architettura", 7),
(1041, "Microeconomia", 8),
(1042, "Macroeconomia", 8),
(1043, "Gestione Aziendale", 8),
(1044, "Turismo Sostenibile", 8),
(1045, "Marketing Turistico", 8),
(1046, "Analisi Matematica I", 9),
(1047, "Microeconomia", 9),
(1048, "Macroeconomia", 9),
(1049, "Gestione Aziendale", 9),
(1050, "Turismo Sostenibile", 9),
(1051, "Marketing Turistico", 9),
(1052, "Architettura", 11),
(1053, "Urbanistica", 11),
(1054, "Analisi Matematica I", 12),
(1055, "Turismo Sostenibile", 12),
(1056, "Marketing Turistico", 12),
(1057, "Pianificazione Urbana", 12),
(1058, "Analisi Matematica I", 13),
(1059, "Fisica I", 13),
(1060, "Analisi Matematica I", 14),
(1061, "Fisica I", 14),
(1062, "Scienze Politiche", 15),
(1063, "Medicina e Chirurgia", 15),
(1064, "Scienze Politiche", 16),
(1065, "Sociologia", 16),
(1066, "Scienze della Comunicazione", 16)]

anni = [2020, 2021, 2022, 2023, 2024, 2025, 2026]

codice_corrente = 0

def codice():
    global codice_corrente
    val = codice_corrente
    codice_corrente += 1
    return val

def materie_anno():
    for codice_materia, nome_materia, uni in materie:
        for anno in anni:
            print(f'insert into Materia_Anno values ({codice()}, {codice_materia}, {anno});')

materie_anno()