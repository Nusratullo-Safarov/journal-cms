<?php
require __DIR__ . '/../includes/bootstrap.php';

if (is_post() && csrf_ok()) logout();
redirect('admin/login.php');
