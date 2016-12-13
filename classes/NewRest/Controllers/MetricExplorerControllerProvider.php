<?php

namespace NewRest\Controllers;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Silex\ControllerCollection;
use Symfony\Component\HttpKernel\Exception\HttpException;
use \Symfony\Component\HttpFoundation\JsonResponse;

use DataWarehouse\Access\MetricExplorer;
use XDUser;

class MetricExplorerControllerProvider extends BaseControllerProvider
{


    /**
     * The identifier that is used to store 'queries' in the user profile.
     *
     * @var string
     */
    const _QUERIES_STORE = 'queries_store';

    /**
     * The identifier that was used to store 'queries' in the user profile.
     * This will be used to find old values to migrate to the new storage
     * methodology.
     *
     * @var string
     */
    const _OLD_QUERIES_STORE = 'queries';

    /**
     * The identifier that is used to store metadata about the queries.
     *
     * @var string
     */
    const _QUERY_METADATA = 'query_metadata';

    /**
     *
     * @var string
     */
    const _QUERIES_MIGRATED = 'queries_migrated';

# ==============================================================================

    /**
     * This function is responsible for the setting up of any routes that this
     * ControllerProvider is going to be managing. It *must* be overridden by
     * a child class.
     *
     * @param Application $app
     * @param ControllerCollection $controller
     * @return null
     */
    public function setupRoutes(Application $app, ControllerCollection $controller)
    {
        $root = $this->prefix;
        $base = '\NewRest\Controllers\MetricExplorerControllerProvider';

        $idConverter = function ($id) {
            return (int)$id;
        };

        // QUERY ROUTES ========================================================
        $controller
            ->get("$root/queries", "$base::getQueries");

        $controller
            ->get("$root/queries/{id}", "$base::getQueryById")
            ->convert('id', $idConverter);

        $controller
            ->post("$root/queries", "$base::createQuery");

        $controller
            ->post("$root/queries/{id}", "$base::updateQueryById")
            ->convert('id', $idConverter);

        $controller
            ->delete("$root/queries/{id}", "$base::deleteQueryById")
            ->convert('id', $idConverter);
        // QUERY ROUTES ========================================================

    }

    /**
     * Retrieve all of the queries that the requesting user has currently saved.
     *
     * @param Request $request
     * @param Application $app
     * @return JsonResponse
     */
    public function getQueries(Request $request, Application $app)
    {
        $action = 'getQueries';
        $payload = array(
            'success' => false,
            'action' => $action,
        );
        $statusCode = 401;

        try {

            $user = $this->authorize($request);
            if (isset($user)) {
                $metaData = new \UserStorage($user, self::_QUERY_METADATA);
                $queries = new \UserStorage($user, self::_QUERIES_STORE);
                $migrated = $this->isMetaDataValid($metaData);

                if (!$migrated) {
                    $this->migrateOldQueries($user, $queries);
                    $metaData->upsert(0, array(self::_QUERIES_MIGRATED => true));
                }

                $data = $queries->get();

                foreach ($data as &$query) {
                    $this->removeRoleFromQuery($user, $query);
                }

                $payload['data'] = $data;
                $payload['success'] = true;
                $statusCode = 200;
            } else {
                $payload['message'] = self::_DEFAULT_ERROR_MESSAGE;
            }
        } catch (BadRequestHttpException $e) {
            $payload['message'] = $e->getMessage();
            $statusCode = $e->getStatusCode();
        } catch (HttpException $e) {
            $payload['message'] = $e->getMessage();
            $statusCode = $e->getStatusCode();
        } catch (\Exception $e) {
            $payload['message'] = $e->getMessage();
            $statusCode = 500;
        }

        return $app->json(
            $payload,
            $statusCode
        );
    }

    /**
     * Retrieve a query's information by unique id for the requesting user.
     *
     * @param Request $request
     * @param Application $app
     * @param $id
     * @return JsonResponse
     */
    public function getQueryById(Request $request, Application $app, $id)
    {
        $action = 'getQueryById';
        $payload = array(
            'success' => false,
            'action' => $action,
        );
        $statusCode = 401;

        try {
            $user = $this->authorize($request);
            if (isset($user)) {
                $queries = new \UserStorage($user, self::_QUERIES_STORE);

                $query = $queries->getById($id);

                if (isset($query)) {
                    $payload['data'] = $query;
                    $payload['success'] = true;
                    $statusCode = 200;
                } else {
                    $payload['message'] = 'Unable to find the query identified by the provided id: ' . $id;
                    $statusCode = 404;
                }
            } else {
                $payload['message'] = self::_DEFAULT_ERROR_MESSAGE;
            }
        } catch (BadRequestHttpException $e) {
            $payload['message'] = $e->getMessage();
            $statusCode = $e->getStatusCode();
        } catch (HttpException $e) {
            $payload['message'] = $e->getMessage();
            $statusCode = $e->getStatusCode();
        } catch (\Exception $e) {
            $payload['message'] = $e->getMessage();
            $statusCode = 500;
        }

        return $app->json(
            $payload,
            $statusCode
        );
    }

