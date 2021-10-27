<?php

require_once '../lib/req_apertura_sessione.php';

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


@require_once("../php-ini" . $_SESSION['suffisso'] . ".php");
@require_once("../lib/funzioni.php");

// istruzioni per tornare alla pagina di login se non c'è una sessione valida

$tipoutente = $_SESSION["tipoutente"]; //prende la variabile presente nella sessione
if ($tipoutente == "")
{
    header("location: ../login/login.php?suffisso=" . $_SESSION['suffisso']);
    die;
}


$titolo = "Inserimento lezione alunno certificato";
$script = "";
stampa_head($titolo, "", $script, "SDMAP");
stampa_testata("<a href='../login/ele_ges.php'>PAGINA PRINCIPALE</a> - $titolo", "", $_SESSION['nome_scuola'], $_SESSION['comune_scuola']);


$ins = false;
$gio = stringa_html('gio');
$mese = stringa_html('mese');
$anno = stringa_html('anno');
$codlez = stringa_html('codlezione');
$materia = stringa_html('materia');
$iddocente = stringa_html('iddocente');
$data = $anno . "-" . $mese . "-" . $gio;
$idalunno = stringa_html('idalunno');
$argomenti = elimina_apici(stringa_html('argomenti'));
$attivita = elimina_apici(stringa_html('attivita'));
$numeroore = stringa_html('orelezione');
$orainizio = stringa_html('orainizio');
$provenienza = stringa_html('provenienza');


//print "Numero ore".$numeroore;
//print $codlez;

$con = mysqli_connect($db_server, $db_user, $db_password, $db_nome) or die("Errore durante la connessione: " . mysqli_error($con));
$idclasse = estrai_classe_alunno($idalunno, $con);


// INSERIMENTO, CANCELLAZIONE O UPDATE DATI LEZIONE   DA RIVEDERE PER INSERIMENTO PRESENZA
$ope = '';
if ($codlez != '')
{

    if ((($argomenti != "") | ($attivita != "")) | ($numeroore != ""))
    {
        $ope = 'U';
        $query = "update tbl_lezionicert
               set numeroore='$numeroore',orainizio='$orainizio',argomenti='$argomenti',attivita='$attivita' 
               where idlezione=$codlez";
    } else
    {
        $ope = 'D';
        $query = "delete from tbl_lezionicert where idlezione=$codlez";
    }
} else
{
    $ope = 'I';
    $query = "insert into tbl_lezionicert(idclasse,datalezione,iddocente,idmateria,idalunno,numeroore,orainizio,argomenti,attivita) values ('$idclasse','$data','$iddocente','$materia','$idalunno','$numeroore','$orainizio','" . elimina_apici($argomenti) . "','" . elimina_apici($attivita) . "')";
}
//eseguiQuery($con,$query); 

if ($ope == 'I')
{
    $ris3 = eseguiQuery($con, $query);
    $codlez = mysqli_insert_id($con);

    $verlez = verifica_lezione_normale($idclasse, $data, $iddocente, $materia, $numeroore, $orainizio, $con, $codlez, $idalunno);
    if (!gestione_manuale_assenze($idclasse, $data, $con))
        ricalcola_assenze_lezioni_classe($con, $idclasse, $data);
    print "<center><b>Inserimento effettuato!</b></center>";
    switch ($verlez)
    {
        case 1:
            print "<center><b><br><br>Aggiunta firma a registro di classe!</b></center>";
            break;
        case 0:
            print "<center><b><br><br>Firma nel registro di classe già esistente!</b></center>";
            break;
        case 2:
            print "<center><b><br><br>Aggiunta lezione nel registro di classe!</b></center>";
            break;
        case 3:
            print "<center><b><br><br>Aggiunta firma a registro di classe!<br></b></center>";
            break;
        case 4:
            print "<center><b><br><br>Firmate lezioni intermedie della stessa materia!<br></b></center>";
            break;
        case 5:

            print "<center><b><br><br>ATTENZIONE! Lezione per stessa materia già inserita in altre ore!<br>Verificare correttezza registro.</b></center>";

            break;
    }
}
if ($ope == 'U')
{

    $ris3 = eseguiQuery($con, $query);

    print "<center><b>Aggiornamento effettuato!</b></center>";
}
if ($ope == 'D')
{
    $ris3 = eseguiQuery($con, $query);
    print "<center><b>Cancellazione effettuata!</b></center>";
}


