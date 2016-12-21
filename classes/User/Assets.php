<?php namespace User;

use Exception;
use PDO;
use XDUser;

class Assets
{

    public static function createAsset(PDO $connection, Asset $asset)
    {
        if (!isset($connection, $asset)) {
            return false;
        }

        if (NULL != $asset->getAssetId()){
            throw new Exception('Cannot create an asset that already has an id');
        }

        $sql = <<<SQL
INSERT INTO assets(module_id, asset_type_id, name, display, enabled) 
VALUES (:module_id, :asset_type_id, :name, :display, :enabled);
SQL;

        $prepared = $connection->prepare($sql);

        $prepared->bindParam('module_id', $asset->getModuleId());
        $prepared->bindParam('asset_type_id', $asset->getAssetTypeId());
        $prepared->bindParam('name', $asset->getName());
        $prepared->bindParam('display', $asset->getDisplay());
        $prepared->bindParam('enabled', $asset->getEnabled());

        $result = $prepared->execute();
        $prepared->closeCursor();

        return $result;
    }

    /**
     * Determine whether or not the '$user' has been granted access to the provided
     * '$asset'.
     *
     * @param PDO $connection
     * @param XDUser $user
     * @param Asset $asset
     * @return bool
     */
    public static function userHasAsset(PDO $connection, XDUser $user, Asset $asset)
    {
        if (!isset($connection, $user, $asset)) {
            return false;
        }

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

        $prepared = $connection->prepare($sql);
        $prepared->bindParam('user_id', $userId);
        $prepared->bindParam('asset_id', $assetId);

        $prepared->execute();

        $result = $prepared->fetchAll(PDO::FETCH_ASSOC);

        return $result[0] == 1;
    }

    /**
     * @param PDO $connection
     * @param XDUser $user
     * @param Asset[] $assets
     * @return bool
     */
    public static function userHasAssets(PDO $connection, XDUser $user, array $assets = array())
    {
        if (!isset($connection, $user, $assets)) {
            return false;
        }

        $userId = $user->getUserID();
        $assetIds = array_reduce($assets, function($carry, Asset $item) use ($connection) {
            $carry []= $connection->quote($item->getAssetId(), PDO::PARAM_INT);
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
        $prepared = $connection->prepare($sql);
        $prepared->bindParam('user_id', $userId);
        $prepared->bindParam('asset_ids', implode(', ', $assetIds));

        $prepared->execute();

        $result = $prepared->fetchAll(PDO::FETCH_ASSOC);

        return $result[0] == 1;
    }

}
