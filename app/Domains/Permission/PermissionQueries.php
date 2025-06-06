<?php

declare(strict_types=1);

namespace App\Domains\Permission;

use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;

class PermissionQueries
{
    public function addNew(Collection $permissions, string $guardName): void
    {
        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => $guardName,
            ]);
        }
    }

    public function getBasicColumns(): string
    {
        return 'id,name';
    }
}
