<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\LoginResource;

class LoginController extends Controller
{
    /**
     * Authenticate a user and return an API token.
     *
     * Validates the user's email and password credentials, then returns the authenticated
     * user data along with a new Sanctum API token for subsequent requests.
     */
    public function store(LoginRequest $request)
    {
        // Step 1: Validate incoming email and password data manually for the API
        $request->authenticate();

        // Step 2: Grab the authenticated instance via the auth helper
        $user =$request->user();

        // Step 3: Generate the raw, plain text token string
        $token = $user->createToken('main')->plainTextToken;

        // Step 4: Return it directly to Postman
        return response()->noContent();
    }

    /**
     * Logout the authenticated user.
     *
     * Revokes the current API access token, effectively logging the user out.
     */
    public function destroy(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->noContent();
    }
}
