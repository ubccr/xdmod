<?php

if (\xd_utilities\getConfiguration('features', 'xsede') == 'on') {
    $gaqFile = 'xsede.php';
} else {
    $gaqFile = 'xdmod.php';
}

require_once(__DIR__ . "/gaq/$gaqFile");
