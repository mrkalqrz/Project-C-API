<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Contracts\Auth\Factory as Auth;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected $auth;

    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    public function handle($request, Closure $next, ...$guards)
    {
        $types = array(
            1 => "masteradmin",
            2 => "admin",
            3 => "declarator",
            4 => "cashier",
            5 => "player",
            6 => "manager",
        );
        if($request->header('Authorization') && auth('user')->check()) {
            $auth_token = explode(" ", $request->header('Authorization'))[1];
            $auth_token_type = explode(".", $auth_token); 
            $auth_token_type = base64_decode($auth_token_type[1]);
            $auth_token_type = json_decode($auth_token_type);  
            $auth_token_type = $auth_token_type->user_type_id;
            for($i=0; count($guards) > $i; $i++){
                if ($types[$auth_token_type] == $guards[$i]) {
                    return $next($request);
                }
            }

            return response('Unauthorized.', 401);
        }

        return response('Unauthorized.', 401);
    }
}
