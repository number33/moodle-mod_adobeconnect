<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/*
* Italian Translation by Rabellino[at]di.unito.it
* Computer Science Department of Torino
*/

$string['admin_httpauth'] = 'HTTP Authentication Header';
$string['admin_httpauth_desc'] = 'Il valore di HTTP_AUTH_HEADER utilizzato nel file custom.ini. Per informazioni: <a href=\"http://docs.moodle.org/en/Remote_learner_adobe_connect_pro\">Help page</a>';
$string['admin_login'] = 'Login Amministratore';
$string['admin_login_desc'] = 'Login main admin account';
$string['admin_password'] = 'Password Amministratore';
$string['admin_password_desc'] = 'Password per il main admin account';
$string['adobeconnect'] = 'Adobe Connect';
$string['adobeconnectfieldset'] = 'Impostazioni Adobe Connect';
$string['adobeconnecthost'] = 'Organizzatore';
$string['adobeconnecthostdescription'] = 'Un organizzatore assegna privilegi agli utenti, inizia e termina una riunione, oltre a poter agire come Relatore';
$string['adobeconnectintro'] = 'Introduzione';
$string['adobeconnectname'] = 'Nome della riunione';
$string['adobeconnectparticipant'] = 'Partecipante';
$string['adobeconnectparticipantdescription'] = 'Partecipa ad una riunione, ma non ne modifica le impostazioni';
$string['adobeconnectpresenter'] = 'Relatore';
$string['adobeconnectpresenterdescription'] = 'Il relatore di una riunione utilizza contenuti, condivide lo schermo del proprio computer, invia messaggi, modera domande, crea note, condivide audio e video ed invia contenuti tramite link web';
$string['duplicatemeetingname'] = 'Il nome di riunione &egrave; gia in uso, vi preghiamo di modificarlo';
$string['duplicateurl'] = 'La URL della riunione &egrave; gia in uso, vi preghiamo di modificarla';
$string['email_login'] = 'Login tramite Email address';
$string['email_login_desc'] = 'Selezionate questa opzione solo se il login al server Connect Pro &egrave; stato impostato all\'uso dell\'email address. Attenzione che modificando '.
                              'questa opzione durante il normale utilizzo di questo modulo, si possono creare duplicati di utenti sul server Connect';
$string['endtime'] = 'Termine';
$string['host'] = 'Server';
$string['host_desc'] = 'Indica il server dove indirizzare le richieste amministrative';
$string['joinmeeting'] = 'Entra nella Riunione';
$string['meethost_desc'] = 'Indica il server su cui si trova Adobe';
$string['meetinghost'] = 'Server Domain';
$string['meetingend'] = 'Termine Riunione';
$string['meetingintro'] = 'Sommario della Riunione';
$string['meetingname'] = 'Nome della Riunione';
$string['meetingstart'] = 'Inizio Riunione';
$string['meetingtype'] = 'Tipo di Riunione';
$string['modulename'] = 'Adobe Connect';
$string['modulenameplural'] = 'Adobe Connect';
$string['meettemplates'] = 'Modelli di Riunione';
$string['meeturl'] = 'URL della Riunione';
$string['port'] = 'Porta';
$string['port_desc'] = '&Egrave; la porta TCP utilizzata per connettersi al server Adobe Connect';
$string['recordinghdr'] = 'Registrazioni della Riunione';
$string['samemeettime'] = 'Data della Riunione non corretta';
$string['selectparticipants'] = 'Assegna ruoli';
$string['starttime'] = 'Inizio';
$string['usergrouprequired'] = 'Questa Riunione righiede che gli utenti siano inseriti in un gruppo per potersi collegare';
$string['testconnection'] = 'Test Connessione';
$string['connectiontesttitle'] = 'Test Connessione Adobe Connect';
$string['conntestintro'] = '<p>Saranno condotti una serie di test al fine di verificare la corretta impostazione del modulo'.
' e per determinare se le credenziali fornite hanno diritti sufficienti sul server Adobe connect per garantire il funzionamento della attivita.'.
' Se uno dei test dovesse fallire, si rende necessario operare per risolverli, o altrimenti il modulo non funzioner&agrave;a</p><p> Per ulteriori informazioni e assistenza su come impostare il server'.
' Adobe Connect Pro, &egrave; possibile consultare le pagine presenti su Moodledocs per questo modulo <a href="{$a->url}">Help page</a></p>';
$string['greaterstarttime'] = 'L\'orario di inizio non puo essere maggiore di quello di fine';
$string['invalidadobemeeturl'] = 'Il valore inserito non e corretto.  Per maggiori informazioni potete selezionare il pulsante di help relativo a questo campo';

$string['adobeconnect:meetingpresenter'] = 'Relatore';
$string['adobeconnect:meetingparticipant'] = 'Partecipante';
$string['adobeconnect:meetinghost'] = 'Organizzatore';

// Error codes.
$string['emptyxml'] = 'Impossibile connettersi al server Adobe Connect Pro.  Riprovate piu tardi e, se il problema dovesse persistere, informate l\'amministratore del sistema.';
$string['adminemptyxml'] = 'Impossibile connettersi al server  Adobe Connect Pro.  Selezionate il link qui sotto per verificare le impostazioni del modulo e provare la connessione';
$string['notsetupproperty'] = 'Il modulo non &egrave; correttamente configurato.  Informate l\'amministratore del sistema Moodle';
$string['adminnotsetupproperty'] = 'Il modulo non &egrave; correttamente configurato.  Selezionate il link qui sotto per verificare le impostazioni del modulo e provare la connessione';

// ADDED FOR COMPLETENESS BY RABSER.
$string['public'] = 'Riunione Pubblica';
$string['private'] = 'Riunione Privata';
$string['notparticipant'] = 'L\' utente non &egrave; iscritto a partecipare a questa riunione'; // The join.php 'You are not a participant for this meeting'.
$string['unableretrdetails'] = 'Impossibile reperire i dettagli per questa riunione'; // The join.php 'Unable to retrieve meeting details'.
$string['nopresenterrole'] = 'Il ruolo Adobe Presenter non &egrave definito nel sistema: segnalate all\' amministratore il problema.'; // The view.php 'error: error finding adobeconnectpresenter role'.
$string['nomeeting'] = 'La riunione richiesta non esiste sul server'; // The view.php 'No meeting exists on the server'.
