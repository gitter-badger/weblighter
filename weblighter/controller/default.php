<?php

abstract class Controller_Default
{
  function __construct()
  {
    $this->data['site_title'] = \Data_Config::$site_title;

    $this->data['theme'] = \Data_Config::$theme;

    if (!empty($_GET['action']))
    {
      $this->data['action'] = $_GET['action'];
    }
    else
    {
      $this->data['action'] = 'home';
    }
    $this->data['url_prefix'] = \Data_Config::$url_prefix;

    //Default Translator or the one set in the route
    if (!empty(func_num_args()))
    {
      $lang = func_get_args()[0];
      $this->prepareTranslator($lang);
    }
    else
    {
      $this->prepareTranslator(\Data_Config::$default_lang);
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
      $this->translator = new weblighter\Translator(\Data_Config::$default_lang);

    }
    $this->data['t'] = $this->translator;
  }

  abstract function display();

  protected $data;
  protected $params;
  protected $translator;
  protected $content;
}
