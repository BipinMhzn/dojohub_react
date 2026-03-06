<?php

namespace App\Modules\Users\Repositories;

use App\Modules\Users\Filters\UsersFilter;
use App\Modules\Users\Interfaces\UserInterface;
use App\Modules\Users\Models\User;
use BaseSystem\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class UserRepository
 */
final class UserRepository extends BaseRepository implements UserInterface
{
    /**
     * UserRepository constructor.
     */
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    public function getFilter(Builder $builder): UsersFilter
    {
        return new UsersFilter($builder);
    }
}
