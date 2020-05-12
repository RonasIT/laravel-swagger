<?php

namespace RonasIT\Support\AutoDoc\Http\Middleware;

use Closure;
use RonasIT\Support\AutoDoc\Services\SwaggerService;

/**
 * @property SwaggerService $service
 */
class AutoDocMiddleware
{
	public static $skipped = false;

	protected $service;

	public function __construct()
	{
		$this->service = app(SwaggerService::class);
	}

	public function handle($request, Closure $next)
	{
		$response = $next($request);
		$allowedEnv = config('auto-doc.allowedEnv');

		if (in_array(config('app.env'), $allowedEnv) && !self::$skipped) {
			$this->service->addData($request, $response);
		}

		self::$skipped = false;

		return $response;
	}
}
