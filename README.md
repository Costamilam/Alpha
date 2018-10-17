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

**Init:**

```php
//Include a composer autoload
require_once "./vendor/autoload.php";

//Import the App class
use Costamilam\Alpha\App;

//Start aplication
App::start();

//To finish execution
App::finish();
```

**Routing:**

```php
//Import the necessary classes
use Costamilam\Alpha\Router;
use Costamilam\Alpha\Request;
use Costamilam\Alpha\Response;

//Define default RegExp for all parameters "bar"
Router::addParamRegExp("bar", "[0-9]*");

//Define method, route and callback
Router::get("/my/route/", function () {
	//...
})
```

| Method | Function | Description |
|--|--|--|
| All | `Router::any` | Any HTTP method |
| GET | `Router::get` | GET HTTP method |
| POST | `Router::post` | POST HTTP method |
| PUT | `Router::put` | PUT HTTP method |
| DELETE | `Router::delete` | DELETE HTTP method |
| PATCH | `Router::patch` | PATCH HTTP method |
| SET | `Router::set` | Define one or more HTTP method |

```php
//Using parameters with "{}"
Router::get("/{foo}/", function () {
	//...
}, array(
	//Optionally, define the RegExp to param, if you don't use, the default is "[^\/]+"
	"param" => array(
		"foo" => "[a-z]+",
		"bar" => "[0-9]?" //Disconsidered, because there is no parameter "bar"
	)
));

//Optionally, execute similar routes, for request "/foo/bar/"
Router::get("/foo/", function () {
	//It's executed
}, array(
	"pathMatchFull" => false
));
Router::get("/foo/bar/", function () {
	//It's executed
});

//Middleware, the function return passes with the next function parameter
Router::get("/foo/", function () {
	//...
	return array("bar", "baz");
});
Router::get("/foo/", function ($bar, $baz) {
	//$bar === "bar";
	//$baz === "baz";
});

//If return is false, the execution is finished
Router::get("/foo/", function () {
	//It's executed

	return false;
});
Router::get("/foo/", function () {
	//It's not executed
});

//To use an external function, pass namespace, the type (-> or ::, instance or static) and the function name as string
Router::get("/foo/", "Namespace\To\Foo::getStaticFoo");
Router::get("/foo/", "Namespace\To\Foo->getInstanceFoo");
Router::get("/foo/", "Namespace\To\Foo->getInstanceBar");

//In /Namespace/To/Foo.php ...
namespace Namespace\To;

class Foo {
	private $foobar = "Foo";

	public static function getStaticFoo() {
		echo "Static Foo!!!";
	}

	public function getInstanceFoo() {
		echo "Instance ".$this->foobar; //Instance Foo

		$this->foobar = "Bar"; //Change foobar
	}

	public function getInstanceBar() {
		echo "Instance ".$this->foobar; //Instance Bar
	}
}
```

> The created instance is saved for reuse. You can use DI (Dependence Injection) by pre-creating the object:

```php
use Namespace\To\Foo;

Router::addInstance("Namespace\To\Foo", new Foo("foo"));
//Or using aliases
Router::addInstance("AliasFoo", new Foo("foo"));

//In /Namespace/To/Foo.php ...
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
Router::any("/foobar", "Namespace\To\Foo->printFoo");
//Or
Router::any("/foobar", "AliasFoo->printFoo");
```

**Request:**

```php
//Get the request HTTP method
Request::method(); //Example: "GET", "POST", "DELETE", ...

//Get the request header
Request::header("foobar");
```

> Multiline header returns separated by commas, for example, "foo, bar, baz"

```php
//Get the request path
Request::path(); //Example: "/", "/foo/", "/foo/123"

//Get the request parameters
Request::param();
//Example for route "/foo/{bar}/baz/{baz}/" and request "/foo/Bar/baz/true/":
//[
//	  "foo" => "Bar",
//	  "baz" => "true"
//]

//Get the request body
Request::body();
//Example:
//[
//	  "foo" => "Bar",
//	  "baz" => "true"
//]
```

**Response:**

