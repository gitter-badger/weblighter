<?php

require_once('../weblighter/weblighter.php');

echo (new \weblighter\weblighter(
 [
 'en/home' => ['ctrl'=>'Controller_Home', 'func'=>'display', 'params'=>['lang'=>'en'] ],
 'fr/home' => ['ctrl'=>'Controller_Home', 'func'=>'display', 'params'=>['lang'=>'fr'] ],
 ]
))->display();
