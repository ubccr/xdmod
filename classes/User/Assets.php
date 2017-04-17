<?php namespace User;

use CCR\DB;
use CCR\DB\iDatabase;
use Exception;
use XDUser;

/**
 * Class Assets
 * @package User
 *
 * It is intended that this class collect all of the functionality required in
 * working with the 'Asset' class and 'assets' table. At present it provides
 * basic CRUD functionality ( Create, Read, Update and Delete ). But, as the use
 * of assets becomes more widespread other functionality may need to reside here.
 *
 */
class Assets
{
    /**
     * Creates a new representation of the provided 'Asset' object in the corresponding
     * 'assets' table.
     *
     * @param Asset $asset the asset to be created. Note, the assetId must not be set.
     * @return Asset with the assetId property populated.
     *
     * @throws Exception if the asset is not set or if the asset already has an assetId.
     */
    public static function createAsset(Asset $asset)
    {
        if (null === $asset) {
            throw new Exception('An asset must be provided to update.');
        }

        $assetId = self::_createAsset(
            DB::factory('database'),
            $asset
        );
        $asset->setAssetId($assetId);

        return $asset;
    }

    /**
     * Retrieves the Asset identified by the provided '$assetId'
     *
     * @param integer $assetId of the Asset to be retrieved
     * @return Asset
     *
     * @throws Exception if an asset id is not provided.
     */
    public static function getAsset($assetId)
    {
        if (null === $assetId) {
            throw new Exception('Must provide an asset id.');
        }

        return self::_getAsset($assetId);
    }

    /**
     * Updates the database representation of the provided '$asset'
     *
     * @param Asset $asset the asset to be updated.
     * @return bool true if 1 row is updated ( based on assetId ) else false.
     * @throws Exception if no $asset is provided or if the $asset does not have an assetId.
     */
    public static function updateAsset(Asset $asset)
    {
        if (null === $asset) {
            throw new Exception('An asset must be provided to update.');
        }

        if (null === $asset->getAssetId()) {
            throw new Exception('A valid asset id is required to complete the requested update.');
        }

        return self::_updateAsset(
            DB::factory('database'),
            $asset
        );
    }

    /**
     * Delete the asset identified by the provided '$assetId'.
     *
     * @param integer $assetId of the asset to be deleted.
     * @return bool if there is 1 row deleted.
     * @throws Exception if the $assetId argument is not provided.
     */
    public static function deleteAsset($assetId)
    {
        if (null === $assetId) {
            throw new Exception('An assetId must be provided.');
        }

        return self::_deleteAsset(
            DB::factory('database'),
            $assetId
        );
    }

    /**
     * @param XDUser $user
     * @return Asset[]
     * @throws Exception
     */
    public static function listAssets(XDUser $user)
    {
        if (null === $user) {
            throw new Exception('A user must be provided');
        }

        return self::_listAssets(
            DB::factory('database'),
            $user
        );
    }

    /**
     * Determine whether or not the provided user has been granted access to the
     * provided asset.
     *
     * @param XDUser $user to be queried for access to '$asset'
     * @param Asset $asset to be queried for.
     * @return bool true if the user has the specified asset else false.
     *
     * @throws Exception if a user or asset is not specified.
     */
    public static function userHasAsset(XDUser $user, Asset $asset)
    {
        if (null === $user->getUserID()) {
            throw new Exception('User must be saved first.');
        }
        if (null === $asset || null === $asset->getAssetId()) {
            throw new Exception('A valid asset must be provided.');
        }
        return self::_userHasAsset(
            DB::factory('database'),
            $user,
            $asset
        );
    }

    /**
     * Determine whether the provided user has been granted access to all of the
     * provided assets.
     *
     * @param  XDUser    $user to be queried for access to '$assets'
     * @param  Asset[]   $assets to be queried for access to.
     * @return bool      true iff the provided user has access to all of the assets
     *                   supplied.
     * @throws Exception if the user or assets are not provided or if the assets
     *                   array is empty.
     */
    public static function userHasAssets(XDUser $user, array $assets)
    {
        if (null === $user->getUserID()) {
            throw new Exception('User must be saved first.');
        }

        return self::_userHasAssets(
            DB::factory('database'),
            $user,
            $assets
        );
    }


    /**
     * Creates the provided '$asset' using the provided '$db'.
     *
     * @param iDatabase $db the database to use when creating the provided asset.
     * @param Asset $asset the asset to be created.
     * @return integer the newly created assetId.
     * @throws Exception if the db or the asset is not set. Also if the assetId is set.
     */
    private static function _createAsset(iDatabase $db, Asset $asset)
    {
        $sql = <<<SQL
INSERT INTO assets(module_id, asset_type_id, name, display, enabled) 
VALUES (:module_id, :asset_type_id, :name, :display, :enabled);
SQL;

        $id = $db->insert($sql, array(
            'module_id' => $asset->getModuleId(),
            'asset_type_id' => $asset->getAssetTypeId(),
            'name' => $asset->getName(),
            'display' => $asset->getDisplay(),
            'enabled' => $asset->getEnabled()
        ));

        return $id;
    }

