<?php

class DatabaseHelper
{
    private $db;

    public function __construct($servername, $username, $password, $dbname, $port)
    {
        $this->db = new mysqli($servername, $username, $password, $dbname, $port);
        if ($this->db->connect_error) {
            die("Connection failed");
        }
    }

    // Funzione per ottenere la matricola reale a partire da email o matricola
    function resolveUserId($idUtente)
    {
        // Se è già numerico, lo consideriamo matricola
        if (is_numeric($idUtente)) {
            return (int)$idUtente;
        }

        // Altrimenti consideriamo che sia l'email universitaria
        $stmt = $this->db->prepare("SELECT Matricola FROM Sistema_Universitario WHERE Email_Uni = ?");
        $stmt->bind_param("s", $idUtente);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        return $row ? (int)$row['Matricola'] : null; // ritorna null se non trovato
    }

    private function buildDateRangeWhere(string $range, $date, string $field = "data"): string
    {
        switch ($range) {
            case "day":
                return "$field >= '$date 00:00:00'
                        AND $field <= '$date 23:59:59'";

            case "week":
                return "$field >= '$date'
                        AND $field < DATE_ADD('$date', INTERVAL 7 DAY)";

            case "month":
                return "$field >= '$date'
                        AND $field < DATE_ADD('$date', INTERVAL 1 MONTH)";

            default:
                return "$field >= CURDATE()
                        AND $field < DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
        }
    }

    private function baseReunionQuery(): string
    {
        return "
            SELECT
                R.Data_Inizio,
                R.Data_Fine,
                R.Online,
                P.Nome        AS nome_prof,
                P.Cognome     AS cognome_prof,
                R.Codice_Stanza AS codice_ufficio,
                L.Nome          AS nome_ufficio,
                Sd.Nome         AS nome_sede
            FROM Ricevimento R
            JOIN Sistema_Universitario SU
                ON SU.Matricola = R.Matricola
            JOIN Persona P
                ON P.CF = SU.CF
            LEFT JOIN Universitario UN
                ON UN.Codice_Uni = R.Codice_Uni
                AND UN.Codice = R.Codice_Stanza
            LEFT JOIN Luogo L
                ON L.Codice = UN.Cod_Luogo
            LEFT JOIN Sede Sd
                ON Sd.Codice = L.Codice
        ";
    }

    function getCFfromMat($mt){
        $query = "
            SELECT CF
            FROM Sistema_Universitario
            WHERE Matricola = ?
            LIMIT 1
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $mt);
        $stmt->execute();

        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        $stmt->close();

