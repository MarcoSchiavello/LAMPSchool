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


$titolo = "Inserimento giustificazioni assenze";
$script = "<script type='text/javascript'>
 <!--
  var stile = 'top=10, left=10, width=600, height=400, status=no, menubar=no, toolbar=no, scrollbars=yes';
     function Popup(apri) {
        window.open(apri, '', stile);
     }
 //-->
</script>";
stampa_head($titolo, "", $script, "SPD");
stampa_testata("<a href='../login/ele_ges.php'>PAGINA PRINCIPALE</a> - $titolo", "", $_SESSION['nome_scuola'], $_SESSION['comune_scuola']);

$idclasse = stringa_html('cl');
$giorno = stringa_html('gio');
$meseanno = stringa_html('meseanno');

$mese = substr($meseanno, 0, 2);
$anno = substr($meseanno, 5, 4);
$data = $anno . "-" . $mese . "-" . $giorno;
$con = mysqli_connect($db_server, $db_user, $db_password, $db_nome) or die("Errore durante la connessione: " . mysqli_error($con));
$elencoalunni = estrai_alunni_classe_data($idclasse, $data, $con);
//PRINT "TTTT ELENCO: $elencoalunni";
$query = "SELECT idalunno AS al,firmapropria FROM tbl_alunni WHERE idalunno IN ($elencoalunni)  ORDER BY cognome, nome, datanascita";
$ris = eseguiQuery($con, $query);
print "<form name='giustass' method='post' action='giustass.php'>";

if ($_SESSION['giustificauscite'] == 'no')
{
    print ("<table width='100%' border='1'><tr class='prima'><td width='50%'>ASSENZE</td><td width='50%'>RITARDI</td></tr>");
} else
{
    print ("<table width='100%' border='1'><tr class='prima'><td width='33%'>ASSENZE</td><td width='33%'>RITARDI</td><td width='34%'>USCITE ANTICIPATE</td></tr>");
}
print ("<tr><td valign='top'>");
while ($recalu = mysqli_fetch_array($ris))
{

    $idalunno = $recalu['al'];
    $firmapropria = $recalu['firmapropria'];
    $query = "select * from tbl_assenze where idalunno=$idalunno and data < '" . $data . "' and (isnull(giustifica) or giustifica=0) order by data ";
    $risass = eseguiQuery($con, $query);
    if (mysqli_num_rows($risass) > 0)
    {
        $datialunno = estrai_dati_alunno($idalunno, $con);
        if ($firmapropria)
            $datialunno .= "<br><small>(autorizzato a firma delle giustifiche)</small>";
        print "<center>$datialunno</center>";
        print "<table align='center' border='1'>
			   <tr class='prima'>
				   <td align='center'>Data assenza</td>
				   <td align='center'>Giustifica</td>
			   </tr>
		   ";
        while ($val = mysqli_fetch_array($risass))
        {
            print "<tr><td align='center'>" . giorno_settimana($val['data']) . " " . data_italiana($val['data']) . "</td><td align='center'><input type=checkbox name='giu" . $val['idassenza'] . "'></td></tr>";
        }

        print "</table><br>";
    }
}

print ("</td>");

print ("<td valign='top'>");
$query = "SELECT idalunno AS al,firmapropria FROM tbl_alunni WHERE idalunno IN (" . $elencoalunni . ")  ORDER BY cognome, nome, datanascita";
$ris = eseguiQuery($con, $query);
while ($recalu = mysqli_fetch_array($ris))
{

    $idalunno = $recalu['al'];
    $firmapropria = $recalu['firmapropria'];
    $query = "select * from tbl_ritardi where idalunno=$idalunno and data <= '" . $data . "' and (isnull(giustifica) or giustifica=0) order by data ";
    $risass = eseguiQuery($con, $query);
    if (mysqli_num_rows($risass) > 0)
    {
        $datialunno = estrai_dati_alunno($idalunno, $con);
        if ($firmapropria)
            $datialunno .= "<br><small>(autorizzato a firma delle giustifiche)</small>";
        print "<center>$datialunno</center>";
        /* print "<table align='center' border='1'>
          <tr class='prima'>
          <td align='center'>Data ritardo</td>
          <td align='center'>Giustifica</td>
          </tr>
          ";
          while($val=mysqli_fetch_array($risass))
          {
          print "<tr><td align='center'>".giorno_settimana($val['data'])." ".data_italiana($val['data'])."</td><td align='center'><input type=checkbox name='giurit".$val['idritardo']."'></td></tr>";
          } */
        print "<table align='center' border='1'>
			   <tr class='prima'>
				   <td align='center'>Data ritardo</td>
				   <td align='center'>Ora ritardo</td>
				   <td align='center'>Giustifica</td>
			   </tr>
		   ";
        while ($val = mysqli_fetch_array($risass))
        {
            print "<tr><td align='center'>" . giorno_settimana($val['data']) . " " . data_italiana($val['data']) . "</td><td align='center'>" . $val['oraentrata'] . "</td><td align='center'><input type=checkbox name='giurit" . $val['idritardo'] . "'></td></tr>";
        }
        print "</table><br>";
    }
}

