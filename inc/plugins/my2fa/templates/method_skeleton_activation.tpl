{$errors}
<form action="usercp.php?action=my2fa&method={$method['publicName']}" method="post">
	<table border="0" cellspacing="{$theme['borderwidth']}" cellpadding="{$theme['tablespace']}" class="tborder">
		<tr>
			<td class="thead"><strong>{$lang->my2fa_title} - {$method['name']}</strong></td>
		</tr>
		<tr>
			<td class="tcat">Just fill the input</td>
		</tr>
		<tr>
			<td class="trow1" style="text-align:center">
				<p>Insert 123 in order to enable it.</p>
				<input type="text" name="otp" class="textbox" style="text-align:center" placeholder="123" autocomplete="off" autofocus />
			</td>
		</tr>
	</table>
	<br />
	<div style="text-align:center">
		<input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
		<input type="submit" class="button" value="{$lang->my2fa_enable_button}" />
		<a href="usercp.php?action=my2fa" class="button small_button">{$lang->my2fa_cancel_button}</a>
	</div>
</form>