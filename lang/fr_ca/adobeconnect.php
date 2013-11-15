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

$string['admin_httpauth'] = 'Authentification dans l\'entête HTTP';
$string['admin_httpauth_desc'] = 'La valeur de la variable HTTP_AUTH_HEADER dans le fichier custom.ini du serveur Adobe Connect';
$string['admin_login'] = 'Nom d\'utilisateur administrateur';
$string['admin_login_desc'] = 'Nom d\'utilisateur du compte administrateur principal';
$string['admin_password'] = 'Mot de passe administrateur';
$string['admin_password_desc'] = 'Mot de passe du compte administrateur principal';
$string['adminemptyxml'] = 'Impossible de se connecter au serveur Adobe Connect pour le moment. Cliquer sur continuer et aller dans la page d\'administration de l\'activité pour tester la connexion';
$string['adminnotsetupproperty'] = 'Le module d\'activité n\'est pas configuré correctement. Cliquer sur continuer et aller dans la page d\'administration de l\'activité pour tester la connexion';
$string['adobeconnect'] = 'Adobe Connect';
$string['adobeconnect:meetinghost'] = 'Hôte de réunion';
$string['adobeconnect:meetingparticipant'] = 'Particpant de réunion';
$string['adobeconnect:meetingpresenter'] = 'Présentateur de réunion';
$string['adobeconnectfieldset'] = 'Réglages Adobe Connect';
$string['adobeconnecthost'] = 'Hôte Adobe Connect';
$string['adobeconnecthostdescription'] = 'En plus d\'avoir les privilèges du présentateur, l\'hôte peut attribuer des privilèges aux utilisateurs ainsi que débuter et terminer une réunion';
$string['adobeconnectintro'] = 'Description';
$string['adobeconnectname'] = 'Nom de la réunion';
$string['adobeconnectparticipant'] = 'Participant Adobe Connect';
$string['adobeconnectparticipantdescription'] = 'Le participant peut voir une réunion, mais ne peut modifier les réglages';
$string['adobeconnectpresenter'] = 'Présentateur Adobe Connect';
$string['adobeconnectpresenterdescription'] = 'Le présentateur d\'une réunion peut présenter du contenu, partager un écran, envoyer des messages textes, modérer les questions, créer des notes, diffuser du contenu audio et vidéo et pousser du contenu provenant d\'un site Web';
$string['connectiontesttitle'] = 'Fenêtre de test de connexion';
$string['conntestintro'] = '<p>Une série de tests a été exécutée afin de déterminer si le serveur Adobe Connect a été convenablement configuré pour que cette intégration fonctionne et aussi pour déterminer si les informations de l\'utilisateur fournit dans les paramètres a les permissions nécessaires pour effectués les taĉhes requisent par le module d\'activité. S\'il y a échec dans les tests ci-dessous, le module d\'activité ne fonctionnera pas correctement.</p><p>Pour obtenir de l\'aide et de la documentation sur comment configurer le serveur Adobe Connect, s\'il vous plait consulter la page d\'aide de la documentation Moodle pour le module d\'activité <a href="{$a->url}">Page d\'aide</a></p>';
$string['duplicatemeetingname'] = 'Une réunion ayant le même nom existe sur le serveur';
$string['duplicateurl'] = 'Une réunion ayant le même URL existe sur le serveur';
$string['email_login'] = 'Nom d\'utilisateur avec adresse de courriel';
$string['email_login_desc'] = 'Cochez cette option seulement si votre serveur Adobe Connect est configuré pour utiliser les noms d\'utilisateur avec adresse de courriel. Note : Modifier cette configuration durant l\'utilisation du module peut potentiellement créer des doublons de réunions au niveau du serveur Adobe Connect';
$string['emptyxml'] = 'Impossible de se connecter au serveur Adobe Connect pour le moment. Contacter les administrateurs du site pour plus d\'information';
$string['endtime'] = 'Heure de fin';
$string['error1'] = 'Vous devez être administrateur du site pour accéder à cette page';
$string['error2'] = 'La propriété \'{$a}\' est vide, S\'il vous plait ajouter une valeur et sauvegarder les paramètres';
$string['errormeeting'] = 'Impossible de récupérer l\'enregistrement';
$string['greaterstarttime'] = 'L\'heure de début ne peut être plus grande que l\'heure de fin';
$string['host'] = 'Hôte du service Web';
$string['host_desc'] = 'Où les requêtes au service Web REST sont envoyées';
$string['https'] = 'Connexion HTTPS';
$string['https_desc'] = 'Connexion au serveur Adobe Connect via HTTPS';
$string['invalidadobemeeturl'] = 'Entrée invalide pour ce champ. Cliquez sur la bulle d\'aide pour les entrées valides';
$string['invalidurl'] = 'L\'URL doit commencer par une letter (a-z)';
$string['joinmeeting'] = 'Joindre la réunion';
$string['longurl'] = 'L\'URL de la réunion est trop long';
$string['meethost_desc'] = 'Nom de domaine du serveur Adobe Connect';
$string['meetinfo'] = 'Gestion de la réunion dans Adobe Connect';
$string['meetinfotxt'] = 'Accéder à la gestion de la réunion dans Adobe Connect';
$string['meetinghost'] = 'Nom de domaine';
$string['meetingend'] = 'Fin de la réunion';
$string['meetingintro'] = 'Description de la réunion';
$string['meetingname'] = 'Nom de la réunion';
$string['meetingstart'] = 'Début de la réunion';
$string['meetingtype'] = 'Type de réunion';
$string['meetingtype_help'] = '<p>Une réunion de type publique permet à n\'importe qui détenant l\'URL, d\'entrer dans la salle de réunion.</p>
<p>Une réunion de type privé permet uniquement l\'entrée dans la salle de réunion aux utilisateurs inscrits en tant que participants. La page de connexion ne permet pas aux invités de se connecter.
La réunion ne commence pas tant qu\'un hôte ou un présentateur n\'est entré dans la salle.</p>
<p>
Si vous créez une réunion de type privé, il est toujours une bonne pratique d\'assigner
au moins un hôte ou un présentateur qui sera présent lors de la réunion car les utilisateurs
ayant le rôle de participant ne pourront entrer si ce n\'est le cas.
</p>
<p>
Si la réunion supporte les groupes séparés, alors au moins un utilisateur dans chacun des groupes devrait avoir le rôle d\'hôte ou de présentateur.
</p>';
$string['meettemplates'] = 'Modèle de réunion';
$string['meettemplates_help'] = '<p>Un modèle deréunion crée une réunion avec une mise en page personnalisée dans la salle.</p>';
$string['meeturl'] = 'URL de la réunion';
$string['meeturl_help'] = '<p>Vous pouvez personnaliser l\'URL qui est utilisé pour se connecter à une réunion Adobe Connect. Le nom de domaine du serveur sera toujours le même,
mais la dernière partie peut être personnalisé.
</p>
<p>Par exemple, si un serveur Adobe Connect est situé à l\'adresse <b>http://adobe.connect.serveur/</b>,
  Lorsque vous inscrivez l\'URL <b>mareunion</b>, le lien pour se connecter à votre réunion est <b>http://adobe.connect.serveur/mareunion</b>.
