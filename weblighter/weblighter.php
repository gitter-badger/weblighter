<?php

namespace weblighter;

class weblighter
{

  function __construct($routes = [])
  {

    //Some Constants to be used everywhere
    define('WEBLIGHTER_LIB_PATH', __DIR__.'/');
    $vpath = str_replace('index.php', '', filter_var($_SERVER['SCRIPT_NAME'], FILTER_SANITIZE_STRING));
    if ($vpath == '/')
    {
      define('VIRTUAL_PATH', '');
    }
    else
    {
      define('VIRTUAL_PATH', $vpath);
    }
    define('APP_PATH', str_replace('index.php', '', filter_var($_SERVER['SCRIPT_FILENAME'], FILTER_SANITIZE_STRING)));

    try
    {

      /*
       * This autoloader will first try to load app files
       * If not found, it will load the same default files but from weblighter folder
       * For example if app config file is missing it will use the default one
      */
      spl_autoload_register(function ($class)
      {
        if (file_exists($filepath = APP_PATH.str_replace('_', '/', strtolower($class)).'.php'))
        {
          require_once $filepath;
        }
        else
        {
          if (file_exists($filepath2 = WEBLIGHTER_LIB_PATH.str_replace('_', '/', strtolower($class)).'.php'))
          {
            require_once $filepath2;
          }
        }
      });

      // DEBUG mode set in app config file
      if (!empty(\Data_Config::$debug))
      {
        error_reporting(-1);
        ini_set('display_errors', 1);
      }

      // Default timezone
      if (!empty(\Data_Config::$timezone))
      {
        if (in_array(\Data_Config::$timezone, timezone_identifiers_list()))
          date_default_timezone_set(\Data_Config::$timezone);
      }

      // WHAT is the current user action ?
      if (empty(\Data_Config::$url_param))
      {
          throw new \Exception('Configuration problem in weblighter url_param missing !');
      }
      if (!empty($_GET[\Data_Config::$url_param]))
      {
        $action = filter_var($_GET[\Data_Config::$url_param], FILTER_SANITIZE_STRING);
      }
      if (empty($action))
      {
        $action = 'home';
      }

      //DEFAULT Lang?
      session_start();

      if (empty($_SESSION['user']['lang']))
      {
        $_SESSION['langs'] = $this->geti18nlocales();

        if (\Data_Config::$default_lang == 'user')
        {
          //Default language is set to "user", it means we'll display
          //the page for the top X preferred language in the user's browser.
          $_SESSION['user']['lang'] = $this->getPreferredUserLanguage();
        }
        else
        {
          //Set the default language as the one defined in the config.
          $_SESSION['user']['lang'] = \Data_Config::$default_lang;
        }
      }

      /*
       * Here is a default router if no routes are defined in the app index.php file
       * Basically, depending the user action, we can "try" to load a controller that may be the right one
       * Example: user goes on ?action=profile, no such route is defined in app index file
       * We'll first check if Controller_Profile class file exists, if not we return an error
       * If we found it, we load this route pretending it's the right one.
      */
      if (empty($routes))
      {
        $controller = '\Controller_'.ucfirst($action);
        if (!class_exists($controller, true))
        {
          throw new \Exception('action '.$action.' not defined!');
        }
        else
        {
          $route = new $controller();
          $this->content = $route->display();
        }
      }
      /* Here is the router when routes are defined.
       * It is very small but powerful as it can handle named parameters
       * here is an example:
       * 'profile/{username:alpha}' => 'Controller_User_Profile'
       * this route will match any alphanumeric given after profile/
       * example profile/fabien_Wang72 would be matched and Controller_User_Profile
       * would be loaded with a data array parameter containing $username = 'fabien_Wang72'
       * isn't it awesome ? You can see bottom here you may have string, number, alpha, hex
       * another example:
       * 'post/{year:number}/{month:number}/{day:number}/{post_id:number}-{post_title:alpha}' => Controller_Blog_Post
       * would match any of these:
       *  post/2012/10/25/1-welcome_on_my_blog
       *  post/2014/09/06/1245-why_do_i_code_so_good
       * Enjoy !
      */
      else
      {
        //$key => $leroute
        //  $leroute['ctrl']    $leroute['func']    $leroute['method'] (get or post, both if empty)

        $vars = [
          '/'        => '\/',
          '{'        => '(?<',
          ':string}' => '>[a-zA-Z]+)',
          ':number}' => '>[0-9]+)',
          ':alpha}'  => '>[a-zA-Z0-9-_]+)',
          ':hex}'    => '>[0-9a-f]+)',
          ];

        if (!empty($_GET['lang']))
        {
          $lang = $_GET['lang'];
          $_SESSION['user']['lang'] = $_GET['lang'];
        }
        else
        {
          $lang = $_SESSION['user']['lang'];
        }


        $found = 0;
        foreach( $routes as $key => $leroute)
        {
          $pattern = strtr($key, $vars);
          $params = [];
          if (preg_match('#^/?'.$pattern.'/?$#', $action, $params))
          {
            $found = 1;

            //route is just the controller (default method is display)
            if (is_string($leroute))
            {
              $controller = $leroute;
              if (!class_exists($controller, true))
              {
                throw new \Exception('{action_not_defined}: '.$action);
              }
              else {
                $route = new $controller($lang);
                $method = "display";
              }
            }
            elseif (is_array($leroute))
            {
              if (!empty($_GET['lang']))
              {
                $route = new $leroute['ctrl']($_GET['lang']);
              }
              else
              {
                if (!empty($leroute['params']['lang']))
                {
                  $route = new $leroute['ctrl']($leroute['params']['lang']);
                }
                else
                {
                  $route = new $leroute['ctrl']($lang);
                }
              }

              if (!empty($leroute['func']))
                $method = $leroute['func'];
              else
                $method = "display";
            }

            if (!empty($leroute['params']))
            {
              if (is_array($leroute['params']))
              {
                  $params = array_merge($leroute['params'], $params, @$_GET);
              }
            }

            if (!empty($params))
              $this->content = $route->$method($params);
            else
              $this->content = $route->$method();

            break;
          }
        }

        if (empty($found))
        {
          /* Routes were defined, but for this specific route, we didn't find a match
           * finally, we'll try to guess that route ;)
          */
          $controller = '\Controller_'.ucfirst($action);
          if (!class_exists($controller, true))
          {
            throw new \Exception('{_action_not_defined}: '.$action);
          }
          else
          {
            $route = new $controller();
            $this->content = $route->display();
          }
        }
      }
    }
    catch(\Exception $e)
    {
      $this->content = (new \Controller_Exception($e->getMessage()))->display();
    }
  }

