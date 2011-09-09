<?php
error_reporting(E_ALL);
ini_set('display_errors', TRUE);
@ini_set('memory_limit', '16M');
@set_time_limit(0);
@ini_set('max_execution_time',0);
@ini_set('set_time_limit',0);
header('Content-Type: text/html; charset=utf-8'); 
@ob_end_flush();


define('SCR_DIR', dirname(__FILE__));

include_once(SCR_DIR . '/config.php');
include_once(SCR_DIR . '/classes/minicurl.class.php');
include_once(SCR_DIR . '/classes/vk_poster.class.php');

$vk = new vk_auth();

if(!$vk->check_auth())
{
	echo $vk->print_last_error();
	exit();
}

$message = 'тестирование; testing';


if ($vk->post_to_user('137527963', $message)) 
{
	echo 'Posted in user page!';
}
else
{
	echo $vk->print_last_error();
	exit();
}



if ($vk->post_to_group('15014694', $message))
{
	echo 'Posted in group!';
}
else
{
	echo $vk->print_last_error();
	exit();
}



if ($vk->post_to_public_page('29986881', $message))
{
	echo 'Posted in public page!';
}
else
{
	echo $vk->print_last_error();
	exit();
}



?>