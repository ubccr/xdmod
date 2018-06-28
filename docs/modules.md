---
title: Modules
---

Rest End Points
---------------
Module's may specify custom rest end points that provide information and services both for its own use as well as that of other modules, XDMoD and or external services.
XDMoD makes use of [Silex](http://silex.sensiolabs.org/) to provide its REST services. This will need to be taken into consideration by modules wishing to provide custom
rest end points.

### Getting Started
To get started providing REST end points within XDMoD a module should make sure that they have the following directory / file structure in place:

 - [module_home]
     - classes/
          - Rest/
              - Controllers/
                  - [some_controller_provider].php
     - configuration/
         - rest.d/
             - [module_name].json

Once the directory structure is in place go ahead and create the controller provider php file, we'll start there first.
#### [some_controller_provider].php
This file should contain a class that extends `\Rest\Controllers\BaseControllerProvider` ( which in turn implements Silex's `ControllerProviderInterface`), this abstract class provides a number of helpful methods for interacting with XDMoD such as determining if a user is authenticated and / or authorized, retrieving the XDUser object for a logged in user and retrieving typed parameters from a request. This class also provides some default implementations for ControllerProviderInterface's less frequently used methods. Easing the amount of boilerplate that module writers are responsible for. These functions can be over written if you wish to utilize them, but it is not required, in fact the  only method that is required is the `setupRoutes` function.

A basic implementation that sets up one route might look something like:
```php
public function setupRoutes(\Silex\Application $application, \Silex\ControllerCollection $controller)
{
    // the $prefix property is set for you if you inherit from BaseControllerProvider and is
    // pulled from the rest.d/<module_name>.json file's prefix property. We'll discuss the configuration file
    // more in the following section.
    $prefix = $this->prefix;
    $controller->get("$prefix/", '\Rest\Controllers\SomeControllerProvider::getIndex');
}
```
We now need to create a method `getIndex`  to handle requests for this route.

A basic example might look something like:
```php
public function getIndex(\Symfony\Component\HttpFoundation\Request $request, \Silex\Application $application)
{
    // Check if user is logged in / authorized ...
    $user = $this->authorize($request);

    // Get some request parameters if needed ...
    $param1 = $this->getStringParam('param1');
    $param2 = $this->getIntParam('param2');

    // Do some work ...
    // where $results is an array or object.
    $results = doWork($param1, $param2);

    // either return a JsonResponse manually or use
    // the $app->json helper method
    return $app->json($results);
}
```
There are two things to note in this example implementation:
  - the function signature
  - and the return statement `return $app->json(..)`

 The function signature is important for obvious reasons while the `$app->json` helper method returns a `\Symfony\Component\HttpFoundation\JsonResponse` which is what Silex will expect that all routes return.

With our code in place we now turn our attention to integrating it with the XDMoD REST stack.

### Integration
In order to integrate a module's rest end points into XDMoD a configuration file will need to be provided by the module creator.

As mentioned in the previous section this file should reside at:

 - [module_home]/configuration/rest.d/[module_name].json

The file should be in the form:
```json
{
    "<route_base_name>": {
        "prefix": "<route_prefix>",
        "controller": "<php_controller_provider>"
    }
}
```
- route_base_name: A 'name' to identify the route.
- route_prefix: the string fragment which will provide a unique path from which all of this module's end points will be served.
- php_controller_provider: The fully qualified PHP class name of the Silex Controller Provider that will handle requests to the route_prefix.

### End Point Loading Order*
Please note that end point configuration files are loaded in the order they are read from the file system.
In most cases this means that they are loaded, and read, in alphabetical order.
