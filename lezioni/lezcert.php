<?php

session_start();

/*
  Copyright (C) 2015 Pietro Tamburrano
  Questo programma è un software libero; potete redistribuirlo e/o
  modificarlo modificarlo secondo i termini della
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
////session_start();
$tipoutente = $_SESSION["tipoutente"]; //prende la variabile presente nella sessione
$id_ut_doc = $_SESSION['idutente'];
if ($tipoutente == "")
{
    header("location: ../login/login.php?suffisso=" . $_SESSION['suffisso']);
    die;
}

// preparazione del link per tornare indietro nel registro di classe
$goback = goBackRiepilogoRegistro($con, "Riepilogo registro", "sost");
$classeregistro = $_SESSION['classeregistro'];
$titolo = "Gestione lezione sostegno";
$script = "";
stampa_head($titolo, "", $script, "SDMAP");
stampa_testata("<a href='../login/ele_ges.php'>PAGINA PRINCIPALE</a>$goback[1] - $titolo", "", "$nome_scuola", "$comune_scuola");


$con = mysqli_connect($db_server, $db_user, $db_password, $db_nome) or die("Errore durante la connessione: " . mysqli_error($con));


$cattedra = '';
$giorno = '';
$meseanno = '';
$anno = '';
$mese = '';
$idclasse = '';
$idalunno = '';
$materia = '';
$iddocente = '';
$idlezione = '';
$orainizionew = '';
$orainizioold = '';

// Creo un array per verificare le ore già impegnate da lezioni
$oredisp = array();
$oredisp[] = 9;
for ($i = 1; $i <= $numeromassimoore; $i++)
    $oredisp[] = 0;

// CODICE PER GESTIONE RICHIAMO DA RIEPILOGO


$idlezione = stringa_html('idlezione');


$provenienza = stringa_html('provenienza');

$cattedra = stringa_html('cattedra');
$dat = stringa_html('data');
$giorno = stringa_html('gio');
//$orainizio=isset($_GET['orainizio'])?$_GET['orainizio']:isset($_POST['orainizio'])?$_POST['orainizio']:'';
$meseanno = stringa_html('meseanno');
$orainizionew = stringa_html('orainizionew');
$orainizioold = stringa_html('orainizioold');

$anno = substr($meseanno, 5, 4);
$mese = substr($meseanno, 0, 2);

$giornosettimana = "";


// print "Id lez. $idlezione";
// print "ttttDATI RICEVUTI $cattedra $giorno $meseanno DAT $ms $anno";


if ($idlezione != "")
{
    $query = "select * from tbl_lezionicert where idlezione=$idlezione";
    $ris = eseguiQuery($con, $query);
    $lez = mysqli_fetch_array($ris);
    $materia = $lez['idmateria'];
    $idclasse = $lez['idclasse'];
    $idalunno = $lez['idalunno'];
    $iddocente = $lez['iddocente'];
    $orainizioold = $lez['orainizio'] . "-" . ($lez['orainizio'] - 1 + $lez['numeroore']);
    // $idlezione=$lez['idlezione'];
    $con = mysqli_connect($db_server, $db_user, $db_password, $db_nome) or die("Errore durante la connessione: " . mysqli_error($con));


    $query = "select idcattedra from tbl_cattnosupp where idalunno=$idalunno and idmateria=$materia and iddocente=$iddocente";

    $ris = eseguiQuery($con, $query);
    if ($nom = mysqli_fetch_array($ris))
    {
        $cattedra = $nom['idcattedra'];
    }

    $giorno = substr($lez['datalezione'], 8, 2);
    $anno = substr($lez['datalezione'], 0, 4);
    $mese = substr($lez['datalezione'], 5, 2);
    $giornosettimana = giorno_settimana($anno . "-" . $mese . "-" . $giorno);
    $meseanno = $mese . " - " . $anno;
}

// FINE CODICE PER GESTIONE DA RIEPILOGO
else
{
    if ($cattedra != "" & $giorno != "" & $meseanno != "") // & $orainizio!="")
    {


        $mese = substr($meseanno, 0, 2);
        $anno = substr($meseanno, 5, 4);

        //  $giornosettimana=giorno_settimana($anno."-".$mese."-".$giorno);

        $query = "select idalunno, idmateria from tbl_cattnosupp where idcattedra=$cattedra";

        $ris = eseguiQuery($con, $query);
        if ($nom = mysqli_fetch_array($ris))
        {
            $materia = $nom['idmateria'];
            $idalunno = $nom['idalunno'];
        }
    }
}

if ($giorno == '')
{
    $giorno = date('d');
}
if ($mese == '')
{
    $mese = date('m');
}
if ($anno == '')
{
    $anno = date('Y');
}

print ('
    <form method="post" action="lezcert.php" name="voti">
          <input type="hidden" name="goback" value="' . $goback[0] . '">
          <input type="hidden" name="idclasse" value="' . $idclasse . '">

    <table align="center">');

if ($provenienza != "")
{
    if ($provenienza == 'argo')
    {
        print ("<tr><td colspan=2 align=center><font size=1><a href='riepargomcert.php?idlezione=" . $idlezione . "'>Ritorna a riepilogo</a><br/>&nbsp;</td></tr>");
    } else
    {
        if ($provenienza == 'tabe')
        {
            print ("<tr><td colspan=2 align=center><font size=1><a href='sitleztota.php?idlezione=" . $idlezione . "'>Ritorna a riepilogo</a><br/>&nbsp;</td></tr>");
        }
    }
}


//if ($codlez != "")
//    print ("<tr><td colspan=2 align=center><font size=1><a href='sitleztota.php?idlezione=".$codlez."'>Ritorna a riepilogo</a><br/>&nbsp;</td></tr>");
//if ($dat != "")
//    print ("<tr><td colspan=2 align=center><font size=1><a href='sitleztota.php?classe=$cla&materia=$mat&data=$dat"."'>Ritorna a riepilogo</a><br/>&nbsp;</td></tr>");
print ('         <tr>
         <td width="50%"><b>Data (gg/mm/aaaa)</b></td>');


//
//   Inizio visualizzazione della data
//


echo('   <td width="50%">');
if ($provenienza == "" && $classeregistro == "")
{
    echo('   <select name="gio"  ONCHANGE="voti.submit()">');
} else
{
    print ("<input type='hidden' name='gio' value='$giorno'>");
    echo('   <select name="gio"  disabled ONCHANGE="voti.submit()">');
}
require '../lib/req_aggiungi_giorni_a_select.php';
/*
  for  ($g = 1; $g <= 31; $g++)
  {
  if ($g < 10)
  {
  $gs = '0' . $g;
  }
  else
  {
  $gs = '' . $g;
  }
  if ($gs == $giorno)
  {
  echo("<option selected>$gs</option>");
  }
  else
  {
  echo("<option>$gs</option>");
  }
  }
 * 
 */