print ("</td>");

if ($_SESSION['giustificauscite'] == 'yes')
{
    print ("<td valign='top'>");
    $query = "SELECT idalunno AS al,firmapropria FROM tbl_alunni WHERE idalunno IN (" . $elencoalunni . ")  ORDER BY cognome, nome, datanascita";
    $ris = eseguiQuery($con, $query);
    while ($recalu = mysqli_fetch_array($ris))
    {

        $idalunno = $recalu['al'];
        $firmapropria = $recalu['firmapropria'];
        $query = "select * from tbl_usciteanticipate where idalunno=$idalunno and data <= '" . $data . "' and (isnull(giustifica) or giustifica=0) order by data ";
        $risass = eseguiQuery($con, $query);
        if (mysqli_num_rows($risass) > 0)
        {
            $datialunno = estrai_dati_alunno($idalunno, $con);
            if ($firmapropria)
                $datialunno .= "<br><small>(autorizzato a firma delle giustifiche)</small>";
            print "<center>$datialunno</center>";
            print "<table align='center' border='1'>
			   <tr class='prima'>
				   <td align='center'>Data uscita</td>
				   <td align='center'>Ora uscita</td>
				   <td align='center'>Giustifica</td>
			   </tr>
		   ";
            while ($val = mysqli_fetch_array($risass))
            {
                print "<tr><td align='center'>" . giorno_settimana($val['data']) . " " . data_italiana($val['data']) . "</td><td align='center'>" . $val['orauscita'] . "</td><td align='center'><input type=checkbox name='giuusc" . $val['iduscita'] . "'></td></tr>";
            }

            print "</table><br>";
        }
    }

    print ("</td>");
}


print ("</tr>");
print ("</table><br>");

if ($_SESSION['giustificaasslezione']=='yes')
{
    print "<table border='1' align='center'><tr class='prima'><td>Assenze a lezioni in DAD</td></tr>";
    print "<tr><td>";
    $query = "SELECT idalunno AS al,firmapropria FROM tbl_alunni WHERE idalunno IN (" . $elencoalunni . ")  ORDER BY cognome, nome, datanascita";
    $ris = eseguiQuery($con, $query);
    while ($recalu = mysqli_fetch_array($ris))
    {
        $idalunno = $recalu['al'];
        $firmapropria = $recalu['firmapropria'];
        $query = "select * from tbl_asslezione where idalunno=$idalunno and data < '" . $data . "' and (isnull(giustifica) or giustifica=0) "
                . " and data not in (select data from tbl_assenze where idalunno=$idalunno)"
                    . " and data in (select datadad from tbl_dad where idclasse=$idclasse)"
                . " order by data ";
        
       // print inspref($query);
        $risass = eseguiQuery($con, $query);
        if (mysqli_num_rows($risass) > 0)
        {
            $datialunno = estrai_dati_alunno($idalunno, $con);
            if ($firmapropria)
                $datialunno .= "<br><small>(autorizzato a firma delle giustifiche)</small>";
            print "<center>$datialunno</center>";
            print "<table align='center' border='1'>
			   <tr class='prima'>
				   <td align='center'>Data assenza</td>
                                   <td align='center'>Materia</td>
				   <td align='center'>Numero ore</td>
				   <td align='center'>Giustifica</td>
			   </tr>
		   ";
            while ($val = mysqli_fetch_array($risass))
            {
              //  if (lezione_dad($idclasse, $val['data'], $con))
                   print "<tr><td align='center'>" . giorno_settimana($val['data']) . " " . data_italiana($val['data']) . "</td><td align='center'>" . decodifica_materia(estrai_materia_lezione($val['idlezione'], $con),$con) . "</td><td align='center'>" . $val['oreassenza'] . "</td><td align='center'><input type=checkbox name='giuassl" . $val['idassenzalezione'] . "'></td></tr>";
            }

            print "</td></tr></table><br>";
        }
    }
    print "</table><br>";
}

print "<input type='hidden' name='idclasse' value='$idclasse'>";
print "<input type='hidden' name='data' value='$data'>";
print "<center><input type=submit value='Registra giustificazioni'></center></form>";
// fine if

mysqli_close($con);
stampa_piede("");