    /**
     * Attempt to retrieve the asset identified by the provided assetId.
     *
     * @param integer $assetId to be used when retrieving an asset.
     * @return null|Asset null if no asset was found for the provided asset id.
     */
    private static function _getAsset($assetId)
    {
        $db = DB::factory('database');
        $query = <<<SQL
SELECT 
a.*
FROM assets a 
WHERE a.asset_id = :asset_id
SQL;
        $results = $db->query($query, array(':asset_id' => $assetId));
        if ( count(results) > 0 ) {
            return new Asset($results[0]);
        }
        return null;
    }

    /**
     * Attempt to update the provided asset's database representation with the
     * information contained in the argument 'asset'.
     *
     * @param iDatabase $db the database to be used when updating the provided
     *                      asset.
     * @param Asset $asset the asset to be updated.
     * @return bool true if the number of rows updated is 1 else false.
     * @throws Exception if the db or asset is not provided or if the asset does
     *                   not have an assetId.
     */
    private static function _updateAsset(iDatabase $db, Asset $asset)
    {
        $query =<<<SQL
UPDATE assets a SET 
a.module_id = :module_id,
a.asset_type_id = :asset_type_id,
a.name = :name,
a.display = :display,
a.enabled = :enabled
WHERE a.asset_id = :asset_id
SQL;
        $updated = $db->execute($query, array(
            ':module_id' => $asset->getModuleId(),
            ':asset_type_id' => $asset->getAssetTypeId(),
            ':name' => $asset->getName(),
            ':display' => $asset->getDisplay(),
            ':enabled' => $asset->getEnabled(),
            ':asset_id' => $asset->getAssetId()
        ));

        return $updated === 1;
    }

    /**
     * Attempt to delete the asset identified by the provided '$assetId'.
     *
     * @param iDatabase $db the database to use
     * @param integer $assetId of the asset to be deleted.
     *
     * @return bool if the number of deleted rows is 1 else false.
     */
    private static function _deleteAsset(iDatabase $db, $assetId)
    {
        $query = "DELETE FROM assets a WHERE a.asset_id = :asset_id";
        $updated = $db->execute($query, array(
            ':asset_id' => $assetId
        ));
        return $updated === 1;
    }

    /**
     * @param iDatabase $db
     * @param XDUser $user
     * @return Asset[]
     */
    private static function _listAssets(iDatabase $db, XDUser $user)
    {
        $userId = $user->getUserID();

        $query =<<<SQL
SELECT DISTINCT
  ast.*
FROM acl_assets aa
  JOIN acls a
    ON a.acl_id = aa.acl_id
  JOIN assets ast
    ON ast.asset_id = aa.asset_id
  JOIN user_acls AS ua
    ON aa.acl_id = ua.acl_id
WHERE
  ua.user_id = :user_id
AND a.enabled = TRUE
AND ast.enabled = TRUE
SQL;
        $results = $db->query($query, array(
            ':user_id' => $userId
        ));

        $assets = array_reduce($results, function ($carry, $item) {
            $carry []= new Asset($item);
        }, array());

        return $assets;
    }

    /**
     * Determine whether or not the '$user' has been granted access to the provided
     * '$asset'.
     *
     * @param iDatabase $db
     * @param XDUser $user
     * @param Asset $asset
     * @return bool
     */
    private static function _userHasAsset(iDatabase $db, XDUser $user, Asset $asset)
    {
        $userId = $user->getUserId();
        $assetId = $asset->getAssetId();

        $sql = <<<SQL
SELECT
  1
FROM acl_assets aa
  JOIN acls a
    ON a.acl_id = aa.acl_id
  JOIN assets ast
    ON ast.asset_id = aa.asset_id
  JOIN user_acls AS ua
    ON aa.acl_id = ua.acl_id
WHERE
  ua.user_id = :user_id
  AND ast.asset_id = :asset_id
  AND ast.enabled = TRUE;
SQL;

        $results = $db->query($sql, array(
            ':user_id' => $userId,
            ':asset_id' => $assetId
        ));

        return $results[0] == 1;
    }

    /**
     * @param iDatabase $db
     * @param XDUser $user
     * @param Asset[] $assets
     * @return bool
     */
    private static function _userHasAssets(iDatabase $db, XDUser $user, array $assets = array())
    {
        if (count($assets) < 1) {
            return false;
        }

        $userId = $user->getUserID();
        $assetIds = array_reduce($assets, function ($carry, Asset $item) {
            $carry []= $item->getAssetId();
        }, array());

        $sql = <<<SQL
SELECT
  1
FROM acl_assets aa
  JOIN acls a
    ON a.acl_id = aa.acl_id
  JOIN assets ast
    ON ast.asset_id = aa.asset_id
  JOIN user_acls AS ua
    ON aa.acl_id = ua.acl_id
WHERE
  ua.user_id = :user_id
  AND ast.asset_id IN (:asset_ids)
  AND ast.enabled = TRUE;
SQL;
        $results = $db->query($sql, array(
            ':user_id' => $userId,
            ':asset_ids' => implode(', ', $assetIds)
        ));

        return count($results) >= 1;
    }
}
