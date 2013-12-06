<?
require 'bean-patrol/vendors/rb.5.3.2.php';
require $_SERVER['DOCUMENT_ROOT'] . '/app/security/bean-patrol.class.php';

$guard = new BeanPatrol();

// If post type is set load the controller
if(isset($_POST['type']))
	$guard->beanCheckpoint($_POST['type']);


// Is user logged in
if($guard->loggedIn()){

	echo 'Hello ' . $guard->userInfo('email') . '!';

} else {

	$guard->renderView('login');

}

?>