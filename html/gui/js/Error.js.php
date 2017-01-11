/**
 * Error.js.php
 *
 * Defines the error codes used by XDMoD. This file is generated from
 * server-side error code definitions in the PHP XDError class.
 */

Ext.namespace('XDMoD.Error');

<?php

    require_once('../../../configuration/linker.php');

    \xd_response\useDynamicJavascriptHeaders();

foreach (XDError::getErrorCodes() as $errorName => $errorCode) {
    echo "XDMoD.Error.$errorName = $errorCode;\n";
}

?>