</p>
<p>Exemple d\'entrées valides :
<ul>
<li>mareunion</li>
<li>/mareunion</li>
</ul>

Exemple d\'entrées invalides :
<ul>
<li>mareunion/mareunion</li>
<li>mareunion/mareunion/</li>
<li>mareunion/mareunion//masecondereunion</li>
<li>mareunion/</li>
</ul>

</p>
<p>Une fois la réunion sauvegardée, vous ne pourrez plus modifier ce champ car il sera désactivé.
Si vous modifiez les réglages de l\'activité et que le <b>Mode de groupe</b> est fixé à aucun groupe, alors vous allez voir une partie de l\'URL dans le champ texte.
Autrement, le champ texte restera vide car chaque groupe aura sa propre URL de réunion.
</p>';
$string['missingexpectedgroups'] = 'Il n\'y a pas de groupe disponible';
$string['modulename'] = 'Adobe Connect';
$string['modulenameplural'] = 'Adobe Connect';
$string['noinstances'] = 'Il n\'y a pas d\'instance du serveur Adobe Connect';
$string['nomeeting'] = 'La réunion n\'existe pas sur le serveur';
$string['nopresenterrole'] = 'Impossible de trouver le rôle présentateur Adobe Connect';
$string['notparticipant'] = 'Vous n\'êtes pas participant pour cette réunion';
$string['notsetupproperty'] = 'Le module d\'activité n\'est pas configuré correctement. Contacter les administrateurs du site pour plus d\'information';
$string['pluginadministration'] = 'Administration Adobe Connect';
$string['pluginname'] = 'Adobe Connect';
$string['port'] = 'Port';
$string['port_desc'] = 'Port utilisé pour se connecter au serveur Adobe Connect';
$string['private'] = 'Privé';
$string['public'] = 'Publique';
$string['recordinghdr'] = 'Enregistrements';
$string['samemeettime'] = 'Heure de la réunion invalide';
$string['selectparticipants'] = 'Assigner des rôles';
$string['starttime'] = 'Heure de début';
$string['unableretrdetails'] = 'Impossible de récupérer les détails de la réunion';
$string['usergrouprequired'] = 'Cette réunion requière aux utilisateurs de faire partie d\'un groupe pour y accéder';
$string['testconnection'] = 'Tester la connexion';