```php
//Change response status
Response::status(404);

//Add a response header
Response::header("Access-Control-Allow-Origin", "localhost");
//Add a response header, without replacing the previous
Response::header("Access-Control-Allow-Origin", "http://domain.com", false);

//Add a multiple response header
Response::multiHeader(array(
	"Access-Control-Allow-Methods" => "POST, GET"
	"Access-Control-Allow-Headers" => "X-PINGOTHER, Content-Type"
));

//Remove a response header
Response::header("Access-Control-Allow-Headers");

//Change the body of the response
Response::text("<h1>Response Text</h1>");

//Change the body of the response using JSON format
Response::json(array(
	"foo" => "bar",
	"baz" => array(true, false, null, "")
));

//Pass "0" or "false" to no cache and an integer in minutes to cache control
Response::cache(15);

//Default cookie options:
//expire = time() + 60 * 30 (30 minutes)
//domain = "HTTP_HOST"
//secure = false
//httponly = true

//Change default cookie options
Response::configureCookie(
	24 * 60, //Time to expire in minutes (24 hours)
	"HTTP_HOST", //If present, use Host request header else use empty string ("")
	true, //Only HTTPS (recommended true)
	true //Access only http, disable access by JavaScript (recommended true)
);

//Send a cookie
Response::cookie("foo", "bar");

//Send a cookie with a different expiration (12 hours, default is 24 hours)
Response::cookie("foo", "bar", 12 * 60);
```

Future implementation:

```php
//Response with file, pass path to file, name and last parameter determines if force download (not implemented)
Response::file("path/to/$file", "Name File", true);

//Redirect to another route (not implemented)
Response::redirect("GET", "/foo/bar/");
```

**Validator:**

```php
//Import the necessary classes
use Costamilam\Alpha\Validator;

//Validate if is empty
Filter::isEmpty("", 0, 0.0, false, array("")); //Return false

//Validate string/int/float using an existing function
Filter::validateString("<p>Foo</p>", $error, true, 1, 100, false, "Bar");
```

| Parameter | Type | Is required? | Description | Exist in `Filter::filterString`? | Exist in `Filter::filterInt`? | Exist in `Filter::filterFloat`? | Exist in `Filter::filterBoolean`? | Exist in `Filter::filterDatetime`? |
|-|-|-|-|-|-|-|-|-|
| 1 | Any | true | Value to filter | true | true | true | true | true |
| 2 | Any | true | Error list, passing by reference | true | true | true | true | true |
| 3 | Boolean | false | Sanitize value | true | true | true | false | true |
| 4 | Integer | false | Minimum value | true | true | true | false | true |
| 5 | Integer | false | Maximum value | true | true | true | false | true |
| 6 | Boolean | false | Is nullable | true | true | true | true | true |
| 7 | Boolean | false | Default value, if is invalid | true | true | true | true | true |

```php
//Create a custom validation, passing the name and callback function
Filter::create("myFilterFunctionForName", function ($value, &$error, $to) {
	$error = array();

	if(strpos($value, "foo") !== false) {
		$error[] = "'foo' is a invalid name";
	}
	if(strpos($value, "bar") !== false) {
		$error[] = "'bar' is a invalid name";
	}

	$value = $to === "upper" ? strtoupper($value) : strtolower($value);

	return $value;
});
```

> You must receive two arguments in callback, the first is the value to filter and the second is the list of errors. Optionally, you can receive other arguments after

```php
//Create a custom validation, passing the name, regexp and the error message (if failure)
Filter::createWithRegExp("myFilterRegExpForName", "/^[a-z ]+$/", "Invalid name!!!");

//For use custum filter
Filter::use("myFilterRegExpForName", "foo bar");

//Example of group filter
$filter = Filter::group(array(
	array("filterString", "My String", false, 10, 50, true "default value"), //Existing function
	"MyAlias" => array("myFilterFunctionForName", "foo bar", "upper") //Custumized function, using an alias
), $error);
//Value of $filter:
//[
//	  0 => "My String",
//    "MyAlias" => "FOO BAR"
//]
//Value of $error:
//[
//	  "MyAlias" => [
//		  "'foo' is a invalid name",
//		  "'bar' is a invalid name"
//	  ]
//]

//You can change default error message of existing filter
Filter::changeErrorMessage(array(
	"maximumValue" => "Maximum value overflow! Try again.",
	"notNull" => "Value is not nullable! Try again."
));
```

