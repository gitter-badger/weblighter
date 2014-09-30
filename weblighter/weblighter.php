<?php

namespace weblighter;

define ('WEBLIGHTER_LIB_PATH', __DIR__.'/');
define ('APP_PATH', str_replace('index.php', '', filter_var($_SERVER['SCRIPT_FILENAME'], FILTER_SANITIZE_STRING)));

class weblighter {

  function __construct($routes = []) {

    try {

      /*
       * This autoloader will first try to load app files
       * If not found, it will load the same default files but from weblighter folder
       * For example if app config file is missing it will use the default one
      */
      spl_autoload_register(function ($class) {
        if (file_exists($filepath = APP_PATH.str_replace('_', '/', strtolower($class)).'.php')) {
          require_once $filepath;
        }
        else {
          if (file_exists($filepath2 = WEBLIGHTER_LIB_PATH.str_replace('_', '/', strtolower($class)).'.php')) {
          require_once $filepath2;
          }
        }
      });

      // DEBUG mode set in app config file
      if (!empty(\Data_Config::$debug)) {
        error_reporting(-1);
        ini_set('display_errors', 1);
      }

      // Default timezone
      if (!empty(\Data_Config::$timezone)) {
        if (in_array(\Data_Config::$timezone, timezone_identifiers_list()))
          date_default_timezone_set(\Data_Config::$timezone);
      }

      // WHAT is the current user action ?
      if (empty(\Data_Config::$url_param)) {
          throw new \Exception('Configuration problem in weblighter url_param missing !');
      }
      if (!empty($_GET[\Data_Config::$url_param])) {
        $action = filter_var($_GET[\Data_Config::$url_param], FILTER_SANITIZE_STRING);
      }
      if (empty($action)) { $action = 'home'; }

      /*
       * Here is a default router if no routes are defined in the app index.php file
       * Basically, depending the user action, we can "try" to load a controller that may be the right one
       * Example: user goes on ?action=profile, no such route is defined in app index file
       * We'll first check if Controller_Profile class file exists, if not we return an error
       * If we found it, we load this route pretending it's the right one.
      */
      if (empty($routes)) {
        $controller = '\Controller_'.ucfirst($action);
        if (!class_exists($controller, true)) {
          throw new \Exception('action '.$action.' not defined!');
        }
        else {
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
      else {
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

        $found = 0;
        foreach( $routes as $key => $leroute) {
          $pattern = strtr($key, $vars);
          $params = [];
          if (preg_match('#^/?'.$pattern.'/?$#', $action, $params)) {
            $found = 1;

            //route is just the controller (default method is display)
            if (is_string($leroute)) {
              $controller = $leroute;
              if (!class_exists($controller, true)) {
                throw new \Exception('action '.$action.' not defined!');
              }
              else {
                $route = new $controller();
                $method = "display";
              }
            }
            elseif (is_array($leroute)) {
              $route = $leroute['ctrl'];
              $method = $leroute['func'];
            }

            $this->content = $route->$method($params);

            break;
          }
        }

        if (empty($found)) {
          /* Routes were defined, but for this specific route, we didn't find a match
           * finally, we'll try to guess that route ;)
          */
          $controller = '\Controller_'.ucfirst($action);
          if (!class_exists($controller, true)) {
            throw new \Exception('action '.$action.' not defined!');
          }
          else {
            $route = new $controller();
            $this->content = $route->display();
          }
        }
      }
    }
    catch(\Exception $e) {
      $this->content = (new \Controller_Exception($e->getMessage()))->display();
    }
  }

  function display() {
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
*/

class Tplparser {
  private $content;
  private $file;
  private $data;

  function __construct($tpl, $data = null) {

    $this->data = $data;

    $this->file = APP_PATH.'themes/'.\Data_Config::$theme.'/'.$tpl;
    $this->content = $this->getFile($this->file);
    $this->content = $this->replaceTags($this->content);
    $this->content = $this->execute($this->content);
  }

  function generateUrl($action) {
    return $this->data['url_prefix'].$action;
  }

  function translate($text) {
    return $this->data['t']->_($text);
  }


  function getFile($file) {
    if (file_exists($file)) {
      return file_get_contents($file);
    }
    else {
      throw new Exception('File not found: '.$file);
    }
  }

  function replaceTags($lecontent) {
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
    $lecontent = preg_replace('~\{(?i)IF (\w+)\.(\w+) = \{(\w+)\}\}~', '<?php if ((isset(\$$1) && is_object(\$$1) && \$$1->$2 == $this->data[\'$3\']) or (isset(\$$1) && is_array(\$$1) && \$$1[\'$2\'] == $this->data[\'$3\']) or (isset($this->data[\'$1\']) && is_object($this->data[\'$1\']) && $this->data[\'$1\']->$2 == $this->data[\'$3\']) or (isset($this->data[\'$1\']) && is_array($this->data[\'$1\']) && $this->data[\'$1\'][\'$2\'] == $this->data[\'$3\'])) { ?>', $lecontent);
    // {IF a = {c}}
    $lecontent = preg_replace('~\{(?i)IF (\w+) = \{(\w+)\}\}~', '<?php if ((isset(\$$1) && isset(\$$2) && \$$1 == \$$2) or (isset(\$$1) && isset($this->data[\'$2\']) && \$$1 == $this->data[\'$2\'])) { ?>', $lecontent);
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
    // {url(link)}
    $lecontent = preg_replace('~\{(?i)URL\((.*?)\)\}~', '<?php echo $this->generateUrl(\'$1\'); ?>', $lecontent);
    // {_Text} => translator
    $lecontent = preg_replace('~\{_(.*?)\}~', '<?php echo $this->translate(\'$1\'); ?>', $lecontent);
    // {a.b}
    $lecontent = preg_replace('~\{(\w+)\.(\w+)\}~', '<?php if (is_object(\$$1)) { echo \$$1->$2; } elseif (is_array(\$$1)) { echo \$$1[\'$2\']; } elseif (is_object($this->data[\'$1\'])) { echo $this->data[\'$1\']->$2; } elseif (is_array($this->data[\'$1\'])) { echo $this->data[\'$1\'][\'$2\']; } ?>', $lecontent);
    // {a}
    $lecontent = preg_replace('~\{(\w+)\}~', '<?php if (isset(\$$1)) {echo \$$1;} elseif (isset($this->data[\'$1\'])) {echo $this->data[\'$1\']; } ?>', $lecontent);
    // {LOOP table AS line}
    $lecontent = preg_replace('~\{(?i)LOOP (\w+) (?i)AS (\w+)\}~', '<?php foreach ( $this->data[\'$1\'] as \$$2 ) { ?>', $lecontent);

    return $lecontent;
  }

  function execute($lecontent) {
    $result = null;

    /*
    //Activate theses lines to debug
    echo $content;
    eval("?>".$content."<?php return true; ?>");
    */

    ob_start();
    $eval = @eval("?>".$lecontent."<?php return true; ?>");

    if ($eval !== true) {
      $result = 'ERROR IN template: '.$this->file.'<br/>';
    }
    else {
      $result = ob_get_contents();
    }
    ob_end_clean();

    return $result;
  }

  function clearData() {
    $this->data = null;
  }

  function display() {
    return $this->content;
  }
}






/*
 * Translator is the internationalization class of weblighter
*/
class Translator {

  function __construct($alocale) {
    $this->locale = $alocale;
    $this->fallbacklocale = \Data_Config::$default_lang;
    $this->localeDir = \Data_Config::$locale_dir;

    if (file_exists($file = APP_PATH.$this->localeDir.$alocale.'.json')) {
      $this->msg = json_decode(file_get_contents($file), true);
    }
    elseif (file_exists($file = WEBLIGHTER_LIB_PATH.$this->localeDir.$alocale.'.json')) {
      $this->msg = json_decode(file_get_contents($file), true);
    }
    else {
      throw new \Exception('Locale file not found for: '.$this->locale);
    }
  }

  /*
   * The function _ will translate a string based on its key
   * If the translation isn't found, it will return the key itself,
   * and will add an exception in the log
  */
  function _($text) {
    if (array_key_exists($text, $this->msg)) {
      return $this->msg[$text];
    }
    else {
      return $text;
      //throw new \Exception('No translation for: '.$text.' in: '.$this->locale);
    }
  }

  protected $locale;

  protected $msg;

  protected $fallbacklocale;

}
