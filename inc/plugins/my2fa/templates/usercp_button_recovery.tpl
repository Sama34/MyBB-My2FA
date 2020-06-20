<br class="clear" />
<a href="javascript:void(0)" title="{$lang->my2fa_recovery_button}" class="open-recovery-codes float_right button small_button" data-selector="#recovery-codes" rel="modal:open">{$lang->my2fa_recovery_button}</a>
<div id="recovery-codes" style="display:none">
	<table border="0" cellspacing="{$theme['borderwidth']}" cellpadding="{$theme['tablespace']}" class="tborder">
		<tr>
			<td class="thead" style="text-align:center"><strong>{$lang->my2fa_recovery_button}</strong></td>
		</tr>
		<tr>
			<td class="trow1" id="recovery-list">{$recoveryCodes}</td>
		</tr>
		<tr>
			<td class="footer" style="text-align:center">
				<form action="usercp.php" method="post" id="recovery-form">
					<input type="hidden" name="action" value="my2fa" />
					<input type="hidden" name="manage" value="recovery" />
					<input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
					<input type="hidden" name="generate" value="1" />
					<input type="submit" class="button" value="{$lang->my2fa_recovery_generate}" onclick="javascript: return my2fa_recovery_generate();" />
				</form>
			</td>
		</tr>
	</table>
</div>
<script type="text/javascript">
	$('a.open-recovery-codes').click(function(event) {
		event.preventDefault();

		$($(this).attr('data-selector')).modal({
			fadeDuration: 250,
			keepelement: true
		});

		return false;
	});

	function my2fa_recovery_generate()
	{
		if(confirm('{$lang->my2fa_recovery_generate_confirm}'))
		{
			$.ajax({
				url: 'usercp.php?' + $('#recovery-form').serialize(),
				type : 'post',
				dataType: 'json',
				success: function (request)
				{
					if(request.errors)
					{
						alert(request.errors);
					}

					if(request.success == 1)
					{
						$('#recovery-list').html(request.content);
					}
				},
			});
		}

		return false;
	}
</script>