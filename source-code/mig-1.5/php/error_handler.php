<?php
// Nous effectuons nous même notre contrôle d'erreur.
error_reporting(0);
// Fonction de gestion des erreurs utilisateur
function usererrorhandler($errno, $errmsg, $filename, $linenum, $vars) {
    // timestamp pour dater l'erreur
    $dt = date("Y-m-d H:i:s (T)");
    // definit un tableau associatif avec les chaînes d'erreur
    // en realité, les seules entrées que nous considérerons
    // seront 2,8,256,512 et 1024
    $errortype = array(
                1   =>  "Erreur",
                2   =>  "Alerte",
                4   =>  "Erreur d'analyse",
                8   =>  "Note",
                16  =>  "Erreur interne",
                32  =>  "Alerte interne",
                64  =>  "Erreur de compilation",
                128 =>  "Alerte  de compilation",
                256 =>  "Erreur utilisateur",
                512 =>  "Alerte utilisateur",
                1024=>  "Note utilisateur"
                );
    // ensemble d'erreur pour lesquelles une trace sera conservée
    $user_errors = array(E_USER_ERROR, E_USER_WARNING, E_USER_NOTICE);
    $err = "<errorentry>\n";
    $err .= "\t<datetime>".$dt."</datetime>\n";
    $err .= "\t<errornum>".$errno."</errnumber>\n";
    $err .= "\t<errortype>".$errortype[$errno]."</errortype>\n";
    $err .= "\t<errormsg>".$errmsg."</errormsg>\n";
    $err .= "\t<scriptname>".$filename."</scriptname>\n";
    $err .= "\t<scriptlinenum>".$linenum."</scriptlinenum>\n";
    if (in_array($errno, $user_errors))
        $err .= "\t<vartrace>".wddx_serialize_value($vars,"Variables")."</vartrace>\n";
    $err .= "</errorentry>\n\n";
    // pour test
    // echo $err;
    // sauve l'erreur dans le fichier, et emaile moi si l'erreur est critique
    error_log($err, 3, "/usr/local/php4/error.log");
    if ($errno == E_USER_ERROR)
        mail("phpdev@mydomain.com","Critical User Error",$err);
}
function distance($vect1, $vect2) {
    if (!is_array($vect1) || !is_array($vect2)) {
        trigger_error("Paramètres incorrects : arrays attendus", E_USER_ERROR);
        return NULL;
    }
    if (count($vect1) != count($vect2)) {
        trigger_error("Les vecteurs doivent être de la même taille", E_USER_ERROR);
        return NULL;
    }
    for ($i=0; $i<count($vect1); $i++) {
        $c1 = $vect1[$i]; $c2 = $vect2[$i];
        $d = 0.0;
        if (!is_numeric($c1)) {
            trigger_error("La coordonnée $i du vecteur 1 n'est pas un nombre. Remplacée par zéro",
                            E_USER_WARNING);
            $c1 = 0.0;
        }
        if (!is_numeric($c2)) {
            trigger_error("La coordonnée $i du vecteur 2 n'est pas un nombre. Remplacée par zéro",
                            E_USER_WARNING);
            $c2 = 0.0;
        }
        $d += $c2*$c2 - $c1*$c1;
    }
    return sqrt($d);
}
$old_error_handler = set_error_handler("userErrorHandler");
// Constante indéfinie, génére une alerte
$t = I_AM_NOT_DEFINED;
// definition de quelques "vecteurs"
$a = array(2,3,"bla");
$b = array(5.5, 4.3, -1.6);
$c = array(1,-3);
// génère une erreur utilisateur
$t1 = distance($c,$b)."\n";
// génère une autre erreur utilisateur
$t2 = distance($b,"i am not an array")."\n";
// génère une alerte
$t3 = distance($a,$b)."\n";
?>
    
