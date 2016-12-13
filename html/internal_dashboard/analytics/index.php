<?php

# NOTE: Analytics dashboard not yet implemented in Open XDMoD.

require_once(dirname(__FILE__) . '/../../../configuration/linker.php');

if (\xd_utilities\getConfiguration('features', 'xsede') == 'on') {
    require_once(dirname(__FILE__) . '/xsede.php');
}
