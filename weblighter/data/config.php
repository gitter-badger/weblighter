<?php

class Data_Config {

  //Site title
  public static $site_title = 'Weblighter';

  //Active theme
  public static $theme = 'default';

  //Default timezone
  public static $timezone = 'Europe/Paris';

  //Default language (set it to 'user' to display in preferred user language)
  public static $default_lang = 'user';

  //Locales directory
  public static $locale_dir = 'i18n/';

  // How will we generate links? (empty it if you have url rewriting active)
  // For url rewriting, refer to htaccess and apache rewrite module or nginx rewrite rules.
  // example values:
  //    '?action=' => will generate links yourwebsite/?action=home
  //    ''         => will generate links yourwebsite/home
  public static $url_prefix = '?action=';

  // From which url parameter do we get the current action?
  public static $url_param = 'action';

  //Will error be output?
  public static $debug = true;

  //Databases to connect to
  /*public static $db = [
    [ 'name' => 'development',
      'type'  => 'mysql',
      'db'    => 'DB_NAME',
      'user'  => 'DB_USERNAME',
      'pass'  => 'DB_PASSWORD'
    ]
  ];*/
}
