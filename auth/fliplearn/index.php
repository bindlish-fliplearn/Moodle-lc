<?php


require_once(__DIR__.'/../../config.php');
require_once(__DIR__.'/auth.php');

$redirecturl = new moodle_url("/");
redirect($redirecturl);