Default error messages

| Name | Message |
|-|-|
| `minimumValue` | Minimum value overflow |
| `maximumValue` | Maximum value overflow |
| `notNullable` | Value is not nullable |
| `invalidBoolean` | Invalid boolean |
| `invalidInt` | Invalid int |
| `invalidFloat` | Invalid float |

**Database:**

```php
//Import the necessary classes
use Costamilam\Alpha\DB;
use Costamilam\Alpha\Router;

//Access to the database
//Note: the connection it isn't now
DB::access("host", "user", "pass", "db");

//Connection charset, recommended "UTF8"
DB::charset("UTF8");

//Select example:
DB::select("SELECT * FROM foobar WHERE foo = 'bar'");
//Return an associative array, for example:
//[
//	  ["id" => 0, "foo" => "bar"],
//	  ["id" => 1, "foo" => "baz"]
//]

//Insert example:
DB::insert("INSERT FROM foobar(foo) VALUES('bar')");
//The last id inserted with this connection
$lastInsert = DB::$insertedId;

//Update example:
DB::update("UPDATE foobar SET foo = 'bar' WHERE foo <> 'bar'");

//Delete example:
DB::delete("DELETE * FROM foobar WHERE foo = 'bar'");

//Passing variables, (prepared statement)
DB::select("SELECT * FROM foobar WHERE id = ?", $lastInsert);

//The type used is the variable's gettype
//For files, pass a Resource:
$foo = "baz";
$file = fopen("path/to/file.txt");
DB::select("INSERT FROM foobar(foo, file) VALUES(?, ?)", $foo, $file);
```

**Auth** (with JWT):

```php
//Import the necessary classes
use Costamilam\Alpha\Auth;
use Costamilam\Alpha\Token;

//For send by HTTP header
Auth::enableHTTPHeaderMode();

//For send by Cookie
Auth::enableCookieMode();
```

> Enabling one mode, disable the other

```php
//Configure the auth module
Token::configure(
	"hs256",					//Algorithm
	"mY SuperSECRET.key",		//Secret key
	"http://commandinvest.com",	//Issuer
	"http://commandinvest.com",	//Audience
	30							//Time to expires (in minutes)
);
```

> If you use cookie mode: The time to expires is used for cookie expiration too, not the default value or set with `Response::configureCookie`

```php
//Listening status changes
Token::onStatus("failure", function () {
	echo "Failed to create token!";
});

//Listening more than one status changes
//Token::onStatus(array("empty", "invalid", "expired") ...);
```

| Name | Description | Response status | Finish execution |
|--|--|--|--|
| `created` | Token successfully created | - | No |
| `failure` | Failed to create token | 500: Internal server error | Yes |
| `empty` | Request don't have a token | 401: Unauthorized | Yes |
| `invalid` | Request has a invalid token | 401: Unauthorized | Yes |
| `expired` | Request has a expired token | 401: Unauthorized | Yes |
| `forbidden` | The token don't have access to the resource | 403: Forbidden | Yes |
| `authorized` | Request has a valid token | - | No |

> The "Response" column is the default value for the response status of the request and the "Finish app" column means that the application ends. You can change it by passing a callback function with `Token::onStatus`

```php
//Create and send a token to the client by configured mode, pass the subject and, optionally, other data
Auth::sendToken(
	17, //User id
	array( //Data to save
		"name" => "Foo", //User name
		"role" => array("salesman", "admin") //User roles, for authenticate
	)
);

//Remove the new token send (cancel "Auth::sendToken")
Auth::removeToken();

//Route auth passing a callback function for validate
Auth::route("ANY", "/foo/bar/", function ($token) {
	$role = $token->getClain("data");
	$role = $role["role"];

	if (in_array("admin", $role)) {
		return true; //Authenticated
	} else {
		return false; //Not authenticated
	}
	//Simplified:
	//return in_array("admin", $role);
});

//For more than one method
//Token::route(array("GET", "POST") ...);

//For more than one route
//Token::route(... "(/baz/[a-z]+|/foo/[0-9]+)" ...);

//For all routes and all methods
//Token::route("ANY", ".*" ...);
```

> If the callback is null, it means that any **authenticated** user can access any data