  function getPreferredUserLanguage()
  {
    $userlangs = explode(',', trim($_SERVER['HTTP_ACCEPT_LANGUAGE']));
    foreach ($userlangs as $lang)
    {
      if (preg_match('/(\*|[a-zA-Z0-9]{1,8}(?:-[a-zA-Z0-9]{1,8})*)(?:\s*;\s*q\s*=\s*(0(?:\.\d{0,3})|1(?:\.0{0,3})))?/', trim($lang), $match))
      {
        if (!isset($match[2]))
        {
            $match[2] = '1.0';
        } else {
            $match[2] = (string) floatval($match[2]);
        }
        if (!isset($languages[$match[2]])) {
            $languages[$match[2]] = array();
        }
        $languages[$match[2]][] = strtolower($match[1]);
      }
    }
    krsort($languages);

    $avail_langs = $this->geti18nlocales();

    foreach ($languages as $lang)
    {

      if (in_array($lang[0], $avail_langs))
      {
        return $lang[0];
      }
    }

    //no preferred language? return the 'en' or the first one available
    if (in_array('en', $avail_langs))
    {
      return 'en';
    }
    return $avail_langs[0];
  }

  function geti18nlocales()
  {
    $locales = glob(APP_PATH.'i18n/*.json');
    $localenames = array();
    foreach ($locales as $locale)
    {
      $loc = str_replace(APP_PATH.'i18n/', '', $locale);
      $loc = str_replace('.json', '', $loc);
      array_push($localenames, $loc);
    }
    return $localenames;
  }

  function display()
  {
    return $this->content;
  }

  private $content;
}







/*
* The template parser replace some tags
* like: {var} to be replaced with $data['var'] or
* {var.subvar} if var is an array or an object property
* Of course, i also made {loop var} to loop on an array :D
* You can even make {loop var as toto}
* small concrete example
*
*   {loop posts in post}
*   <article>
*     <h1>{post.title}</h1>
*     <p class="date">posted by {post.author} on {post.date}</p>
*     <p>{post.content}</p>
*   </article>
*   {/loop}

* Type argument allows to define the type of content to be loaded
* php will be parsed and execute
* markdown will be parsed and rendered
*/

class Tplparser
{
  private $content;
  private $file;
  private $data;

