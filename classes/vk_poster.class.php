<?

class vk_auth
{

	private $email = '';
	private $pwd = '';
	private $phone = '';
	private $sleeptime = 1;
	private $minicurl;


	function __construct()
	{
		$this->email = VKEMAIL;
		$this->pwd = VKPWD;
		$this->phone = VKPHONE;
		$this->sleeptime = SLEEPTIME;
		$this->minicurl = new minicurl(TRUE, COOKIES_FILE, 'Mozilla/5.0 (Windows NT 6.1; rv:2.0.1) Gecko/20100101 Firefox/4.0.1');
	}

/*
* public auth functions
*/

	public function check_auth()
	{
		if (strlen($this->phone) != 4)
		{
			$this->put_error_in_logfile('4 LAST DIGITS from phone!!!');
			exit();
		}

		if($this->need_auth())
		{
			if(!$this->auth())
			{
				$this->put_error_in_logfile('Not authorised!');
				return FALSE;
			}
		}

		return TRUE;
	}

/*
* public posting functions 
*/

	public function post_to_user($user_id, $message, $friends_only = FALSE)
	{
		// check_auth() - так ли тут нужно? по-моему, нет
		
		if (!is_numeric($user_id))
		{
			$this->put_error_in_logfile('$user_id - only numbers!');
			return FALSE;
		}

		$hash = $this->get_hash('id' . $user_id);
		if (empty($hash))
		{
			$this->put_error_in_logfile('JS-Field "post_hash" not found!');
			return FALSE;
		}

		if(!$this->post_to_wall_query($hash, $user_id, $message, FALSE, $friends_only, 'feed'))
		{
			$this->put_error_in_logfile('Message not posted!');
			return FALSE;
		}

		return TRUE;
	}

	public function post_to_group($group_id, $message, $official = FALSE)
	{
		if (!is_numeric($group_id))
		{
			$this->put_error_in_logfile('$group_id - only numbers!');
			return FALSE;
		}

		$hash = $this->get_hash('club' . $group_id);
		if (empty($hash))
		{
			$this->put_error_in_logfile('JS-Field "post_hash" not found!');
			return FALSE;
		}

		$group_id = '-' . $group_id;

		if(!$this->post_to_wall_query($hash, $group_id, $message, $official, FALSE))
		{
			$this->put_error_in_logfile('Message not posted!');
			return FALSE;
		}

		return TRUE;
	}

	public function post_to_public_page($page_id, $message)
	{
		if (!is_numeric($page_id))
		{
			$this->put_error_in_logfile('$page_id - only numbers!');
			return FALSE;
		}

		$hash = $this->get_hash('public' . $page_id);
		if (empty($hash))
		{
			$this->put_error_in_logfile('JS-Field "post_hash" not found!');
			return FALSE;
		}

		$page_id = '-' . $page_id;

		if(!$this->post_to_wall_query($hash, $page_id, $message))
		{
			$this->put_error_in_logfile('Message not posted!');
			return FALSE;
		}

		return TRUE;
	}

/*
* public other functions
*/

	public function print_last_error()
	{
		$errors = array_reverse(file(LOG_FILE));
		return '<b>Error!</b><br>' . $errors[0];
	}

/*
* private auth functions
*/

	private function need_auth()
	{
		$result = $this->minicurl->get_file('http://vkontakte.ru/settings');
		$this->sleep();
		return strpos($result, 'HTTP/1.1 302 Found') !==FALSE;
	}

	private function auth()
	{
		$this->minicurl->clear_cookies();

		$location = $this->get_auth_location();
		if($location === FALSE){
			$this->put_error_in_logfile('Not recieved Location!');
			return FALSE;
		}

		$sid = $this->get_auth_cookies($location);
		if(!$sid){
			$this->put_error_in_logfile('Not received cookies!');
			return FALSE;
		}

		$this->minicurl->set_cookies('remixsid=' . $sid . '; path=/; domain=.vkontakte.ru');

		return TRUE;
	}

