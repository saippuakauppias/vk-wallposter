<?

class vk_auth
{
	
	private $email = '';
	private $pwd = '';
	private $hash = '';
	private $ppid = '';
	private $sleeptime = 1;
	private $minicurl;

	
	function __construct($email, $pwd, $ppid, $sleeptime)
	{
		$this->email = $email;
		$this->pwd = $pwd;
		$this->ppid = $ppid;
		$this->sleeptime = $sleeptime;
		$this->minicurl = new minicurl(TRUE, COOKIES_FILE, 'Mozilla/5.0 (Windows NT 6.1; rv:2.0.1) Gecko/20100101 Firefox/4.0.1');;
	}

	public function check_auth()
	{
		$hash = $this->get_hash();

		if(empty($hash))
		{
			if($this->auth())
			{
				$hash = $this->get_hash();
				if (!empty($hash))
				{
					$this->put_error_in_logfile('JS-Field "post_hash" not found!');
					return FALSE;
				}
			}
			else
			{
				$this->put_error_in_logfile('Not authorised!');
				return FALSE;
			}
		}

		$this->hash = $hash;
		return TRUE;
	}

	public function post_to_wall($msg)
	{
		if(!$this->post_to_wall_query($msg))
		{
			$this->put_error_in_logfile('Message not posted!');
			return FALSE;
		}
		return TRUE;
	}

	public function print_last_error()
	{
		$errors = array_reverse(file(LOG_FILE));
		return '<b>Error!</b><br>' . $errors[0];
	}

	private function auth()
	{
		$this->minicurl->clear_cookies();

		$location = $this->get_auth_location();
		if($location === FALSE){
			$this->put_error_in_logfile('vK not return Location!');
			return FALSE;
		}

		$sid = $this->get_auth_cookies($location);
		if(!$sid){
			$this->put_error_in_logfile('vK not authorised!');
			return FALSE;
		}

		$this->minicurl->set_cookies('remixsid=' . $sid . '; path=/; domain=.vkontakte.ru');

		return TRUE;
	}

	private function get_hash()
	{
		$result = $this->minicurl->get_file('http://vkontakte.ru/public' . $this->ppid);
		preg_match('#"post_hash":"(\w+)"#isU', $result, $match);

		if (strpos($result, 'action="https://login.vk.com/?act=login'))
		{
			unset($match[1]);
		}

		$this->sleep();
		return ((isset($match[1])) ? $match[1] : '');
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


	private function post_to_wall_query($msg)
	{
		$post = array(
			'act' => 'post',
			'al' => '1',
			'facebook_export' => '',
			'friends_only' => '',
			'hash' => $this->hash,
			'message' => $msg,
			'note_title' => '',
			'official' => '',
			'status_export' => '',
			'to_id' => '-' . $this->ppid,
			'type' => 'all',
		);

		$result = $this->minicurl->get_file('http://vkontakte.ru/al_wall.php', $post);

		$this->sleep();
		return strpos($result, '<!>0<!><input');
	}

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