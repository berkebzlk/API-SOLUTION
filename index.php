<?php
require_once 'Autoloader.php';
Autoloader::register();
new Api();

class Api
{
	private static $db;

	public static function getDb()
	{
		return self::$db;
	}

	public function __construct()
	{
		self::$db = (new Database())->init();

		// Get the base path of the script and remove trailing slashes.
		$base_path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');

		// Parse the request URI and extract the path.
		$uri = parse_url($_SERVER['REQUEST_URI'])['path'];

		// Check if the URI is the base path or the base path with a trailing slash.
		if ($uri === $base_path || $uri === $base_path . '/') {
			// If so, set the URI to '/'
			$uri = '/';
		} else {
			// If not, adjust the URI by removing the base path.
			$uri = substr($uri, strlen($base_path));
		}
		
		$httpVerb = isset($_SERVER['REQUEST_METHOD']) ? strtolower($_SERVER['REQUEST_METHOD']) : 'cli';

		$wildcards = [
			':any' => '[^/]+',
			':num' => '[0-9]+',
		];
		$routes = [
			'get /constructionStages' => [
				'class' => 'ConstructionStages',
				'method' => 'getAll',
			],
			'get /constructionStages/(:num)' => [
				'class' => 'ConstructionStages',
				'method' => 'getSingle',
			],
			'post /constructionStages' => [
				'class' => 'ConstructionStages',
				'method' => 'post',
				'bodyType' => 'ConstructionStagesCreate'
			],
			'patch /constructionStages/(:num)' => [
				'class' => 'ConstructionStages',
				'method' => 'patch',
				'bodyType' => 'ConstructionStagesCreate'
			],
			'delete /constructionStages/(:num)' => [
				'class' => 'ConstructionStages',
				'method' => 'delete',
			],
		];

		$response = [
			'error' => 'No such route',
		];

		if ($uri) {
			foreach ($routes as $pattern => $target) {
				$pattern = str_replace(array_keys($wildcards), array_values($wildcards), $pattern);
				if (preg_match('#^' . $pattern . '$#i', "{$httpVerb} {$uri}", $matches)) {
					$params = [];
					array_shift($matches);
					if ($httpVerb === 'post' || $httpVerb === 'patch') {
						$data = json_decode(file_get_contents('php://input'));
						$params = [new $target['bodyType']($data)];
					}
					$params = array_merge($params, $matches);
					$response = call_user_func_array([new $target['class'], $target['method']], $params);
					break;
				}
			}

			// echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
		}
	}
}
