<?php

namespace App\Models;

use CodeIgniter\Model;

class UserProfileModel extends Model
{
    protected $table         = 'user_profiles';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['user_id', 'phone', 'avatar', 'language', 'theme', 'navbar_height', 'taskbar_width', 'auto_switch_node'];
    protected $useTimestamps = true;

    public function forUser(int $userId): array
    {
        return $this->where('user_id', $userId)->first() ?? [];
    }

    public function savePreference(int $userId, array $fields): void
    {
        $fields = array_intersect_key($fields, array_flip($this->allowedFields));
        // A caller (e.g. ProfileController::update() with no phone/avatar
        // in the submission) can end up with nothing left to save here -
        // update() with an empty dataset throws DataException, and an
        // insert() with only user_id set is a pointless empty row.
        if ($fields === []) {
            return;
        }

        $existing = $this->where('user_id', $userId)->first();
        if ($existing) {
            $this->update($existing['id'], $fields);
            return;
        }
        $this->insert(['user_id' => $userId] + $fields);
    }
}
