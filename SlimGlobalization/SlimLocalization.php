<?php

namespace SlimLocalization;

class SlimLocalizationMiddleware extends \Slim\Middleware {

    /**
     * Creates a new instance of the middleware with the given associative
     * array of languages.
     */
    function __construct($languages = null) {
        $this->languages = $languages;
        $this->defaultLanguage = reset(array_keys($languages));
    }

    /**
     * Adds routes to switch between languages to the middleware's application
     */
    function addRoutes() {
        $app = $this->app;
        $_this = $this;

        $app->get('/switch', array(&$this, 'actionSwitchLanguage'))
            ->name('lang');
    }

    /**
     * Action to switch application language
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
     * Sets the given user language.
     *
     * If the language is not part of the list of languages, reverts
     * to the default language.
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
     * @param   string  $uri    The uri from which to strip the language
     * @param   string  $language   The language to strip
     * @return  string Uri without the language
     */
    function stripLanguage($uri, $language) {
        $newUri = ltrim($uri, '/');
        $newUri = substr($newUri, strlen($language));
        return $newUri;
    }

    /**
     * Returns the path with the new language.
     * 
     * If the given language is not valid, method uses the default
     * language.
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
