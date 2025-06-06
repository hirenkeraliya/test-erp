<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\App;
use Spatie\Permission\Contracts\Role as SpatieRole;
use Spatie\Permission\Exceptions\RoleDoesNotExist;
use Spatie\Permission\Guard;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Traits\HasPermissions;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Permission\Traits\RefreshesPermissionCache;

class Role extends Model implements SpatieRole
{
    use HasFactory;
    use HasRoles;
    use HasPermissions;
    use RefreshesPermissionCache;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['name', 'guard_name'];

    public static function findByName(string $name, ?string $guardName = null): SpatieRole
    {
        $guardName ??= Guard::getDefaultName(static::class);

        $role = static::findByParam([
            'name' => $name,
            'guard_name' => $guardName,
        ]);

        if (! $role instanceof self) {
            throw RoleDoesNotExist::named($name, $guardName);
        }

        return $role;
    }

    public static function findById(int|string $id, ?string $guardName = null): SpatieRole
    {
        $guardName ??= Guard::getDefaultName(static::class);

        $role = static::findByParam([
            'id' => $id,
            'guard_name' => $guardName,
        ]);

        if (! $role instanceof self) {
            throw RoleDoesNotExist::withId($id, $guardName);
        }

        return $role;
    }

    public static function findOrCreate(string $name, ?string $guardName = null): self
    {
        $guardName ??= Guard::getDefaultName(static::class);

        $role = static::findByParam([
            'name' => $name,
            'guard_name' => $guardName,
        ]);

        if (! $role instanceof self) {
            /* @phpstan-ignore-next-line */
            return static::query()->create([
                'name' => $name,
                'guard_name' => $guardName,
            ] + (App::get(PermissionRegistrar::class)->teams ? [
                App::get(PermissionRegistrar::class)->teamsKey => getPermissionsTeamId(),
            ] : []));
        }

        return $role;
    }

    /**
     * A role may be given various permissions.
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            config('permission.models.permission'),
            config('permission.table_names.role_has_permissions'),
            App::get(PermissionRegistrar::class)->pivotRole,
            App::get(PermissionRegistrar::class)->pivotPermission
        );
    }

    protected static function findByParam(array $params = []): ?self
    {
        $query = self::query();

        if (App::get(PermissionRegistrar::class)->teams) {
            $teamsKey = App::get(PermissionRegistrar::class)->teamsKey;

            $query->where(fn ($q) => $q->whereNull($teamsKey)
                ->orWhere($teamsKey, $params[$teamsKey] ?? getPermissionsTeamId())
            );
            unset($params[$teamsKey]);
        }

        foreach ($params as $key => $value) {
            $query->where($key, $value);
        }

        return $query->first();
    }
}
