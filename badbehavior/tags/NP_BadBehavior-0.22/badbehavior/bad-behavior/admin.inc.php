<?php if (!defined('BB2_CORE')) die('I said no cheating!');

function bb2_admin_pages() {
    //global $member;
	//if ($member->isAdmin()) {
    //    $bb2_is_admin = true;
    //}

	//if ($bb2_is_admin) {
		//add_options_page(__("Bad Behavior"), __("Bad Behavior"), 8, 'bb2_options', 'bb2_options');
	//}
    global $admin, $minaccess;
    if (!$minaccess || $minaccess == 0) $minaccess = 8;
    if (intval($admin) < 1 || !($admin >= $minaccess)) doError("You do not have sufficient privileges.");
    bb2_options();
}

function bb2_options()
{
	$settings = bb2_read_settings();

	if ($_POST) {
		if ($_POST['display_stats']) {
			$settings['display_stats'] = true;
		} else {
			$settings['display_stats'] = false;
		}
		if ($_POST['strict']) {
			$settings['strict'] = true;
		} else {
			$settings['strict'] = false;
		}
		if ($_POST['verbose']) {
			$settings['verbose'] = true;
		} else {
			$settings['verbose'] = false;
		}
		bb2_write_settings($settings);
?>
	<div id="message" class="updated fade"><p><strong><?php echo 'Options saved.' ?></strong></p></div>
<?php
	}
?>
	<div class="wrap">
	<h2><?php echo "Bad Behavior"; ?></h2>
	<form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
	<p>For more information please visit the <a href="http://www.bad-behavior.ioerror.us/">Bad Behavior</a> homepage.</p>
	<p>If you find Bad Behavior valuable, please consider making a <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&business=error%40ioerror%2eus&item_name=Bad%20Behavior%20<?php echo BB2_VERSION; ?>%20%28From%20Admin%29&no_shipping=1&cn=Comments%20about%20Bad%20Behavior&tax=0&currency_code=USD&bn=PP%2dDonationsBF&charset=UTF%2d8">financial contribution</a> to further development of Bad Behavior.</p>

	<fieldset class="options">
	<legend><?php echo 'Statistics'; ?></legend>
	<?php bb2_insert_stats(true); ?>
	<p><label><input type="checkbox" name="display_stats" value="true" <?php if ($settings['display_stats']) { ?>checked="checked" <?php } ?>/> <?php echo 'Display statistics in blog footer'; ?></label></p>
	</fieldset>

	<fieldset class="options">
	<legend><?php echo 'Logging'; ?></legend>
	<p><label><input type="checkbox" name="verbose" value="true" <?php if ($settings['verbose']) { ?>checked="checked" <?php } ?>/> <?php echo 'Verbose HTTP request logging'; ?></label></p>
	<legend><?php echo 'Strict Mode'; ?></legend>
	<p><label><input type="checkbox" name="strict" value="true" <?php if ($settings['strict']) { ?>checked="checked" <?php } ?>/> <?php echo 'Strict checking (blocks more spam but may block some people)'; ?></label></p>
	</fieldset>

	<p class="submit"><input type="submit" name="submit" value="<?php echo 'Update &raquo;'; ?>" /></p>
	</form>
	</div>
<?php
}

bb2_admin_pages();

?>
