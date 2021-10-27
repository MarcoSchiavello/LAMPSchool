<?php

require_once '../lib/req_apertura_sessione.php';

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

@require_once("../php-ini" . $_SESSION['suffisso'] . ".php");
@require_once("../lib/funzioni.php");
$con = mysqli_connect($db_server, $db_user, $db_password, $db_nome) or die("Errore durante la connessione: " . mysqli_error($con));

// istruzioni per tornare alla pagina di login se non c'� una sessione valida

// DEFINIZIONE ARRAY PER MEMORIZZAZZIONE IN CSV
$listamaterie = array();
$listamaterie[] = "Alunno";


$tipoutente = $_SESSION["tipoutente"]; //prende la variabile presente nella sessione
if ($tipoutente == "")
{
    header("location: ../login/login.php?suffisso=" . $_SESSION['suffisso']);
    die;
}

//
//    Parte iniziale della pagina
//

$titolo = "Registrazione dati alunno";
$script = "<script type='text/javascript'>
         <!--
               var stile = 'top=10, left=10, width=1024, height=400, status=no, menubar=no, toolbar=no, scrollbars=yes';
               function Popup(apri) 
               {
                  window.open(apri, '', stile);
               }
         //-->
         </script>";

stampa_head($titolo, "", $script, "SPD");

stampa_testata("<a href='../login/ele_ges.php'>PAGINA PRINCIPALE</a> - $titolo", "", $_SESSION['nome_scuola'], $_SESSION['comune_scuola']);

$idalunno = stringa_html('idalunno');
$periodo = stringa_html('periodo');
$cl = stringa_html('cl');
$provenienza = stringa_html('prov');
//if ($giorno=='')
//   $giorno=date('d');
//if ($mese=='')
//   $mese=date('m');
//if ($anno=='')
//   $anno=date('Y');

$querydel = "DELETE FROM tbl_valutazionifinali
           WHERE idalunno=$idalunno
           AND periodo=$periodo";
// print inspref($querydel);           
$ris = eseguiQuery($con,$querydel);



// ESTRAGGO TUTTE LE MATERIE PER LA CLASSE
$query = "SELECT distinct tbl_materie.idmateria FROM tbl_cattnosupp,tbl_materie 
WHERE tbl_cattnosupp.idmateria=tbl_materie.idmateria
and tbl_cattnosupp.idclasse=$cl
and tbl_cattnosupp.iddocente <> 1000000000";

$votiinseriti = false;
// print inspref($query);
$rismat = eseguiQuery($con, $query);
while ($val = mysqli_fetch_array($rismat))
{
    $idmateria = $val['idmateria'];
    $schevotounico = "unico_" . $val['idmateria'];
    $schevotoscritto = "scritto_" . $val['idmateria'];
    $schevotoorale = "orale_" . $val['idmateria'];
    $schevotopratico = "pratico_" . $val['idmateria'];
    //$schegiudizio="giu_".$val['idmateria'];
    $schenote = "not_" . $val['idmateria'];
    $scheass = "ass_" . $val['idmateria'];
    $votounico = stringa_html($schevotounico);
    $votoscritto = stringa_html($schevotoscritto);
    $votoorale = stringa_html($schevotoorale);
    $votopratico = stringa_html($schevotopratico);
    //$giudizio=stringa_html($schegiudizio); 
    $note = stringa_html($schenote);
    $ass = stringa_html($scheass)!=''?stringa_html($scheass):'0';
    if (($votounico != '' & $votounico != '99' & $votounico != NULL) |
            ($votoorale != '' & $votoorale != '99' & $votoorale != NULL) |
            ($votoscritto != '' & $votoscritto != '99' & $votoscritto != NULL) |
            ($votopratico != '' & $votopratico != '99' & $votopratico != NULL) |
            ($schenote != ''))
    {
        $queryins = "INSERT into tbl_valutazionifinali(idalunno,idmateria,votounico,codsissi,votoscritto,votoorale,votopratico,assenze,note,periodo)
						 VALUES ('$idalunno','$idmateria','$votounico','','$votoscritto','$votoorale','$votopratico','$ass','" . elimina_apici($note) . "','$periodo')";
        $risins = eseguiQuery($con, $queryins);
        $votiinseriti = true;
    }
}

// INSERISCO VOTO CONDOTTA
//if ($votiinseriti)
//{
$idmateria = -1;
$schevoto = "unico_-1";
$schenote = "not_-1";
$voto = stringa_html($schevoto);
$note = stringa_html($schenote);

$queryins = "INSERT into tbl_valutazionifinali(idalunno,idmateria,codsissi,votounico,periodo,note)
	                 VALUES ('$idalunno','$idmateria','','$voto','$periodo','$note')";
$risins = eseguiQuery($con, $queryins);
//}
// INSERISCO GIUDIZIO GENERALE

$querydel = "DELETE FROM tbl_giudizi
           WHERE idalunno=$idalunno
           AND periodo='$periodo'
           AND idclasse=$cl";

// print inspref($querydel);           

$ris = eseguiQuery($con,$querydel);

$giudizio = $_POST['giudizio'];
$queryins = "INSERT into tbl_giudizi(idclasse,idalunno,periodo,giudizio)
	                 VALUES ('$cl','$idalunno','$periodo','" . elimina_apici($giudizio) . "')";
$risins = eseguiQuery($con, $queryins);

if ($provenienza == 'tab')
{
    //  header("location: ../scrutini/riepvoti.php?cl=$cl&periodo=$periodo");
    print "
        <form method='post' id='formscr' action='../scrutini/riepvoti.php'>
        <input type='hidden' name='cl' value='$cl'>
        <input type='hidden' name='periodo' value='$periodo'>
        
        </form>
        <SCRIPT language='JavaScript'>
           document.getElementById('formscr').submit();
        </SCRIPT>";
} else
{
    print ("
         <form method='post' id='formscr' action='schedaalu.php'>
         <input type='hidden' name='cl' value='$cl'>
         <input type='hidden' name='periodo' value='$periodo'>
         <input type='hidden' name='idalunno' value='$idalunno'>
        </form>
        <SCRIPT language='JavaScript'>
           document.getElementById('formscr').submit();
        </SCRIPT>");
}


mysqli_close($con);





stampa_piede("");