  function __construct($tpl = null, $data = null, $type = 'php')
  {

    if (!empty($tpl))
    {
      $this->setData($data);

      if (!file_exists($this->file = APP_PATH.'themes/'.\Data_Config::$theme.'/'.$tpl )) $this->file = APP_PATH.$tpl;
      $this->content = $this->getFile($this->file);

      if ($type == 'php')
      {
        $this->content = $this->replaceTags($this->content);
        $this->content = $this->execute($this->content);
      }
      elseif ($type == 'markdown')
      {
        $parsedown = new \Vendors_Parsedown_Parsedown();
        $parsedown->setMarkupEscaped(true);
        $this->content = $parsedown->text($this->content);
      }
    }
  }

  function getContent()
  {
    return $this->content;
  }

  function setData($data)
  {
    $this->data = $data;
  }

  function setContent($content)
  {
    $this->content = $content;
  }

  function generateUrl($action)
  {
    if (empty($action))
    {
      $action = $this->data['action'];
    }
    return VIRTUAL_PATH.$this->data['url_prefix'].$action;
  }

  function formatDate($date)
  {
    if (empty($date)) return '';

    $ldate = new \DateTime($date);

    $dateformat = $this->data['t']->_('date_format');
    if (empty($dateformat)) $dateformat = "Y-m-d";
    return date_format($ldate, $dateformat);
  }

  function translate($text)
  {
    return $this->data['t']->_($text);
  }

  function getFile($file)
  {
    if (file_exists($file) && is_file($file))
    {
      return file_get_contents($file);
    }
    else
    {
      throw new \Exception('File not found: '.$file);
    }
  }

