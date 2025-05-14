<?php

namespace Database\Traits;

use Illuminate\Database\Schema\Blueprint;

trait EditorTrait
{
    protected function editorLogs(Blueprint $blueprint): void
    {
        $blueprint->bigInteger('created_by')->unsigned()->nullable();
        $blueprint->bigInteger('updated_by')->unsigned()->nullable();
        $blueprint->bigInteger('deleted_by')->unsigned()->nullable();

        $blueprint->foreign('created_by')->references('id')->on('users');
        $blueprint->foreign('updated_by')->references('id')->on('users');
        $blueprint->foreign('deleted_by')->references('id')->on('users');

        $blueprint->timestamps();
        $blueprint->softDeletes();
    }
}
