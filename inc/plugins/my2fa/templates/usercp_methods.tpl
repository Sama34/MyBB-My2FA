<table border="0" cellspacing="{$theme['borderwidth']}" cellpadding="{$theme['tablespace']}" class="tborder">
	<tr>
		<td class="thead" colspan="2"><strong>{$lang->my2fa_title} - {$lang->my2fa_methods}</strong></td>
	</tr>
	<tr>
		<td class="tcat" colspan="2">{$lang->my2fa_description}</td>
	</tr>
	{$my2faUsercpMethodRows}
</table>
<script>
	var my2faDisableButtons = $('.my2fa__button--disable');
	my2faDisableButtons.on('click', function(event) {
		event.preventDefault();
		var form = $(this).closest('form');

		MyBB.prompt('{$lang->my2fa_disable_user_method_confirmation}', {
			buttons: [
				{title: yes_confirm, value: true},
				{title: no_confirm, value: false}
			],
			submit: function (e, v, m, f) {
				if (v)
					form.submit();
			}
		})
	})
</script>