<?php

namespace App\Controllers;

use CodeIgniter\HTTP\RedirectResponse;

class LocaleController extends BaseController
{
    public function update(): RedirectResponse
    {
        $locale = (string) $this->request->getPost('locale');
        if (in_array($locale, config('App')->supportedLocales, true)) {
            session()->set('locale', $locale);
            // Persisted to the user's profile too, not just the session -
            // so the preference survives across devices/sessions once logged in.
            if (auth()->loggedIn()) {
                model(\App\Models\UserProfileModel::class)->savePreference(auth()->id(), ['language' => $locale]);
            }
        }

        return redirect()->back();
    }
}
