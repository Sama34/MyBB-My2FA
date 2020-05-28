<tr>
	<td class="trow1" width="100%">
		<strong>{$method['name']}</strong>
		<div class="smalltext">{$method['description']}</div>
	</td>
	<td class="my2fa__control-buttons trow1">
		<form action="usercp.php?action=my2fa&method={$method['publicName']}" method="post">
			<input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
			<input type="submit" class="my2fa__button my2fa__button--enable button" value="{$lang->my2fa_enable_button}" />
		</form>
	</td>
</tr>