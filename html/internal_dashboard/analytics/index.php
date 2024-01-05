<?php

# NOTE: Analytics dashboard not yet implemented in Open XDMoD.

require_once(__DIR__ . '/../../../configuration/linker.php');

if (\xd_utilities\getConfiguration('features', 'xsede') == 'on') {
    require_once(__DIR__ . '/xsede.php');
}
