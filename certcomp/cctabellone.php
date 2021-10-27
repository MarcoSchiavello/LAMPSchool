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

// istruzioni per tornare alla pagina di login se non c'� una sessione valida

$tipoutente = $_SESSION["tipoutente"]; //prende la variabile presente nella sessione
if ($tipoutente == "")
{
    header("location: ../login/login.php?suffisso=" . $_SESSION['suffisso']);
    die;
}

//
//    Parte iniziale della pagina
//

$titolo = "Certificazione competenze";
$script = "";

stampa_head($titolo, "", $script, "SPAD");
stampa_testata("<a href='../login/ele_ges.php'>PAGINA PRINCIPALE</a> - $titolo", "", $_SESSION['nome_scuola'], $_SESSION['comune_scuola']);

if ($_SESSION['livello_scuola'] == 1)
    $annocomp = "anno = '5'";
if ($_SESSION['livello_scuola'] == 2)
    $annocomp = "anno = '3'";
if ($_SESSION['livello_scuola'] == 3)
    $annocomp = "anno = '5' or anno = '8'";
if ($_SESSION['livello_scuola'] == 4)
    $annocomp = "anno = '5'";


$idclasse = stringa_html('idclasse');
$ricarica = stringa_html('ricarica');

$con = mysqli_connect($db_server, $db_user, $db_password, $db_nome) or die("Errore durante la connessione: " . mysqli_error($con));

if ($idclasse != '')
    $scrutiniochiuso = !scrutinio_aperto($idclasse, $_SESSION['numeroperiodi'], $con);
if ($scrutiniochiuso)
{
    $datastampa = data_italiana(estrai_datascrutinio($idclasse, $_SESSION['numeroperiodi'], $con));
    $firmadirig = estrai_dirigente($con);
}

$_SESSION['ccritorno'] = 'tab';


