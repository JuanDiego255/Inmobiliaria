<?php
$code = isset($_GET['code']) ? $_GET['code'] : '';
if (!$code) {
    header('Location: /');
    exit;
}
header('Location: /auth/gcal/exchange?code=' . rawurlencode($code));
exit;
