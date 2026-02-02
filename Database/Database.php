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

    // Funzione privata per ottenere la matricola reale a partire da email o matricola
    private function resolveUserId($idUtente)
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
        $sql = "SELECT sede.Nome as nome, sede.Codice_Prov, sede.Codice_Citta, sede.N_Civico, indirizzo.Via, indirizzo.Nome as nome_indirizzo, sede.descrizione
                FROM sede, indirizzo
                WHERE sede.Codice_Prov = indirizzo.Codice_Prov
                    AND sede.Codice_Citta = indirizzo.Codice_Citta
                    AND sede.N_Civico = indirizzo.N_Civico";
        $result = $this->db->query($sql);
        $campuses = $result->fetch_all(MYSQLI_ASSOC);
        return $campuses;
    }

    public function getTimesStudent($idUtente)
    {
        $cf = $this->resolveUserId($idUtente);
        if (!$cf) {
            return []; // nessun evento se utente non trovato
        }


    }

    public function getReunionStudent($idUtente)
    {
        $cf = $this->resolveUserId($idUtente);
        if (!$cf) {
            return []; // nessun evento se utente non trovato
        }
    }

    public function getTimesProfessor($idUtente)
    {
        $cf = $this->resolveUserId($idUtente);
        if (!$cf) {
            return []; // nessun evento se utente non trovato
        }
    }

    public function getReunionProfessor($idUtente)
    {
        $cf = $this->resolveUserId($idUtente);
        if (!$cf) {
            return []; // nessun evento se utente non trovato
        }
    }

    public function getSignInChannals($idUtente)
    {
        $cf = $this->resolveUserId($idUtente);
        if (!$cf) {
            return []; // nessun evento se utente non trovato
        }
    }

    public function getStaffEvents($idUtente)
    {
        $cf = $this->resolveUserId($idUtente);
        if (!$cf) {
            return []; // nessun evento se utente non trovato
        }
    }

    public function getSignInEvents($idUtente)
    {
        // Risolvi l'utente in CF
        $cf = $this->resolveUserId($idUtente);
        if (!$cf) {
            return []; // nessun evento se utente non trovato
        }

        $sql = "SELECT e.Nome as nome, e.Inizio as inizio, e.Fine as fine, e.Descrizione as descrizione
            FROM Evento e
            INNER JOIN Segna s
                ON e.Codice = s.Codice_Evento
            WHERE s.CF = ?
            AND e.Fine > NOW()
            ORDER BY e.Inizio ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $cf);
        $stmt->execute();
        $result = $stmt->get_result();
        $eventi = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $eventi;
    }

    public function getNotifications($idUtente)
    {
        $matricola = $this->resolveUserId($idUtente);
        if (!$matricola) {
            return []; // nessuna notifica se utente non trovato
        }

        $stmt = $this->db->prepare("SELECT Codice as codice, Descizione as descrizione, Chiusa as chiusa
            FROM Notifica
            WHERE Matricola = ?
            ORDER BY Codice DESC");

        $stmt->bind_param("i", $matricola);
        $stmt->execute();
        $result = $stmt->get_result();
        $notifiche = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $notifiche;
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
}
?>