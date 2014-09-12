<?php

require_once('../weblighter/weblighter.php');

echo (new \weblighter\weblighter(
 [
 'home' => 'Controller_Home'
 ]
))->display();
