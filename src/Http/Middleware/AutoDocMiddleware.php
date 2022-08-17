<?php

namespace KWXS\Support\AutoDoc\Http\Middleware;

use Closure;
use KWXS\Support\AutoDoc\Services\SwaggerService;

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
		$allowedEnv = config('swagger.allowedEnv');

		if (in_array(config('app.env'), $allowedEnv) && !self::$skipped) {
			$this->service->addData($request, $response);
		}

		self::$skipped = false;

		return $response;
	}
}