print ('
         <form method="post" action="cctabellone.php" name="voti">
   
         <p align="center">
         <table align="center">

         ');


//
//   Classi
//

print('
        <tr>
        <td width="50%"><b>Classe</b></td>
        <td width="50%">
        <SELECT ID="idclasse" NAME="idclasse" ONCHANGE="voti.submit()"><option value="">');

$query = "select idclasse, anno, sezione, specializzazione from tbl_classi where $annocomp order by anno, sezione, specializzazione";

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
      </td></tr></table><br></form>');


//
//  ALUNNI
//
print "Idclasse : $idclasse";

if ($idclasse != '')
{

    if ($tipoutente == 'S' | $tipoutente == 'P' | $tipoutente == 'D')
    {
        $competenzedes = array();
        $competenzecod = array();

        $annoclasse = decodifica_anno_classe($idclasse, $con);

        if ($annoclasse == 3 || $annoclasse == 8)
            $livscuola = 2;
        if ($annoclasse == 5)
            $livscuola = $_SESSION['livello_scuola'];
        $query = "select * from tbl_certcompcompetenze where livscuola='$livscuola' and valido order by numprogressivo,idccc";
        $ris = eseguiQuery($con, $query);
        while ($rec = mysqli_fetch_array($ris))
        {
            $competenzedes[] = $rec['compcheuropea'];
            $competenzecod[] = $rec['idccc'];
        }
        $query = "select idalunno, cognome, nome, datanascita from tbl_alunni where idclasse='$idclasse' order by cognome,nome,datanascita";

        $ris = eseguiQuery($con, $query);
        $numeroalunni = mysqli_num_rows($ris);

        if ($numeroalunni > 0)
        {
            print("<table border=1>
        <tr class='prima'>
        <td width='50%'><b>Alunno</b></td>");
            foreach ($competenzedes as $descr)
            {
                if ($descr == '')
                    $descr = "Altro";
                print "<td><small><small>$descr<big><big></td>";
            }
            print "<td></td></tr>";
            while ($nom = mysqli_fetch_array($ris))
            {
                $proposteimportate = false;
                $idalunno = $nom['idalunno'];
                $query = "select * from tbl_certcompvalutazioni where idalunno='$idalunno'";
                $ris2 = eseguiQuery($con, $query);
                if (mysqli_num_rows($ris2) == 0)
                {
                    if (importa_proposte($con, $idalunno, $livscuola))
                        $proposteimportate = true;
                }
                print "<tr>";
                print "<td>" . $nom['cognome'] . " " . $nom['nome'] . " (" . data_italiana($nom['datanascita']) . ")</td>";


                foreach ($competenzecod as $codice)
                {
                    if (cerca_competenza_ch_europea($con, $codice) != "")
                        print "<td><small><small>" . decodifica_livello_certcomp($con, cerca_livello_comp($con, $nom['idalunno'], $codice)) . "<big><big></td>";
                    else
                        print "<td><small><small>" . cerca_giudizio_comp($con, $nom['idalunno'], $codice) . "<big><big></td>";
                }
                if ($scrutiniochiuso)
                {
                    print "<td>";
                    print "<a href='./stampacertcomp.php?idalunno=$idalunno&data=$datastampa&firma=$firmadirig' target='_blank'>Stampa</a>&nbsp;";
                    //print "<a href='./ccvalutazioni.php?idalunno=$idalunno&idclasse=$idclasse'>Modifica</a>";
                    print "</td>";
                } else
                {
                    print "<td>";
                    print "<a href='./stampacertcomp.php?idalunno=$idalunno' target='_blank'>Stampa</a>&nbsp;";
                    print "<a href='./ccvalutazioni.php?idalunno=$idalunno&idclasse=$idclasse'>Modifica</a>";
                    print "</td>";
                }
                print "</tr>";
            }
            print "</table>";

            if ($proposteimportate)
                print "<center><font color='green'><big>Proposte importate!</big></font></center><br>";
            else
            if ($ricarica != 'yes')
                print "<center><font color='red'><big>Nessuna nuova proposta presente!</big></font></center><br>";
            else
                print "<center><font color='green'><big>Proposte reimportate!</big></font></center><br>";
            if ($scrutiniochiuso)
            {
                print "<br><br><center><a href='./stampacertcomp.php?classe=$idclasse&data=$datastampa&firma=$firmadirig' target='_blank'>Stampa schede</a><br><br>";
            } else
            {
                print "<form name='ricaricaproposte' action='ricaricapropostecert.php' method='post'>
						 <input type='hidden' name='idclasse' value='$idclasse'>
                                                 <input type='hidden' name='livscuola' value='$livscuola'>    
						 <center><br>ATTENZIONE! La reimportazione delle proposte annullerà eventuali modifiche apportate.<br>
						 <input type='submit' value='Ricarica proposte'></center></form>";


                print "<br><br><center><a href='./stampacertcomp.php?classe=$idclasse' target='_blank'>Stampa schede</a><br><br>";
            }
        }
    }
    if ($tipoutente == 'A')
    {
        $competenzedes = array();
        $competenzecod = array();

        $annoclasse = decodifica_anno_classe($idclasse, $con);

        if ($annoclasse == 3 || $annoclasse == 8)
            $livscuola = 2;
        if ($annoclasse == 5)
            $livscuola = $_SESSION['livello_scuola'];
        $query = "select * from tbl_certcompcompetenze where livscuola='$livscuola' and valido order by numprogressivo,idccc";
        $ris = eseguiQuery($con, $query);
        while ($rec = mysqli_fetch_array($ris))
        {
            $competenzedes[] = $rec['compcheuropea'];
            $competenzecod[] = $rec['idccc'];
        }





        $query = "select idalunno, cognome, nome, datanascita from tbl_alunni where idclasse='$idclasse' order by cognome,nome,datanascita";

        $ris = eseguiQuery($con, $query);
        $numeroalunni = mysqli_num_rows($ris);

        if ($numeroalunni > 0)
        {
            print("<table border=1>
        <tr class='prima'>
        <td width='50%'><b>Alunno</b></td>");
            foreach ($competenzedes as $descr)
            {
                if ($descr == '')
                    $descr = "Altro";
                print "<td><small><small>$descr<big><big></td>";
            }
            print "<td></td></tr>";
            while ($nom = mysqli_fetch_array($ris))
            {
                $proposteimportate = false;
                $idalunno = $nom['idalunno'];
                print "<tr>";
                print "<td>" . $nom['cognome'] . " " . $nom['nome'] . " (" . data_italiana($nom['datanascita']) . ")</td>";


                foreach ($competenzecod as $codice)
                {
                    if (cerca_competenza_ch_europea($con, $codice) != "")
                        print "<td><small><small>" . decodifica_livello_certcomp($con, cerca_livello_comp($con, $nom['idalunno'], $codice)) . "<big><big></td>";
                    else
                        print "<td><small><small>" . cerca_giudizio_comp($con, $nom['idalunno'], $codice) . "<big><big></td>";
                }
                if ($scrutiniochiuso)
                {
                    print "<td>";
                    print "<a href='./stampacertcomp.php?idalunno=$idalunno&data=$datastampa&firma=$firmadirig' target='_blank'>Stampa</a>&nbsp;";
                    //print "<a href='./ccvalutazioni.php?idalunno=$idalunno&idclasse=$idclasse'>Modifica</a>";
                    print "</td>";
                } else
                {
                    print "<td>";
                    print "<a href='./stampacertcomp.php?idalunno=$idalunno' target='_blank'>Stampa</a>&nbsp;";
                    //print "<a href='./ccvalutazioni.php?idalunno=$idalunno&idclasse=$idclasse'>Modifica</a>";
                    print "</td>";
                }
                print "</tr>";
            }
            print "</table>";


            if ($scrutiniochiuso)
            {
                print "<br><br><center><a href='./stampacertcomp.php?classe=$idclasse&data=$datastampa&firma=$firmadirig' target='_blank'>Stampa schede</a><br><br>";
            } else
            {

                print "<br><br><center><a href='./stampacertcomp.php?classe=$idclasse' target='_blank'>Stampa schede</a><br><br>";
            }
        }
    }
}



mysqli_close($con);
stampa_piede("");