echo("</select>");


if ($provenienza == "" && $classeregistro == "")
{
    echo('   <select name="meseanno" ONCHANGE="voti.submit()">');
} else
{
    print ("<input type='hidden' name='meseanno' value='$meseanno'>");
    echo("   <select name='meseanno' disabled ONCHANGE='voti.submit()'>");
}
require '../lib/req_aggiungi_mesi_a_select.php';
/*
  for  ($m = 9; $m <= 12; $m++)
  {
  if ($m < 10)
  {
  $ms = "0" . $m;
  } else
  {
  $ms = '' . $m;
  }
  if ($ms == $mese)
  {
  echo("<option selected>$ms - $annoscol");
  } else
  {
  echo("<option>$ms - $annoscol");
  }
  }
  $annoscolsucc = $annoscol + 1;
  for ($m = 1; $m <= 8; $m++)
  {
  if ($m < 10)
  {
  $ms = '0' . $m;
  } else
  {
  $ms = '' . $m;
  }
  if ($ms == $mese)
  {
  echo("<option selected>$ms - $annoscolsucc");
  } else
  {
  echo("<option>$ms - $annoscolsucc");
  }
  }
 * 
 */
echo("</select>");


//
//  Fine visualizzazione della data
//


echo("        
      </td></tr>");


//
//   Leggo il nominativo del docente e lo visualizzo
//

if ($materia != "" and $idclasse != "")
{
    if ($id_ut_doc != $iddocente)   // Se la visualizzazione avviene di altro docente
    {
        if (cattedra($id_ut_doc, $materia, $idclasse, $con))
        {
            $query = "select iddocente, cognome, nome from tbl_docenti where idutente=$id_ut_doc";
        } else
        {
            $query = "select iddocente, cognome, nome from tbl_docenti where idutente=$iddocente";
        }
    } else
    {
        $query = "select iddocente, cognome, nome from tbl_docenti where idutente=$id_ut_doc";
    }
} else
{
    $query = "select iddocente, cognome, nome from tbl_docenti where idutente=$id_ut_doc";
}

// print $query;
$ris = eseguiQuery($con, $query);
if ($nom = mysqli_fetch_array($ris))
{
    $iddocente = $nom["iddocente"];
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
if ($provenienza == "")
{
    print('
        <tr>
        <td width="50%"><b>Cattedra</b></p></td>
        <td width="50%"> 
        <SELECT ID="cattedra" NAME="cattedra" ONCHANGE="voti.submit()"><option value="">&nbsp;');
} else
{
    print('
        <tr>
        <td width="50%"><b>Cattedra</b></p></td>
        <td width="50%">
        <SELECT ID="cattedra" disabled NAME="cattedra" ONCHANGE="voti.submit()"><option value="">&nbsp;');
}
//
//  Riempimento combobox delle tbl_classi/materie
//
if ($classeregistro == "")
{
    $query = "select idcattedra,tbl_alunni.idalunno,tbl_cattnosupp.idmateria, cognome, nome, datanascita, denominazione
        from tbl_cattnosupp, tbl_alunni, tbl_materie 
        where iddocente=$iddocente 
        and tbl_cattnosupp.idalunno=tbl_alunni.idalunno 
        and tbl_cattnosupp.idmateria = tbl_materie.idmateria 
        order by cognome,nome, denominazione";
} else
{
    $query = "select idcattedra,tbl_alunni.idalunno,tbl_cattnosupp.idmateria, cognome, nome, datanascita, denominazione
        from tbl_cattnosupp, tbl_alunni, tbl_materie
        where iddocente=$iddocente
        and tbl_cattnosupp.idclasse=$classeregistro
        and tbl_cattnosupp.idalunno=tbl_alunni.idalunno
        and tbl_cattnosupp.idmateria = tbl_materie.idmateria
        order by cognome,nome, denominazione";
}


$ris = eseguiQuery($con, $query);
if (mysqli_num_rows($ris) == 1)
{
    $nom = mysqli_fetch_array($ris);
    $strvis = $nom['cognome'] . "&nbsp;" . $nom['nome'] . "&nbsp;" . $nom['datanascita'] . "&nbsp;-&nbsp;" . $nom['denominazione'];

    print "<option value='";
    print ($nom["idcattedra"]);
    print "'";


    print " selected";

    print ">$strvis";
    $idalunno = $nom['idalunno'];
    $materia = $nom['idmateria'];
} else
{
    while ($nom = mysqli_fetch_array($ris))
    {
        print "<option value='";
        print ($nom["idcattedra"]);
        print "'";

        if ($idalunno == $nom["idalunno"] & $materia == $nom["idmateria"])
        {
            print " selected";
        }
        print ">";
        print ($nom["cognome"]);
        print "&nbsp;";
        print($nom["nome"]);
        print "&nbsp;(";
        print($nom["datanascita"]);
        print ")&nbsp;-&nbsp;";
        print($nom["denominazione"]);
    }
}

echo('
      </SELECT>
      </td></tr>');


echo("<tr><td><b>Ore lezione (prima-ultima):</b></td><td>");


if ($idalunno != '' & $materia != '' & $giorno != '' & $mese != '')
{
    // Verifico se esiste già qualche lezione nella giornata

    $query = "select idlezione, orainizio, numeroore from tbl_lezionicert
           where idalunno='$idalunno' and idmateria='$materia' and datalezione='$anno-$mese-$giorno'";
    // print inspref($query);
    $reslezpres = eseguiQuery($con, $query);

    if (mysqli_num_rows($reslezpres) > 0)
    {
        echo("Modif. lez.:");
        if ($orainizionew != "")
        {
            print "<select name='orainizioold' disabled ONCHANGE='voti.submit()'><option value=''>&nbsp;";
        } else
        {
            if ($provenienza == "")
            {
                print "<select name='orainizioold'  ONCHANGE='voti.submit()'><option value=''>&nbsp;";
            } else
            {
                print "<select name='orainizioold' disabled ONCHANGE='voti.submit()'><option value=''>&nbsp;";
            }
        }
        while ($vallezpres = mysqli_fetch_array($reslezpres))
        {
            $strore = $vallezpres['orainizio'] . "-" . ($vallezpres['orainizio'] - 1 + $vallezpres['numeroore']);
            if ($strore != $orainizioold)
            {
                print "<option>" . $strore;
            } else
            {
                print "<option selected>" . $strore;
                // print("<input type='hidden' name='idlezione' value='".$vallezpres['idlezione']."'>");
            }
            for ($i = $vallezpres['orainizio']; $i <= ($vallezpres['orainizio'] - 1 + $vallezpres['numeroore']); $i++)
                $oredisp[$i] = 1;
        }
        print "</select>";
    } else
    {
        $orainizioold = "";
    }

    echo("Nuova lez.:");

    if ($orainizioold != "")
    {
        print "<select name='orainizionew' disabled ONCHANGE='voti.submit()'><option value=''>&nbsp;";
    } else
    {
        if ($provenienza == "")
        {
            print "<select name='orainizionew'  ONCHANGE='voti.submit()'><option value=''>&nbsp;";
        } else
        {
            print "<select name='orainizionew' disabled ONCHANGE='voti.submit()'><option value=''>&nbsp;";
        }
    }

    for ($i = 1; $i <= $numeromassimoore; $i++)
    {
        for ($j = $i; $j <= $numeromassimoore; $j++)
        {
            if (!occupata($oredisp, $i, $j, $numeromaxorelez))
            {
                $strore = "$i-$j";
                if ($strore != $orainizionew)
                {
                    print "<option>$strore";
                } else
                {
                    print "<option selected>$strore";
                }
            }
        }
    }

    print "</select>";
}

echo "</td></tr>";


echo('</table>
 
       <table align="center">
       <td>');
//     <p align="center"><input type="submit" value="Visualizza voti" name="b"></p>
echo('</form></td>
   
       </table><hr>');


if ($mese == "")
{
    $m = 0;
} else
{
    $m = $mese;
}
if ($giorno == "")
{
    $g = 0;
} else
{
    $g = $giorno;
}

if ($anno == "")
{
    $a = 0;
} else
{
    $a = $anno;
}

$giornosettimana = giorno_settimana($anno . "-" . $mese . "-" . $giorno);
//if ($cattedra!="")
//{
//   $query="select * from tbl_cattsupp where iddocente='$iddocente' and idclasse='$idclasse' and idmateria='$materia'";
//   // print inspref($query);
//   $ris=eseguiQuery($con,$query);
//   $numerorighe=mysqli_num_rows($ris);
//}


if (!checkdate($m, $g, $a))
{
    print ("<center> <big><big>Data non corretta!</big></big> </center>");
} else
{
    if ($giornosettimana == "Dom" | giorno_festa($anno . "-" . $mese . "-" . $giorno, $con))
    {
        print ("<center> <big><big>Il giorno selezionato &egrave; festivo!</big></big> </center>");
    }
//else if (($anno.$mese.$giorno)>date("Ymd"))
//   print ("<Center> <big><big>Data selezionata maggiore della data odierna!<small><small> </center>");   
    else
    {
        if (($cattedra != "") & ($orainizioold != "" | $orainizionew != ""))
        {
            // $idclasse=$nome;
            /*  $classe="";

              $query='select * from tbl_classi where idclasse="'.$idclasse.'" ';
              $ris=eseguiQuery($con,$query);
              if($val=mysqli_fetch_array($ris))
              $classe=$val["anno"]." ".$val["sezione"]." ".$val["specializzazione"];

             */
            //
            //    ESTRAZIONE DEI DATI DELLA LEZIONE
            //

            // TTTTT
            // $query="select * from tbl_lezioni where idclasse=".$idclasse." and idmateria=".$materia." and datalezione='".$anno."-".$mese."-".$giorno."'";

            if ($orainizionew != '')
            {
                $postratt = strpos($orainizionew, "-");
                $orainizio = substr($orainizionew, 0, $postratt);
            } else
            {
                $postratt = strpos($orainizioold, "-");
                $orainizio = substr($orainizioold, 0, $postratt);
            }

            if ($idlezione != "")
            {
                $query = "select * from tbl_lezionicert where idlezione='$idlezione'";
            } else
            {
                $query = "select * from tbl_lezionicert where idalunno=$idalunno and idmateria=$materia and orainizio='$orainizio' and datalezione='" . $anno . "-" . $mese . "-" . $giorno . "'";
            }
            // print $query."<br/>";

            $ris = eseguiQuery($con, $query);
            $l = mysqli_fetch_array($ris);

            if ($l != NULL)
            {
                $numeroore = $l['numeroore'];
                // $orainizio=$l['orainizio'];
                $argomenti = $l['argomenti'];
                $attivita = $l['attivita'];
                $idlezione = $l['idlezione'];
                $iddocente = $l['iddocente'];
            } else
            {
                $numeroore = 0;
                // $orainizio=0;
                $argomenti = "";
                $attivita = "";
                $idlezione = '';
            }


            echo "<p align='center'>
        <font size=6>Lezione svolta<br/></font>
    
        <form method='post' action='inslezcert.php'>";
            echo "<table border=2 align='center'>";
            echo "<tr class='prima'><td>Argomenti</td><td>Attivit&agrave;</td></tr>";
            echo "<tr>";

            $durata = 0;
            // TTTT Lezione 10
            if ($orainizionew != '')
            {
                $postratt = strpos($orainizionew, "-");
                $ini = substr($orainizionew, 0, $postratt);
                $fin = substr($orainizionew, $postratt + 1);
                $durata = 1 + ($fin - $ini);
            } else
            {
                $postratt = strpos($orainizioold, "-");
                $ini = substr($orainizioold, 0, $postratt);
                $fin = substr($orainizioold, $postratt + 1);
                $durata = 1 + ($fin - $ini);
            }

            print "<td><input type='hidden' name='orelezione' value='$durata'>";
            print "<textarea cols=50 rows=10 name='argomenti'>";
            print $argomenti;
            print "</textarea></td>";
            print "<td><textarea  cols=50 rows=10 name='attivita'>";
            print $attivita;
            print "</textarea></td>";
            print "</tr></table>";


            echo '
       <table align="center">
       <tr>
       
       
       <p align="center"><input type=hidden value=' . $idalunno . ' name=idalunno>
       <p align="center"><input type=hidden value=' . $giorno . ' name=gio>
       <p align="center"><input type=hidden value=' . $mese . ' name=mese>
       <p align="center"><input type=hidden value=' . $anno . ' name=anno>
       <p align="center"><input type=hidden value=' . $materia . ' name=materia>
       <p align="center"><input type=hidden value=' . $orainizio . ' name=orainizio>
       <p align="center"><input type=hidden value=' . $durata . ' name=orelezione>
       <p align="center"><input type=hidden value=' . $id_ut_doc . ' name=iddocente>
       <p align="center"><input type=hidden value=' . $provenienza . ' name=provenienza>
       <p align="center"><input type=hidden value=' . $idlezione . ' name=codlezione>';

            if (controlla_scadenza($maxgiorniritardolez, $giorno, $mese, $anno))
            {
                if ($iddocente == $id_ut_doc)
                {
                    echo '<p align="center"><input type=submit name=b value="Inserisci lezione">';
                } else
                {
                    if (cattedra($id_ut_doc, $materia, $idalunno, $con))
                    {
                        echo '<p align="center"><input type=submit name=b value="Modifica lezione">';
                    }
                }
            } else
            {
                print '<p align="center"><font color="red"><b>Tempo scaduto per modifica lezione! Rivolgersi a dirigente scolastico!</b></font></p>';
            }



            echo '</form>
       </tr>';
            echo '</table>';
        } else
        {

            print "";
        }
    }
}

mysqli_close($con);
stampa_piede("");

function occupata($oredisp, $i, $j, $maxore)
{
    $occ = false;
    for ($k = $i; $k <= $j; $k++)
        if ($oredisp[$k] == 1)
        {
            $occ = true;
        }
    if (($j - $i + 1) > $maxore)
    {
        $occ = true;
    }
    return $occ;
}

function cattedra($id_ut_doc, $idmateria, $idalunno, $con)
{
    $querycatt = "select * from tbl_cattnosupp where idalunno='$idalunno' and idmateria='$idmateria' and iddocente='$id_ut_doc' and iddocente<>1000000000";
    $riscatt = eseguiQuery($con,$querycatt);
    if (mysqli_num_rows($riscatt) > 0)
    {
        return true;
    } else
    {
        return false;
    }
}
