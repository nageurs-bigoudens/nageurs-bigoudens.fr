<?php
// src/controller/Security.php
//
// htmlawed nettoie les entrées de l'utilisateur, en particulier le html de l'éditeur

class Security
{
	private static $configHtmLawed = array(
	    'safe'=>1, // protection contre les élements et attributs dangereux

	    // liste blanche d'éléments HTML
	    'elements'=> 'h1, h2, h3, h4, h5, h6, p, s, em, span, strong, a, ul, ol, li, sup, sub, code, blockquote, div, pre, table, caption, colgroup, col, tbody, tr, th, td, figure, img, figcaption, iframe, small',

	    // liste noire d'attributs HTML
	    'deny_attribute'=> 'id, class' // on garde 'style'
	);

	// faire qu'un certain élément puisse n'avoir que certains attributs, regarder la doc
	private static $specHtmLawed = '';

	public static function secureString(string $chaine): string
	{
	    return trim(htmLawed($chaine, self::$configHtmLawed, self::$specHtmLawed));;
	}

	public static function secureFileName(string $chaine): string
	{
		// sécuriser un nom avec chemin avec basename?
		//$chaine = basename($chaine);

		/* 
		- caractères interdits sous windows / \ : * ?  " < > |
		- mac autorise les /
		- mac interdit :
		- linux autorise tout sauf les /
		- imagemagick ne supporte pas les :

		- 'espace' fonctionne
		- / remplacé par firefox en :
		- \ retire ce qui est devant le \
		- * fonctionne
		- ? permet le téléchargement mais pas l'affichage
		- " ne fonctionne pas, remplacé par %22, filtrer %22
		- < > fonctionnent
		- | fonctionne
		- = fonctionne, mais je filtre parce qu'on en trouve dans une URL
		- ' ` fonctionnent
		- % fonctionne
		- (){}[] fonctionnent
		- ^ fonctionne
		- # ne fonctionne pas
		- ~ fonctionne
		- & fonctionne
		- ^ pas encore testé
		*/

		// => on remplace tout par des _
		// filtrer / et \ semble inutile

		$cibles = [' ', '/', '\\', ':', '*', '?', '<', '>', '|', '=', "'", '`', '"', '%22', '#'];
		$chaine = str_replace($cibles, '_', $chaine); // nécéssite l'extension mbstring
		$chaine = mb_strtolower($chaine);
		return($chaine);
		
		// les problèmes avec \ persistent !!
		// => javascript
		// malheureusement document.getElementById('upload').files[0].name = chaine; ne marche pas! interdit!
		// javascript ne doit pas pouvoir accéder au système de fichiers
		// solutions:
		// - au lieu de fournir une chaine (le chemin du fichier), donner un objet à files[0].name
		// - créer une copie du fichier et l'envoyer à la place
		// - envoyer le fichier en AJAX
		// - envoyer le nom du fichier à part puis renommer en PHP
	}
}

// erreurs à la création des mots de passe
function removeSpacesTabsCRLF(string $chaine): string
{
	$cibles = [' ', "\t", "\n", "\r"]; // doubles quotes !!
	return(str_replace($cibles, '', $chaine));
}

// lien sans http://
function fixLinks($data)
{
    // 1/
    // si une adresse est de type "domaine.fr" sans le http:// devant, le comportement des navigateurs est de rechercher un fichier comme si mon adresse commençait par file://
    // tomber ainsi sur une page d'erreur est parfaitement déroutant

    // regex pour détecter les balises <a> et ajouter http:// au début des liens si nécessaire
    $pattern = '#(<a[^>]+href=")((?!https?://)[^>]+>)#';
	//$data = preg_replace($pattern, '$1http://$2', $data);

    // 2/
    // cas où la regex fait mal son boulot:
    // l'erreur 404 est gérée par le .htaccess
    // et le visiteur est redirigé à la page "menu"
    // (ça ne règle pas le problème mais c'est mieux)

    // 3/
    // quand l'éditeur est ouvert (avant de valider l'article),
    // le lien qu'on vient de créer apparaît dans l'infobulle,
    // cliquer dessus ouvre un onglet sur une erreur 404
    // solution partielle avec le .htaccess
    //
    // solution? fermer ce nouvel onglet avec echo '<SCRIPT>javascript:window.close()</SCRIPT>';
    // comment déclencher le JS? en faisant qu'une erreur 404 causée pour cette raison soit particulière?

    return($data);
}
