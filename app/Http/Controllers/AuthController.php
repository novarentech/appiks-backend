<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckUsernameRequest;
use App\Http\Requests\UserLoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Traits\ApiResponder;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Support\Facades\Auth;
use Dedoc\Scramble\Attributes\ExcludeAllRoutesFromDocs;
use Dedoc\Scramble\Attributes\ExcludeRouteFromDocs;

class AuthController extends Controller
{
    use ApiResponder;

    /**
     * Get JWT token(login)
     *
     * Mendapatkan JWT token untuk mengakses guarded route
     */
    #[Group('Authentication')]
    public function login(UserLoginRequest $request, \App\Actions\LoginAction $action)
    {
        $result = $action->handle($request->validated());

        if (! $result) {
            return $this->error('Unauthorized', 401, null);
        }

        return $this->success($result);
    }

    /**
     * Get the autheticated user profile
     */
    #[Group('User')]
    #[ExcludeRouteFromDocs]
    public function me()
    {
        $user = Auth::user();
        $data = User::with(['school', 'room', 'mentor', 'counselor'])->where('id', $user->id)->first();

        return $this->success(new UserResource($data));
    }

    /**
     * Invalidate the JWT (logout)
     *
     * Meng-invalidasi token JWT sehingga tidak bisa dipakai lagi
     */
    #[Group('Authentication')]
    public function logout()
    {
        Auth::logout(true);

        return $this->success(null, 'Success logout');
    }

    /**
     * Get JWT refreshed token
     *
     * Mendapatkan refresh token apabila token JWT sudah expired (max 2 jam setelah login pertama)
     */
    #[Group('Authentication')]
    #[ExcludeRouteFromDocs]
    public function refresh()
    {
        $token = Auth::refresh();

        return $this->success([
            'token' => $token,
            'expiresIn' => now()
                ->addMinutes(Auth::factory()->getTTL())
                ->setTimezone(config('app.timezone'))
                ->toIso8601String(),
        ]);
    }

    /**
     * Is username used
     */
    #[Group('User')]
    #[ExcludeRouteFromDocs]
    public function checkUsername(CheckUsernameRequest $request)
    {
        return $this->success(['username' => true], 'Username not exist');
    }
}
