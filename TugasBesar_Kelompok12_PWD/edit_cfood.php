<?php
// edit_cfood.php
$editId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($editId <= 0) {
    header('Location: my_tickets.php');
    exit;
}

// forward ke cfood.php dengan parameter edit_order
$_GET['edit_order'] = $editId;
require 'cfood.php';