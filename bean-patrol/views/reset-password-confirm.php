<form method="POST" action="/">
	<input id="password" type="password" name="password" placeholder="Password">
	<input id="password-confirm" type="password" name="password-confirm" placeholder="Confirm Password">
	<input type="hidden" name="type" value="reset-confirm">
	<input type="hidden" name="token" value="<?=$_GET['tkn']?>">
	<input type="submit" value="Reset your password">
</form>