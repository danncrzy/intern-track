<?php
require __DIR__ . '/config/config.php';
redirect(me() ? home_url() : BASE . '/auth/login.php');
