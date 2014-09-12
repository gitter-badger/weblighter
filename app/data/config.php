<?php

class Data_Config {

  public static $site_title = 'Webligher demo app';

  public static $site_slogan = 'Yet a very small framework but powerful enough';

  public static $theme = 'plain';

  //Default language
  public static $default_lang = 'fr_FR';
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

  public static $debug = true;
}