  function replaceTags($lecontent)
  {
    // {/LOOP} ou {/IF}
    $lecontent = preg_replace('~\{/(?i)(LOOP|IF)\}~', '<?php } ?>', $lecontent);
    // {IF a = true} (or false)
    $lecontent = preg_replace('~\{(?i)IF (\w+) (=|(?i)eq) ((?i)TRUE|(?i)FALSE)\}~', '<?php if ((isset(\$$1) && \$$1 == $3) or (isset($this->data[\'$1\']) && $this->data[\'$1\'] == $3)) { ?>', $lecontent);
    // {IF a ! empty}
    $lecontent = preg_replace('~\{(?i)IF (\w+) (!|(?i)not) ((?i)empty)\}~', '<?php if (!empty($this->data[\'$1\'])) { ?>', $lecontent);
    // {IF a.b ! empty}
    $lecontent = preg_replace('~\{(?i)IF (\w+)\.(\w+) (!|(?i)not) ((?i)empty)\}~', '<?php if ((is_object(\$$1) and !empty(\$$1->$2)) or (is_array(\$$1) && !empty(\$$1[\'$2\'])) or (is_object($this->data[\'$1\']) && !empty($this->data[\'$1\']->$2)) or (is_array($this->data[\'$1\']) && !empty($this->data[\'$1\'][\'$2\']))) { ?>', $lecontent);
    // {IF a ! empty}

    // {IF a.b = {c}}
    $lecontent = preg_replace('~\{(?i)IF (\w+)\.(\w+) (=|(?i)eq) \{(\w+)\}\}~', '<?php if ((isset(\$$1) && is_object(\$$1) && \$$1->$2 == $this->data[\'$4\']) or (isset(\$$1) && is_array(\$$1) && \$$1[\'$2\'] == $this->data[\'$4\']) or (isset($this->data[\'$1\']) && is_object($this->data[\'$1\']) && $this->data[\'$1\']->$2 == $this->data[\'$4\']) or (isset($this->data[\'$1\']) && is_array($this->data[\'$1\']) && $this->data[\'$1\'][\'$2\'] == $this->data[\'$4\'])) { ?>', $lecontent);
    // {IF a = {c}}
    $lecontent = preg_replace('~\{(?i)IF (\w+) (=|(?i)eq) \{(\w+)\}\}~', '<?php if ((isset(\$$1) && isset(\$$3) && \$$1 == \$$3) or (isset(\$$1) && isset($this->data[\'$3\']) && \$$1 == $this->data[\'$3\'])) { ?>', $lecontent);
    // {IF {a} = {c}}
    $lecontent = preg_replace('~\{(?i)IF \{(\w+)\} (=|(?i)eq) \{(\w+)\}\}~', '<?php if ((isset(\$$1) && isset(\$$3) && \$$1 == \$$3) or (isset(\$$1) && isset($this->data[\'$3\']) && \$$1 == $this->data[\'$3\']) or (isset($this->data[\'$1\']) && isset($this->data[\'$3\']) && $this->data[\'$1\'] == $this->data[\'$3\']) or (isset(\$$3) && isset($this->data[\'$1\']) && \$$3 == $this->data[\'$1\'])) { ?>', $lecontent);
    // {IF a.b = true} (or false)
    $lecontent = preg_replace('~\{(?i)IF (\w+)\.(\w+) (=|(?i)eq) (?i)(TRUE|FALSE)\}~', '<?php if ((is_object(\$$1) && \$$1->$2 == $3) or (is_array(\$$1) && \$$1[\'$2\'] == $3)) { ?>', $lecontent);
    // {IF a.b = c}
    $lecontent = preg_replace('~\{(?i)IF (\w+)\.(\w+) (=|(?i)eq) (\w+)\}~', '<?php if ((is_object(\$$1) && \$$1->$2 == \$$3) or (is_array(\$$1) && \$$1[\'$2\'] == \$$3)) { ?>', $lecontent);
    // {IF a.b = 'c'}
    $lecontent = preg_replace('~\{(?i)IF (\w+)\.(\w+) (=|(?i)eq) \'(\w+)\'\}~', '<?php if (((is_object(\$$1) && \$$1->$2 == \'$4\') or (is_array(\$$1) && \$$1[\'$2\'] == \'$4\')) || ((is_object($this->data[\'$1\']) && $this->data[\'$1\']->$2 == \'$4\') or (is_array($this->data[\'$1\']) && $this->data[\'$1\'][\'$2\'] == \'$4\'))) { ?>', $lecontent);
    // {IF a = c}
    $lecontent = preg_replace('~\{(?i)IF (\w+) (=|(?i)eq) (\w+)\}~', '<?php if (\$$1 == \$$2) { ?>', $lecontent);
    // {IF a = 'c'}
    $lecontent = preg_replace('~\{(?i)IF (\w+) (=|(?i)eq) \'(\w+)\'\}~', '<?php if ((isset(\$$1) && (\$$1 == \'$3\')) or (isset($this->data[\'$1\']) && ( $this->data[\'$1\'] == \'$3\'))) { ?>', $lecontent);
    // {IF a cs 'c'}
    $lecontent = preg_replace('~\{(?i)IF (\w+) ((?i)cs) \'(\w+)\'\}~', '<?php if ((isset(\$$1) && (strpos(\$$1, \'$3\') !== false)) or (isset($this->data[\'$1\']) && (strpos($this->data[\'$1\'], \'$3\') !== false))) { ?>', $lecontent);
    // {ELSE}
    $lecontent = preg_replace('~\{(?i)ELSE\}~', '<?php } else { ?>', $lecontent);
    // {url(link)} if {url()}, will go to current action
    $lecontent = preg_replace('~\{(?i)URL\((.*?)\)\}~', '<?php echo $this->generateUrl(\'$1\'); ?>', $lecontent);
    // {app}
    $lecontent = preg_replace('~\{(?i)APP}~', '<?php echo VIRTUAL_PATH; ?>', $lecontent);
    // {_{var}} => translator
    $lecontent = preg_replace('~\{_\{(\w+)\}\}~', '<?php if (isset(\$$1)) {echo $this->translate(\$$1);} elseif (isset($this->data[\'$1\'])) {echo $this->translate($this->data[\'$1\']);} ?>', $lecontent);
    // {_Text} => translator
    $lecontent = preg_replace('~\{_(.*?)\}~', '<?php echo $this->translate(\'$1\'); ?>', $lecontent);
    // {date(a.b)}
    $lecontent = preg_replace('~\{(?i)DATE\((\w+)\.(\w+)\)\}~', '<?php if (is_object(\$$1)) { echo $this->formatDate(\$$1->$2); } elseif (is_array(\$$1)) { echo $this->formatDate(\$$1[\'$2\']); } elseif (is_object($this->data[\'$1\'])) { echo $this->formatDate($this->data[\'$1\']->$2); } elseif (is_array($this->data[\'$1\'])) { echo $this->formatDate($this->data[\'$1\'][\'$2\']); } ?>', $lecontent);
    // {date(a)}
    $lecontent = preg_replace('~\{(?i)DATE\((\w+)\)\}~', '<?php echo $this->formatDate(\$$1); ?>', $lecontent);
    // {a.b}
    $lecontent = preg_replace('~\{(\w+)\.(\w+)\}~', '<?php if (is_object(\$$1)) { echo \$$1->$2; } elseif (is_array(\$$1)) { echo \$$1[\'$2\']; } elseif (is_object($this->data[\'$1\'])) { echo $this->data[\'$1\']->$2; } elseif (is_array($this->data[\'$1\'])) { echo $this->data[\'$1\'][\'$2\']; } ?>', $lecontent);
    // {a}
    $lecontent = preg_replace('~\{(\w+)\}~', '<?php if (isset(\$$1)) {echo \$$1;} elseif (isset($this->data[\'$1\'])) {echo $this->data[\'$1\']; } ?>', $lecontent);
    // {LOOP table AS line}
    $lecontent = preg_replace('~\{(?i)LOOP (\w+) (?i)AS (\w+)\}~', '<?php foreach ( $this->data[\'$1\'] as \$$2 ) { ?>', $lecontent);

    return $lecontent;
  }

