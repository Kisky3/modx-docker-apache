<?php
if (!isset($modx) || !$modx->isLoggedin()) {
    exit;
}
if (!$modx->hasPermission('messages')) {
    $e->setError(3);
    $e->dumpError();
}

$id = $_REQUEST['id'];

// check the user is allowed to delete this message
$tbl_user_messages = $modx->getFullTableName('user_messages');
$rs = $modx->db->select('recipient', $tbl_user_messages, "id={$id}");
if ($modx->db->getRecordCount($rs) != 1) {
    echo 'Wrong number of messages returned!';
    exit;
}

$row = $modx->db->getRow($rs);
if ($row['recipient'] != $modx->getLoginUserID()) {
    echo 'You are not allowed to delete this message!';
    exit;
}

// delete message
$rs = $modx->db->delete($tbl_user_messages, "id={$id}");
if (!$rs) {
    echo 'Something went wrong while trying to delete the message!';
    exit;
}
header('Location: index.php?a=10');
