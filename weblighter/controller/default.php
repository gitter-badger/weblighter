<?php

abstract class Controller_Default
{
  function __construct()
  {
    $this->data['site_title'] = \Data_Config::$site_title;

    $this->data['theme'] = \Data_Config::$theme;

    $this->data['action'] = CURRENT_ACTION;

    $this->data['url_prefix'] = \Data_Config::$url_prefix;

    //Langs and current language
    $this->data['langs'] = $_SESSION['langs'];
    $this->data['lang']  = $_SESSION['user']['lang'];

    //Default Translator or the one set in the route
    if (!empty(func_num_args()))
    {
      $lang = func_get_args()[0];
      $this->prepareTranslator($lang);
    }
    else
    {
      $this->prepareTranslator($_SESSION['user']['lang']);
    }

  }

  function prepareTranslator($lang)
  {
    if (!empty($lang))
    {
      $this->translator = new weblighter\Translator($lang);
    }
    else
    {
      $this->translator = new weblighter\Translator($_SESSION['user']['lang']);

    }
    $this->data['t'] = $this->translator;
  }

  abstract function display();

  protected $data;
  protected $params;
  protected $translator;
  protected $content;
}