	private function get_auth_location()
	{
		$html = $this->minicurl->get_file('http://vkontakte.ru/');
		preg_match('#<input type="hidden" name="ip_h" value="([a-z0-9]*?)" \/>#isU', $html, $matches);

		$post = array(
			'act' => 'login',
			'al_frame' => '1',
			'captcha_key' => '',
			'captcha_sid' => '',
			'email' => $this->email,
			'expire' => '',
			'from_host' => 'vkontakte.ru',
			'ip_h' => (isset($matches[1]) ? $matches[1]: ''),
			'pass' => $this->pwd,
			'q' => '1',
		);

		$auth = $this->minicurl->get_file('http://login.vk.com/?act=login', $post, 'http://vkontakte.ru/');
		preg_match('#Location\: ([^\r\n]+)#is', $auth, $match);

		$this->sleep();
		return ((isset($match[1])) ? $match[1] : FALSE);
	}

	private function get_auth_cookies($location)
	{
		$result = $this->minicurl->get_file($location);

		$this->sleep();
		return ((strpos($result, "setCookieEx('sid', ") === FALSE) ? FALSE :
				substr($result, strpos($result, "setCookieEx('sid', '") + 20, 60));
	}

/*
* private posting functions
*/

	private function post_to_wall_query($hash, $to_id, $message, $official=FALSE, $friends_only=FALSE, $type='all')
	{
		$official = $official ? '1' : '';
		$friends_only = $friends_only ? '1' : '';

		$post = array(
			'act' => 'post',
			'al' => '1',
			'facebook_export' => '',
			'friends_only' => $friends_only,
			'hash' => $hash,
			'message' => $message,
			'note_title' => '',
			'official' => $official,
			'status_export' => '',
			'to_id' => $to_id,
			'type' => $type,
		);

		$result = $this->minicurl->get_file('http://vkontakte.ru/al_wall.php', $post);

		$this->sleep();
		preg_match('#>\d<!>\d+<!>([\d]+)<!>#isU', $result, $match);

		return (isset($match[1]) AND ($match[1] == '0'));
	}

	private function get_hash($page_id)
	{
		$result = $this->minicurl->get_file('http://vkontakte.ru/' . $page_id);
		$this->sleep();

		preg_match('#Location\: ([^\r\n]+)#is', $result, $match);
        if (isset($match[1]) AND !empty($match[1]))
        {
        	$result = $this->minicurl->get_file('http://vkontakte.ru' . $match[1]);
			$this->sleep();
			unset($match);

			preg_match("#act: '([^']+)', code: ge\('code'\)\.value, to: '([^']+)', al_page: '([^']+)', hash: '([^']+)'#is", $result, $match);

			$post = array(
				'act' => $match[1],
				'al' => '1', // хз что это
				'al_page' => $match[3],
				'code' => $this->phone,
				'hash' => $match[4],
				'to' => $match[2]
			);

			$result = $this->minicurl->get_file('http://vkontakte.ru/login.php', $post);
			$this->sleep();
			unset($match);

			preg_match('#>/([a-z0-9\.\-_]+)<#is', $result, $match);

			if (isset($match[1]) AND !empty($match[1]))
			{
				$result = $this->minicurl->get_file('http://vkontakte.ru/' . $match[1]);
				$this->sleep();
				unset($match);
			}
        }
		preg_match('#"post_hash":"([^"]+)"#isU', $result, $match);

		if (strpos($result, 'action="https://login.vk.com/?act=login'))
		{
			unset($match[1]);
		}

		return (isset($match[1]) ? $match[1] : '');
	}

/*
* private other functions
*/

	private function sleep()
	{
		if ($this->sleeptime)
		{
			sleep($this->sleeptime + rand(1, 4));
		}
	}

	private function put_error_in_logfile($msg)
	{
		$msg = '[' . date('Y.m.d H:i:s') . ']: ' . $msg . "\n";
		$fp = fopen(LOG_FILE, 'a');
		fwrite($fp, $msg);
		fclose($fp);
	}
}

?>