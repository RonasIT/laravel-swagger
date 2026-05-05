<?php

namespace RonasIT\AutoDoc\RequestContext\Resolvers;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class SecurityTokenResolver
{
    public function usesAuth(Request $request): bool
    {
        $security = config('auto-doc.security');
        $securityDriver = config("auto-doc.security.security_drivers.{$security}");

        $securityToken = match (Arr::get($securityDriver, 'in')) {
            'header' => $request->cookie($securityDriver['name']),
            'query' => $request->query($securityDriver['name']),
            default => null,
        };

        return !empty($securityToken);
    }
}