    /**
     * Create a new query to be stored in the requesting users User Profile.
     *
     * @param Request $request
     * @param Application $app
     * @return JsonResponse
     */
    public function createQuery(Request $request, Application $app)
    {
        $action = 'creatQuery';
        $payload = array(
            'success' => false,
            'action' => $action,
        );
        $statusCode = 401;
        try {
            $user = $this->authorize($request);
            if (isset($user)) {
                $queries = new \UserStorage($user, self::_QUERIES_STORE);
                $data = json_decode(
                    $this->getStringParam($request, 'data', true),
                    true
                );
                $success = $queries->insert($data) != null;
                $payload['success'] = $success;
                if ($success) {
                    $payload['success'] = true;
                    $payload['data'] = $data;
                    $statusCode = 200;
                }  else {
                    $payload['message'] = 'Error creating chart. User is over the chart limit.';
                    $statusCode = 500;
                }
            } else {
                $payload['message'] = self::_DEFAULT_ERROR_MESSAGE;
            }
        } catch (BadRequestHttpException $e) {
            $payload['message'] = $e->getMessage();
            $statusCode = $e->getStatusCode();
        } catch (HttpException $e) {
            $payload['message'] = $e->getMessage();
            $statusCode = $e->getStatusCode();
        } catch (\Exception $e) {
            $payload['message'] = $e->getMessage();
            $statusCode = 500;
        }

        return $app->json(
            $payload,
            $statusCode
        );

    }

    /**
     * Update the query identified by the provided 'id' parameter with the
     * values of the following form params ( if provided ):
     *   - name
     *   - config
     *
     * @param Request $request
     * @param Application $app
     * @param $id
     * @return JsonResponse
     */
    public function updateQueryById(Request $request, Application $app, $id)
    {
        $action = 'updateQuery';
        $payload = array(
            'success' => false,
            'action' => $action,
            'message' => 'success'
        );
        $statusCode = 401;

        try {
            $user = $this->authorize($request);
            if (isset($user)) {
                $queries = new \UserStorage($user, self::_QUERIES_STORE);

                $query = $queries->getById($id);
                if (isset($query)) {


                    $data = $this->getStringParam($request, 'data');
                    if (isset($data)) {
                        $jsonData = json_decode($data, true);
                        $name = isset($jsonData['name']) ? $jsonData['name'] : null;
                        $config = isset($jsonData['config']) ? $jsonData['config'] : null;
                    } else {
                        $name = $this->getStringParam($request, 'name');
                        $config = $this->getStringParam($request, 'config');
                    }

                    if (isset($name)) $query['name'] = $name;
                    if (isset($config)) $query['config'] = $config;

                    $queries->upsert($id, $query);

                    // required for the UI to do it's thing.
                    $total = count($queries->get());

                    // make sure everything is in place for returning to the
                    // front end.
                    $payload['total'] = $total;
                    $payload['success'] = true;
                    $statusCode = 200;
                } else {
                    $payload['message'] = 'There was no query found for the given id';
                    $statusCode = 404;
                }
            } else {
                $payload['message'] = self::_DEFAULT_ERROR_MESSAGE;
            }
        } catch (BadRequestHttpException $e) {
            $payload['message'] = $e->getMessage();
            $statusCode = $e->getStatusCode();
        } catch (HttpException $e) {
            $payload['message'] = $e->getMessage();
            $statusCode = $e->getStatusCode();
        } catch (\Exception $e) {
            $payload['message'] = $e->getMessage();
            $statusCode = 500;
        }

        return $app->json(
            $payload,
            $statusCode
        );
    }

