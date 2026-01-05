<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirect()
   {
    return Socialite::driver('google')
        ->redirectUrl(config('services.google.redirect'))
        ->stateless()
        ->redirect();
    }

    public function callback()
    {
        $googleUser = Socialite::driver('google')
            ->redirectUrl(config('services.google.redirect'))
            ->stateless()
            ->user();

        $user = User::where('email', $googleUser->getEmail())->first();

        if (!$user) {
            $user = User::create([
                'name' => $googleUser->getName() ?: 'ZenTask User',
                'email' => $googleUser->getEmail(),
                'password' => Str::random(32),
                'google_id' => $googleUser->getId(),
                'google_avatar' => $googleUser->getAvatar(),
                'email_verified_at' => now(),
                'role' => 'user',
                'subscription_plan' => 'free',
                'plan_started_at' => now(),
                'is_subscribed' => false,
            ]);
        } else {
            $user->update([
                'google_id' => $googleUser->getId(),
                'google_avatar' => $googleUser->getAvatar(),
            ]);
        }

        Auth::login($user, true);

        return redirect()->route('dashboard');
    }
}
