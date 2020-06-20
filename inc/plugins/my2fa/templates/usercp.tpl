<html>
<head>
	<title>{$mybb->settings['bbname']} - {$lang->my2fa_title}</title>
	{$headerinclude}
	<style type="text/css">
		.my2fa__button { color: White !important }
		.my2fa__button--enable { background: Green !important; }
		.my2fa__button--disable { background: Maroon !important; }
		.my2fa__button--manage { background: Navy !important; }
		.my2fa__control-buttons { white-space: nowrap; text-align: center; }
		.my2fa__control-buttons form { display: inline-block; }
		.my2fa__codes-used { text-decoration: line-through }
		.my2fa__codes-list {
			-moz-column-count: 2;
			-webkit-column-count: 2;
			column-count: 2;
		}
	</style>
</head>
<body>
	{$header}
	<table width="100%" border="0" align="center">
	<tr>
		{$usercpnav}
		<td valign="top">
			{$my2faUsercpContent}
		</td>
	</tr>
	</table>
	{$footer}
</body>
</html>