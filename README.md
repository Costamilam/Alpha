# Alpha

The simple PHP framework for routing, control, database access, auth and more

**Site**: [https://costamilam.github.io/Alpha/](https://costamilam.github.io/Alpha/)

**Repository**: [https://github.com/Costamilam/Alpha](https://github.com/Costamilam/Alpha)

**Packagist**: [https://packagist.org/packages/costamilam/alpha](https://packagist.org/packages/costamilam/alpha)

**License**: [BSD 3-Clause](https://github.com/Costamilam/Alpha/blob/master/LICENSE)

## Requisits

- PHP 5.5+
- [Lcobucci/JWT](https://github.com/lcobucci/jwt) (v 3.2) library for token authentication

## Install

With [Composer](https://getcomposer.org/):

```
composer require costamilam/alpha:dev-master
```

## Features

**Starting:**

```php
//Include a composer autoload
require_once './vendor/autoload.php';

//Import the App class
use Costamilam\Alpha\App;

//Start aplication passing the mode ('prod' for production or 'dev' for development)
App::start('dev');

//Get formatted application start date, the default is 'Y-m-d H:i:s.u'
App::startedAt('U');
```

**Routing:**

```php
//Import the necessary classes
use Costamilam\Alpha\Router;

//Load file based on path (not accepted regex)
Router::fromFile('/foo', './router/foo.php');

//Create a route by defining the method, route, and callback
Router::set('GET', '/my/route/', function () {
	//...
});

//Define more than one method
Router::set(array('GET', 'POST'), '/my/route/', function () {
	//...
});
```

| Method | Function | Description |
|-|-|-|
| Enumeration | `Router::set` | Define one or more HTTP method |
| Any | `Router::any` | Any HTTP method |
| GET | `Router::get` | GET HTTP method |
| POST | `Router::post` | POST HTTP method |
| PUT | `Router::put` | PUT HTTP method |
| DELETE | `Router::delete` | DELETE HTTP method |
| PATCH | `Router::patch` | PATCH HTTP method |
| OPTIONS | `Router::options` | OPTIONS HTTP method |
| CONNECT | `Router::connect` | CONNECT HTTP method |
| TRACE | `Router::trace` | TRACE HTTP method |

```php
//Using parameters with '{}'
Router::get('/{foo}/', function () {
	//...
}, array(
    //Optionally, define the RegExp or function to validate the parameters (of path or body), if you don't use, the default is '[^\/]+' for path parameters and body parameter is not validate
    'param' => array(
        'foo' => '[a-z]+',
        //Disconsidered, because there is no parameter 'bar'
        'bar' => function($param) {
            return strtoupper($param) === 'BAR';
        }
    ),
    'body' => array(
        //Defined parameters are required by default, add the character '?' in the end to make it optional
        'foo?' => '[a-zA-Z0-9_.]{3,10}',
        //Get the parameter by reference to format it
        'bar' => function (&$param) {
            $param = strtoupper($param);

            return $param === 'BAR';
        }
    )
));

//Define default RegExp or function to validate all path parameters 'bar'
Router::addPathParamValidator('foo', '[0-9]*');

//Define default RegExp or function to validate all body parameters 'bar'
Router::addBodyParamValidator('bar', function($param) {
    return $param === 'bar';
});

//The callback function recive a parameter and has return if it is valid, you can receive the parameter as a reference (&$param) to format and validate
Router::addBodyParamValidator('baz', function(&$param) {
    $param = strtoupper($param);

    return $param === 'BAZ';
});

//Set optional param with '?'
Router::get('/{foo}/{bar}?/', function () {
	Request::param();
	//If request is '/theFOO/'
	//[
	//	  'foo' => 'theFOO',
	//	  'bar' => null
	//]
});

//You can pass regexp in the route, but it is not a parameter
Router::get('/{foo}/[0-9]+/', function () {
	Request::param();
	//[
	//	  'foo' => 'The value of {foo}'
	//]
});

//Optionally, execute similar routes by adding '.*' at the end of the route, for request '/foo/bar/'
Router::get('/foo/.*', function () {
	//It is executed
});
Router::get('/foo/bar/', function () {
	//But it is not executed
});

//Middleware, you can call Router::next to execute the next function, passing parameters received as arguments
Router::get('/foo/.*', function () {
	//...
	Router::next('bar', 'baz');
});
Router::get('/foo/bar/', function ($bar, $baz) {
	//$bar === 'bar';
	//$baz === 'baz';
});

//To use an external function, pass namespace, the type ('->' for instance or '::' for static) and the function name as string
Router::get('/foo/', 'Namespace\To\Foo::getStaticFoo');
Router::get('/foo/', 'Namespace\To\Foo->getInstanceFoo');
Router::get('/foo/', 'Namespace\To\Foo->getInstanceBar');

//In '/Namespace/To/Foo.php' ...
namespace Namespace\To;

class Foo {
	private $foobar = 'Foo';

	public static function getStaticFoo() {
		echo 'Static Foo!!!';

		return true;
	}

	public function getInstanceFoo() {
		echo 'Instance '.$this->foobar;     //Instance Foo

		$this->foobar = 'Bar';              //Change foobar

		return true;
	}

	public function getInstanceBar() {
		echo 'Instance '.$this->foobar;     //Instance Bar
	}
}
```

> The created instance is saved for reuse. You can use DI (Dependence Injection) by pre-creating the object:

```php
use Namespace\To\Foo;

Router::addInstance('Namespace\To\Foo', new Foo('foo'));
//Or using aliases
Router::addInstance('AliasFoo', new Foo('foo'));

//In '/Namespace/To/Foo.php' ...
namespace Namespace\To;

class Foo {
	private $foobar;

	public function __construct($foobar) {
		$this->foobar = $foobar;
	}

	public function printFoo() {
		echo $this->foobar;
	}
}

//Using
Router::any('/foobar', 'Namespace\To\Foo->printFoo');
//Or
Router::any('/foobar', 'AliasFoo->printFoo');
```

**Request:**

```php
//Import the necessary classes
use Costamilam\Alpha\Request;

//Get the request HTTP method
Request::method(); //Example: 'GET', 'POST', 'DELETE', ...

//Get the request path, for example: '/', '/foo/', '/foo/123'
Request::path();

//Get the request token
Request::token();
```

> The token must be passed in the `Authorization` header

```php

//Get the request header
Request::header('foobar');
```

> Multiline header returns separated by commas, for example, 'foo, bar, baz'

```php
//Get the request cookie
Request::cookie('foobar');

//Get the request parameters
Request::param();
//Example for route '/foo/{bar}/baz/{baz}/' and request '/foo/Bar/baz/true/':
//[
//	  'foo' => 'Bar',
//	  'baz' => 'true'
//]

//Get fields specific to the request parameters, if the key does not exist, create it with null value
Request::param('baz', 'bar', 'qux');

//Get the request query string parameters
Request::queryString();
//Example
//[
//	  'foo' => 'Bar',
//	  'baz' => 'true'
//]

//Get fields specific to the request query string parameters, if the key does not exist, create it with null value
Request::queryString('baz', 'bar', 'qux');

//Get the request body
Request::body();
//Example:
//[
//	  'bar' => 'Bar',
//	  'baz' => 'true'
//]

//Get fields specific to the request body, if the key does not exist, create it with null value
Request::body('foo', 'bar');
//Example:
//[
//	  'foo' => null,
//	  'baz' => 'true'
//]
```

**Response:**

```php
//Import the necessary classes
use Costamilam\Alpha\Response;

//Change response status
Response::status(404);

//Add a response header
Response::header('Access-Control-Allow-Origin', 'localhost');
//Add a response header, without replacing the previous
Response::header('Access-Control-Allow-Origin', 'http://domain.com', false);

//Add a multiple response header
Response::multiHeader(array(
	'Access-Control-Allow-Methods' => 'POST, GET'
	'Access-Control-Allow-Headers' => 'X-PINGOTHER, Content-Type'
));

//Remove a response header
Response::header('Access-Control-Allow-Headers');

//Pass '0' or 'false' to no cache and an integer in minutes to cache control
Response::cache(15);

//Default cookie options:
//expire = time() + 60 * 30 (30 minutes)
//domain = ''
//secure = false
//httponly = true

//Change default cookie options
Response::configureCookie(
	24 * 60,        //Time to expire in minutes (24 hours)
	'HTTP_HOST',    //If present, use Host request header else use empty string ('')
	true,           //Only HTTPS (recommended true)
	true            //Access only http, disable access by JavaScript (recommended true)
);
```

> Attention: If you set the domain to 'HTTP_HOST' and access with a local server passing the port, for example 'localhost:8000', the cookie will not work. In this case, you need to set the domain to 'localhost' without the port

```php
//Send a cookie
Response::cookie('foo', 'bar');

//Send a cookie with a different expiration (12 hours, default is 24 hours)
Response::cookie('foo', 'bar', 12 * 60);

//Change the body of the response using JSON format
Response::json(array(
	'foo' => 'bar',
	'baz' => array(true, false, null, '')
));

//Send a token
Response::token('MyToken');
```

> The Token is passed by the `Token` header

**Database:**

```php
//Import the necessary classes
use Costamilam\Alpha\DB;

//Access to the database
//Note: the connection it isn't now
DB::access('host', 'user', 'pass', 'db');

//Connection charset, recommended 'UTF8'
DB::charset('UTF8');

//For disconnect, it call automacally on execution end
DB::disconnect();

//Select example:
DB::select('SELECT * FROM foobar WHERE foo LIKE "ba%"');
//Return an associative array, for example:
//[
//	  ['id' => 0, 'foo' => 'bar'],
//	  ['id' => 1, 'foo' => 'baz']
//]

//Insert example:
DB::insert('INSERT FROM foobar(foo) VALUES("bar")');
//The last id inserted with this connection
$lastInsert = DB::$insertedId;

//Update example:
DB::update('UPDATE foobar SET foo = "bar" WHERE foo <> "bar"');

//Delete example:
DB::delete('DELETE * FROM foobar WHERE foo = "bar"');

//Passing variables, (prepared statement)
DB::select('SELECT * FROM foobar WHERE id = ?', $lastInsert);

//The type used is the variable's gettype
//For files, pass a Resource:
$foo = 'baz';
$file = fopen('path/to/file.txt');
DB::select('INSERT FROM foobar(foo, file) VALUES(?, ?)', $foo, $file);
```

**Authentication** (with JSON Web Token):

```php
//Import the necessary classes
use Costamilam\Alpha\Token;

//Configure the token
Token::configure(
	'hs256',                    //Algorithm
	'mY SuperSECRET.key',       //Secret key
	'https://example.com',      //Issuer
	'https://example.com',      //Audience
	30                          //Time to expires (in minutes)
);

//Create a token, pass the subject and, optionally, other data
Token::create(
    17,						//For example, the user id
    array(					//Data to save
        'name' => 'Foo',			//User name
        'role' => array('salesman', 'admin')	//User roles, for authenticate
    )
);

//Get token payload
Token::payload($myToken);

//Token verification, return the error as string or "ok"
Token::verify($myToken);
```

| Returned value | Is error | Description |
|-|-|-|
| Ok | No | Token exists, is valid and is not expired |
| Empty | Yes | Token does not exist |
| Invalid | Yes | Token is invalid |
| Expired | Yes | Token has expired |
