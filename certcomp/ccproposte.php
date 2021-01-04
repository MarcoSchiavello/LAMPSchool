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

@require_once("../php-ini" . $_SESSION['suffisso'] . ".php");
@require_once("../lib/funzioni.php");

// istruzioni per tornare alla pagina di login se non c'� una sessione valida
////session_start();
$tipoutente = $_SESSION["tipoutente"]; //prende la variabile presente nella sessione
if ($tipoutente == "")
{
    header("location: ../login/login.php?suffisso=" . $_SESSION['suffisso']);
    die;
}

//
//    Parte iniziale della pagina
//

$titolo = "Inserimento e modifica valutazioni proposte per certificazione competenze";
$script = "";

stampa_head($titolo, "", $script, "DS");
stampa_testata("<a href='../login/ele_ges.php'>PAGINA PRINCIPALE</a> - $titolo", "", $_SESSION['nome_scuola'], $_SESSION['comune_scuola']);

//
//    Condizione per classi da selezionare in base al livello scuola
//

if ($_SESSION['livello_scuola'] == 1)
    $annocomp = "anno = '5'";
if ($_SESSION['livello_scuola'] == 2)
    $annocomp = "anno = '3'";
if ($_SESSION['livello_scuola'] == 3)
    $annocomp = "anno = '5' or anno = '8'";
if ($_SESSION['livello_scuola'] == 4)
    $annocomp = "anno = '5'";


$idclasse = stringa_html('idclasse');
$idalunno = stringa_html('idalunno');

$con = mysqli_connect($db_server, $db_user, $db_password, $db_nome) or die("Errore durante la connessione: " . mysqli_error($con));

$id_ut_doc = $_SESSION["idutente"];


print ('
         <form method="post" action="ccproposte.php" name="voti">
   
         <p align="center">
         <table align="center">

         ');



//
//   Leggo il nominativo del docente e lo visualizzo
//

$query = "select cognome, nome from tbl_docenti where idutente='$id_ut_doc'";
$ris = eseguiQuery($con, $query);
if ($nom = mysqli_fetch_array($ris))
{
    $cognomedoc = $nom["cognome"];
    $nomedoc = $nom["nome"];
    $nominativo = $nomedoc . " " . $cognomedoc;
}


print("    
        <tr>
              <td><b>Docente</b></td>

          <td>
          <INPUT TYPE='text' VALUE='$nominativo' disabled>
          <input type='hidden' value='$iddocente' name='iddocente'>
          </td></tr>");

//
//   Classi
//

print('
        <tr>
        <td width="50%"><b>Classe</b></td>
        <td width="50%">
        <SELECT ID="idclasse" NAME="idclasse" ONCHANGE="voti.submit()"><option value="">');

$query = "select distinct tbl_cattnosupp.idclasse, anno, sezione, specializzazione from tbl_cattnosupp, tbl_classi where tbl_cattnosupp.idclasse=tbl_classi.idclasse and iddocente='$id_ut_doc' and ($annocomp) order by anno, sezione, specializzazione";

$ris = eseguiQuery($con, $query);
while ($nom = mysqli_fetch_array($ris))
{
    print "<option value='";
    print ($nom["idclasse"]);
    print "'";
    if ($idclasse == $nom["idclasse"])
    {
        print " selected";
    }
    print ">";
    print ($nom["anno"]);
    print "&nbsp;";
    print($nom["sezione"]);
    print "&nbsp;";
    print($nom["specializzazione"]);
}

echo('
      </SELECT>
      </td></tr>');


//
//  ALUNNI
//

if ($idclasse != '')
{
    if (scrutinio_aperto($idclasse, $_SESSION['numeroperiodi'], $con))
    {
        print('
        <tr>
        <td width="50%"><b>Alunno</b></td>
        <td width="50%">
        <SELECT ID="idalunno" NAME="idalunno" ONCHANGE="voti.submit()"><option value="">');


        $query = "select idalunno, cognome, nome, datanascita from tbl_alunni where idclasse='$idclasse' order by cognome,nome,datanascita";

        $ris = eseguiQuery($con, $query);
        while ($nom = mysqli_fetch_array($ris))
        {
            print "<option value='";
            print ($nom["idalunno"]);
            print "'";
            if ($idalunno == $nom["idalunno"])
            {
                print " selected";
            }
            print ">";
            print ($nom["cognome"]);
            print "&nbsp;";
            print($nom["nome"]);
            print "&nbsp;(";
            print(data_italiana($nom["datanascita"]));
            print ")";
        }

        echo('
      </SELECT>
      </td></tr>');
    } else
        print "<tr><td colspan=2><b><center><br>Scrutinio già chiuso!<br></center></b></td></tr>";
}
echo('</table>
 
       ');

echo('</form><hr>');


//
//  Se è stato selezionato l'alunno si procede all'inserimento/modifica delle
//  valutazioni
//
//Stabilisco il livello scuola


$annoclasse = decodifica_anno_classe($idclasse, $con);

if ($annoclasse == 3 || $annoclasse == 8)
    $livscuola = 2;
if ($annoclasse == 5)
    $livscuola = $_SESSION['livello_scuola'];

if ($idalunno != '')
{
    print "<form name='regprop' action='ccinsproposte.php' method='POST'>";
    print "<input type='hidden' name='iddocente' value='$id_ut_doc'>";
    print "<input type='hidden' name='idalunno' value='$idalunno'>";
    print "<input type='hidden' name='livscuola' value='$livscuola'>";

    print "<table border=1>";
    $query = "select * from tbl_certcompcompetenze where livscuola='$livscuola' and valido order by numprogressivo,idccc";
    $ris = eseguiQuery($con, $query);
    while ($rec = mysqli_fetch_array($ris))
    {
        print "<tr>";
        print "<td valign='middle' width=5%>" . $rec['numprogressivo'] . "</td>";
        if ($rec['compcheuropea'] != '')
        {  // Prevede valutazione sul livello
            print "<td valign='middle' width=25%>" . $rec['compcheuropea'] . "</td>";
            print "<td valign='middle' width=60%>" . $rec['compprofilo'] . "</td>";
            print "<td valign='middle' width=10%>";
            $livellocomp = cerca_livello_prop($con, $idalunno, $id_ut_doc, $rec[idccc]);
            

            $queryliv = "select * from tbl_certcomplivelli where livscuola='$livscuola' order by livello";
            $risliv = eseguiQuery($con, $queryliv);
            print "<select name='selcmp_" . $rec['idccc'] . "'><option value='0'>&nbsp;";
            while ($recliv = mysqli_fetch_array($risliv))
            {
                $codliv = $recliv['idccl'];
                $desliv = $recliv['livello'];
                if ($codliv == $livellocomp)
                    $sel = "selected";
                else
                    $sel = "";
                print "<option value='$codliv' $sel>$desliv</option>";
            }
            print "</select>";
            print "</td>";
        }
        else
        {
            print "<td colspan=3 valign='middle' width=60%>" . $rec['compprofilo'] . ""
                    . "<br><textarea cols=120 name='txtcmp_" . $rec['idccc'] . "'>";

            print cerca_giudizio_prop($con, $idalunno, $id_ut_doc, $rec['idccc']);

            print "</textarea></td>";
        }
        print "</tr>";
    }
    print "</table>";

    print "<center><br><input type='submit' value='Registra proposte'><br>";

    print "</form>";
}



mysqli_close($con);
stampa_piede("");

