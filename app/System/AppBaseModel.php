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
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

abstract class AppBaseModel extends BaseModel implements Auditable
{
    use AuditableTrait;
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

    // Exclude EditorLogs columns — redundant with audits.user_id / audits.event
    //    protected $auditExclude = [
    //        'created_by',
    //        'updated_by',
    //        'deleted_by',
    //    ];

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
