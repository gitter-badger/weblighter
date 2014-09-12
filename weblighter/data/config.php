<?php

class Data_Config {

  public static $site_title = 'Weblighter';

  public static $site_slogan = 'Weblighter default website';

  //Put the active theme here
  public static $theme = 'default';

  //Default language
  public static $default_lang = 'en_US';
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

  public static $debug = true;
}
