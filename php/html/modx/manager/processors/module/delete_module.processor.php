<?php
if (!isset($modx) || !$modx->isLoggedin()) {
    exit;
}
if (!$modx->hasPermission('delete_module')) {
    $e->setError(3);
    $e->dumpError();
}
$id = intval($_GET['id']);

// invoke OnBeforeModFormDelete event
$tmp = array("id" => $id);
$modx->invokeEvent("OnBeforeModFormDelete", $tmp);

//ok, delete the module.
$sql = "DELETE FROM " . $modx->getFullTableName("site_modules") . " WHERE id=" . $id . ";";
$rs = $modx->db->query($sql);
if (!$rs) {
    echo "Something went wrong while trying to delete the module...";
    exit;
} else {

    //ok, delete the module dependencies.
    $sql = "DELETE FROM " . $modx->getFullTableName("site_module_depobj") . " WHERE module='" . $id . "';";
    $rs = $modx->db->query($sql);

    //ok, delete the module user group access.
    $sql = "DELETE FROM " . $modx->getFullTableName("site_module_access") . " WHERE module='" . $id . "';";
    $rs = $modx->db->query($sql);

    // invoke OnModFormDelete event
    $tmp = array("id" => $id);
    $modx->invokeEvent("OnModFormDelete", $tmp);


    // empty cache
    $modx->clearCache(); // first empty the cache
    // finished emptying cache - redirect

    $header = "Location: index.php?a=106&r=2";
    header($header);
}
