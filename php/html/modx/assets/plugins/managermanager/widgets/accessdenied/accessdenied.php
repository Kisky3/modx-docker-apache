<?php
/**
 * mm_widget_accessdenied
 * @version 1.1 (2012-11-13)
 *
 * Close access for some documents by ids.
 * Icon by designmagus.com
 * Originally written by Metaller
 *
 * @uses ManagerManager plugin 0.4.
 *
 * @link http://code.divandesign.biz/modx/mm_widget_accessdenied/1.1
 *
 * @copyright 2012
 */

function mm_widget_accessdenied($ids = '', $message = '', $roles = ''){
	global $modx;

	if ($modx->event->name !== 'OnDocFormRender' || !useThisRule($roles)) {
        return;
    }

	if (!in_array((int)$_GET['id'], makeArray($ids))) {
        return;
    }

    $output = "//  -------------- accessdenied widget include ------------- \n";
    if (!$message) {
        $message = '<span>Access denied</span>Access to current document closed for security reasons.';
    }
    $output .= includeCss(MODX_BASE_URL . 'assets/plugins/managermanager/widgets/accessdenied/accessdenied.css');

    $output .= '
    jQuery("input, div, form[name=mutate]").remove(); // Remove all content from the page
    jQuery("body").prepend(\'<div id="aback"><div id="amessage">' . $message . '</div></div>\');
    jQuery("#aback").css({height: jQuery("body").height()} );';

    $modx->event->output($output . "\n");
}
