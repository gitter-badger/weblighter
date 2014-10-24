<?php

require_once('../weblighter/weblighter.php');

echo (new \weblighter\weblighter(
 [
 'home' => 'Controller_Home',
 'en/home' => ['ctrl'=>'Controller_Home', 'params'=>['lang'=>'en_US'] ],
 'fr/home' => ['ctrl'=>'Controller_Home', 'params'=>['lang'=>'fr_FR'] ],
 ]
))->display();
