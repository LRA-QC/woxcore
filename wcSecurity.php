<?php 
//! Class that manage the authentication
/*! Class that manage the authentication
*/
class wcSecurity
{
	/// this function return a boolean indicating if the user is currently authenticated
	static function isAuthenticated()
	{
		if ( isset( $_SESSION['user']['id'] ) )
			return true;
		return false;
	}
	
	/// this command will redirect the client if he his not currently authenticated
	static function requireAuthentication( $urlRedirect = 'login.php' )
	{
		if ( wcSecurity::isAuthenticated() == false )
			wcCore::redirect( $urlRedirect );
	}
	
	/// authenticate the client with the specified username and password (username is currently the email address)
	static function authenticate($user,$password)
	{
		//wcSecurity::logoff();
		$db 	= wcCore::getDatabaseHandle();
	
		$email	= trim( wcCore::stringEncode( strip_tags ( stripslashes  ( $user ) ) ) );
       
		


		$db		= wcCore::getDatabaseHandle();
		$data = $db->querySelect('members', sprintf( "email = '%s'  and hash1='%s' ", $email, sha1($password)), '*' , 1);  

        $kdata	= count($data);
        
        if ($kdata>0)
        {
			$_SESSION['user']['email'] =  $email;
			$_SESSION['user']['nickname'] = $data[0]['nickname'] ;
			$_SESSION['user']['id'] = $data[0]['id'] ;
			$_SESSION['user']['firstname'] = $data[0]['firstname'] ;
			$_SESSION['user']['lastname'] = $data[0]['lastname'] ;

			$sql=sprintf("email = '%s' and hash1='%s' ", $email, sha1($password) );
			$db->queryUpdate('Members', 'dateLastlogin=now()', $sql);
			
//			echo sprintf("{ success: true, nickname: \"%s\", lastlogin: \"%s\"}", $data[0]['nickname'],$data[0]['dateLastlogin']);
      	}
	}
	
	/// terminate the current connection, logoff and close our session
	static function logoff($urlRedirect='/')
	{
		unset( $_SESSION['user'] );
		if ( isset( $_COOKIE[ session_name() ] ) )   //kill session cookie
			setcookie(session_name(), '', time()-42000, '/');
		wcCore::redirect( $urlRedirect );
	}
	
	/// get the current username of our current session
	static function getUsername()
	{
		if ( isset( $_SESSION['user']['name'] ) )
			return $_SESSION['user']['name'];
		return null;	
	}
}

?>
