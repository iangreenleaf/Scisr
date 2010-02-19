#!/usr/bin/env php
<?php

require_once('Scisr.php');

$cli = new Scisr_CLI();
$status = $cli->process();
exit($status);
