<?php
require_once '../../session.php';
start_app_session();
logout_session();
header('Location: /PERSYS/index.php');
exit;