echo "<p align='center'>";

if ($_SESSION['regcl'] != "")
{
    $pr = $_SESSION['prove'];
    $cl = $_SESSION['regcl'];
    $ma = $_SESSION['regma'];
    $gi = $_SESSION['reggi'];
    $_SESSION['regcl'] = "";
    $_SESSION['regma'] = "";
    $_SESSION['reggi'] = "";

    print "
			  <form method='post' id='formlez' action='../regclasse/$pr'>
			  <input type='hidden' name='gio' value='$gi'>
			  <input type='hidden' name='meseanno' value='$ma'>
			  <input type='hidden' name='idclasse' value='$cl'>
			  <input type='hidden' name='materia' value='$materia'>
			  <input type='hidden' name='provenienza' value='$provenienza'>
			  <br><div style=\"text-align: center;\"><input type='submit' value='OK'></div>
			  </form>
			  ";
} else
{
    //  codice per richiamare il form delle tbl_lezioni;
    //  tttt se si viene dal riepilogo ritornare al riepilogo passando l'idlezione
    print ("
   <form action='lezcert.php' method='POST'>
   <input type='hidden' name='materia' value='$materia'>
   <input type='hidden' name='provenienza' value='$provenienza'>
   <p align='center'>");

    // Se la lezione non è stata cancellata si passa il codice
    if ($ope != 'D')
    {
        print ('<p align="center"><input type=hidden value=' . $codlez . ' name=idlezione>');
    }

    print('<input type="submit" value="OK" name="b"></p></form>');
}

stampa_piede("");

function verifica_lezione_normale($idclasse, $data, $iddocente, $materia, $numeroore, $orainizio, $conn, $codlez, $idalunno)
{
    //
    //  Return
    //  0 - Firma già esistente
    //  1 - firma aggiunta
    //  2 - Firma lezione aggiunta
    //  3 - Situazione da gestire manualmente (lezioni non coincidenti)
    //  4 - Lezioni intermedie della stessa materia
    //  5 - Situazione anomala da verificare
    //  6 - La lezione normale è una supplenza
    // VERIFICO SE LA LEZIONE ESISTE CON ORARI IDENTICI
    $query = "select * from tbl_lezioni
	        where idclasse=$idclasse
	        and datalezione='$data'
	        and idmateria=$materia
	        and numeroore=$numeroore
	        and orainizio=$orainizio";
    $ris = eseguiQuery($conn, $query);

    if (mysqli_num_rows($ris) == 1)
    {
        $rec = mysqli_fetch_array($ris);
        $idlezione = $rec['idlezione'];
        // VERIFICO SE ESISTE GIA' LA FIRMA
        $query = "select * from tbl_firme
	        where idlezione=$idlezione
	        and iddocente=$iddocente";
        $ris = eseguiQuery($conn, $query);

        if (mysqli_num_rows($ris) == 0)
        {
            // INSERISCO LA FIRMA E MEMORIZZO ID LEZIONE NORM SE NON ESISTE
            $query = "insert into tbl_firme(idlezione,iddocente) values ($idlezione,$iddocente)";
            eseguiQuery($conn, $query);
            $query = "update tbl_lezionicert set idlezionenorm=$idlezione
                      where idlezione=$codlez";
            eseguiQuery($conn, $query);
            return 1;
        } else
        {
            // TTTT 17/11/2018
            $query = "update tbl_lezionicert set idlezionenorm=$idlezione
                      where idlezione=$codlez";
            eseguiQuery($conn, $query);
        }
        return 0;
    }


    // VERIFICO SE ESISTE LEZIONE PER LA MATERIA IN ORARI SOVRAPPONIBILI
    // A QUELLI DELLA LEZIONE DI SOSTEGNO
    $query = "select * from tbl_lezioni
	        where idclasse=$idclasse
	        and datalezione='$data'
	        and idmateria=$materia
	        and orainizio<=$orainizio
	        and (orainizio+numeroore-1)>=($orainizio+$numeroore-1)";
    $ris = eseguiQuery($conn, $query);
    if (mysqli_num_rows($ris) == 1)
    {
        $rec = mysqli_fetch_array($ris);
        $orainizionorm = $rec['orainizio'];
        $numeroorenorm = $rec['numeroore'];
        $idlezioneorig = $rec['idlezione'];
        $idclasseorig = $rec['idclasse'];
        $datalezioneorig = $rec['datalezione'];
        $iddocenteorig = $rec['iddocente'];
        $idmateriaorig = $rec['idmateria'];
        //
        // INIZIO COINCIDENTE MA DURATA INFERIORE
        //

        if ($orainizio == $orainizionorm)
        {
            // cambio numero di ore a lezione originale
            $query = "update tbl_lezioni set numeroore=$numeroore where idlezione=$idlezioneorig";
            eseguiQuery($conn, $query);
            $query = "update tbl_lezionicert set idlezionenorm=$idlezioneorig
                      where idlezione=$codlez";
            eseguiQuery($conn, $query);

            // inserisco lezione senza docente sostegno
            $numorenuo = $numeroorenorm - $numeroore;
            $orainizionuo = $orainizionorm + $numeroore;
            $query = "insert into tbl_lezioni(idclasse,datalezione,iddocente,idmateria,numeroore,orainizio)
	                           values ($idclasseorig,'$datalezioneorig',$iddocenteorig,$idmateriaorig,$numorenuo,$orainizionuo)";
            eseguiQuery($conn, $query);
            $idnuovalezione = mysqli_insert_id($conn);



            // estraggo le firme della lezione originale

            $query = "select * from tbl_firme where idlezione=$idlezioneorig";
            $ris = eseguiQuery($conn, $query);
            $codfirme = array();
            while ($rec = mysqli_fetch_array($ris))
            {
                $codfirme[] = $rec['iddocente'];
            }
            // inserisco le firme per la nuova lezione e aggiorno idlezionenorm
            for ($i = 0; $i < count($codfirme); $i++)
            {
                $query = "insert into tbl_firme(idlezione,iddocente)
				 	                             values($idnuovalezione," . $codfirme[$i] . ")";
                eseguiQuery($conn, $query);
            }

            // INSERISCO LA FIRMA DEL DOCENTE DI SOSTEGNO PER LA LEZIONE INIZIALE
            $query = "insert into tbl_firme(idlezione,iddocente) values ($idlezioneorig,$iddocente)";
            eseguiQuery($conn, $query);
        }

        //
        // INIZIO NON COINCIDENTE MA FINE COINCIDENTE
        //
        else
        {
            if (($orainizio + $numeroore) == ($orainizionorm + $numeroorenorm))
            {
                // cambio numero di ore a lezione originale
                $query = "update tbl_lezioni set numeroore=" . ($numeroorenorm - $numeroore) . " where idlezione=$idlezioneorig";
                eseguiQuery($conn, $query);


                // inserisco lezione con docente di sostegno
                $numorenuo = $numeroore;
                $orainizionuo = $orainizio;
                $query = "insert into tbl_lezioni(idclasse,datalezione,iddocente,idmateria,numeroore,orainizio)
	                           values ($idclasseorig,'$datalezioneorig',$iddocenteorig,$idmateriaorig,$numorenuo,$orainizionuo)";
                eseguiQuery($conn, $query);
                $idnuovalezione = mysqli_insert_id($conn);
                $query = "update tbl_lezionicert set idlezionenorm=$idnuovalezione
                      where idlezione=$codlez";
                eseguiQuery($conn, $query);
                // estraggo i codici degli alunni con assenze lezione
                $query = "select * from tbl_asslezione where idlezione=$idlezioneorig";
                $ris = eseguiQuery($conn, $query);
                $codalunni = array();
                $oreass = array();
                while ($rec = mysqli_fetch_array($ris))
                {
                    $codalunni[] = $rec['idalunno'];
                    $oreass[] = $rec['oreassenza'];
                }

                // estraggo le firme della lezione originale

                $query = "select * from tbl_firme where idlezione=$idlezioneorig";
                $ris = eseguiQuery($conn, $query);
                $codfirme = array();
                while ($rec = mysqli_fetch_array($ris))
                {
                    $codfirme[] = $rec['iddocente'];
                }
                // inserisco le firme per la nuova lezione
                for ($i = 0; $i < count($codfirme); $i++)
                {
                    $query = "insert into tbl_firme(idlezione,iddocente)
				 	                             values($idnuovalezione," . $codfirme[$i] . ")";
                    eseguiQuery($conn, $query);
                }

                // INSERISCO LA FIRMA DEL DOCENTE DI SOSTEGNO PER LA NUOVA LEZIONE
                $query = "insert into tbl_firme(idlezione,iddocente) values ($idnuovalezione,$iddocente)";
                eseguiQuery($conn, $query);
            }

            //
            // LEZIONE DI SOSTEGNO INTERMEDIA
            //
            else
            {
                // cambio numero di ore a lezione originale
                $query = "update tbl_lezioni set numeroore=" . ($orainizio - $orainizionorm) . " where idlezione=$idlezioneorig";
                eseguiQuery($conn, $query);


                // inserisco lezione con docente di sostegno
                $numorenuo = $numeroore;
                $orainizionuo = $orainizio;
                $query = "insert into tbl_lezioni(idclasse,datalezione,iddocente,idmateria,numeroore,orainizio)
	                           values ($idclasseorig,'$datalezioneorig',$iddocenteorig,$idmateriaorig,$numorenuo,$orainizionuo)";
                eseguiQuery($conn, $query);
                $idnuovalezione = mysqli_insert_id($conn);
                $query = "update tbl_lezionicert set idlezionenorm=$idnuovalezione
                      where idlezione=$codlez";
                eseguiQuery($conn, $query);

                // estraggo le firme della lezione originale

                $query = "select * from tbl_firme where idlezione=$idlezioneorig";
                $ris = eseguiQuery($conn, $query);
                $codfirme = array();
                while ($rec = mysqli_fetch_array($ris))
                {
                    $codfirme[] = $rec['iddocente'];
                }
                // inserisco le firme per la nuova lezione
                for ($i = 0; $i < count($codfirme); $i++)
                {
                    $query = "insert into tbl_firme(idlezione,iddocente)
				 	                             values($idnuovalezione," . $codfirme[$i] . ")";
                    eseguiQuery($conn, $query);
                }

                // INSERISCO LA FIRMA DEL DOCENTE DI SOSTEGNO PER LA NUOVA LEZIONE
                $query = "insert into tbl_firme(idlezione,iddocente) values ($idnuovalezione,$iddocente)";
                eseguiQuery($conn, $query);

                // CREO LA TERZA LEZIONE (DOPO IL SOSTEGNO)

                $numorenuo = ($orainizionorm + $numeroorenorm - ($orainizio + $numeroore));
                $orainizionuo = $orainizio + $numeroore;
                $query = "insert into tbl_lezioni(idclasse,datalezione,iddocente,idmateria,numeroore,orainizio)
	                           values ($idclasseorig,'$datalezioneorig',$iddocenteorig,$idmateriaorig,$numorenuo,$orainizionuo)";
                eseguiQuery($conn, $query);
                $idnuovalezione = mysqli_insert_id($conn);

                // inserisco le firme per la nuova lezione
                for ($i = 0; $i < count($codfirme); $i++)
                {
                    $query = "insert into tbl_firme(idlezione,iddocente)
				 	                             values($idnuovalezione," . $codfirme[$i] . ")";
                    eseguiQuery($conn, $query);
                }
            }
        }

        return 3;
    } else
    {
        $rec = mysqli_fetch_array($ris);
        $idlezione = $rec['idlezione'];
        // VERIFICO SE ESISTE LEZIONE PER LA MATERIA IN ORARI
        // NON SOVRAPPONIBILI
        // A QUELLI DELLA LEZIONE DI SOSTEGNO
        // MA NELLA STESSA DATA
        $query = "select * from tbl_lezioni
	            where idclasse=$idclasse
	            and datalezione='$data'
	            and idmateria=$materia";
        $ris = eseguiQuery($conn, $query);

        if (mysqli_num_rows($ris) == 0)
        {
            // INSERISCO LEZIONE E FIRMA PERCHE' NELLA GIORNATA
            // NON CI SONO ANCORA LEZIONI DELLA MATERIA
            $query = "insert into tbl_lezioni(iddocente,idmateria,idclasse,datalezione,orainizio,numeroore)
                                   values ($iddocente,$materia,$idclasse,'$data',$orainizio,$numeroore)";
            eseguiQuery($conn, $query);
            $idlezione = mysqli_insert_id($conn);
            $query = "insert into tbl_firme(idlezione,iddocente) values ($idlezione,$iddocente)";
            eseguiQuery($conn, $query);
            $query = "update tbl_lezionicert set idlezionenorm=$idlezione
                      where idlezione=$codlez";
            eseguiQuery($conn, $query);
            return 2;
        } else
        {
            //
            //  Verifico se ci sono lezioni divise intermedie per la stessa materia
            //  Es. Ita 2-2 e Ita 3-3  - Sost. Ita 2-3

            $query = "select * from tbl_lezioni
			           where idclasse=$idclasse
	                 and datalezione='$data'
	                 and idmateria=$materia
	                 and orainizio>=$orainizio
	                 and (orainizio+numeroore-1)<=($orainizio+$numeroore-1)";
            $ris = eseguiQuery($conn, $query);
            if (mysqli_num_rows($ris) > 0)
            {

                // SPEZZO ANCHE LA LEZIONE DEL SOSTEGNO

                $rec = mysqli_fetch_array($ris);
                $idlezfirma = $rec['idlezione'];
                $orainizio = $rec['orainizio'];
                $numeroore = $rec['numeroore'];
                $query = "insert into tbl_firme(idlezione,iddocente)
				 	                             values($idlezfirma,$iddocente)";
                eseguiQuery($conn, $query);
                // Modifico la lezione di sostegno originale
                $query = "update tbl_lezionicert
                        set idlezionenorm=$idlezfirma,
                            orainizio=$orainizio,
                            numeroore=$numeroore
                            where idlezione=$codlez";
                eseguiQuery($conn, $query);
                while ($rec = mysqli_fetch_array($ris))
                {
                    // Inserisco la firma per la "sottolezione"
                    $idlezfirma = $rec['idlezione'];
                    $orainizio = $rec['orainizio'];
                    $numeroore = $rec['numeroore'];
                    $idmateria = $rec['idmateria'];
                    $datalezione = $rec['datalezione'];
                    $query = "insert into tbl_firme(idlezione,iddocente)
				 	                             values($idlezfirma,$iddocente)";
                    // print ("tttt ".inspref($query));
                    // Inserisco la sottolezione di sostegno
                    eseguiQuery($conn, $query);
                    $query = "insert into tbl_lezionicert(iddocente,datalezione,orainizio,numeroore,idmateria,idlezionenorm,idalunno)
				 	                             values($iddocente,'$datalezione',$orainizio,$numeroore,$idmateria,$idlezfirma,$idalunno)";
                    // print ("tttt ".inspref($query));
                    eseguiQuery($conn, $query);
                }
                return 4;
            }
            $query = "insert into tbl_lezioni(iddocente,idmateria,idclasse,datalezione,orainizio,numeroore)
                                   values ($iddocente,$materia,$idclasse,'$data',$orainizio,$numeroore)";
            eseguiQuery($conn, $query);
            $idlezione = mysqli_insert_id($conn);
            $query = "insert into tbl_firme(idlezione,iddocente) values ($idlezione,$iddocente)";
            eseguiQuery($conn, $query);
            $query = "update tbl_lezionicert set idlezionenorm=$idlezione
                      where idlezione=$codlez";
            eseguiQuery($conn, $query);
            return 5;
        }
    }
}
