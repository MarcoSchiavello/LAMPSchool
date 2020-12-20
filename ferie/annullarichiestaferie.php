<?php

session_start();

/*
  Copyright (C) 2015 Pietro Tamburrano
  Questo programma è un software libero; potete redistribuirlo e/o modificarlo secondo i termini della
  GNU Affero General Public License come pubblicata
  dalla Free Software Foundation; sia la versione 3,
  sia (a vostra scelta) ogni versione successiva.

  Questo programma é distribuito nella speranza che sia utile
  ma SENZA ALCUNA GARANZIA; senza anche l'implicita garanzia di
  POTER ESSERE VENDUTO o di IDONEITA' A UN PROPOSITO PARTICOLARE.
  Vedere la GNU Affero General Public License per ulteriori dettagli.

  Dovreste aver ricevuto una copia della GNU Affero General Public License
  in questo programma; se non l'avete ricevuta, vedete http://www.gnu.org/licenses/
 */

require_once '../php-ini' . $_SESSION['suffisso'] . '.php';
require_once '../lib/funzioni.php';
//require_once '../lib/ db / query.php';
//$lQuery = LQuery::getIstanza();
// istruzioni per tornare alla pagina di login se non c'è una sessione valida
////session_start();

$tipoutente = $_SESSION["tipoutente"]; //prende la variabile presente nella sessione
//$iddocente = $_SESSION["idutente"];


if ($tipoutente == "")
{
    header("location: ../login/login.php?suffisso=" . $_SESSION['suffisso']);
    die;
}

$titolo = "Annullamento richiesta ferie";
$script = "";
stampa_head($titolo, "", $script, "SDP");
stampa_testata("<a href='../login/ele_ges.php'>PAGINA PRINCIPALE</a> - $titolo", "", $_SESSION['nome_scuola'], $_SESSION['comune_scuola']);

$con = mysqli_connect($db_server, $db_user, $db_password, $db_nome) or die("Errore durante la connessione: " . mysqli_error($con));

$nominativodirigente = estrai_dati_docente(1000000000, $con);
$prot = stringa_html('prot');
$mf = stringa_html('mf');


if ($mf == 'yes')  // Effettuata da preside o staff per mancata fruizione
    $query = "update tbl_richiesteferie set annullata=1 where idrichiestaferie=$prot";
else  // Effettuata dal docente prima dell'autorizzazione
    $query = "update tbl_richiesteferie set concessione=9 where idrichiestaferie=$prot";

eseguiQuery($con, $query);

if ($mf== 'yes')
{
print "<form method='post' id='formlez' action='esamerichferie.php'>
       <input type='submit' value='OK'>
       </form>
       <SCRIPT language='JavaScript'>
	  {
	      document.getElementById('formlez').submit();
	  }
       </SCRIPT>";
}
else
{
    print "<form method='post' id='formlez' action='richferie.php'>
       <input type='submit' value='OK'>
       </form>
       <SCRIPT language='JavaScript'>
	  {
	      document.getElementById('formlez').submit();
	  }
       </SCRIPT>";
}
    
mysqli_close($con);
stampa_piede("");