  function execute($lecontent)
  {
    $result = null;

    /*
    //Activate theses lines to debug
    echo $lecontent;
    eval("?>".$lecontent."<?php return true; ?>");
    */

    ob_start();
    $eval = @eval("?>".$lecontent."<?php return true; ?>");

    if ($eval !== true)
    {
      $result = 'ERROR IN template: '.$this->file.'<br/>';
    }
    else
    {
      $result = ob_get_contents();
    }
    ob_end_clean();

    return $result;
  }

  function clearData()
  {
    $this->data = null;
  }

  function display()
  {
    return $this->content;
  }
}






/*
 * Translator is the internationalization class of weblighter
*/
class Translator {

  function __construct($alocale)
  {
    $this->locale = $alocale;
    $this->fallbacklocale = \Data_Config::$default_lang;
    $this->localeDir = \Data_Config::$locale_dir;

    //First: Load Weblighter messages
    if (file_exists($file = WEBLIGHTER_LIB_PATH.$this->localeDir.$alocale.'.json'))
    {
      $msg = json_decode(file_get_contents($file), true);
    }
    elseif (file_exists($file = WEBLIGHTER_LIB_PATH.$this->localeDir.$this->fallbacklocale.'.json'))
    {
      $msg = json_decode(file_get_contents($file), true);
    }

    //Then: Load Application messages
    if (file_exists($file = APP_PATH.$this->localeDir.$alocale.'.json'))
    {
      $msg2 = json_decode(file_get_contents($file), true);
    }
    elseif (file_exists($file = APP_PATH.$this->localeDir.$this->fallbacklocale.'.json'))
    {
      $msg2 = json_decode(file_get_contents($file), true);
    }

    $this->msg = @array_merge($msg, $msg2);

    if (empty($this->msg))
    {
      throw new \Exception('Locale not found or parse error: '.$alocale);
    }
  }

  /*
   * The function _ will translate a string based on its key
   * If the translation isn't found, it will return the key itself,
   * and will add an exception in the log
  */
  function _($text)
  {
    //var_dump($text);
    if (empty($this->msg)) {
      //add a log error because it seems the i18n lang file is corrupted here
      return $text;
    }
    //var_dump(array_key_exists($text, $this->msg));
    //var_dump($this->msg[$text]);
    if (array_key_exists($text, $this->msg))
    {
      $ltext = $this->msg[$text];

      $tr = new Tplparser(null);
      $data['url_prefix'] = \Data_Config::$url_prefix;
      $data['t'] = $this;
      $tr->setData($data);
      $tr->setContent($tr->replaceTags($ltext));
      $ltext = $tr->execute($tr->getContent());

      return $ltext;

    }
    else
    {
      $tr = new Tplparser(null);
      $data['url_prefix'] = \Data_Config::$url_prefix;
      $data['t'] = $this;
      $tr->setData($data);
      $tr->setContent($tr->replaceTags($text));
      $otext = $tr->execute($tr->getContent());

      //return something different if DEBUG is ON and text isn't translated
      if ($otext == @$this->msg[$text])
      {
        return $this->msg[$text];
      }
      else
      {
        $multiple = preg_match('~\{(\w+)\}~', $text);

        if (\Data_Config::$debug)
        {
          if ($multiple)
          {
            return $otext;
          }
          else
          {
            return '<font color="red">I18N: '.$text.'</font>';
          }
        }
        else
        {
          return $text;
        }

      }
    }
  }

  protected $locale;

  protected $msg;

  protected $fallbacklocale;

}
