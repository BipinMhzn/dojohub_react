<?php

namespace App\System;

use App\System\User\Database\Models\User;
use BaseSystem\Foundation\Database\BaseModel;
use BaseSystem\Foundation\Database\EditorLogs;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;

abstract class AppBaseModel extends BaseModel
{
    use SoftDeletes;
    use HasUlids;
    use HasFactory;
    use EditorLogs;

    protected $hidden = [
        'created_by',
        'updated_by',
        'deleted_by',
        'deleted_at',
    ];

    /**
     * @return Authenticatable|null
     */
    public function editorProvider(): ?Authenticatable
    {
        return Auth::user();
    }

    /**
     * @return string
     */
    public function getEditorModelClassName(): string
    {
        return User::class;
    }

    /**
     * @return string
     */
    public function getMorphClass(): string
    {
        return class_basename($this);
    }
}
