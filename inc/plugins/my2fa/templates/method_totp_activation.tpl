{$errors}
<form action="usercp.php?action=my2fa&method={$method['publicName']}" method="post">
	<table border="0" cellspacing="{$theme['borderwidth']}" cellpadding="{$theme['tablespace']}" class="tborder">
		<tr>
			<td class="thead"><strong>{$lang->my2fa_title} - {$method['name']}</strong></td>
		</tr>
		<tr>
			<td class="tcat">{$lang->my2fa_totp_main_instruction}</td>
		</tr>
		<tr>
			<td class="trow1" style="text-align:center">
				<p>
					{$lang->my2fa_totp_instruction_1}
					{$lang->my2fa_totp_manual_secret_key_1} <a href="javascript:void(0)" title="{$secretKey}" class="open-secret-code" data-selector="#secret-code" rel="modal:open">{$lang->my2fa_totp_manual_secret_key_2}</a>.
				</p>
				<img src="{$qrCode}" width="150" height="150" style="border:20px solid white;margin:15px 0" />
				<p>
					<strong>{$lang->my2fa_totp_instruction_2}</strong><br>
					{$lang->my2fa_totp_instruction_3}
				</p>
				<input type="text" name="otp" class="textbox" style="text-align:center" placeholder="123456" autocomplete="off" autofocus />
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
<div id="secret-code" style="display:none">
	<table border="0" cellspacing="{$theme['borderwidth']}" cellpadding="{$theme['tablespace']}" class="tborder">
		<tr>
			<td class="thead" style="text-align:center"><strong>{$lang->my2fa_totp_secret_key}</strong></td>
		</tr>
		<tr>
			<td class="trow1" style="text-align:center"><code>{$secretKey}</code></td>
		</tr>
	</table>
</div>
<script type="text/javascript">
	$('a.open-secret-code').click(function(event) {
		event.preventDefault();

		$($(this).attr('data-selector')).modal({
			fadeDuration: 250,
			keepelement: true
		});

		return false;
	});
</script>