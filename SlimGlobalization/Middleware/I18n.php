<?php
/**
 * Slim Globalization Plugin
 * 
 * @author     Dimitry Zolotaryov <dimitry@webit.ca>
 * @copyright  2012 
 * @link       http://webit.ca/slim
 * @version    1.0.0
 * @package    SlimGlobalization
 * 
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 * 
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 * LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */
namespace SlimGlobalization\Middleware;

/**
 * Internationalization middleware
 *
 * Automatically handles prefixing every request URL with the user's 
 * language. Adds the route named 'lang' for switching language.
 *
 * Use:
 *  $app->add(new \SlimGlobalization\Middleware\I18n(array(
 *      'en' => 'English',
 *      'fr' => 'Français',
 *  )));
 *
 * @author  Dimitry Zolotaryov
 * @package SlimGlobalization
 * @since   1.0
 */
class I18n extends \Slim\Middleware {

    /**
     * Creates a new instance of the middleware with the given associative
     * array of languages.
     *
     * @param   array   $languages Language IDs and names
     */
    function __construct($languages = null) {
        $this->languages = $languages;
        $this->defaultLanguage = reset(array_keys($languages));
    }

    /**
     * Adds routes to switch between languages to the middleware's application
     */
    protected function addRoutes() {
        $app = $this->app;
        $_this = $this;

        $app->get('/language', array(&$this, 'actionSwitchLanguage'))
            ->name('lang');
    }

    /**
     * Action to switch application language.
     */
    function actionSwitchLanguage() {
        $app = $this->app;
        $request = $app->request();

        $oldLanguage = $this->getUserLanguage();
        $language    = $this->setUserLanguage($request->get('lang'));

        $redirectTo = '/';
        if ($request->get('next')) {
            $redirectTo = $request->get('next');
        }
        $app->environment()->offsetSet('SCRIPT_NAME', "/$language");

        $app->redirect($redirectTo);
    }

    /**
     * Returns the users language.
     *
     * Looks in the session and in the browser settings for the user's 
     * prefered language. If the language is not part of the supported
     * set, returns the default language.
     *
     * @return  string The user's language or the default language
     */
    function getUserLanguage() {
        $language = null;
        if (isset($_SESSION['language'])) {
            $language = $_SESSION['language'];
        } else if ($accept_languages = $this->app->request()->headers('ACCEPT_LANGUAGE')) {
            foreach (explode(',', $accept_languages) as $language_identifier) {
                $user_lang = reset(explode(';', trim($language_identifier)));
                if (isset($this->languages[$user_lang])) {
                    $language = $user_lang;
                    break;
                }
            }

        } else {
            $language = $this->defaultLanguage;
        }
        return $language;
    }

    /**
     * Stores the user's language in the session
     *
     * If the language is not part of the list of languages, reverts
     * to the default language. Sessions must be enabled.
     *
     * @param   string  $language   The language to switch to
     * @return  string The actual langauge assigned
     */
    function setUserLanguage($language) {
        $_SESSION['language'] = $this->verifyLanguage($language);
        return $_SESSION['language'];
    }

    /**
     * Returns either the same language or the default language if the given
     * language is not valid.
     *
     * @param   string  $language   The language to verify
     * @return  string The same language or the default language
     */
    function verifyLanguage($language) {
        if (!isset($this->languages[$language])) {
            $language = $this->defaultLanguage;
        }
        return $language;
    }

    /**
     * Returns the given Uri stripped of language
     *
     * @param   string  $uri    The URI from which to strip the language
     * @param   string  $language   The language to strip
     * @return  string URI without the language
     */
    function stripLanguage($uri, $language) {
        $newUri = ltrim($uri, '/');
        $newUri = substr($newUri, strlen($language));
        return $newUri;
    }

    /**
     * Returns the path modified with the language.
     * 
     * If the given language is not valid, method uses the default
     * language.
     *
     * @param   string  $resourceUri The URI to prefix with new language
     * @param   string  $language   The new language
     * @return  string  The URI with the different language
     */
    function changeResourceLanguage($resourceUri, $language) {
        $language = $this->verifyLanguage($language);
        $pathParts = explode('/', ltrim(strval($resourceUri), '/')); 

        $newPath = null;
        if (!$pathParts[0]) {
            // the uri is blank
            $newPath = "/$language/";
        } elseif (!isset($this->languages[$pathParts[0]])) {
            // the first part is not a valid language (i.e. there is no language set)
            $newPath = "/$language$resourceUri";
        } elseif ($pathParts[0] == $language) {
            // the language is the same, does nothing
            $newPath = $resourceUri;
        } else {
            $newPath = "/$language/" . join('/', $pathParts);
        }

        return $newPath;
    }

    /**
     * Function called when executing the middleware
     */
    function call() {
        $app = $this->app;

        $resourceUri = $app->request()->getResourceUri();
        $userLanguage = $this->getUserLanguage();
        $uriShouldBe = $this->changeResourceLanguage($resourceUri, $userLanguage);
        
        if ($uriShouldBe != $resourceUri) {
            $app->redirect($uriShouldBe);
        } else {
            $app->config('languages', $this->languages);
            $app->config('language.id', $userLanguage);
            $app->config('language.name', $this->languages[$userLanguage]);

            $realPath = $this->stripLanguage($resourceUri, $userLanguage);

            $environment = $app->environment();
            $environment->offsetSet('PATH_INFO', $realPath);
            $environment->offsetSet('SCRIPT_NAME', $environment->offsetGet('SCRIPT_NAME') . "/$userLanguage");

            $this->addRoutes();
            $this->next->call();
        }
    }
}
