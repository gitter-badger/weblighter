<?php

require_once('../weblighter/weblighter.php');

echo (new \weblighter\weblighter(
 [
 'home' => 'Controller_Home',
 'en/home' => ['ctrl'=>'Controller_Home', 'func'=>'display', 'params'=>['lang'=>'en'] ],
 'fr/home' => ['ctrl'=>'Controller_Home', 'func'=>'display', 'params'=>['lang'=>'fr'] ],
 ]
))->display();
