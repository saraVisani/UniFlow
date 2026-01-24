#studenti = [0,2,4,6,11,12,14,15,16,17,18,19,21,23,24,26,27,29,30,31,32,33,34,36,37,39,40,42,43,45,48,50,51,52,53,54,56,57,59,61,63,64,66,68,69,70,71,72,74]
studenti = [125,126,127,128,129,130,131,132,133,134,135,136,137,138,139,140,141,142,143,144,145,146,147,148,149,150,151,152,153,154,155,156,157,158,159,160,161,162,163,164,165,166,167,168,169,170,171,172,173,174,175,176,177,178,179,180,181,182,183,184,185,186,187,188,189,190,191,192,193,194,195,196,197,198,199,200,201,202,203,204,205,206,207,208,209,210,211,212,213,214,215,216,217,218,219,220,221,222,223,224]
anni = [2020, 2021, 2022, 2023, 2024, 2025, 2026]
corsi = [("ECO01","Economia Aziendale","Studio dell'organizzazione e gestione delle imprese","null","Economia e Management"),
("ECO02","Economia del Turismo","Formazione economica per il settore turistico","null","Economia e Management"),
("ECO03","Economia e Finanza","Analisi dei mercati finanziari e degli strumenti di investimento","#1E90FF","Economia e Management"),
("TUR01","Scienze del Turismo","Gestione e valorizzazione dei sistemi turistici","null","Economia e Management"),
("ING01","Ingegneria Gestionale","Ottimizzazione dei processi aziendali e industriali","null","Ingegneria e Architettura"),
("ING02","Ingegneria Meccanica","Progettazione e analisi di sistemi meccanici","null","Ingegneria e Architettura"),
("ING_INF01","Ingegneria e Scienze Informatiche","Corso triennale di ingegneria e informatica, sviluppo software e sistemi intelligenti","#00FF00","Ingegneria e Architettura"),
("ING_INF02","Informatica","Programmazione, algoritmi e sistemi informatici","#00FF00","Ingegneria e Architettura"),
("ING_INF03","Data Science","Analisi dati, statistica e machine learning","#32CD32","Ingegneria e Architettura"),
("ROB01","Robotica","Progettazione e programmazione di sistemi robotici","#FF69B4","Ingegneria e Architettura"),
("MAT01","Matematica","Studio delle strutture matematiche e dei modelli teorici","null","Scienze"),
("FIS01","Fisica","Studio delle leggi fondamentali della natura","null","Scienze"),
("CHI01","Chimica","Studio della materia e delle sue trasformazioni","null","Scienze"),
("BIO01","Biologia","Studio dei sistemi biologici","null","Scienze"),
("STA01","Statistica","Analisi dei dati e modelli statistici","#FFD700","Scienze"),
("ANA01","Analisi Matematica","Studio dei fondamenti dell'analisi reale e complessa","null","Scienze"),
("MED01","Medicina e Chirurgia","Formazione del medico e delle professioni sanitarie","null","Medicina e Chirurgia"),
("INF01","Infermieristica","Assistenza infermieristica e sanitaria","null","Medicina e Chirurgia"),
("FAR01","Farmacia","Studio del farmaco e della sua applicazione clinica","null","Farmacia e Biotecnologie"),
("BIOFAR01","Biotecnologie Farmaceutiche","Tecnologie per la ricerca e sviluppo di farmaci","#FF4500","Farmacia e Biotecnologie"),
("GIU01","Giurisprudenza","Formazione giuridica completa per le professioni legali","null","Giurisprudenza"),
("DIR01","Scienze dei Servizi Giuridici","Studio del diritto applicato ai servizi","null","Giurisprudenza"),
("POL01","Scienze Politiche","Analisi dei sistemi politici e istituzionali","null","Scienze Politiche"),
("SOC01","Sociologia","Studio delle dinamiche sociali","null","Sociologia"),
("COM01","Scienze della Comunicazione","Teorie e tecniche della comunicazione","null","Sociologia"),
("PSI01","Psicologia","Studio del comportamento e dei processi cognitivi","#FF69B4","Psicologia"),
("LET01","Lettere Moderne","Studio della letteratura e della lingua italiana","null","Studi Umanistici"),
("STO01","Storia","Analisi storica dall'antichità all'età contemporanea","null","Studi Umanistici"),
("FIL01","Filosofia","Studio del pensiero filosofico","null","Studi Umanistici"),
("LIN01","Lingue e Letterature Straniere","Studio delle lingue e culture straniere","null","Lingue e Letterature, Traduzione e Interpretazione"),
("ARC01","Architettura","Progettazione architettonica e urbana","null","Ingegneria e Architettura"),
("DES01","Design Industriale","Progettazione di prodotti e servizi","null","Ingegneria e Architettura"),
("URB01","Urbanistica","Pianificazione e gestione dello spazio urbano","#00CED1","Ingegneria e Architettura"),
("ARC_CES01", "Architettura e Progettazione Edile", "Corso base di Architettura con focus su progettazione e costruzioni", "null", "Ingegneria e Architettura"),
("ARC_CES02", "Ingegneria Civile e Ambientale", "Studio delle strutture civili e gestione delle infrastrutture", "null", "Ingegneria e Architettura"),
("DES_CES01", "Design Industriale e Innovazione", "Progettazione di prodotti industriali e innovazione tecnologica", "null", "Ingegneria e Architettura"),
("URB_CES01", "Urbanistica e Pianificazione Territoriale", "Analisi e pianificazione dello spazio urbano", "null", "Ingegneria e Architettura"),
("TEC_CES01", "Tecnologie per l'Architettura", "Laboratori e applicazioni di nuove tecnologie nel design architettonico", "#00cc99", "Ingegneria e Architettura")]

import random

codice_corrente = 48

def codice():
    global codice_corrente
    val = codice_corrente
    codice_corrente += 1
    return val

for studente in studenti:
    anno = random.choice(anni)
    cod_corso, resto = random.choice(corsi)[:2]
    print(f"insert into Piano_Didattico values ({codice()}, {anno}, \"{cod_corso}\", {studente});")
