<?php

class Data_Config {

  //Site title
  public static $site_title = 'Webligher demo app';

  //Site slogan
  public static $site_slogan = 'Yet a very small framework but powerful enough';

  //Active theme
  public static $theme = 'plain';

  //Default timezone
  public static $timezone = 'Europe/Paris';

  //Default language
  public static $default_lang = 'fr';

  //Locales directory
  public static $locale_dir = 'i18n/';

  // How will we generate links? (empty it if you have url rewriting active)
  // For url rewriting, refer to htaccess and apache rewrite module or nginx rewrite rules.
  // example values:
  //    '?action=' => will generate links yourwebsite/?action=home
  //    ''         => will generate links yourwebsite/home
  public static $url_prefix = '?do=';

  // From which url parameter do we get the current action?
  public static $url_param = 'do';

  //Will error be output?
  public static $debug = true;
}
