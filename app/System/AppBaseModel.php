<?php

namespace App\System;

use App\System\User\Models\User;
use BaseSystem\Foundation\Database\BaseModel;
use BaseSystem\Foundation\Database\EditorLogs;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

abstract class AppBaseModel extends BaseModel
{
    use EditorLogs;
    use HasFactory;
    use HasUlids;
    use SoftDeletes;

    protected $hidden = [
        'created_by',
        'updated_by',
        'deleted_by',
        'deleted_at',
    ];

    public function editorProvider(): ?Authenticatable
    {
        return Auth::user();
    }

    public function getEditorModelClassName(): string
    {
        return User::class;
    }

    public function getMorphClass(): string
    {
        return class_basename($this);
    }
}
