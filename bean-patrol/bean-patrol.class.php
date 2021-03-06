<?
class BeanPatrol {

		public $errors;

		function __construct(){
			require $_SERVER['DOCUMENT_ROOT'] . '/app/rb.php';

			if (session_status() === PHP_SESSION_NONE){session_start();}

			// ========== CONFIGURE HERE ===============
			$database = '{{your_db_name}}';
			$user = '{{your_db_user}}';
			$pass = '{{your_db_password}}';
			// ========== STOP CONFIGURE ===============

			R::setup('mysql:host=localhost;
			dbname='.$database,$user,$pass);
			R::$writer->setUseCache(true);

		}

		public function login($email, $password) {
			$salt = R::find('user', ' email = "' . $email . '"');
			foreach($salt as $grain){
				$saltstr = $grain->salt;
			}

			if(isset($saltstr)){
				$salty = $this->salt($password, $saltstr);
				$user = R::find('user', ' email = "' . $email . '" AND password = "' . $salty . '" LIMIT 0,1');

				foreach($user as $member){
					$_SESSION['auth'] = true;
					$_SESSION['email'] = $member->email;
					return true;
				}
					
			} else {
				echo 'User not found.';
			}

		}

		public function logout(){
			$_SESSION['auth'] = false;
			$_SESSION['email'] = false;
		}

		public function register($email, $password, $passwordConfirm){

			$user = R::dispense('user');
			$salt = $this->generateSalt();

			if(!$this->compareValues($password, $passwordConfirm)){
				$this->logError('Passwords do not match');
				return $this->errors;
			} else {

				if(!$this->uniqueEmail($email)){
					return $this->errors;
				} else {
					$user->email = $email;
					$user->password = $this->salt($password, $salt);
					$user->salt = $salt;
					$user->ip = $_SERVER['REMOTE_ADDR'];
					$id = R::store($user);

					$this->login($user->email, $password);

					if($id){
						return $id;
					} else {
						print_r($this->errors);
					}
				}

			}

		}

		public function resetPassword($email){
			$users = R::find('user', ' email = "' . $email . '" LIMIT 0,1');
			
			if($users){
				foreach($users as $user){
					$user->bean_patrol_token = $this->generateSalt();
					R::store($user);

					// email user
					$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
					$this->mail($user->email, 'Password Reset', 'Click to reset <a href="' . $protocol . $_SERVER['SERVER_NAME'] . '?tkn=' . $user->bean_patrol_token . '">your password</a>');

				}	
			} else {
				echo 'blast';
				return false;
			}

		}

		public function confirmResetPassword($token, $password, $passwordConfirm){
			$users = R::find('user', ' bean_patrol_token = "' . $token . '"');

			if(!$this->compareValues($password, $passwordConfirm)){
				return $errors[] = 'Passwords do not match';
			} else {

				if($users){
					foreach($users as $user){
						$salt = $this->generateSalt();
						$user->password = $this->salt($password, $salt);
						$user->salt = $salt;
						$user->bean_patrol_token = '';
						R::store($user);
						$this->login($user->email, $password);
					}	
				}
			}
		}

		public function beanCheckpoint($type){
			switch($type){
				
				case 'login':
					if(!$this->login($_POST['email'], $_POST['password'])){
						// render error message
					}
					break;

				case 'register':
					if(!$this->register($_POST['email'], $_POST['password'], $_POST['password-confirm'])){
						// render error message
					}
					break;

				case 'logout':
					$this->logout();
					break;

				case 'reset':
					$this->resetPassword($_POST['email']);
					break;

				case 'reset-confirm':
					$this->confirmResetPassword($_POST['token'], $_POST['password'], $_POST['password-confirm']);
					break;

			}
		}

		public function loggedIn(){
			if(isset($_SESSION['auth']) && $_SESSION['auth']){
				return true;
			} else {
				return false;
			}
		}

		public function logout(){
			$_SESSION['auth'] = false;
			$_SESSION['email'] = false;
		}

		public function userInfo($field){
			$user = R::load('user', ' WHERE `email` = ' . $_SESSION['email']);
			return $user->$field;
		}

		public function logError($message){
			$errors[] = $message;
		}

		public function getErrors(){
			return $this->errors;
		}


		/*===================================
		Helpers
		===================================*/
	
		public function salt($pass, $salt){
			return md5($pass . $salt);
		}

		public function compareValues($one, $two){
			return ($one == $two) ? true : false;
		}

		public function uniqueEmail($email){
			$result = R::find('user', ' email = "' . $email . '"');
			if(isset($result[1])){
				$this->logError('Email already exists.');
				return false;
			} else {
				return true;
			}
		}

		public function generateSalt(){
			return md5(RAND(0, 1000000) . date('U'));
		}

		public function renderView($view){
			include('views/' . $view . '.php');
		}

		public function mail($to, $subject, $message){
			$headers = "Content-Type: text/html";
			mail($to, $subject, $message, $headers);
		}
		
}
?>