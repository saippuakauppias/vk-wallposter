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

// авторизация на сайте
if($vk->check_auth())
{
    echo 'Authorised in vk!<br>';
}
else
{
    echo $vk->print_last_error();
    exit();
}

// сообщение для публикации (обязательно в UTF-8)
$message = array('Ребята, вы такие клёвые!', 'Шексна RIDE рулит!',
                'Мутите давайте видос!', 'Дениска,поставь корк уже!',
                'Фотки классные у вас!', 'Хочу покатать к вам!');
$message = $message[array_rand($message)];

// публикация сообщения на странице юзера контакта
if ($vk->post_to_user(137527963, $message))
{
    echo 'Posted in user page!';
}
else
{
    echo $vk->print_last_error();
    exit();
}

// публикация сообщения в группе
if ($vk->post_to_group(15014694, $message))
{
    echo 'Posted in group!';
}
else
{
    echo $vk->print_last_error();
    exit();
}

// публикация сообщения на публичной странице
if ($vk->post_to_public_page(29986881, $message))
{
    echo 'Posted in public page!';
}
else
{
    echo $vk->print_last_error();
    exit();
}


?>