        return $row ? $row["CF"] : null;
    }

    public function usernameOk($username)
    {
        // ottieni la matricola (da email o direttamente)
        $matricola = $this->resolveUserId($username);

        // se resolveUserId ha dato null, l'utente non esiste
        if ($matricola === null) {
            return false;
        }

        // controllo esplicito che la matricola esista nel DB
        $stmt = $this->db->prepare("SELECT Matricola FROM Sistema_Universitario WHERE Matricola = ?");
        $stmt->bind_param("i", $matricola);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        return !empty($row); // true se esiste
    }

    public function passwordOk($username, $password){
        $mt = $this->resolveUserId($username);
        if ($mt === null ) {
            return false;
        }

        $query = "SELECT Password
                    FROM Sistema_Universitario
                    WHERE Matricola = ? AND Password = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("is", $mt, $password);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        return !empty($row);
    }

    public function accessLevelOk($username, $level){
        $mt = $this->resolveUserId($username);
        if ($mt === null) {
            return false;
        }

        $query = "SELECT Livello_Permesso
                    FROM Sistema_Universitario, Persona
                    WHERE Matricola = ?
                    AND Persona.Livello_Permesso >= ?
                    AND Sistema_Universitario.CF = Persona.CF";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ii", $mt, $level);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        return !empty($row);
    }

    public function getLevelAccess($username){
        $mt = $this->resolveUserId($username);
        if ($mt === null) {
            return false;
        }

        $query = "SELECT Persona.Livello_Permesso
                FROM Sistema_Universitario
                JOIN Persona ON Sistema_Universitario.CF = Persona.CF
                WHERE Matricola = ?";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $mt);
        $stmt->execute();

        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        return $row ? (int)$row['Livello_Permesso'] : false;
    }

    public function getUserJob($username){
        $mt = $this->resolveUserId($username);
        if ($mt === null) {
            return false;
        }

        // Studente
        $stmt = $this->db->prepare("SELECT Matricola FROM Studente WHERE Matricola = ?");
        $stmt->bind_param("i", $mt);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $stmt->close();
            return 'studente';
        }
        $stmt->close();

        // Professore
        $stmt = $this->db->prepare("SELECT Matricola FROM Professore WHERE Matricola = ?");
        $stmt->bind_param("i", $mt);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $stmt->close();
            return 'professore';
        }
        $stmt->close();

        // Segreteria
        $stmt = $this->db->prepare("SELECT Matricola FROM Segreteria WHERE Matricola = ?");
        $stmt->bind_param("i", $mt);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $stmt->close();
            return 'segreteria';
        }
        $stmt->close();

        return false; // non trovato
    }

    public function getMostRecentPublicEvents($number = 3)
    {
        $sql = "SELECT Nome AS titolo, Inizio AS data, Descrizione AS descrizione
                FROM evento
                WHERE pubblico = 1
                ORDER BY Inizio DESC
                LIMIT ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $number);
        $stmt->execute();
        $result = $stmt->get_result();
        $events = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $events;
    }

    public function getMostPopularFAQsByLevel($number = 3, $Forum = 2, $Grado = 0)
    {
        $sql = "SELECT t.Titolo AS domanda
                FROM Thread t
                INNER JOIN Canale c
                    ON t.Cod_Forum = c.Cod_Forum AND t.Cod_Canale = c.Codice
                WHERE t.Cod_Forum = ? AND c.Grado = ?
                ORDER BY t.Likes DESC
                LIMIT ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("iii", $Forum, $Grado, $number);
        $stmt->execute();
        $result = $stmt->get_result();
        $faqs = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $faqs;
    }

    public function getAllCampuses()
    {
        $sql = "SELECT sede.Nome as nome, sede.Codice_Prov, sede.Codice_Citta, sede.N_Civico, indirizzo.Via, indirizzo.Nome as nome_indirizzo, sede.descrizione, sede.`Path`, sede.Descrizione_Img
                FROM sede, indirizzo
                WHERE sede.Codice_Prov = indirizzo.Codice_Prov
                    AND sede.Codice_Citta = indirizzo.Codice_Citta
                    AND sede.N_Civico = indirizzo.N_Civico";
        $result = $this->db->query($sql);
        $campuses = $result->fetch_all(MYSQLI_ASSOC);
        return $campuses;
    }

    public function getTimesStudent($idUtente, $range, $date)
    {
        $cf = $this->resolveUserId($idUtente);
        if ($cf === null) {
            return []; // nessun evento se utente non trovato
        }
        $when = $this->buildDateRangeWhere($range, $date, "O.Orario_inizio");

        $sql = "SELECT
                O.Orario_inizio,
                O.Orario_fine,

                M.Nome              AS nome_materia,
                MO.Descrizione      AS nome_modulo,

                MO.Matricola_Tit    AS prof_titolare,

                C.Codice_Stanza     AS codice_aula,
                L.Nome              AS nome_aula,
                C.Lab               AS laboratorio,

                S.Nome              AS nome_sede
            FROM Orario O

            -- Filtra lezioni dello studente
            JOIN Composto_Da CD
                ON CD.Cod_Mat_Anno = O.Cod_Mat_Anno
            JOIN Piano_Didattico PD
                ON PD.Codice_PianoDid = CD.Codice_PianoDid
                AND PD.Matricola = ?

            -- Modulo / Materia
            JOIN Modulo MO
                ON MO.Codice_Corso = O.Codice_Corso
                AND MO.Cod_Mat_Anno = O.Cod_Mat_Anno
                AND MO.Codice = O.Codice_Modulo

            JOIN Materia_Anno MA
                ON MA.Cod_Mat_Anno = MO.Cod_Mat_Anno
            JOIN Materia M
                ON M.Codice = MA.Codice_Mat

            -- Aula / sede
            JOIN Classe C
                ON C.Codice_Uni = O.Codice_Uni
                AND C.Codice_Stanza = O.Codice_Stanza

            JOIN Universitario U
                ON U.Codice_Uni = C.Codice_Uni
                AND U.Codice = C.Codice_Stanza

            JOIN Luogo L
                ON L.Codice = U.Cod_Luogo

            JOIN Sede S
                ON S.Codice = U.Codice_Uni

            WHERE $when
            ORDER BY O.Orario_inizio ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $cf);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $row;
    }

    public function getReunionStudent($idUtente, $range, $date)
    {
        $matricolaStud = $this->resolveUserId($idUtente);
        if ($matricolaStud === null ) {
            return [];
        }

        $when = $this->buildDateRangeWhere($range, $date, "R.Data_Inizio");

        $sql = $this->baseReunionQuery() . "
            JOIN Slot S
                ON S.Codice_Ric = R.Codice
            WHERE S.Matricola = ?
            AND $when
            ORDER BY R.Data_Inizio ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $matricolaStud);
        $stmt->execute();

        $result = $stmt->get_result();
        $rows = $result->fetch_all(MYSQLI_ASSOC);

        $stmt->close();
        return $rows;
    }


    public function getTimesProfessor($idUtente, $range, $date)
    {
        $cf = $this->resolveUserId($idUtente);
        if ($cf === null) {
            return []; // nessun evento se utente non trovato
        }
        $when = $this->buildDateRangeWhere($range, $date,"O.Orario_inizio");

        $query = "SELECT
                O.Orario_inizio,
                O.Orario_fine,

                M.Nome              AS nome_materia,
                MO.Descrizione      AS nome_modulo,

                MO.Matricola_Tit    AS prof_titolare,

                C.Codice_Stanza     AS codice_aula,
                L.Nome              AS nome_aula,
                C.Lab               AS laboratorio,

                S.Nome              AS nome_sede
            FROM Orario O

            -- filtra lezioni del professore
            JOIN Insegna I
                ON I.Cod_Mat_Anno = O.Cod_Mat_Anno
                AND I.Matricola = ?

            -- Modulo / Materia
            JOIN Modulo MO
                ON MO.Codice_Corso = O.Codice_Corso
                AND MO.Cod_Mat_Anno = O.Cod_Mat_Anno
                AND MO.Codice = O.Codice_Modulo

            JOIN Materia_Anno MA
                ON MA.Cod_Mat_Anno = MO.Cod_Mat_Anno
            JOIN Materia M
                ON M.Codice = MA.Codice_Mat

            -- Aula / sede
            JOIN Classe C
                ON C.Codice_Uni = O.Codice_Uni
                AND C.Codice_Stanza = O.Codice_Stanza

            JOIN Universitario U
                ON U.Codice_Uni = C.Codice_Uni
                AND U.Codice = C.Codice_Stanza

            JOIN Luogo L
                ON L.Codice = U.Cod_Luogo

            JOIN Sede S
                ON S.Codice = U.Codice_Uni

            WHERE $when
            ORDER BY O.Orario_inizio ASC
        ";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $cf);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $row;
    }
    public function getReunionProfessor($idUtente, $range, $date)
    {
        $matricolaProf = $this->resolveUserId($idUtente);
        if ($matricolaProf === null) {
            return [];
        }

        $when = $this->buildDateRangeWhere($range, $date, "R.Data_Inizio");

        $sql = $this->baseReunionQuery() . "
            WHERE R.Matricola = ?
            AND $when
            ORDER BY R.Data_Inizio ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $matricolaProf);
        $stmt->execute();

        $result = $stmt->get_result();
        $rows = $result->fetch_all(MYSQLI_ASSOC);

        $stmt->close();
        return $rows;
    }

    public function getSignInChannals($level)
    {
        $sql = "
            SELECT
                C.Cod_Forum,
                C.Codice,
                C.Nome AS nome_canale,
                C.Grado,
                C.Visualizzare,
                C.Visualizzare_Tutti,
                F.Nome AS nome_forum
            FROM Canale C
            JOIN Forum F
                ON F.Codice = C.Cod_Forum
            WHERE
                C.Grado <= ?  -- livello base
                OR (C.Grado = ? + 1 AND C.Visualizzare = 1)
                OR (C.Visualizzare_Tutti = 1)
            ORDER BY F.Nome, C.Codice
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ii", $level, $level);
        $stmt->execute();

        $result = $stmt->get_result();
        $rows = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // raggruppa per forum
        $grouped = [];
        foreach ($rows as $row) {
            $forumId = $row['Cod_Forum'];
            if (!isset($grouped[$forumId])) {
                $grouped[$forumId] = [
                    'Cod_Forum' => $forumId,
                    'nome_forum' => $row['nome_forum'],
                    'canali' => []
                ];
            }
            $grouped[$forumId]['canali'][] = [
                'Codice' => $row['Codice'],
                'nome_canale' => $row['nome_canale'],
                'Grado' => $row['Grado'],
                'Visualizzare' => $row['Visualizzare'],
                'Visualizzare_Tutti' => $row['Visualizzare_Tutti']
            ];
        }

        // ritorna array numerico invece che associativo
        return array_values($grouped);
    }

    public function getStaffEvents($idUtente, $range, $date)
    {
        $mt = $this->resolveUserId($idUtente);
        if ($mt === null) return [];
        $cf = $this->getCFfromMat($mt);

        $when = $this->buildDateRangeWhere($range, $date, "O.Inizio");

        $sql = "
            SELECT DISTINCT
                E.Codice,
                E.Nome,
                O.Inizio      AS orario_inizio,
                O.Fine        AS orario_fine,
                L.Nome        AS nome_luogo,
                S.Nome        AS nome_sede,

                -- ruolo dinamico
                CASE
                    WHEN E.CF = ? THEN 'promotore'
                    WHEN C.CF IS NOT NULL THEN 'collaboratore'
                    WHEN R.CF IS NOT NULL THEN 'rappresentante'
                END AS ruolo
            FROM Evento E
            JOIN Orario_Evento O
                ON O.Codice_Evento = E.Codice

            -- collaboratore
            LEFT JOIN Collaboratore C
                ON C.Codice_Evento = E.Codice
                AND C.CF = ?

            -- rappresentante di promotore
            LEFT JOIN Propongono P
                ON P.Codice_Evento = E.Codice
            LEFT JOIN Rappresentano R
                ON R.Codice_Promotore = P.Codice
                AND R.CF = ?

            -- luogo / sede
            LEFT JOIN Luogo L
                ON L.Codice = O.Cod_Luogo
            LEFT JOIN Sede S
                ON S.Codice = L.Codice

            WHERE (
                E.CF = ? OR C.CF IS NOT NULL OR R.CF IS NOT NULL
            )
            AND $when
            ORDER BY O.Inizio ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ssss", $cf, $cf, $cf, $cf);
        $stmt->execute();

        $result = $stmt->get_result();
        $eventi = $result->fetch_all(MYSQLI_ASSOC);

        $stmt->close();
        return $eventi;
    }

    public function getSignInEvents($idUtente, $range, $date)
    {
        $mt = $this->resolveUserId($idUtente);
        if ($mt === null) return [];
        $cf = $this->getCFfromMat($mt);

        $when = $this->buildDateRangeWhere($range, $date, "O.Inizio");

        $sql = "
            SELECT
                E.Codice,
                E.Nome,
                O.Inizio      AS orario_inizio,
                O.Fine        AS orario_fine,
                L.Nome        AS nome_luogo,
                S.Nome        AS nome_sede,
                'partecipante' AS ruolo
            FROM Evento E
            JOIN Segna Sg
                ON Sg.Codice_Evento = E.Codice
                AND Sg.CF = ?
            JOIN Orario_Evento O
                ON O.Codice_Evento = E.Codice
            LEFT JOIN Luogo L
                ON L.Codice = O.Cod_Luogo
            LEFT JOIN Universitario U
                ON U.Cod_Luogo = L.Codice
            LEFT JOIN Sede S
                ON S.Codice = U.Codice_Uni
            WHERE $when
            ORDER BY O.Inizio ASC;
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $cf);
        $stmt->execute();

        $result = $stmt->get_result();
        $eventi = $result->fetch_all(MYSQLI_ASSOC);

        $stmt->close();
        return $eventi;
    }

    public function getOpenNotifications($idUtente)
    {
        $matricola = $this->resolveUserId($idUtente);
        if ($matricola === null) {
            return []; // nessuna notifica se utente non trovato
        }

        $stmt = $this->db->prepare("SELECT Codice as codice, Descrizione as descrizione, Chiusa as chiusa
            FROM Notifica
            WHERE Matricola = ?
            AND Notifica.Chiusa = 0
            ORDER BY Codice ASC");

        $stmt->bind_param("i", $matricola);
        $stmt->execute();
        $result = $stmt->get_result();
        $notifiche = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $notifiche;
    }
    public function getAllNotifications($idUtente)
    {
        $matricola = $this->resolveUserId($idUtente);
        if ($matricola === null) {
            return []; // nessuna notifica se utente non trovato
        }

        $stmt = $this->db->prepare("SELECT Codice as codice, Descrizione as descrizione, Chiusa as chiusa
            FROM Notifica
            WHERE Matricola = ?
            ORDER BY Codice ASC");

        $stmt->bind_param("i", $matricola);
        $stmt->execute();
        $result = $stmt->get_result();
        $notifiche = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $notifiche;
    }

    public function closeNotification($cod){
        $sql = "UPDATE Notifica
                SET Chiusa = 1
                WHERE Codice = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $cod);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result;
    }

    public function freeClassrooms($idUtente){
        $matricola = $this->resolveUserId($idUtente);
        if ($matricola === null) {
            return []; // nessuna notifica se utente non trovato
        }

        // Data/ora corrente per "in quel momento"
        $now = date('Y-m-d H:i:s');

        $sql = "SELECT DISTINCT
                c.Codice_Stanza,
                l.Nome,
                c.Lab
            FROM Classe c
            JOIN Universitario u ON c.Codice_Uni = u.Codice_Uni
            JOIN Luogo l ON u.Cod_Luogo = l.Codice
            JOIN Segreteria s ON s.Codice_Uni = c.Codice_Uni
            WHERE s.Matricola = ?
                -- Aula della segreteria
                AND NOT EXISTS (
                    -- Occupata da lezioni?
                    SELECT 1 FROM Orario o
                    WHERE o.Codice_Uni = c.Codice_Uni
                        AND o.Codice_Stanza = c.Codice_Stanza
                        AND o.Orario_inizio <= ?
                        AND o.Orario_fine > ?
                )
                AND NOT EXISTS (
                    -- Occupata da eventi?
                    SELECT 1 FROM Orario_Evento oe
                    JOIN Universitario ue ON oe.Cod_Luogo = ue.Cod_Luogo
                    WHERE ue.Codice_Uni = c.Codice_Uni
                        AND c.Codice_Stanza = (
                            SELECT cc.Codice_Stanza
                            FROM Classe cc
                            WHERE cc.Codice_Uni = ue.Codice_Uni
                                AND cc.Codice_Stanza = ue.Codice
                            LIMIT 1
                        )
                        AND oe.Inizio <= ?
                        AND oe.Fine > ?
                )
            ORDER BY l.Nome
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("issss", $idUtente, $now, $now, $now, $now);
        $stmt->execute();
        $result = $stmt->get_result();
        $classi = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $classi;
    }

    public function getCampusById($id){
        $query = "SELECT sede.Nome as nome, Provincia.Nome as provincia, Citta.Nome as citta, sede.N_Civico, indirizzo.Via, indirizzo.Nome as nome_indirizzo, sede.Descrizione as descrizione
                FROM sede
                JOIN indirizzo ON sede.Codice_Prov = indirizzo.Codice_Prov
                    AND sede.Codice_Citta = indirizzo.Codice_Citta
                    AND sede.N_Civico = indirizzo.N_Civico
                JOIN Provincia ON sede.Codice_Prov = Provincia.Codice
                JOIN Citta ON sede.Codice_Citta = Citta.Codice
                WHERE sede.Codice = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $sede = $result->fetch_assoc();
        $stmt->close();
        return $sede;
    }

    public function getSpacesByCampus($id) {
        $sql = "SELECT
                l.Nome AS nome,
                l.Capienza AS capienza,
                u.Codice AS codice_stanza,
                CASE
                    WHEN c.Codice_Stanza IS NOT NULL THEN 'CLASSE'
                    WHEN o.Codice_Stanza IS NOT NULL THEN 'UFFICIO'
                    ELSE 'UNIVERSITARIO'
                END AS tipo,
                c.Lab AS laboratorio,
                p.Nome AS prof_nome,
                p.Cognome AS prof_cognome
            FROM Universitario u
            JOIN Luogo l ON l.Codice = u.Cod_Luogo
            LEFT JOIN Classe c
                ON c.Codice_Uni = u.Codice_Uni
            AND c.Codice_Stanza = u.Codice
            LEFT JOIN Ufficio o
                ON o.Codice_Uni = u.Codice_Uni
            AND o.Codice_Stanza = u.Codice
            LEFT JOIN Sistema_Universitario su
                ON su.Matricola = o.Matricola
            LEFT JOIN Persona p
                ON p.CF = su.CF
            WHERE u.Codice_Uni = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $spazzi = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $spazzi;
    }

    public function getSecretariatByCampusId($id) {
        $sql = "SELECT p.Nome AS nome, p.Cognome AS cognome, su.Email_Uni AS email
            FROM Segreteria s
            JOIN Sistema_Universitario su ON s.Matricola = su.Matricola
            JOIN Persona p ON su.CF = p.CF
            WHERE s.Codice_Uni = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $segreteria = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $segreteria;
    }

    public function getProfessorsByCampusId($id) {
        $sql = "SELECT DISTINCT
                pe.Nome AS nome,
                pe.Cognome AS cognome,
                su.Email_Uni AS email
            FROM Professore pr
            JOIN Sistema_Universitario su
                ON su.Matricola = pr.Matricola
            JOIN Persona pe
                ON pe.CF = su.CF
            JOIN Insegna i
                ON i.Matricola = pr.Matricola
            JOIN Materia_Anno ma
                ON ma.Cod_Mat_Anno = i.Cod_Mat_Anno
            JOIN Materia m
                ON m.Codice = ma.Codice_Mat
            WHERE m.Codice_Uni = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $professori = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $professori;
    }

    public function getCoursesByCampusId($id) {
        $sql = "SELECT
                c.Codice AS codice,
                c.Nome AS nome,
                c.Descrizione AS descrizione,
                COALESCE(c.Colore, a.Colore) AS colore,
                a.Nome AS ambito
            FROM Seguito_In si
            JOIN Corso c
                ON c.Codice = si.Codice_Corso
            JOIN Ambito a
                ON a.Nome = c.Ambito
            WHERE si.Codice_Uni = ?
            ORDER BY c.Nome";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $corsi = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $corsi;
    }

    public function getEventsByCampusId($id) {
        $sql = "SELECT
                e.Codice AS id,
                e.Nome AS nome,
                e.Descrizione AS descrizione,
                e.Posti AS posti,
                e.Pubblico AS pubblico,
                le.Nome AS luogo,
                oe.Inizio AS inizio,
                oe.Fine AS fine
            FROM Universitario u
            JOIN Orario_Evento oe
                ON oe.Cod_Luogo = u.Cod_Luogo
            JOIN Evento e
                ON e.Codice = oe.Codice_Evento
            JOIN Luogo le
                ON le.Codice = u.Cod_Luogo
            WHERE u.Codice_Uni = ?
            AND oe.Fine > NOW()
            ORDER BY oe.Inizio ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $cal = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $cal;
    }

    public function getAmbitiForPage(){
        $sql = "SELECT
                    a.Nome        AS ambito_nome,
                    a.Colore      AS ambito_colore,
                    c.Codice      AS corso_codice,
                    c.Nome        AS corso_nome,
                    c.Descrizione AS corso_descrizione,
                    c.Colore      AS corso_colore
                FROM Ambito a
                LEFT JOIN Corso c ON c.Ambito = a.Nome
                ORDER BY a.Nome, c.Nome;";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();
        $cal = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $cal;
    }

    public function corsesForPage(){
        $sql = "SELECT
                    c.Codice AS corso_codice,
                    c.Nome AS corso_nome,
                    c.Descrizione AS corso_descrizione,
                    c.Colore AS corso_colore,

                    s.Codice AS sede_codice,
                    s.Nome AS sede_nome,
                    s.Descrizione AS sede_descrizione,

                    m.Codice AS materia_codice,
                    m.Nome AS materia_nome,

                    f.Obbligatorio,
                    f.Grado,
                    f.Periodo,
                    f.CFU
                FROM Corso c
                LEFT JOIN Seguito_In si ON si.Codice_Corso = c.Codice
                LEFT JOIN Sede s ON s.Codice = si.Codice_Uni
                LEFT JOIN Formato_Da f ON f.Codice_Corso = c.Codice
                LEFT JOIN Materia m ON m.Codice = f.Codice_Mat
                ORDER BY c.Codice;";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();
        $cal = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $cal;
    }

    public function AllCourses(){
        $sql = "SELECT c.Codice AS corso_codice, c.Nome AS corso_nome, c.Descrizione AS corso_descrizione
                FROM Corso c";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();
        $cal = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $cal;
    }

    public function getCourseByCode($code){
        $sql = "SELECT c.Nome AS corso_nome
                FROM Corso c
                WHERE c.Codice = ?
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $code);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        $stmt->close();
        return $row;
    }

    public function getLessionsByCourse($corso, $grado, $anno) {

        $sql = "SELECT
                    o.Codice AS orario_codice,
                    o.Orario_inizio,
                    o.Orario_fine,

                    m.Nome AS materia_nome,
                    ma.Anno,

                    mo.Codice AS modulo_codice,
                    mo.Descrizione AS modulo_descrizione,

                    l.Nome AS nome_stanza,
                    o.Codice_Stanza AS codice_stanza,
                    l.Capienza,
                    c.Lab,
                    s.Nome AS nome_sede
                FROM Orario o
                JOIN Materia_Anno ma
                    ON ma.Cod_Mat_Anno = o.Cod_Mat_Anno
                JOIN Materia m
                    ON m.Codice = ma.Codice_Mat
                JOIN Modulo mo
                    ON mo.Codice_Corso = o.Codice_Corso
                AND mo.Cod_Mat_Anno = o.Cod_Mat_Anno
                AND mo.Codice = o.Codice_Modulo
                JOIN Formato_Da f
                    ON f.Codice_Mat = m.Codice
                AND f.Codice_Corso = o.Codice_Corso
                JOIN Classe c
                    ON o.Codice_Uni = c.Codice_Uni
                    AND o.Codice_Stanza = c.Codice_Stanza
                JOIN Universitario u
                    ON o.Codice_Uni = u.Codice_Uni
                    AND o.Codice_Stanza = u.Codice
                JOIN Luogo l
                    ON u.Cod_Luogo  = l.Codice
                JOIN Sede s
                    ON o.Codice_Uni = s.Codice
                WHERE o.Codice_Corso = ?
                AND f.Grado = ?
                AND ma.Anno = ?
                ORDER BY o.Orario_inizio
            ";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("sii", $corso, $grado, $anno);
        $stmt->execute();

        $result = $stmt->get_result();
        $rows = $result->fetch_all(MYSQLI_ASSOC);

        $stmt->close();
        return $rows;
    }

    public function getCampusNameByCode($sede) {
        $sql = "SELECT s.Nome
                FROM Sede s
                WHERE s.Codice = ?
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $sede);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row;
    }

    public function getReunionsByCampus($sede, $date){
        $sql = "SELECT DISTINCT
                    r.Codice            AS ricevimento_codice,
                    r.Online,
                    r.Data_Inizio,
                    r.Data_Fine,
                    r.N_Slot,

                    p.Matricola,
                    pe.Nome,
                    pe.Cognome,

                    s.Nome              AS nome_sede
                FROM Ricevimento r
                JOIN Professore p
                    ON p.Matricola = r.Matricola
                JOIN Sistema_Universitario su
                    ON su.Matricola = p.Matricola
                JOIN Persona pe
                    ON pe.CF = su.CF
                LEFT JOIN Ufficio u
                    ON u.Matricola = p.Matricola
                AND u.Codice_Uni = ?
                LEFT JOIN Sede s
                    ON s.Codice = r.Codice_Uni
                WHERE r.Data_Inizio >= ?
                AND (
                        -- ricevimenti in presenza nella sede
                        (r.Online = 0 AND r.Codice_Uni = ?)
                        OR
                        -- ricevimenti online se il prof ha ufficio nella sede
                        (r.Online = 1 AND u.Codice_Uni IS NOT NULL)
                    )
                ORDER BY r.Data_Inizio";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("iss", $sede, $date, $sede);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $row;
    }

    public function getAllCampusesWithCode()
    {
        $sql = "SELECT sede.Codice as codice, sede.Nome as nome, sede.Codice_Prov, sede.Codice_Citta, sede.N_Civico, indirizzo.Via, indirizzo.Nome as nome_indirizzo, sede.descrizione
                FROM sede, indirizzo
                WHERE sede.Codice_Prov = indirizzo.Codice_Prov
                    AND sede.Codice_Citta = indirizzo.Codice_Citta
                    AND sede.N_Civico = indirizzo.N_Civico";
        $result = $this->db->query($sql);
        $campuses = $result->fetch_all(MYSQLI_ASSOC);
        return $campuses;
    }

    public function getEventsByCampusIdInYear($sede, $date){
        $sql = "SELECT
                e.Codice AS id,
                e.Nome AS nome,
                e.Descrizione AS descrizione,
                e.Posti AS posti,
                e.Pubblico AS pubblico,
                le.Nome AS luogo,
                oe.Inizio AS inizio,
                oe.Fine AS fine
            FROM Universitario u
            JOIN Orario_Evento oe
                ON oe.Cod_Luogo = u.Cod_Luogo
            JOIN Evento e
                ON e.Codice = oe.Codice_Evento
            JOIN Luogo le
                ON le.Codice = u.Cod_Luogo
            WHERE u.Codice_Uni = ?
            AND YEAR(oe.Fine) = ?
            ORDER BY oe.Inizio ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ii", $sede, $date);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $row;
    }

    public function getFreeClassroomsByCampus($sede){
        $now = date('Y-m-d H:i:s');

        $sql = "SELECT DISTINCT
                    c.Codice_Stanza,
                    l.Nome,
                    c.Lab
                FROM Classe c
                JOIN Universitario u ON c.Codice_Uni = u.Codice_Uni
                JOIN Luogo l ON u.Cod_Luogo = l.Codice
                WHERE c.Codice_Uni = ?
                    -- Aula libera da lezioni
                    AND NOT EXISTS (
                        SELECT 1 FROM Orario o
                        WHERE o.Codice_Uni = c.Codice_Uni
                            AND o.Codice_Stanza = c.Codice_Stanza
                            AND o.Orario_inizio <= ?
                            AND o.Orario_fine > ?
                    )
                    -- Aula libera da eventi
                    AND NOT EXISTS (
                        SELECT 1 FROM Orario_Evento oe
                        JOIN Universitario ue ON oe.Cod_Luogo = ue.Cod_Luogo
                        WHERE ue.Codice_Uni = c.Codice_Uni
                            AND c.Codice_Stanza = (
                                SELECT cc.Codice_Stanza
                                FROM Classe cc
                                WHERE cc.Codice_Uni = ue.Codice_Uni
                                    AND cc.Codice_Stanza = ue.Codice
                                LIMIT 1
                            )
                            AND oe.Inizio <= ?
                            AND oe.Fine > ?
                    )
                ORDER BY l.Nome
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("issss", $sede, $now, $now, $now, $now);
        $stmt->execute();
        $result = $stmt->get_result();
        $classi = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $classi;
    }

    public function getPersone(){
        $sql = "SELECT
                    p.Nome AS nome,
                    p.Cognome AS cognome,
                    p.Email AS email,
                    su.Email_Uni AS email_uni,
                    su.Matricola AS matricola,
                    p.Livello_Permesso AS livello
                FROM Persona p
                JOIN Sistema_Universitario su
                ON su.CF = p.CF

            ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();
        $persone = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $persone;
    }

    public function getEventsByPerson($idUtente, $luogo=-1, $range=-1, $date=-1) {
        $mt = $this->resolveUserId($idUtente);
        if ($mt === null) return [];
        $cf = $this->getCFfromMat($mt);

        if($luogo == -1 ) {
            $where = "";

        } else {
            $where = "AND L.Codice = ?";
        }

        if($date == -1){
            $when = $this->buildDateRangeWhere($range, date("d/m/Y"), "O.Inizio");
        } else{
            $when = $this->buildDateRangeWhere($range, $date, "O.Inizio");
        }

        $sql = "
                    SELECT DISTINCT
                        E.Codice,
                        E.Nome,
                        O.Inizio      AS orario_inizio,
                        O.Fine        AS orario_fine,
                        L.Nome        AS nome_luogo,
                        CASE
                            WHEN S.Codice IS NOT NULL
                            THEN S.Nome
                        END AS nome_sede,
                        CASE
                            WHEN S.Codice IS NOT NULL THEN
                                CONCAT(
                                    ISD.Via, ' ',
                                    ISD.Nome, ' ',
                                    S.N_Civico, ', ',
                                    CS.Nome, ' (', S.Codice_Prov, ')'
                                )
                            ELSE
                                CONCAT(
                                    IES.Via, ' ',
                                    IES.Nome, ' ',
                                    X.N_Civico, ', ',
                                    CE.Nome, ' (', X.Codice_Prov, ')'
                                )
                        END AS indirizzo,

                        -- ruolo dinamico
                        CASE
                            WHEN E.CF = ? THEN 'promotore'
                            WHEN EXISTS (
                                SELECT 1
                                FROM Collaboratore C
                                WHERE C.Codice_Evento = E.Codice
                                AND C.CF = ?
                            ) THEN 'collaboratore'
                            WHEN EXISTS (
                                SELECT 1
                                FROM Propongono P
                                JOIN Rappresentano R
                                ON R.Codice_Promotore = P.Codice
                                WHERE P.Codice_Evento = E.Codice
                                AND R.CF = ?
                            ) THEN 'rappresentante'
                            WHEN EXISTS (
                                SELECT 1
                                FROM Segna Sg
                                WHERE Sg.Codice_Evento = E.Codice
                                AND Sg.CF = ?
                            ) THEN 'iscritto'
                        END AS ruolo

                    FROM Evento E
                    JOIN Orario_Evento O
                        ON O.Codice_Evento = E.Codice

                    -- luogo / sede
                    LEFT JOIN Luogo L
                        ON L.Codice = O.Cod_Luogo

                    LEFT JOIN Universitario Uni
                        ON Uni.Cod_Luogo = L.Codice

                    LEFT JOIN Sede S
                        ON S.Codice = Uni.Codice_Uni

                    LEFT JOIN Esterno X
                        ON X.Cod_Luogo = L.Codice

                    LEFT JOIN Indirizzo ISD
                        ON ISD.Codice_Prov = S.Codice_Prov
                        AND ISD.Codice_Citta = S.Codice_Citta
                        AND ISD.N_Civico = S.N_Civico

                    LEFT JOIN Indirizzo IES
                        ON IES.Codice_Prov = X.Codice_Prov
                        AND IES.Codice_Citta = X.Codice_Citta
                        AND IES.N_Civico = X.N_Civico

                    LEFT JOIN Citta CS
                        ON CS.Codice_Prov = S.Codice_Prov
                        AND CS.Codice = S.Codice_Citta

                    LEFT JOIN Citta CE
                        ON CE.Codice_Prov = X.Codice_Prov
                        AND CE.Codice = X.Codice_Citta

                    WHERE (
                        E.CF = ?
                        OR EXISTS (
                            SELECT 1
                            FROM Collaboratore C
                            WHERE C.Codice_Evento = E.Codice
                            AND C.CF = ?
                        )
                        OR EXISTS (
                            SELECT 1
                            FROM Propongono P
                            JOIN Rappresentano R
                            ON R.Codice_Promotore = P.Codice
                            WHERE P.Codice_Evento = E.Codice
                            AND R.CF = ?
                        )
                        OR EXISTS (
                            SELECT 1
                            FROM Segna Sg
                            WHERE Sg.Codice_Evento = E.Codice
                            AND Sg.CF = ?
                        )
                    )
                    AND $when
                    $where
                    ORDER BY O.Inizio ASC
                ";

        $stmt = $this->db->prepare($sql);
        if ($luogo == -1) {
            $stmt->bind_param(
                "ssssssss",
                $cf, $cf, $cf, $cf,
                $cf, $cf, $cf, $cf
            );
        } else {
            $stmt->bind_param(
                "ssssssssi",
                $cf, $cf, $cf, $cf,
                $cf, $cf, $cf, $cf,
                $luogo
            );
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $eventi = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $eventi;
    }

    public function getAllPlaces(){
        $sql = "SELECT
                    l.Codice AS codice,
                    l.Nome AS nome,
                    l.Capienza AS capienza,
                    CASE
                        WHEN S.Codice IS NOT NULL
                        THEN S.Nome
                    END AS nome_sede,
                    CASE
                        WHEN S.Codice IS NOT NULL THEN
                            CONCAT(
                                ISD.Via, ' ',
                                ISD.Nome, ' ',
                                S.N_Civico, ', ',
                                CS.Nome, ' (', S.Codice_Prov, ')'
                            )
                        ELSE
                            CONCAT(
                                IES.Via, ' ',
                                IES.Nome, ' ',
                                X.N_Civico, ', ',
                                CE.Nome, ' (', X.Codice_Prov, ')'
                            )
                    END AS indirizzo
                FROM Luogo l
                LEFT JOIN Universitario Uni
                        ON Uni.Cod_Luogo = L.Codice

                    LEFT JOIN Sede S
                        ON S.Codice = Uni.Codice_Uni

                    LEFT JOIN Esterno X
                        ON X.Cod_Luogo = L.Codice

                    LEFT JOIN Indirizzo ISD
                        ON ISD.Codice_Prov = S.Codice_Prov
                        AND ISD.Codice_Citta = S.Codice_Citta
                        AND ISD.N_Civico = S.N_Civico

                    LEFT JOIN Indirizzo IES
                        ON IES.Codice_Prov = X.Codice_Prov
                        AND IES.Codice_Citta = X.Codice_Citta
                        AND IES.N_Civico = X.N_Civico

                    LEFT JOIN Citta CS
                        ON CS.Codice_Prov = S.Codice_Prov
                        AND CS.Codice = S.Codice_Citta

                    LEFT JOIN Citta CE
                        ON CE.Codice_Prov = X.Codice_Prov
                        AND CE.Codice = X.Codice_Citta
                ORDER BY l.Codice Desc";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();
        $places = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $places;
    }

    public function getPeoples(){
        $sql = "SELECT
                    p.CF AS codice,
                    p.Nome AS nome,
                    p.Cognome AS cognome,
                    p.Email AS email
                FROM Persona p
                ORDER BY p.Cognome, p.Nome";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();
        $peoples = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $peoples;
    }

    public function getAllEvents(){
        $sql = "SELECT
                    e.Codice AS codice,
                    e.Nome AS nome,
                    e.Descrizione AS descrizione,
                    e.Posti AS posti,
                    e.Inizio AS inizio,
                    e.Fine AS fine,
                    e.CF as id_rappresentante,
                    p.Nome AS nome_rappresentante,
                    p.Cognome AS cognome_rappresentante,
                    e.Pubblico
                FROM Evento e
                LEFT JOIN Persona p
                ON p.CF = e.CF
                ORDER BY e.Codice ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();
        $events = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $events;
    }

    public function getEventById($id){
        $sql = "SELECT
                    e.Codice AS codice,
                    e.Nome AS nome,
                    e.Descrizione AS descrizione,
                    e.Posti AS posti,
                    e.Inizio AS inizio,
                    e.Fine AS fine,
                    e.CF as id_rappresentante,
                    p.Nome AS nome_rappresentante,
                    p.Cognome AS cognome_rappresentante,
                    e.Pubblico
                FROM Evento e
                LEFT JOIN Persona p
                ON p.CF = e.CF
                WHERE e.Codice = ?
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $event = $result->fetch_assoc();
        $stmt->close();

        return $event;
    }

    public function getCollaboratorsByEvent($id){
        $sql = "SELECT
                    c.CF AS codice,
                    p.Nome AS nome,
                    p.Cognome AS cognome,
                    p.Email AS email
                FROM Collaboratore c
                JOIN Persona p ON p.CF = c.CF
                WHERE c.Codice_Evento = ?
                ORDER BY p.Cognome, p.Nome";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $collaborators = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $collaborators;
    }

    public function getPromotersComponents($id){
        $sql = "SELECT
                    r.CF AS codice,
                    p.Nome AS nome,
                    p.Cognome AS cognome,
                    p.Email AS email
                FROM Rappresentano r
                JOIN Persona p ON p.CF = r.CF
                WHERE r.Codice_Promotore = ?
                ORDER BY p.Cognome, p.Nome";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $components = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $components;
    }

    public function getPromotersByEvent($id){
        $sql = "SELECT
                    r.Codice AS codice,
                    p.Nome AS nome,
                    p.Email AS email
                FROM Propongono r
                JOIN Promotore p ON p.Codice = r.Codice
                WHERE r.Codice_Evento = ?
                ORDER BY p.Nome";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $promoters = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        foreach ($promoters as &$promoter) {
            $promoter["componenti"] =
                $this->getPromotersComponents($promoter["codice"]);
        }

        return $promoters;
    }

    public function getAllPromoters($cf = null){
        if($cf !== null){
            $id = "";
            $mat = $this->resolveUserId($cf);
            if($mat === null){
                $id = $cf;
            } else {
                $id = $this->getCFfromMat($mat);
            }
            $sql = "
                SELECT DISTINCT
                    p.Codice AS codice,
                    p.Nome AS nome,
                    p.Email AS email
                FROM Promotore p
                JOIN Rappresentano r
                    ON r.Codice_Promotore = p.Codice
                WHERE r.CF = ?
                ORDER BY p.Nome
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param(
                "s",
                $id
            );
        } else {
            $sql = "
                SELECT
                    p.Codice AS codice,
                    p.Nome AS nome,
                    p.Email AS email
                FROM Promotore p
                ORDER BY p.Nome
            ";
            $stmt = $this->db->prepare($sql);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $promoters = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        foreach ($promoters as &$promoter) {
            $promoter["componenti"] =
                $this->getPromotersComponents($promoter["codice"]);
        }

        return $promoters;
    }

    public function getPromotersByCf($user){
        $mat = $this->resolveUserId($user);
        if($mat !== null){
            $user = $this->getCFfromMat($mat);
        }

        $sql = "SELECT
                    p.Codice AS codice,
                    p.Nome AS nome,
                    p.Email AS email
                FROM Promotore p
                JOIN Rappresentano r ON p.Codice_Promotore = p.Codice
                AND r.CF = ?
                ORDER BY nome";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $user);
        $stmt->execute();
        $result = $stmt->get_result();
        $promoters = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        foreach ($promoters as &$promoter) {
            $promoter["componenti"] =
                $this->getPromotersComponents($promoter["codice"]);
        }

        return $promoters;
    }

    public function getPromotersByCode($code){
        $sql = "SELECT
                    p.Codice AS codice,
                    p.Nome AS nome,
                    p.Email AS email
                FROM Promotore p
                WHERE p.Codice = ?
                ORDER BY nome";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $code);
        $stmt->execute();
        $result = $stmt->get_result();
        $promoters = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        foreach ($promoters as &$promoter) {
            $promoter["componenti"] =
                $this->getPromotersComponents($promoter["codice"]);
        }

        return $promoters;
    }

    public function getEventDatesByEvent($id){
        $sql = "SELECT
                    o.Codice AS codice,
                    o.Codice_Evento AS codice_evento,
                    o.Inizio AS inizio,
                    o.Fine AS fine,
                    o.Cod_Luogo AS codice_luogo,
                    l.Nome AS nome_luogo,
                    CASE
                            WHEN S.Codice IS NOT NULL
                            THEN CONCAT(S.Nome, ' ', Uni.Codice)
                        END AS nome_sede,
                        CASE
                            WHEN S.Codice IS NOT NULL THEN
                                CONCAT(
                                    ISD.Via, ' ',
                                    ISD.Nome, ' ',
                                    S.N_Civico, ', ',
                                    CS.Nome, ' (', S.Codice_Prov, ')'
                                )
                            ELSE
                                CONCAT(
                                    IES.Via, ' ',
                                    IES.Nome, ' ',
                                    X.N_Civico, ', ',
                                    CE.Nome, ' (', X.Codice_Prov, ')'
                                )
                        END AS indirizzo
                FROM Orario_Evento o
                JOIN Luogo l ON l.Codice = o.Cod_Luogo
                    LEFT JOIN Universitario Uni
                        ON Uni.Cod_Luogo = l.Codice

                    LEFT JOIN Sede S
                        ON S.Codice = Uni.Codice_Uni

                    LEFT JOIN Esterno X
                        ON X.Cod_Luogo = l.Codice

                    LEFT JOIN Indirizzo ISD
                        ON ISD.Codice_Prov = S.Codice_Prov
                        AND ISD.Codice_Citta = S.Codice_Citta
                        AND ISD.N_Civico = S.N_Civico

                    LEFT JOIN Indirizzo IES
                        ON IES.Codice_Prov = X.Codice_Prov
                        AND IES.Codice_Citta = X.Codice_Citta
                        AND IES.N_Civico = X.N_Civico

                    LEFT JOIN Citta CS
                        ON CS.Codice_Prov = S.Codice_Prov
                        AND CS.Codice = S.Codice_Citta

                    LEFT JOIN Citta CE
                        ON CE.Codice_Prov = X.Codice_Prov
                        AND CE.Codice = X.Codice_Citta
                WHERE o.Codice_Evento = ?
                ORDER BY o.Inizio ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $dates = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $dates;
    }

    private function insertCollaboratoriRichiesta($codiceRichiesta, $input){
        if (!empty($input["collaboratoriDaAggiungere"])) {

            $stmt = $this->db->prepare("
                INSERT INTO Agg_Collaboratore
                (
                    Codice_Richiesta,
                    CF
                )
                VALUES (?, ?)
            ");

            foreach ($input["collaboratoriDaAggiungere"] as $cf) {

                $stmt->bind_param(
                    "is",
                    $codiceRichiesta,
                    $cf
                );

                $stmt->execute();
            }
        }


        if (!empty($input["collaboratoriDaRimuovere"])) {

            $stmt = $this->db->prepare("
                INSERT INTO Elim_Collaboratore
                (
                    Codice_Richiesta,
                    CF
                )
                VALUES (?, ?)
            ");

            foreach ($input["collaboratoriDaRimuovere"] as $cf) {

                $stmt->bind_param(
                    "is",
                    $codiceRichiesta,
                    $cf
                );

                $stmt->execute();
            }
        }
    }

    private function insertPromotoriRichiesta($codiceRichiesta, $input){
        if (!empty($input["promotoriDaAggiungere"])) {

            $stmt = $this->db->prepare("
                INSERT INTO Agg_Promotore
                (
                    Codice_Promotore,
                    Codice_Richiesta
                )
                VALUES (?, ?)
            ");

            foreach ($input["promotoriDaAggiungere"] as $cf) {

                $stmt->bind_param(
                    "si",
                    $cf,
                    $codiceRichiesta
                );

                $stmt->execute();
            }
        }


        if (!empty($input["promotoriDaRimuovere"])) {

            $stmt = $this->db->prepare("
                INSERT INTO Elim_Promotore
                (
                    Codice_Promotore,
                    Codice_Richiesta
                )
                VALUES (?, ?)
            ");

            foreach ($input["promotoriDaRimuovere"] as $cf) {

                $stmt->bind_param(
                    "si",
                    $cf,
                    $codiceRichiesta
                );

                $stmt->execute();
            }
        }
    }

    private function getNextCode($classe, $attributo){
        $sql = "SELECT $attributo
                FROM $classe
                ORDER BY $attributo ASC
                FOR UPDATE";

        $result = $this->db->query($sql);

        $codice = 0;

        while ($row = $result->fetch_assoc()) {
            if ((int)$row[$attributo] === $codice) {
                $codice++;
            } else if ((int)$row[$attributo] > $codice) {
                break;
            }
        }

        return $codice;
    }

    private function insertCambioOrario($codiceRichiesta, $codiceOrario, $inizio, $fine, $luogo){

        $codice = $this->getNextCode(
            "Cambiare_Orario",
            "Codice"
        );

        $stmt = $this->db->prepare("
            INSERT INTO Cambiare_Orario
            (
                Codice,
                Codice_Ric,
                Codice_Orario,
                Nuovo_Inizio,
                Nuova_Fine,
                Codice_Luogo
            )
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "iiissi",
            $codice,
            $codiceRichiesta,
            $codiceOrario,
            $inizio,
            $fine,
            $luogo
        );

        $stmt->execute();
    }

    private function insertOrariRichiesta($codiceRichiesta, $input){
        // rimozioni
        if (!empty($input["orariDaRimuovere"])) {

            foreach ($input["orariDaRimuovere"] as $orario) {

                $this->insertCambioOrario(
                    $codiceRichiesta,
                    $orario["codice"],
                    null,
                    null,
                    null
                );
            }
        }


        // aggiunte
        if (!empty($input["orariDaAggiungere"])) {

            foreach ($input["orariDaAggiungere"] as $orario) {

                $this->insertCambioOrario(
                    $codiceRichiesta,
                    NULL,
                    $orario["inizio"],
                    $orario["fine"],
                    $orario["luogo"]
                );
            }
        }


        // modifiche
        if (!empty($input["orariModificati"])) {

            foreach ($input["orariModificati"] as $orario) {

                $inizio = $orario["inizio"] ?? null;
                $fine = $orario["fine"] ?? null;
                $luogo = $orario["luogo"] ?? null;


                $this->insertCambioOrario(
                    $codiceRichiesta,
                    $orario["codice"],
                    $inizio,
                    $fine,
                    $luogo
                );
            }
        }
    }

    private function updateEvento($input){

        $campi = [];
        $valori = [];
        $tipi = "";

        if ($input["nome"] !== null) {
            $campi[] = "Nome = ?";
            $valori[] = $input["nome"];
            $tipi .= "s";
        }

        if ($input["descrizione"] !== null) {
            $campi[] = "Descrizione = ?";
            $valori[] = $input["descrizione"];
            $tipi .= "s";
        }

        if ($input["posti"] !== null) {
            $campi[] = "Posti = ?";
            $valori[] = $input["posti"];
            $tipi .= "i";
        }

        if ($input["rappresentante"] !== null) {
            $campi[] = "Rappresentante = ?";
            $valori[] = $input["rappresentante"];
            $tipi .= "s";
        }

        // nessuna modifica ai campi principali
        if (count($campi) == 0) {
            return;
        }

        $sql = "
            UPDATE Evento
            SET " . implode(", ", $campi) . "
            WHERE Codice = ?
        ";

        $valori[] = $input["idEvento"];
        $tipi .= "i";

        $stmt = $this->db->prepare($sql);

        $stmt->bind_param(
            $tipi,
            ...$valori
        );

        $stmt->execute();
    }

    private function updateCollaboratori($input){

        if (!empty($input["collaboratoriDaAggiungere"])) {

            $stmt = $this->db->prepare("
                INSERT INTO Collaboratore
                (
                    Codice_Evento,
                    CF
                )
                VALUES (?, ?)
            ");

            foreach ($input["collaboratoriDaAggiungere"] as $cf) {

                $stmt->bind_param(
                    "is",
                    $input["idEvento"],
                    $cf
                );

                $stmt->execute();
            }
        }


        if (!empty($input["collaboratoriDaRimuovere"])) {

            $stmt = $this->db->prepare("
                DELETE FROM Collaboratore
                WHERE Codice_Evento = ?
                AND CF = ?
            ");

            foreach ($input["collaboratoriDaRimuovere"] as $cf) {

                $stmt->bind_param(
                    "is",
                    $input["idEvento"],
                    $cf
                );

                $stmt->execute();
            }
        }
    }

    private function updatePromotori($input){

        if (!empty($input["promotoriDaAggiungere"])) {

            $stmt = $this->db->prepare("
                INSERT INTO Propongono
                (
                    Codice_Evento,
                    CF
                )
                VALUES (?, ?)
            ");

            foreach ($input["promotoriDaAggiungere"] as $cf) {

                $stmt->bind_param(
                    "is",
                    $input["idEvento"],
                    $cf
                );

                $stmt->execute();
            }
        }


        if (!empty($input["promotoriDaRimuovere"])) {

            $stmt = $this->db->prepare("
                DELETE FROM Propongono
                WHERE Codice_Evento = ?
                AND CF = ?
            ");

            foreach ($input["promotoriDaRimuovere"] as $cf) {

                $stmt->bind_param(
                    "is",
                    $input["idEvento"],
                    $cf
                );

                $stmt->execute();
            }
        }
    }

    private function updateOrari($input){
         // elimina
        if (!empty($input["orariDaRimuovere"])) {
            $stmt = $this->db->prepare("
                DELETE FROM Orario_Evento
                WHERE Codice = ?
                AND Codice_Evento = ?
            ");

            foreach ($input["orariDaRimuovere"] as $orario) {

                $codice = $orario["codice"];

                $stmt->bind_param(
                    "ii",
                    $codice,
                    $input["idEvento"]
                );

                $stmt->execute();
            }
        }

        // aggiungi
        if (!empty($input["orariDaAggiungere"])) {
            $stmt = $this->db->prepare("
                INSERT INTO Orario_Evento
                (
                    Codice,
                    Codice_Evento,
                    Inizio,
                    Cod_Luogo,
                    Fine
                )
                VALUES (?, ?, ?, ?, ?)
            ");

            foreach ($input["orariDaAggiungere"] as $orario) {

                $codice = $this->getNextCode(
                    "Orario_Evento",
                    "Codice"
                );

                $inizio = $orario["inizio"];
                $fine = $orario["fine"];
                $luogo = $orario["luogo"];

                $stmt->bind_param(
                    "iisis",
                    $codice,
                    $input["idEvento"],
                    $inizio,
                    $luogo,
                    $fine
                );

                $stmt->execute();
            }
        }

        // modifica
        if (!empty($input["orariModificati"])) {
            foreach ($input["orariModificati"] as $orario) {

                $campi = [];
                $valori = [];
                $tipi = "";


                if ($orario["inizio"] !== null) {
                    $campi[] = "Inizio = ?";
                    $valori[] = $orario["inizio"];
                    $tipi .= "s";
                }


                if ($orario["fine"] !== null) {
                    $campi[] = "Fine = ?";
                    $valori[] = $orario["fine"];
                    $tipi .= "s";
                }


                if ($orario["luogo"] !== null) {
                    $campi[] = "Cod_Luogo = ?";
                    $valori[] = $orario["luogo"];
                    $tipi .= "i";
                }


                // nessuna modifica reale
                if (count($campi) == 0) {
                    continue;
                }


                $sql = "
                    UPDATE Orario_Evento
                    SET " . implode(", ", $campi) . "
                    WHERE Codice = ?
                    AND Codice_Evento = ?
                ";


                $valori[] = $orario["codice"];
                $valori[] = $input["idEvento"];

                $tipi .= "ii";


                $stmt = $this->db->prepare($sql);


                $stmt->bind_param(
                    $tipi,
                    ...$valori
                );


                $stmt->execute();
            }
        }
    }

    function addNewEvent($input, &$message, &$success){
        $this->db->begin_transaction();

        try {

            $input["idEvento"] = $this->getNextCode(
                "Evento",
                "Codice"
            );

            $stmt = $this->db->prepare("
                INSERT INTO Evento
                (
                    Codice,
                    Nome,
                    Descrizione,
                    Posti,
                    Rappresentante
                )
                VALUES (?, ?, ?, ?, ?)
            ");

            $stmt->bind_param(
                "issis",
                $input["idEvento"],
                $input["nome"],
                $input["descrizione"],
                $input["posti"],
                $input["rappresentante"]
            );

            $stmt->execute();

            // modifica collaboratori
            $this->updateCollaboratori($input);

            // modifica promotori
            $this->updatePromotori($input);

            // modifica orari
            $this->updateOrari($input);

            $this->db->commit();

            $success = true;
            $message = "Evento inserito.";

        } catch(Exception $e){

            $this->db->rollback();

            $success = false;
            $message = $e->getMessage();
        }
    }

    function addNewRequestEvent($input, &$message, &$success){
        $this->db->begin_transaction();

        try {
            $codiceRichiesta = $this->getNextCode(
                "Richiesta_Evento",
                "Codice"
            );

            $nome = $input["nome"];
            $descrizione = $input["descrizione"];
            $posti = $input["posti"];
            $rappresentante = $input["rappresentante"];
            $richiedente = $input["richiedente"];

            $stmt = $this->db->prepare("
                INSERT INTO Richiesta_Evento
                (
                    Codice,
                    Tipo,
                    Nome,
                    Descrizione,
                    Posti,
                    Codice_Evento,
                    Rappresentante,
                    Richiedente,
                    Richiedente_Inserimento
                )
                VALUES (?, 'Inserimento', ?, ?, ?, NULL, ?, NULL, ?)
            ");

            $stmt->bind_param(
                "issisi",
                $codiceRichiesta,
                $nome,
                $descrizione,
                $posti,
                $rappresentante,
                $richiedente
            );

            $stmt->execute();

            $this->insertCollaboratoriRichiesta(
                $codiceRichiesta,
                $input
            );

            $this->insertPromotoriRichiesta(
                $codiceRichiesta,
                $input
            );

            $this->insertOrariRichiesta(
                $codiceRichiesta,
                $input
            );

            $this->db->commit();

            $success = true;
            $message = "Richiesta di inserimento inviata.";

        } catch (Exception $e) {

            $this->db->rollback();

            $success = false;
            $message = $e->getMessage();
        }
    }

    function editNewEvent($input, &$message, &$success){
        $this->db->begin_transaction();

        try {

            // blocca evento
            $stmt = $this->db->prepare("
                SELECT Codice
                FROM Evento
                WHERE Codice = ?
                FOR UPDATE
            ");

            $stmt->bind_param("i", $input["idEvento"]);
            $stmt->execute();

            // modifica dati principali
            $this->updateEvento($input);

            // modifica collaboratori
            $this->updateCollaboratori($input);

            // modifica promotori
            $this->updatePromotori($input);

            // modifica orari
            $this->updateOrari($input);

            $this->db->commit();

            $success = true;
            $message = "Evento modificato.";

        } catch(Exception $e){

            $this->db->rollback();

            $success = false;
            $message = $e->getMessage();
        }
    }

    function editNewRequestEvent($input, &$message, &$success){

        $this->db->begin_transaction();

        try {
            $stmt = $this->db->prepare("
                SELECT Codice
                FROM Evento
                WHERE Codice = ?
                FOR UPDATE
            ");
            $stmt->bind_param("i", $input["idEvento"]);
            $stmt->execute();

            $codiceRichiesta = $this->getNextCode(
                "Richiesta_Evento",
                "Codice"
            );

            $nome = $input["nome"];
            $descrizione = $input["descrizione"];
            $posti = $input["posti"];
            $rappresentante = $input["rappresentante"];
            $idEvento = $input["idEvento"];
            $richiedente = $input["richiedente"];

            $stmt = $this->db->prepare("
                INSERT INTO Richiesta_Evento
                (
                    Codice,
                    Tipo,
                    Nome,
                    Descrizione,
                    Posti,
                    Codice_Evento,
                    Rappresentante,
                    Richiedente,
                    Richiedente_Inserimento
                )
                VALUES (?, 'Modifica', ?, ?, ?, ?, ?, ?, NULL)
            ");

            $stmt->bind_param(
                "issiiss",
                $codiceRichiesta,
                $nome,
                $descrizione,
                $posti,
                $idEvento,
                $rappresentante,
                $richiedente
            );

            $stmt->execute();

            $this->insertCollaboratoriRichiesta(
                $codiceRichiesta,
                $input
            );

            $this->insertPromotoriRichiesta(
                $codiceRichiesta,
                $input
            );

            $this->insertOrariRichiesta(
                $codiceRichiesta,
                $input
            );

            $this->db->commit();

            $success = true;
            $message = "Richiesta di modifica inviata.";

        } catch (Exception $e) {

            $this->db->rollback();

            $success = false;
            $message = $e->getMessage();
        }
    }

    function deleteNewEvent($input, &$message, &$success){

        $this->db->begin_transaction();

        try {

            // Blocca l'evento
            $stmt = $this->db->prepare("
                SELECT Codice
                FROM Evento
                WHERE Codice = ?
                FOR UPDATE
            ");
            $stmt->bind_param("i", $input["idEvento"]);
            $stmt->execute();

            // Blocca gli orari
            $stmt = $this->db->prepare("
                SELECT Codice
                FROM Orario_Evento
                WHERE Codice_Evento = ?
                FOR UPDATE
            ");
            $stmt->bind_param("i", $input["idEvento"]);
            $stmt->execute();

            // Blocca SEGNA
            $stmt = $this->db->prepare("
                SELECT CF
                FROM Segna
                WHERE Codice_Evento = ?
                FOR UPDATE
            ");
            $stmt->bind_param("i", $input["idEvento"]);
            $stmt->execute();

            // Blocca Propongono
            $stmt = $this->db->prepare("
                SELECT Codice
                FROM Propongono
                WHERE Codice_Evento = ?
                FOR UPDATE
            ");
            $stmt->bind_param("i", $input["idEvento"]);
            $stmt->execute();

            // Blocca Collaboratore
            $stmt = $this->db->prepare("
                SELECT CF
                FROM Collaboratore
                WHERE Codice_Evento = ?
                FOR UPDATE
            ");
            $stmt->bind_param("i", $input["idEvento"]);
            $stmt->execute();

            // Elimina l'evento (le tabelle figlie vengono eliminate automaticamente)
            $stmt = $this->db->prepare("
                DELETE
                FROM Evento
                WHERE Codice = ?
            ");
            $stmt->bind_param("i", $input["idEvento"]);
            $stmt->execute();

            $this->db->commit();
            $success = true;
            $message = "Evento eliminato.";

        } catch (Exception $e) {

            $this->db->rollback();
            $success = false;
            $message = $e->getMessage();
        }
    }

    function deleteNewRequestEvent($input, &$message, &$success){

        $this->db->begin_transaction();

        try {
            $stmt = $this->db->prepare("
                SELECT Codice
                FROM Evento
                WHERE Codice = ?
                FOR UPDATE
            ");
            $stmt->bind_param("i", $input["idEvento"]);
            $stmt->execute();

            $codiceRichiesta = $this->getNextCode(
                "Richiesta_Evento",
                "Codice"
            );

            $stmt = $this->db->prepare("
                INSERT INTO Richiesta_Evento
                (
                    Codice,
                    Tipo,
                    Nome,
                    Descrizione,
                    Posti,
                    Codice_Evento,
                    Rappresentante,
                    Richiedente,
                    Richiedente_Inserimento
                )
                VALUES (?, 'Eliminazione', NULL, NULL, NULL, ?, NULL, ?, NULL)
            ");

            $stmt->bind_param(
                "iis",
                $codiceRichiesta,
                $input["idEvento"],
                $input["richiedente"]
            );

            $stmt->execute();

            $this->db->commit();

            $success = true;
            $message = "Richiesta di eliminazione inviata.";

        } catch (Exception $e) {

            $this->db->rollback();

            $success = false;
            $message = $e->getMessage();
        }
    }

    private function generateEmptyEvent($tipo, $cf, $id_Evento){

        $codiceRichiesta = $this->getNextCode(
            "Richiesta_Evento",
            "Codice"
        );

        $stmt = $this->db->prepare("
            INSERT INTO Richiesta_Evento
            (
                Codice,
                Tipo,
                Nome,
                Descrizione,
                Posti,
                Codice_Evento,
                Rappresentante,
                Richiedente,
                Richiedente_Inserimento
            )
            VALUES (?, ?, NULL, NULL, NULL, ?, NULL, ?, NULL)
        ");

        $stmt->bind_param(
            "isis",
            $codiceRichiesta,
            $tipo,
            $id_Evento,
            $cf
        );

        $stmt->execute();

        return $codiceRichiesta;
    }

    function addNewEventDate($input, &$message, &$success){
        $this->db->begin_transaction();
        try{
            // blocca evento
            $stmt = $this->db->prepare("
                SELECT Codice
                FROM Evento
                WHERE Codice = ?
                FOR UPDATE
            ");

            $stmt->bind_param("i", $input["idEvento"]);
            $stmt->execute();

            // modifica orari
            $this->updateOrari($input);

            $this->db->commit();

            $success = true;
            $message = "Orari dell'evento aggiunti.";

        } catch (Exception $e) {

            $this->db->rollback();

            $success = false;
            $message = $e->getMessage();
        }
    }

    function addNewRequestEventDate($input, &$message, &$success){
        $this->db->begin_transaction();
        try{
            $codice_Richiesta = $this->generateEmptyEvent("Inserimento", $input["richiedente"], $input["idEvento"]);
            $this->insertOrariRichiesta(
                $codice_Richiesta,
                $input
            );
            $this->db->commit();

            $success = true;
            $message = "Richiesta di inserimento inviata.";

        } catch (Exception $e) {

            $this->db->rollback();

            $success = false;
            $message = $e->getMessage();
        }
    }

    function editNewEventDate($input, &$message, &$success){
        $this->db->begin_transaction();
        try{
            // blocca evento
            $stmt = $this->db->prepare("
                SELECT Codice
                FROM Evento
                WHERE Codice = ?
                FOR UPDATE
            ");

            $stmt->bind_param("i", $input["idEvento"]);
            $stmt->execute();

            // modifica orari
            $this->updateOrari($input);

            $this->db->commit();

            $success = true;
            $message = "Orari dell'evento modificati.";

        } catch (Exception $e) {

            $this->db->rollback();

            $success = false;
            $message = $e->getMessage();
        }
    }

    function editNewRequestEventDate($input, &$message, &$success){
        $this->db->begin_transaction();
        try{
            $codice_Richiesta = $this->generateEmptyEvent("Modifica", $input["richiedente"], $input["idEvento"]);
            $this->insertOrariRichiesta(
                $codice_Richiesta,
                $input
            );
            $this->db->commit();

            $success = true;
            $message = "Richiesta di modifica inviata.";

        } catch (Exception $e) {

            $this->db->rollback();

            $success = false;
            $message = $e->getMessage();
        }
    }

    function deleteNewEventDate($input, &$message, &$success){
        $this->db->begin_transaction();
        try{
            // Blocca l'evento
            $stmt = $this->db->prepare("
                SELECT Codice
                FROM Evento
                WHERE Codice = ?
                FOR UPDATE
            ");
            $stmt->bind_param("i", $input["idEvento"]);
            $stmt->execute();

            // Blocca gli orari
            $stmt = $this->db->prepare("
                SELECT Codice
                FROM Orario_Evento
                WHERE Codice_Evento = ?
                FOR UPDATE
            ");
            $stmt->bind_param("i", $input["idEvento"]);
            $stmt->execute();

            $stmt = $this->db->prepare("
                DELETE
                FROM Orario_Evento
                WHERE Codice = ?
                AND Codice_Evento = ?
            ");

            foreach ($input["orariDaRimuovere"] as $orario){
                $stmt->bind_param("ii", $orario["codice"], $input["idEvento"]);
                $stmt->execute();
            }

            $this->db->commit();
            $success = true;
            $message = "Orari dell'evento eliminati.";

        } catch (Exception $e) {

            $this->db->rollback();

            $success = false;
            $message = $e->getMessage();
        }
    }

    function deleteNewRequestEventDate($input, &$message, &$success){
        $this->db->begin_transaction();
        try{
            $codice_Richiesta = $this->generateEmptyEvent("Eliminazione", $input["richiedente"], $input["idEvento"]);
            $this->insertOrariRichiesta(
                $codice_Richiesta,
                $input
            );
            $this->db->commit();

            $success = true;
            $message = "Richiesta di eliminazione inviata.";

        } catch (Exception $e) {

            $this->db->rollback();

            $success = false;
            $message = $e->getMessage();
        }
    }

    function acceptRequests($classe, $tipo, $input, &$message, &$success){
        switch($classe){
            case "Evento":
                switch($tipo){
                    case "Inserimento":
                        $this->addNewEvent($input, $message, $success);
                        break;
                    case "Modifica":
                        $this->editNewEvent($input, $message, $success);
                        break;
                    case "Eliminazione":
                        $this->deleteNewEvent($input, $message, $success);
                        break;
                }
                break;
            case "Orario":
                switch($tipo){
                    case "Inserimento":
                        $this->addNewEventDate($input, $message, $success);
                        break;
                    case "Modifica":
                        $this->editNewEventDate($input, $message, $success);
                        break;
                    case "Eliminazione":
                        $this->deleteNewEventDate($input, $message, $success);
                        break;
                }
                break;
        }
    }

    function giveIdRichiedente($classe, $id, $input, &$message, &$success){
        $cf = "";
        $mat = $this->resolveUserId($id);
        if($mat==null){
            return $input["richiedente"];
        }else{
            $cf = $this->getCFfromMat($mat);

            $sql = $this->db->prepare(
                "Select p.Codice as codice
                    From Promotore p
                    Join Rappresentano r on p.Codice = r.Codice_Promotore
                    Join Persone u on u.CF = r.CF
                    And u.CF = ?");

            switch($classe){
                case "Inserimento_Evento":
                    $sql->bind_param("s", $cf);
                    $sql->execute();

                    $result = $sql->get_result();

                    while ($row = $result->fetch_assoc()) {

                        foreach ($input["promotoriDaAggiungere"] as $promotore) {

                            if ($promotore["codice"] == $row["codice"]) {
                                return $cf;
                            }
                        }
                    }
                    $success = false;
                    $message = "Non rappresenti nessun dei promotori inseriti per l'evento che vuoi inserire.";
                    return null;
                case "Other_Evento":
                    return $cf;
                default: return $mat;
            }
        }
    }

    //usata in api-azioneLuoghi
    function getIndirizzi() {
        $sql = "SELECT Codice_Prov AS cod_Prov, Codice_Citta AS cod_Citta, N_Civico AS civico, Via AS via, Nome AS nome
                FROM Indirizzo";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();
        $indirizzo = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $indirizzo;
    }

    function getcities(){
        $sql = "SELECT Codice_Prov AS cod_Prov, Codice AS codice, Nome AS nome
                FROM Citta";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();
        $citta = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $citta;
    }

    function getProvincies(){
        $sql = "SELECT Codice AS codice, Nome AS nome
                FROM Provincia";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();
        $provincia = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $provincia;
    }

    function getAllVie(){
        $sql = "SELECT DISTINCT Via AS via
                FROM Indirizzo
                ORDER BY Via";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();
        $vie = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $vie;
    }

    function getProfessors(){
        $sql = "SELECT p.Matricola AS matr, o.Cognome AS cognome, o.Nome AS nome, s.Email_Uni AS email
                FROM Professore p
                JOIN Sistema_Universitario s ON s.Matricola = p.Matricola
                JOIN Persona o ON o.CF = s.CF
                ORDER BY o.Cognome, o.Nome";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();
        $prof = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $prof;
    }

    function getAllExternPlaces(){
        $sql = "SELECT Codice_Prov AS cod_Prov, Codice_Citta AS cod_Citta, N_Civico AS civico, e.Codice AS codice,
                Capienza AS capienza, Nome AS nome, e.Cod_Luogo AS cod_Luogo
                FROM Esterno e
                JOIN Luogo l ON l.Codice = e.Cod_Luogo
                ORDER BY Nome";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();
        $ext = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $ext;
    }

    function getPlaceByCode($idPlace){
        $sql = "
            SELECT
                L.Codice,
                L.Nome,
                L.Capienza,

                E.Codice_Prov,
                E.Codice_Citta,
                E.N_Civico,

                I.Via,
                I.Nome AS NomeVia,
                P.Nome AS Provincia,
                Ci.Nome AS Citta,

                U.Codice_Uni,
                U.Codice AS cod_stanza,

                Cl.Lab,

                Uf.Matricola

            FROM Luogo L

            LEFT JOIN Esterno E
                ON L.Codice = E.Cod_Luogo

            LEFT JOIN Indirizzo I
                ON E.Codice_Prov = I.Codice_Prov
                AND E.Codice_Citta = I.Codice_Citta
                AND E.N_Civico = I.N_Civico

            LEFT JOIN Provincia P
                ON E.Codice_Prov = P.Codice

            LEFT JOIN Citta Ci
                ON E.Codice_Citta = Ci.Codice
                AND E.Codice_Prov = Ci.Cod_Prov

            LEFT JOIN Universitario U
                ON L.Codice = U.Cod_Luogo

            LEFT JOIN Classe Cl
                ON U.Codice_Uni = Cl.Codice_Uni
                AND U.Codice = Cl.Codice_Stanza

            LEFT JOIN Ufficio Uf
                ON U.Codice_Uni = Uf.Codice_Uni
                AND U.Codice = Uf.Codice_Stanza

            WHERE L.Codice = ?
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i",$idPlace);
        $stmt->execute();

        $result = $stmt->get_result();
        $luogo = $result->fetch_assoc();

        $stmt->close();


        if(!$luogo)
            return null;


        $tipo = "universitario";

        if($luogo["Codice_Prov"] !== null){
            $tipo = "esterno";
        }
        else if($luogo["Lab"] !== null){
            $tipo = "classe";
        }
        else if($luogo["Matricola"] !== null){
            $tipo = "ufficio";
        }


        $response = [
            "codice_luogo" => $luogo["Codice"],
            "nome" => $luogo["Nome"],
            "capienza" => $luogo["Capienza"],
            "tipo" => $tipo
        ];


        if($tipo === "esterno"){

            $response["indirizzo"] = [
                "provincia" => $luogo["Codice_Prov"],
                "prov_nome" => $luogo["Provincia"],
                "citta" => $luogo["Codice_Citta"],
                "citta_nome" => $luogo["Citta"],
                "via" => $luogo["Via"],
                "nomeVia" => $luogo["NomeVia"],
                "civico" => $luogo["N_Civico"]
            ];

        }

        if($tipo === "classe"){

            $response["cod_uni"] = $luogo["Codice_Uni"];
            $response["cod_stanza"] = $luogo["cod_stanza"];
            $response["lab"] = (bool)$luogo["Lab"];

        }

        if($tipo === "ufficio"){

            $response["cod_uni"] = $luogo["Codice_Uni"];
            $response["cod_stanza"] = $luogo["cod_stanza"];
            $response["assegnato"] = $luogo["Matricola"];

        }

        return $response;
    }

    public function getSedeByCode($id){
        $sql = "
            SELECT
                S.Codice,
                S.Nome,
                S.Descrizione,
                S.Path,
                S.Descrizione_Img,

                S.Codice_Prov,
                S.Codice_Citta,
                S.N_Civico,

                I.Via,
                I.Nome AS NomeVia,

                P.Nome AS Provincia,
                C.Nome AS Citta

            FROM Sede S

            JOIN Indirizzo I
                ON S.Codice_Prov = I.Codice_Prov
                AND S.Codice_Citta = I.Codice_Citta
                AND S.N_Civico = I.N_Civico

            JOIN Provincia P
                ON S.Codice_Prov = P.Codice

            JOIN Citta C
                ON S.Codice_Citta = C.Codice
                AND S.Codice_Prov = C.Cod_Prov

            WHERE S.Codice = ?
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();

        $result = $stmt->get_result();
        $sede = $result->fetch_assoc();

        $stmt->close();


        if(!$sede)
            return null;


        return [
            "codice" => $sede["Codice"],

            "nome" => $sede["Nome"],
            "descrizione" => $sede["Descrizione"],

            "path" => $sede["Path"],
            "descrizioneImmagine" => $sede["Descrizione_Img"],

            "indirizzo" => [
                "provincia" => $sede["Codice_Prov"],
                "prov_nome" => $sede["Provincia"],
                "citta" => $sede["Codice_Citta"],
                "citta_nome" => $sede["Citta"],
                "via" => $sede["Via"],
                "nomeVia" => $sede["NomeVia"],
                "civico" => $sede["N_Civico"]
            ]
        ];
    }

    private function deleteRecord($tabella, $where, $types, $valori){
        $sql = "
            DELETE FROM $tabella
            WHERE ".implode(" AND ", $where);


        $stmt = $this->db->prepare($sql);
        $stmt->bind_param(
            $types,
            ...$valori
        );
        $stmt->execute();
        if($stmt->affected_rows === 0){
            $stmt->close();
            throw new Exception("Nessuna riga eliminata.");
        }
        $stmt->close();
    }

    function deleteRecordElementi($tipo, $elementi, &$message, &$success){

        $config = [
            "provincia" => [
                "tabella" => "Provincia",
                "chiave" => ["Codice"]
            ],

            "citta" => [
                "tabella" => "Citta",
                "chiave" => ["Codice_Prov", "Codice"]
            ]
        ];

        if(!isset($config[$tipo])){
            $success = false;
            $message = "Tipo eliminazione non valido";
            return;
        }

        $tabella = $config[$tipo]["tabella"];
        $chiavi = $config[$tipo]["chiave"];

        $this->db->begin_transaction();

        try{

            foreach($elementi as $elemento){

                $where = [];
                $valori = [];
                $types = "";

                foreach($chiavi as $chiave){

                    $where[] = "$chiave = ?";

                    if($chiave === "Codice_Prov"){
                        $valori[] = $elemento["cod_Prov"];
                    }else{
                        $valori[] = $elemento["codice"];
                    }

                    $types .= "s";
                }

                $this->deleteRecord(
                    $tabella,
                    $where,
                    $types,
                    $valori
                );
            }

            $this->db->commit();

            $success = true;
            $message = ucfirst($tipo)." eliminati correttamente";

        }catch(Throwable $e){

            $this->db->rollback();

            $success = false;

            if($e instanceof mysqli_sql_exception && $e->getCode() == 1451){
                $message = "Impossibile eliminare il $tipo perché è ancora utilizzato.";
            }else{
                $message = "Errore durante l'eliminazione del $tipo.";
            }
        }
    }

    function saveRecordElementi($tipo, $aggiunti, $modificati, $eliminati, &$message, &$success){

        $config = [

            "provincia" => [
                "tabella" => "Provincia",
                "chiave" => [
                    "codice"
                ],
                "campo" => [
                    "codice"
                ],
                "campi" => [
                    "Nome"
                ],
                "tipo" => [
                    "s"
                ]
            ],

            "citta" => [
                "tabella" => "Citta",
                "chiave" => [
                    "Codice_Prov",
                    "Codice"
                ],
                "campo" => [
                    "cod_Prov",
                    "codice"
                ],
                "campi" => [
                    "Nome"
                ],
                "tipo" => [
                    "s",
                    "s"
                ]
            ]
        ];

        if(!isset($config[$tipo])){
            $success = false;
            $message = "Tipo non valido";
            return;
        }

        $tabella = $config[$tipo]["tabella"];
        $chiavi = $config[$tipo]["chiave"];
        $campiChiave = $config[$tipo]["campo"];
        $campi = $config[$tipo]["campi"];
        $tipi = $config[$tipo]["tipo"];
        $this->db->begin_transaction();
        try {
            // ADD
            foreach($aggiunti as $elemento){

                $colonne = [];
                $valori = [];
                $types = "";

                foreach($elemento as $index => $valore){

                    $colonne[] = array_keys($elemento)[$index];
                    $valori[] = $valore;
                    $types .= $tipi[$index];
                }
                $sql = "
                    INSERT INTO $tabella
                    (".implode(",", $colonne).")
                    VALUES
                    (".implode(",", array_fill(0, count($valori), "?")).")
                ";
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param(
                    $types,
                    ...$valori
                );
                $stmt->execute();
                $stmt->close();
            }
            // EDIT
            foreach($modificati as $elemento){
                $set = [];
                $valori = [];
                $types = "";
                foreach($campi as $index => $campo){
                    if(isset($elemento[$campo]) && $elemento[$campo] !== null){
                        $set[] = "$campo = ?";
                        $valori[] = $elemento[$campo];
                        $types .= $tipi[$index];
                    }
                }
                if(count($set) === 0)
                    continue;

                $where = [];
                foreach($chiavi as $index => $chiave){
                    $where[] = "$chiave = ?";
                    $valori[] = $elemento[$campiChiave[$index]];
                    $types .= $tipi[$index];
                }
                $sql = "
                    UPDATE $tabella
                    SET ".implode(",", $set)."
                    WHERE ".implode(" AND ", $where);

                $stmt = $this->db->prepare($sql);
                $stmt->bind_param(
                    $types,
                    ...$valori
                );
                $stmt->execute();
                $stmt->close();
            }
            // DELETE
            foreach($eliminati as $elemento){
                $where = [];
                $valori = [];
                $types = "";
                foreach($chiavi as $index => $chiave){
                    $where[] = "$chiave = ?";
                    $valori[] = $elemento[$campiChiave[$index]];
                    $types .= $tipi[$index];
                }
                $this->deleteRecord(
                    $tabella,
                    $where,
                    $types,
                    $valori
                );
            }
            $this->db->commit();
            $success = true;
            $message = ucfirst($tipo)." salvati correttamente";
        } catch(Throwable $e) {
            $this->db->rollback();
            $success = false;
            if($e instanceof mysqli_sql_exception && $e->getCode() == 1451){
                $message = "Impossibile modificare il $tipo perché alcuni elementi sono ancora utilizzati.";
            } else {
                $message = "Errore durante il salvataggio del $tipo.";
            }
        }
    }

    function deleteElementi($tipo, $elemento, &$message, &$success){
        $config = [

            "luogo" => [
                "tabella" => "Luogo",
                "chiave" => [
                    "Codice"
                ],
                "campo" => [
                    "codice"
                ],
                "tipo" => [
                    "i"
                ]
            ],

            "sede" => [
                "tabella" => "Sede",
                "chiave" => [
                    "Codice"
                ],
                "campo" => [
                    "codice"
                ],
                "tipo" => [
                    "i"
                ]
            ]
        ];

        if(!isset($config[$tipo])){
            $success = false;
            $message = "Tipo non valido";
            return;
        }
        $tabella = $config[$tipo]["tabella"];
        $chiavi = $config[$tipo]["chiave"];
        $campi = $config[$tipo]["campo"];
        $tipi = $config[$tipo]["tipo"];
        $where = [];
        $valori = [];
        $types = "";

        foreach($chiavi as $index => $chiave){
            $where[] = "$chiave = ?";
            $valori[] = $elemento[$campi[$index]];
            $types .= $tipi[$index];
        }
        $this->db->begin_transaction();
        try {
            $this->deleteRecord(
                $tabella,
                $where,
                $types,
                $valori
            );
            $this->db->commit();
            $success = true;
            $message = ucfirst($tipo)." eliminato correttamente";
        } catch(Throwable $e) {
            $this->db->rollback();
            $success = false;
            if($e instanceof mysqli_sql_exception && $e->getCode() == 1451){
                $message = "Impossibile eliminare il $tipo perché è ancora utilizzato.";
            } else {
                $message = "Errore durante eliminazione del $tipo.";
            }
        }
    }

    function insertElement($tabelle, $campi, $valori, $tipoCampi, &$message, &$success){

        $this->db->begin_transaction();
        $codiciCreati = [];
        try {
            foreach($tabelle as $index => $tabella){
                $colonne = $campi[$index];
                $dati = $valori[$index];
                $types = $tipoCampi[$index];

                // gestione AUTO
                foreach($dati as $i => $valore){
                    if($valore === "AUTO"){
                        $codice = $this->getNextCode(
                            $tabella,
                            $colonne[$i]
                        );
                        $dati[$i] = $codice;
                        $codiciCreati[$tabella] = $codice;
                    }
                    else if(str_starts_with($valore, "AUTO:")){
                        $tabellaRiferimento = explode(":", $valore)[1];
                        if(!isset($codiciCreati[$tabellaRiferimento])){
                            throw new Exception(
                                "Codice automatico non trovato per $tabellaRiferimento"
                            );
                        }
                        $dati[$i] = $codiciCreati[$tabellaRiferimento];
                    }
                }
                $placeholder = array_fill(0, count($colonne), "?");
                $sql = "
                    INSERT INTO $tabella
                    (".implode(",", $colonne).")
                    VALUES
                    (".implode(",", $placeholder).")
                ";
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param(
                    $types,
                    ...$dati
                );
                $stmt->execute();
                $stmt->close();
            }
            $this->db->commit();
            $success = true;
            $message = "Inserimento completato";
        } catch(Throwable $e){
            $this->db->rollback();
            $success = false;
            if($e instanceof mysqli_sql_exception && $e->getCode() == 1062){
                $message = "Elemento già esistente.";
            }
            else{
                $message = "Errore durante inserimento.";
            }
        }
    }

    function updateElement($tabelle, $campi, $valori, $tipoCampi, $campiWhere, $valoriWhere, $tipoCampiWhere, $elim, &$message, &$success){
        $this->db->begin_transaction();
        try {
            // DELETE
            foreach($elim as $elemento){
                $where = [];
                foreach($elemento["whereCampi"] as $campo){
                    $where[] = "$campo = ?";
                }
                $sql = "
                    DELETE FROM ".$elemento["tabella"]."
                    WHERE ".implode(" AND ", $where);


                $stmt = $this->db->prepare($sql);
                $stmt->bind_param(
                    implode("", $elemento["whereTipi"]),
                    ...$elemento["whereValori"]
                );
                $stmt->execute();
                $stmt->close();
            }

            // UPDATE
            foreach($tabelle as $index => $tabella){
                $set = [];
                foreach($campi[$index] as $campo){
                    $set[] = "$campo = ?";
                }
                $where = [];
                foreach($campiWhere[$index] as $campo){
                    $where[] = "$campo = ?";
                }
                $sql = "
                    UPDATE $tabella
                    SET ".implode(",", $set)."
                    WHERE ".implode(" AND ", $where);
                $dati = array_merge(
                    $valori[$index],
                    $valoriWhere[$index]
                );
                $types =
                    implode("", $tipoCampi[$index]).
                    implode("", $tipoCampiWhere[$index]);
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param(
                    $types,
                    ...$dati
                );
                $stmt->execute();
                $stmt->close();
            }
            $this->db->commit();
            $success = true;
            $message = "Modifica completata";


        } catch(Throwable $e){
            $this->db->rollback();
            $success = false;
            if($e instanceof mysqli_sql_exception){
                if($e->getCode() == 1451){
                    $message = "Impossibile modificare: elemento ancora utilizzato.";
                }
                else if($e->getCode() == 1062){
                    $message = "Elemento già esistente.";
                }
                else{
                    $message = "Errore database.";
                }
            } else {
                $message = "Errore durante modifica.";
            }
        }
    }

    function getImmagineSede($idSede){
        $sql = "
            SELECT Path
            FROM Sede
            WHERE Codice = ?
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param(
            "i",
            $idSede
        );
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result["Path"] ?? null;
    }

    function saveRecordElementiBetter(
        $insertTabelle,
        $insertCampi,
        $insertValori,
        $insertTipi,
        $updateTabelle,
        $updateCampi,
        $updateValori,
        $updateTipi,
        $updateWhereCampi,
        $updateWhereValori,
        $updateWhereTipi,
        $delete,
        &$message,
        &$success
    ){
        $this->db->begin_transaction();
        try{
            // DELETE
            foreach($delete as $elemento){
                $where = [];
                foreach($elemento["whereCampi"] as $campo){
                    $where[] = "$campo = ?";
                }
                $sql = "
                    DELETE FROM {$elemento["tabella"]}
                    WHERE ".implode(" AND ", $where);
                $stmt = $this->db->prepare($sql);
                $valori = $elemento["whereValori"];
                $parametri = [];
                foreach($valori as &$valore){
                    $parametri[] = &$valore;
                }
                $stmt->bind_param(
                    implode("", $elemento["whereTipi"]),
                    ...$parametri
                );
                $stmt->execute();
                $stmt->close();
            }
            // UPDATE
            foreach($updateTabelle as $i => $tabella){
                $set = [];
                foreach($updateCampi[$i] as $campo){
                    $set[] = "$campo = ?";
                }
                $where = [];
                foreach($updateWhereCampi[$i] as $campo){
                    $where[] = "$campo = ?";
                }
                $sql = "
                    UPDATE $tabella
                    SET ".implode(",", $set)."
                    WHERE ".implode(" AND ", $where);
                $stmt = $this->db->prepare($sql);
                $valori = array_merge(
                    $updateValori[$i],
                    $updateWhereValori[$i]
                );
                $parametri = [];
                foreach($valori as &$valore){
                    $parametri[] = &$valore;
                }
                $stmt->bind_param(
                    implode("", $updateTipi[$i]) .
                    implode("", $updateWhereTipi[$i]),
                    ...$parametri
                );
                $stmt->execute();
                $stmt->close();
            }
            // INSERT
            $codiciCreati = [];
            foreach($insertTabelle as $i => $tabella){
                $colonne = $insertCampi[$i];
                $dati = $insertValori[$i];
                foreach($dati as $j => $valore){
                    if($valore === "AUTO"){
                        $codice = $this->getNextCode(
                            $tabella,
                            $colonne[$j]
                        );
                        $dati[$j] = $codice;
                        $codiciCreati[$tabella] = $codice;
                    }
                    else if(
                        is_string($valore) &&
                        str_starts_with($valore, "AUTO:")
                    ){
            $parti = explode(":", $valore);
            if(count($parti) == 2){
                $codiceTemporaneo = $parti[1];
                $codice = $this->getNextCode(
                    $tabella,
                    $colonne[$j]
                );
                $dati[$j] = $codice;
                $codiciCreati[$tabella][$codiceTemporaneo] = $codice;
            }
            else if (count($parti) == 3){
                $tabellaRef = $parti[1];
                $codiceTemporaneo = $parti[2];
                if(!isset($codiciCreati[$tabellaRef][$codiceTemporaneo])){
                    throw new Exception(
                        "Codice automatico non trovato per $tabellaRef:$codiceTemporaneo"
                    );
                }
                $dati[$j] =
                    $codiciCreati[$tabellaRef][$codiceTemporaneo];
            }
        }
    }
                $sql = "
                    INSERT INTO $tabella
                    (".implode(",", $colonne).")
                    VALUES
                    (".implode(",", array_fill(0, count($colonne), "?")).")
                ";
                $stmt = $this->db->prepare($sql);
                $parametri = [];
                foreach($dati as &$valore){
                    $parametri[] = &$valore;
                }
                $stmt->bind_param(
                    $insertTipi[$i],
                    ...$parametri
                );
                $stmt->execute();
                $stmt->close();
            }
            $this->db->commit();
            $success = true;
            $message = "Operazione completata.";
        }catch(Throwable $e){
            $this->db->rollback();
            $success = false;
            if($e instanceof mysqli_sql_exception){
                switch($e->getCode()){
                    case 1062:
                        $message = "Elemento già esistente.";
                        break;
                    case 1451:
                        $message = "Elemento ancora utilizzato.";
                        break;
                    default:
                        $message = "Errore database: ".$e->getMessage();
                }
            }else{
                $message = "Errore durante il salvataggio: ".$e->getMessage();
            }
        }
    }

    //sta venendo usata in api-savePromotore
    public function getLevelAccessByCF($cf){
        $query = "
            SELECT Livello_Permesso
            FROM Persona
            WHERE CF = ?
        ";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param(
            "s",
            $cf
        );
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row ? (int)$row["Livello_Permesso"] : false;
    }

    private function getReunions($inizio, $fine, $matricola = null){

        $sql = "
            SELECT
                R.Codice AS codice,
                R.Online AS online,
                R.Data_Inizio AS data_inizio,
                R.Data_Fine AS data_fine,
                R.N_Slot AS n_slot,
                R.Matricola AS matricola,

                P.Nome AS nome,
                P.Cognome AS cognome,
                SU.Email_Uni AS email,

                CASE
                    WHEN R.Online = 0 THEN R.Codice_Uni
                END AS cod_uni,

                CASE
                    WHEN R.Online = 0 THEN L.Codice
                END AS cod_stanza,

                CASE
                    WHEN R.Online = 0 THEN L.Nome
                END AS nome_stanza,

                CASE
                    WHEN R.Online = 0 THEN L.Capienza
                END AS capienza

            FROM Ricevimento R

            JOIN Sistema_Universitario SU
                ON SU.Matricola = R.Matricola

            JOIN Persona P
                ON P.CF = SU.CF

            LEFT JOIN Universitario U
                ON U.Codice_Uni = R.Codice_Uni
                AND U.Codice = R.Codice_Stanza

            LEFT JOIN Luogo L
                ON L.Codice = U.Cod_Luogo

            WHERE R.Data_Inizio >= ?
            AND R.Data_Inizio < ?
        ";

        $tipi = "ss";
        $param = [$inizio, $fine];

        if($matricola !== null){
            $sql .= " AND R.Matricola = ?";
            $tipi .= "i";
            $param[] = $matricola;
        }

        $sql .= " ORDER BY R.Data_Inizio ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($tipi, ...$param);
        $stmt->execute();

        $ricevimenti = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $slots = $this->getSlots($inizio, $fine);

        $indice = [];

        foreach($ricevimenti as &$ric){
            $ric["slots"] = [];
            $indice[$ric["codice"]] = &$ric;
        }
        unset($ric);

        foreach($slots as $slot){
            if(isset($indice[$slot["codice_ric"]])){
                $indice[$slot["codice_ric"]]["slots"][] = $slot;
            }
        }

        return $ricevimenti;
    }

    private function getSlots($inizio, $fine, $matricola = null){

        $sql = "
            SELECT
                s.Codice_Ric AS codice_ric,
                s.N_Slot AS slot,
                s.Matricola AS matricola,
                p.Nome AS nome,
                p.Cognome AS cognome,
                u.Email_Uni AS email

            FROM Slot s

            JOIN Sistema_Universitario u
                ON s.Matricola = u.Matricola

            JOIN Persona p
                ON u.CF = p.CF

            JOIN Ricevimento r
                ON s.Codice_Ric = r.Codice

            WHERE r.Data_Inizio >= ?
            AND r.Data_Inizio < ?
        ";

        $tipi = "ss";
        $param = [$inizio, $fine];

        if($matricola !== null){
            $sql .= " AND s.Matricola = ?";
            $tipi .= "i";
            $param[] = $matricola;
        }

        $sql .= " ORDER BY s.Codice_Ric, s.N_Slot";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($tipi, ...$param);
        $stmt->execute();

        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $stmt->close();

        return $result;
    }

    private function getOffices($matricola = null){

        $sql = "
            SELECT
                l.Codice AS codice,
                u.Codice_Uni AS cod_uni,
                u.Codice_Stanza AS cod_stanza,
                se.Nome AS nome_sede,
                l.Nome AS nome_stanza,
                l.Capienza AS capienza,
                p.Nome AS nome,
                p.Cognome AS cognome,
                s.Matricola AS matricola,
                s.Email_Uni AS email,
                CONCAT(
                    i.Via, ' ',
                    i.Nome, ' ',
                    se.N_Civico, ', ',
                    c.Nome, ' (', se.Codice_Prov, ')'
                ) AS indirizzo

            FROM Ufficio u

            JOIN Universitario un
                ON u.Codice_Uni = un.Codice_Uni
                AND u.Codice_Stanza = un.Codice

            JOIN Sede se
                ON un.Codice_Uni = se.Codice

            LEFT JOIN Indirizzo i
                ON i.Codice_Prov = se.Codice_Prov
                AND i.Codice_Citta = se.Codice_Citta
                AND i.N_Civico = se.N_Civico

            LEFT JOIN Citta c
                ON c.Codice_Prov = se.Codice_Prov
                AND c.Codice = se.Codice_Citta

            JOIN Luogo l
                ON un.Cod_Luogo = l.Codice

            JOIN Sistema_Universitario s
                ON s.Matricola = u.Matricola

            JOIN Persona p
                ON p.CF = s.CF
        ";

        if($matricola !== null){
            $sql .= " WHERE u.Matricola = ?";
        }

        $sql .= " ORDER BY u.Matricola";

        $stmt = $this->db->prepare($sql);

        if($matricola !== null){
            $stmt->bind_param("i", $matricola);
        }

        $stmt->execute();

        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $stmt->close();

        return $result;
    }

    public function getAllReunions($inizio, $fine){
        return $this->getReunions($inizio, $fine);
    }

    public function getAllReunionsByProfessor($inizio, $fine, $user){
        $mat = $this->resolveUserId($user);
        if(!$mat){
            return [];
        }
        return $this->getReunions($inizio, $fine, $mat);
    }

    public function getAllSlots($inizio, $fine){
        return $this->getSlots($inizio, $fine);
    }

    public function getAllUserSlots($inizio, $fine, $user){
        $mat = $this->resolveUserId($user);
        if(!$mat){
            return [];
        }
        return $this->getSlots($inizio, $fine, $mat);
    }

    public function getAllOffices(){
        return $this->getOffices();
    }

    public function getAllOfficesByProfessor($user){
        $mat = $this->resolveUserId($user);
        if(!$mat){
            return [];
        }
        return $this->getOffices($mat);
    }

    public function getStudents(){
        $sql = " SELECT
                    p.Nome AS nome,
                    p.Cognome AS cognome,
                    u.Matricola AS matr,
                    u.Email_Uni AS email
                FROM Studente s
                JOIN Sistema_Universitario u
                    ON s.Matricola = u.Matricola
                JOIN Persona p
                    ON u.CF = p.CF
                ORDER BY p.Cognome, p.Nome, u.Matricola
            ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();
        $stud = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $stud;
    }
}
?>