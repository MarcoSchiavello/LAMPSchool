<?php

session_start();

/*
  Copyright (C) 2015 Pietro Tamburrano
  Questo programma è un software libero; potete redistribuirlo
  e/o modificarlo secondo i termini della
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

/* programma per la modifica di un docente
  riceve in ingresso iddocente */
@require_once("../php-ini" . $_SESSION['suffisso'] . ".php");
@require_once("../lib/funzioni.php");

// istruzioni per tornare alla pagina di login
////session_start();
$tipoutente = $_SESSION["tipoutente"]; //prende la variabile presente nella sessione
if ($tipoutente == "")
{
    header("location: ../login/login.php?suffisso=" . $_SESSION['suffisso']);
    die;
}
$a = stringa_html('a');
$b = stringa_html('b');
$titolo = "Modifica lezione";
$script = "";
stampa_head($titolo, "", $script, "SDMAP");
stampa_testata("<a href='../login/ele_ges.php'>PAGINA PRINCIPALE</a> - <a href='vis_lez.php?iddocente=$b'>ELENCO LEZIONI</a> - $titolo", "", $_SESSION['nome_scuola'], $_SESSION['comune_scuola']);


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

$sql = "SELECT * from tbl_lezioni where (idlezione='$a')";
$result = eseguiQuery($con, $sql);
$reclez = mysqli_fetch_array($result);
if (!($result))
{
    print("Query fallita");
} else
{
    $data = $reclez['datalezione'];
    $inizio = $reclez['orainizio'];
    $durata = $reclez['numeroore'];

    $giorno = substr($reclez['datalezione'], 8, 2);
    $anno = substr($reclez['datalezione'], 0, 4);
    $mese = substr($reclez['datalezione'], 5, 2);
    //
    //   Inizio visualizzazione della data
    //
    print "<form action='mod_lez_ins.php' method='POST'>";
    print "<table width=50% align=center>";
    print ('         <tr>
         <td width="50%"><b>Data (gg/mm/aaaa)</b></td>');
    echo('   <td width="50%">');
    echo('   <select name="giorno">');
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

    echo('<select name="meseanno">');
    require '../lib/req_aggiungi_mesi_a_select.php';
    /*
      for  ($m = 9; $m <= 12; $m++)
      {
      if ($m < 10)
      {
      $ms = "0" . $m;
      }
      else
      {
      $ms = '' . $m;
      }
      if ($ms == $mese)
      {
      echo("<option selected>$ms - $_SESSION['annoscol']");
      }
      else
      {
      echo("<option>$ms - $_SESSION['annoscol']");
      }
      }
      $_SESSION['annoscol']succ = $_SESSION['annoscol'] + 1;
      for ($m = 1; $m <= 8; $m++)
      {
      if ($m < 10)
      {
      $ms = '0' . $m;
      }
      else
      {
      $ms = '' . $m;
      }
      if ($ms == $mese)
      {
      echo("<option selected>$ms - $_SESSION['annoscol']succ");
      }
      else
      {
      echo("<option>$ms - $_SESSION['annoscol']succ");
      }
      }
     * 
     */
    echo("</select>");
    echo("</td></tr>");

    echo("<tr><td><b>Ore lezione (prima-ultima):</b></td><td>");

    print "<select name='periodo'>";

    for ($i = 1; $i <= $_SESSION['numeromassimoore']; $i++)
    {
        for ($j = $i; $j <= $_SESSION['numeromassimoore']; $j++)
        {
            $strore = "$i-$j";
            if ($i == $inizio & ($j == ($i + $durata - 1)))
            {
                print "<option selected>$strore";
            } else
            {
                print "<option>$strore";
            }
        }
    }

    print "</select>";
}

echo "</td></tr></table>";
print ("<input type='hidden' name='idlezione' value='$a'>");
print ("<input type='hidden' name='iddocente' value='$b'>");
print("<br><div style=\"text-align: center;\"><INPUT TYPE='SUBMIT' VALUE='Modifica'></div>");
print("</form><br/>");

stampa_piede("");
mysqli_close($con);

