<form action="usercp.php" method="get">
	<input type="hidden" name="action" value="my2fa" />
	<input type="hidden" name="method" value="{$method['publicName']}" />
	<input type="hidden" name="disable" value="1" />
	<input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
	<input type="submit" class="my2fa__button my2fa__button--disable button" value="{$lang->my2fa_disable_button}" />
</form>