<?php

require_once(__DIR__ . '/../../configuration/linker.php');

/**
 * Entry point for handling REST API requests.
 *
 * If you are looking to add additional sub-routes to the application then
 * the prescribed method is to:
 *
 *     - Create a class that extends BaseControllerProvider
 *     - implement the 'setupRoutes' function so that it provides the
 *       sub-routes you require.
 *     - Create a new instance of that class in the application factory and
 *       'mount' it just like the other ControllerProviders.
 *     - NOTE: You can override any of the 'setupXXX' functions defined in
 *       the BaseControllerProvider provided that your routes require this
 *       global functionality.
 */

// CREATE: our new Silex Application
$app = \NewRest\XdmodApplicationFactory::getInstance();

// START: our engines!
$app->run();
