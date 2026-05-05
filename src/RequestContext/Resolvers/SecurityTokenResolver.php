<?php

namespace RonasIT\AutoDoc\RequestContext\Resolvers;

use Illuminate\Http\Request;

class SecurityTokenResolver
{
    public function hasSecurityToken(Request $request): bool
    {
        $securityDriver = $this->getSecurityDriver();

        $securityToken = match ($securityDriver['in'] ?? null) {
            'header' => $request->header($securityDriver['name']),
            'cookie' => $request->cookie($securityDriver['name']),
            'query' => $request->query($securityDriver['name']),
            default => null,
        };

        return !empty($securityToken);
    }

    protected function getSecurityDriver(): array
    {
        $security = config('auto-doc.security', '');

        return config("auto-doc.security_drivers.{$security}", []);
    }
}
