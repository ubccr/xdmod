<?php

if (\xd_utilities\getConfiguration('features', 'xsede') == 'on') {
    $gaqFile = 'xsede.php';
} else {
    $gaqFile = 'xdmod.php';
}

require_once(dirname(__FILE__) . "/gaq/$gaqFile");
