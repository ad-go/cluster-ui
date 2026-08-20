<?php

namespace App\Controllers;

use App\Models\UserProfileModel;
use CodeIgniter\HTTP\ResponseInterface;

class ProfileController extends BaseController
{
    public function index(): string
    {
        $profile = model(UserProfileModel::class)->forUser(auth()->id());

        return view('Profile/index', ['user' => auth()->user(), 'profile' => $profile] + $this->layoutData());
    }

    // Reactive form, no save button - same per-field autosave pattern as
    // SettingsController::update(), one field per request. Password is
    // sent (and applied) like any other field, but the view always
    // re-renders it blank - see Profile/index.php - so it never echoes back
    // a hash and can't be re-submitted by an untouched autosave timer.
    public function updateField(): ResponseInterface
    {
        $user  = auth()->user();
        $field = (string) $this->request->getPost('field');
        $value = trim((string) $this->request->getPost('value'));

        $rules = [
            'username' => 'permit_empty|min_length[3]|max_length[30]',
            'email'    => 'permit_empty|valid_email',
            'phone'    => 'permit_empty|max_length[30]',
            'password' => 'permit_empty|min_length[8]',
        ];
        if (! array_key_exists($field, $rules)) {
            return $this->response->setStatusCode(422)->setJSON(['ok' => false]);
        }
        if (! $this->validateData(['value' => $value], ['value' => $rules[$field]])) {
            return $this->response->setStatusCode(422)->setJSON(['ok' => false, 'errors' => $this->validator->getErrors()]);
        }
        if ($value === '') {
            // Blank means "leave unchanged" for every one of these fields,
            // same semantics the old full-form submit used - not "clear it".
            return $this->response->setJSON(['ok' => true, 'csrf' => $this->csrfPayload()]);
        }

        if ($field === 'username') {
            if ($value !== $user->username) {
                $user->username = $value;
                auth()->getProvider()->save($user);
            }
        } elseif ($field === 'phone') {
            model(UserProfileModel::class)->savePreference(auth()->id(), ['phone' => $value]);
        } else {
            // email / password both live on Shield's email+password
            // identity, not the user row - see the original update()'s own
            // comment on why $identity is mutated directly rather than via
            // the nullsafe operator.
            $identityModel = new \CodeIgniter\Shield\Models\UserIdentityModel();
            $identity      = $identityModel->getIdentityByType($user, \CodeIgniter\Shield\Authentication\Authenticators\Session::ID_TYPE_EMAIL_PASSWORD);
            if ($identity !== null) {
                if ($field === 'email' && $value !== $identity->secret) {
                    $identity->secret = $value;
                    $identityModel->save($identity);
                } elseif ($field === 'password') {
                    $identity->secret2 = service('passwords')->hash($value);
                    $identityModel->save($identity);

                    // Cluster-wide session kill on password change (see
                    // ad-go/cluster's README, "How password-change
                    // invalidation works"). Keyed by $identity->secret, not
                    // $value - if email and password autosave in quick
                    // succession, this is already the NEW email, which is
                    // what SessionInvalidationFilter will look up on every
                    // node from now on.
                    if (class_exists(\AdGo\Cluster\Cluster::class)) {
                        (new \AdGo\Cluster\Cluster())->broadcastPasswordChange($identity->secret);
                    }
                }
            }
        }

        return $this->response->setJSON(['ok' => true, 'csrf' => $this->csrfPayload()]);
    }

    // Reactive avatar upload - same pattern as SettingsController::
    // uploadLogo(), replacing the old <form enctype="multipart/form-data">
    // submit now that the button next to it is gone.
    public function uploadAvatar(): ResponseInterface
    {
        $avatar = $this->request->getFile('avatar');
        if ($avatar === null || ! $avatar->isValid() || $avatar->hasMoved()) {
            return $this->response->setStatusCode(422)->setJSON(['ok' => false]);
        }

        $model = model(UserProfileModel::class);
        $old   = $model->forUser(auth()->id())['avatar'] ?? null;
        if ($old && is_file(FCPATH . $old)) {
            @unlink(FCPATH . $old);
        }

        $name = $avatar->getRandomName();
        $avatar->move(FCPATH . 'uploads/avatars', $name);
        $path = 'uploads/avatars/' . $name;
        $model->savePreference(auth()->id(), ['avatar' => $path]);

        return $this->response->setJSON(['ok' => true, 'path' => $path, 'csrf' => $this->csrfPayload()]);
    }

    // Reactive, small AJAX updates - theme toggle and the two resizable
    // divider positions. No save button anywhere in the UI for these; the
    // browser fires this on toggle / on pointerup after a drag.
    public function preference(): ResponseInterface
    {
        $allowed = ['theme', 'navbar_height', 'taskbar_width', 'language', 'auto_switch_node'];
        $data    = array_intersect_key($this->request->getJSON(true) ?? $this->request->getPost() ?? [], array_flip($allowed));
        if ($data === []) {
            return $this->response->setStatusCode(422)->setJSON(['ok' => false]);
        }
        model(UserProfileModel::class)->savePreference(auth()->id(), $data);

        return $this->response->setJSON(['ok' => true, 'csrf' => $this->csrfPayload()]);
    }

    // Thumbnail "Delete" badge next to the avatar upload field - removes
    // the file from disk and clears the DB reference, reactively (no full
    // page reload / no separate save button needed for this action).
    public function deleteAvatar(): ResponseInterface
    {
        $model   = model(UserProfileModel::class);
        $profile = $model->forUser(auth()->id());
        $path    = $profile['avatar'] ?? null;
        if ($path && is_file(FCPATH . $path)) {
            @unlink(FCPATH . $path);
        }
        $model->savePreference(auth()->id(), ['avatar' => null]);

        return $this->response->setJSON(['ok' => true, 'csrf' => $this->csrfPayload()]);
    }
}
