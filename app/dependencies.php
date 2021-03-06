<?php

//use Respect\Validation\Validator as v;

// Get the container
$container = $app->getContainer();


// monolog
$container['logger'] = function ($c) {
	$settings = $c->get('settings')['logger'];
	$logger = new Monolog\Logger($settings['name']);
	$logger->pushProcessor(new Monolog\Processor\UidProcessor());
	$logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'], $settings['level']));
	return $logger;
};

// DB dependency
$container['db'] = function ($c) {

	$db = $c->get('settings')['db'];
	
	$mng = new \MongoDB\Driver\Manager("mongodb://".$db['username'].":".$db['password']."@".$db['host']);
	
	return new \App\Models\DB($mng, $db['database']);

};


// CONTROLLERS
$container['staticPages'] = function($c) {

	return new \App\Controllers\StaticPagesController($c);

};

$container['apiController'] = function($c) {

	return new \App\Controllers\ApiController($c);

};


//GLOBALS
$container['global'] = function($c) {
	
	return $c->get('globals');

};


//HANDLERS

$container['errorHandler'] = function ($c) {
    return new \App\Handlers\Error($c['logger']);
};


//MODELS 

$container['utils'] = function($c) {

	return new \App\Models\Utilities($c);

};

$container['oauth2'] = function($c) {

	return new \App\Models\Oauth2($c);

};

$container['mugfile'] = function($c) {

	return new \App\Models\Mugfile($c);

};

$container['user'] = function($c) {

	return new \App\Models\User($c);

};
