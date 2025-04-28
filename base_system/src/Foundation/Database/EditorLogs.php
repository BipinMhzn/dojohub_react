<?php

namespace BaseSystem\Foundation\Database;


use BaseSystem\Controllers\Exceptions\EditorNullException;
use Illuminate\Database\Eloquent\Concerns\HasRelationships;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait EditorLogs
{
    use HasRelationships;

    /**
     * @var bool
     */
    protected static bool $hasDeleterLog = true;

    /**
     * @var bool
     */
    protected static bool $failIfEditorNotFound = false;

    /**
     * The created by column name
     * @var string
     */
    protected string $createdBy = 'created_by';

    /**
     * The updated by column name
     * @var string
     */
    protected string $updatedBy = 'updated_by';

    /**
     * The deleted by column name
     * @var string
     */
    protected string $deletedBy = 'deleted_by';

    /**
     * The name of primary key of the owner model
     * @var string
     */
    protected string $ownerPrimaryKeyName = "id";

    /**
     * The editor model
     * @var null
     */
    protected mixed $editor = null;

    /**
     * Let's add some foundation code of this trait
     */
    public static function bootEditorLogs(): void
    {
        static::creating(function ($model) {
            $self = new static();
            $editor = $self->getEditor();
            if ($editor) {
                $model->{$self->createdBy} = $editor->{$self->ownerPrimaryKeyName};
                $model->{$self->updatedBy} = $editor->{$self->ownerPrimaryKeyName};
            } elseif (static::$failIfEditorNotFound) {
                throw new EditorNullException();
            }
        });

        static::updating(function ($model) {
            $self = new static();
            $editor = $self->getEditor();
            if ($editor) {
                $model->{$self->updatedBy} = $editor->{$self->ownerPrimaryKeyName};
            } elseif (static::$failIfEditorNotFound) {
                throw new EditorNullException();
            }
        });

        if (static::$hasDeleterLog) {
            static::deleting(function ($model) {
                $self = new static();
                $editor = $self->getEditor();
                if ($editor) {
                    $model->{$self->updatedBy} = $editor->{$self->ownerPrimaryKeyName};
                    $model->{$self->deletedBy} = $editor->{$self->ownerPrimaryKeyName};
                    $model->save();
                } elseif (static::$failIfEditorNotFound) {
                    throw new EditorNullException();
                }
            });
        }
    }

    public function getEditor()
    {
        if (!$this->editor) {
            $this->editor = $this->editorProvider();
        }
        return $this->editor;
    }

    /**
     * Set editor of the model
     * @param $editor
     */
    public function setEditor($editor): void
    {
        $this->editor = $editor;
    }

    /**
     * Tell what model should the model use while updating/creating/deleting.
     * Example return Auth::user()
     */
    abstract public function editorProvider();

    /**
     * The initial creator of this model
     * @return BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo($this->getEditorModelClassName(), $this->createdBy, $this->ownerPrimaryKeyName);
    }

    /**
     * What model to use if editor log is enabled. example User::class
     * @return string
     */
    abstract public function getEditorModelClassName(): string;

    /**
     * The last latest updater of this model
     * @return BelongsTo
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo($this->getEditorModelClassName(), $this->updatedBy, $this->ownerPrimaryKeyName);
    }

    /**
     * The latest deleter of this model
     * @return BelongsTo
     */
    public function deleter(): BelongsTo
    {
        return $this->belongsTo($this->getEditorModelClassName(), $this->deletedBy, $this->ownerPrimaryKeyName);
    }

}
