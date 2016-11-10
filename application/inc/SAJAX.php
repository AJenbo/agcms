<?php
/**
 * Declare the SAJAX class
 *
 * PHP version 5
 *
 * @version  1.14
 * @category SAJAX
 * @package  SAJAX
 * @author   Anders Jenbo <anders@jenbo.dk>
 * @license  LGPLv3 https://www.gnu.org/licenses/lgpl-3.0.en.html
 * @link     https://github.com/AJenbo/Sajax
 */

/**
 * Simple AJAX handeling
 *
 * PHP version 5
 *
 * @category SAJAX
 * @package  SAJAX
 * @author   Anders Jenbo <anders@jenbo.dk>
 * @license  LGPLv3 https://www.gnu.org/licenses/lgpl-3.0.en.html
 * @link     https://github.com/AJenbo/PHP-imap
 */
class SAJAX
{
    public static $debugMode = false;
    public static $remoteUri = '';
    public static $failureRedirect = '';
    public static $requestType = 'GET';
    private static $functions = [];

    public static function handleClientRequest()
    {
        if (empty($_GET['rs']) && empty($_POST['rs'])) {
            return;
        }

        ob_start();

        $args = [];
        if (!empty($_GET['rs'])) {
            // Always call server
            header('Cache-Control: max-age=0, must-revalidate'); // HTTP/1.1
            header('Pragma: no-cache');                          // HTTP/1.0
            $funcName = $_GET['rs'];
            if (!empty($_GET['rsargs'])) {
                $args = $_GET['rsargs'];
            }
        } else {
            $funcName = $_POST['rs'];
            if (!empty($_POST['rsargs'])) {
                $args = $_POST['rsargs'];
            }
        }
        if ($args) {
            $args = json_decode($args, true);
        }

        $error = '';
        if (!isset(self::$functions[$funcName])) {
            $error = $funcName . ' not callable';
        } else {
            $result = call_user_func_array($funcName, $args);

            $error = ob_get_contents();
            ob_end_clean();
        }

        header('Content-Type: text/plain; charset=UTF-8');
        echo $error ? '-:' . $error : '+:' . json_encode($result);
        die();
    }

    public static function showJavascript()
    {
        static $jsHasBeenShown = false;
        if (!$jsHasBeenShown) {
            echo 'sajax_debug_mode=' . (self::$debugMode ? 'true' : 'false') . ';sajax_failure_redirect = "' . self::$failureRedirect . '";';
            foreach (self::$functions as $function => $options) {
                echo 'function x_' . $function . '() {return sajax_do_call("' . $function . '", arguments, "' . $options['method'] . '", ' . ($options['asynchronous'] ? 'true' : 'false') . ', "' . $options['uri'] . '");}';
            }
            $jsHasBeenShown = true;
        }
    }

    public static function export(array $functions)
    {
        foreach ($functions as $function => $options) {
            if (!function_exists($function)) {
                throw new Exception('SAJAX: Cannot export "' . $function . '" as it doesn\'t exists!');
            }

            if (empty($options['method'])) {
                $options['method'] = self::$requestType;
            }

            if (!isset($options['asynchronous'])) {
                $options['asynchronous'] = true;
            }

            if (empty($options['uri'])) {
                $options['uri'] = self::$remoteUri;
            }

            self::$functions[$function] = $options;
        }
    }
}
