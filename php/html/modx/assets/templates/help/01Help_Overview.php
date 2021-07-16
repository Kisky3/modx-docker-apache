<?php
if(!isset($modx) || !$modx->isLoggedin()) exit;
?>

<div class="sectionHeader"><?php echo $_lang['about_title']; ?></div><div class="sectionBody">
<?php echo $_lang['about_msg']; ?>
</div>

<div class="sectionHeader"><?php echo $_lang['help_title']; ?></div><div class="sectionBody">
<?php echo $_lang['help_msg']; ?>
</div>

<div class="sectionHeader"><?php echo $_lang['credits']; ?></div>
<div class="sectionBody">
<ul>
<li><a href="https://www.php.net/copyright.php" target="_blank">&copy; 2001-<?php echo date('Y');?> The PHP Group</a></li>
<li><a href="https://www.mysql.com" target="_blank">&copy; <?php echo date('Y');?>, Oracle Corporation and/or its affiliates</a></li>
<li><a href="http://www.destroydrop.com/javascripts/tree/" target="_blank">&copy; 2002-2003 Geir Landr&ouml;</a></li>
<li><a href="https://www.everaldo.com/" target="_blank">&copy; 2006-<?php echo date('Y');?> Everaldo Coelho.</a></li>
</ul>
<?php echo $_lang['credits_shouts_msg']; ?>
</div>