    /**
     * Delete the query identified by the provided form-param 'id'.
     *
     * @param Request $request
     * @param Application $app
     * @param $id of the query to be deleted.
     * @return JsonResponse
     */
    public function deleteQueryById(Request $request, Application $app, $id)
    {
        $action = 'deleteQueryById';
        $payload = array(
            'success' => false,
            'action' => $action,
            'message' => 'success'
        );
        $statusCode = 401;

        try {
            $user = $this->authorize($request);
            if (isset($user)) {
                $queries = new \UserStorage($user, self::_QUERIES_STORE);
                $query = $queries->getById($id);


                if (isset($query)) {

                    $before = count($queries->get());
                    $after = $queries->delById($id);
                    $success = $before > $after;

                    // make sure everything is in place for returning to the
                    // front end.
                    $payload['success'] = $success;
                    $payload['message'] = $success ? $payload['message'] : 'There was an error removing the query identified by: ' . $id;

                    $statusCode = $success ? 200 : 500;
                } else {
                    $payload['message'] = 'There was no query found for the given id';
                    $statusCode = 404;
                }
            } else {
                $payload['message'] = self::_DEFAULT_ERROR_MESSAGE;
            }
        } catch (BadRequestHttpException $e) {
            $payload['message'] = $e->getMessage();
            $statusCode = $e->getStatusCode();
        } catch (HttpException $e) {
            $payload['message'] = $e->getMessage();
            $statusCode = $e->getStatusCode();
        } catch (\Exception $e) {
            $payload['message'] = $e->getMessage();
            $statusCode = 500;
        }

        return $app->json(
            $payload,
            $statusCode
        );
    }

    /**
     * A helper function that is used to migrate / clean up users old query
     * stores to the new ones.
     *
     * @param \XDUser $user
     * @param \UserStorage $queries
     * @param bool|true $removeOldQueries
     *
     * @throws \Exception
     */
    private function migrateOldQueries(\XDUser $user, \UserStorage $queries, $removeOldQueries = true)
    {
        if (!isset($user)) return;

        $profile = $user->getProfile();
        $oldQueries = $profile->fetchValue(self::_OLD_QUERIES_STORE);

        if (isset($oldQueries) && !is_array($oldQueries)) {
            $oldQueries = json_decode($oldQueries, true);
        }

        if (isset($oldQueries['data'])) {
            $oldQueries = $oldQueries['data'];
            if (isset($oldQueries) && !is_array($oldQueries)) {
                $oldQueries = json_decode($oldQueries, true);
            }
        }

        if (isset($oldQueries) && count($oldQueries) > 0) {
            $validQueries = $this->retrieveValidQueries($oldQueries);
            if (count($validQueries) > 0) {
                foreach ($validQueries as $query) {
                    $queries->insert($query);
                }
            }
        }
    }

    private function removeRoleFromQuery(XDUser $user, array &$query)
    {
        // If the query doesn't have a config, stop.
        if (!array_key_exists('config', $query)) {
            return;
        }

        // If the query config doesn't have an active role, stop.
        $queryConfig = json_decode($query['config']);
        if (!property_exists($queryConfig, 'active_role')) {
            return;
        }

        // Remove the active role from the query config.
        $activeRoleId = $queryConfig->active_role;
        unset($queryConfig->active_role);

        // Convert the active role into global filters.
        MetricExplorer::convertActiveRoleToGlobalFilters($user, $activeRoleId, $queryConfig->global_filters);

        // Store the updated config in the query.
        $query['config'] = json_encode($queryConfig);
    }

    /**
     * Another helper function that just encapsulates the logic of what it means
     * to filter valid queries from the old queries.
     *
     * @param array $oldQueries the array that will be used as a source for valid
     *                          queries.
     *
     * @return array of queries that contain non-null and non-empty string name
     *               and config properties.
     */
    private function retrieveValidQueries(array $oldQueries)
    {
        $results = array();
        foreach ($oldQueries as $oldQuery) {
            $hasName = !empty($oldQuery['name']);
            $hasConfig = !empty($oldQuery['config']);
            $isValid = $hasName && $hasConfig;

            if ($isValid) $results[] = $oldQuery;
        }
        return $results;
    }

    /**
     * @param \UserStorage $metaData
     * @return bool
     */
    public function isMetaDataValid(\UserStorage $metaData)
    {

        $meta = $this->toArray($metaData->get());

        if (count($meta) < 1 || (count($meta) > 1 && !isset($meta[0][self::_QUERIES_MIGRATED]))) {
            $meta[self::_QUERIES_MIGRATED] = false;
            $metaData->upsert(0, $meta);
            return false;
        }

        return true;
    }

    /**
     *
     * @param mixed $value
     *
     * @return mixed
     */
    private function toArray($value)
    {
        return is_string($value) ? json_decode($value) : $value;
    }

}
