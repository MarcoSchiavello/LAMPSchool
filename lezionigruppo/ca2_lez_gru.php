<?php

require_once '../lib/req_apertura_sessione.php';

/*
  Copyright (C) 2015 Pietro Tamburrano
  Questo programma è un software libero; potete redistribuirlo e/o modificarlo secondo i termini della
  GNU Affero General Public License come pubblicata
  dalla Free Software Foundation; sia la versione 3,
  sia (a vostra scelta) ogni versione successiva.

  Questo programma è distribuito nella speranza che sia utile
  ma SENZA ALCUNA GARANZIA; senza anche l'implicita garanzia di
  POTER ESSERE VENDUTO o di IDONEITA' A UN PROPOSITO PARTICOLARE.
  Vedere la GNU Affero General Public License per ulteriori dettagli.

  Dovreste aver ricevuto una copia della GNU Affero General Public License
  in questo programma; se non l'avete ricevuta, vedete http://www.gnu.org/licenses/
 */


/* programma per la cancellazione di un docente
  riceve in ingresso i dati del docente */
@require_once("../php-ini" . $_SESSION['suffisso'] . ".php");
@require_once("../lib/funzioni.php");


// istruzioni per tornare alla pagina di login

$tipoutente = $_SESSION["tipoutente"]; //prende la variabile presente nella sessione
if ($tipoutente == "")
{
    header("location: ../login/login.php?suffisso=" . $_SESSION['suffisso']);
    die;
}

$titolo = "Cancellazione lezione di gruppo";
$script = "";
stampa_head($titolo, "", $script, "SDMAP");
stampa_testata("<a href='../login/ele_ges.php'>PAGINA PRINCIPALE</a> - <a href='vis_lez_gru.php'>ELENCO LEZIONI</a> - $titolo", "", $_SESSION['nome_scuola'], $_SESSION['comune_scuola']);


$idlezionegruppo = stringa_html('idlezione');
$iddocente = stringa_html('iddocente');

$con = mysqli_connect($db_server, $db_user, $db_password, $db_nome);


if (!$con)
{
    print("<H1>connessione al server mysql fallita</H1>");
    exit;
}
$DB = true;
if (!$DB)
{
    print("<H1>connessione al database fallita</H1>");
    exit;
}
$query = "select idlezione from tbl_lezioni where idlezionegruppo=$idlezionegruppo";
$ris = eseguiQuery($con,$query);
$lezioni = array();
while ($rec = mysqli_fetch_array($ris))
{
    $lezioni[] = $rec['idlezione'];
}
$cancellazioneok = true;
foreach ($lezioni as $idlezione)
{

    $f = "DELETE FROM tbl_firme WHERE idlezione='$idlezione' and iddocente='$iddocente'";
    eseguiQuery($con,$f);


    $q = "SELECT * FROM tbl_firme where idlezione='$idlezione'";
    $res = eseguiQuery($con,$q);

    if (mysqli_num_rows($res) == 0)  // Non ci sono altre firme
    {

        $f = "DELETE FROM tbl_valutazioniintermedie WHERE idlezione='$idlezione'";
        eseguiQuery($con,$f);
        $idclasse = estrai_classe_lezione($idlezione, $con);
        $datalezione = estrai_data_lezione($idlezione, $con);
        $f = "DELETE FROM tbl_lezioni WHERE idlezione='$idlezione'";
        eseguiQuery($con,$f);
        if (!gestione_manuale_assenze($idclasse, $datalezione, $con))
           ricalcola_assenze_lezioni_classe($con, $idclasse, $datalezione);
    } else
        $cancellazioneok = false;
}
if ($cancellazioneok)
{
    $f = "DELETE FROM tbl_lezionigruppi WHERE idlezionegruppo='$idlezionegruppo'";
    $res = eseguiQuery($con,$f);
}
//header("location: ../lezioni/vis_lez.php?iddocente=$iddocente");
print "
        <form method='post' id='formlez' action='vis_lez_gru.php'>
             <input type='hidden' name='iddocente' value='$iddocente'>
        </form>
        <SCRIPT language='JavaScript'>
        {
           document.getElementById('formlez').submit();
        }
        </SCRIPT>";


stampa_piede("");
mysqli_close($con);

