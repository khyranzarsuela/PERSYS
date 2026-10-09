<?php
require_once '../../session.php';
start_app_session();
logout_session();
header('Location: //ProjM/PERSYS/index.php');
exit;
