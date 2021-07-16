<?php

class ContextMenu {

    var $i;

    function __construct($id = '', $width = 120, $visible = false) {
        global $ContextMenuCnt;
        if (!$ContextMenuCnt) {
            $ContextMenuCnt = 0;
        }
        $ContextMenuCnt++;
        $this->html = '';
        $this->visible = $visible ? $visible : false;
        $this->width = is_numeric($width) ? intval($width) : 120;
        $this->id = isset($id) ? $id : "cntxMnu{$ContextMenuCnt}"; // set id
        $this->i = 0;
    }

    function addItem($text, $action = "", $img = "", $disabled = 0) {
        global $base_url, $manager_theme, $_style;
        if (!$img) {
            $img = $base_url . $_style['tx'];
        }
        if (substr($action, 0, 3) == "js:") {
            $action = substr($action, 3);
        } else {
            if (substr($action, 0, 3) == "hl:") {
                $action = "window.location.href='" . substr($action, 3) . "'";
            } else {
                $action = "window.location.href='" . $action . "'";
            }
        }
        $action = " onmouseover=\"this.className='cntxMnuItemOver';\" onmouseout=\"this.className='cntxMnuItem';\" onclick=\"$action; hideCntxMenu('" . $this->id . "');\"";
        if ($disabled) {
            $action = "";
        }
        $this->html .= "
			<div class='" . ($disabled ? "cntxMnuItemDisabled" : "cntxMnuItem") . "' $action>
				<img src='$img' align='absmiddle' />&nbsp;$text
			</div>
		";
        $this->i++;
    }

    function addSeparator() {
        $this->html .= "
			<div class='cntxMnuSeparator'></div>
		";
    }

    function render() {
        global $modx;
        global $ContextMenuScript;

        $html = $ContextMenuScript .
            "<div id='" . $this->id . "' class='contextMenu' style='width:" . $this->width . "px; visibility:" . ($this->visible ? 'visible' : 'hidden') . "'>" . $this->html . "</div>";
        $ContextMenuScript = ""; // reset css
        return $html;
    }

    function getClientScriptObject() {
        return "getCntxMenu('" . $this->id . "')";
    }
}

$ContextMenuScript = <<<BLOCK
<script>
	function getCntxMenu(id) {
		return $(id);
	}
	function hideCntxMenu(id){
		var cm = getCntxMenu(id);
		cm.style.visibility = 'hidden';
	}
</script>
BLOCK;

?>
