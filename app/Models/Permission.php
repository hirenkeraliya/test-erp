<?php

declare(strict_types=1);

namespace App\Models;

use App\Exceptions\RedirectBackWithErrorException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use Spatie\Permission\Contracts\Permission as ContractsPermission;
use Spatie\Permission\Guard;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Traits\HasPermissions;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Permission\Traits\RefreshesPermissionCache;

class Permission extends Model implements ContractsPermission
{
    use HasFactory;
    use HasRoles;
    use HasPermissions;
    use RefreshesPermissionCache;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['name', 'guard_name'];

    public static function findByName(string $name, ?string $guardName = null): self
    {
        return self::firstOrCreate([
            'name' => Str::snake($name),
            'guard_name' => $guardName,
        ]);
    }

    public static function findById(int|string $id, ?string $guardName = null): self
    {
        $guardName ??= Guard::getDefaultName(static::class);
        $permission = static::getPermission([
            'id' => $id,
            'guard_name' => $guardName,
        ]);

        if (! $permission instanceof self) {
            throw new RedirectBackWithErrorException('This account is not authorized to perform this action.');
        }

        return $permission;
    }

    public static function findOrCreate(string $name, ?string $guardName = null): self
    {
        $guardName ??= Guard::getDefaultName(static::class);
        $permission = static::getPermission([
            'name' => $name,
            'guard_name' => $guardName,
        ]);

        if (! $permission instanceof self) {
            return static::query()->create([
                'name' => $name,
                'guard_name' => $guardName,
            ]);
        }

        return $permission;
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            config('permission.models.role'),
            config('permission.table_names.role_has_permissions'),
            App::get(PermissionRegistrar::class)->pivotPermission,
            App::get(PermissionRegistrar::class)->pivotRole
        );
    }

    protected static function getPermissions(array $params = [], bool $onlyOne = false): Collection
    {
        return App::get(PermissionRegistrar::class)
            ->setPermissionClass(static::class)
            ->getPermissions($params, $onlyOne);
    }

    protected static function getPermission(array $params = []): ?self
    {
        return static::getPermissions($params, true)->first();
    }
}
