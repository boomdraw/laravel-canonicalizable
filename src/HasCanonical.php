<?php

namespace Boomdraw\Canonicalizable;

use Boomdraw\Canonicalizer\Facades\Canonicalizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait HasCanonical
{
    /**
     * Get fields to generate canonical for.
     *
     * @return CanonicalFieldsCollection
     */
    abstract public function getCanonicalFields(): CanonicalFieldsCollection;

    /**
     * Get the model's original attribute values.
     *
     * @param string|null $key
     * @param mixed $default
     * @return mixed|array
     */
    abstract public function getOriginal($key = null, $default = null);

    /**
     * Updates canonical field immediately.
     *
     * @param string $fieldName
     * @return bool
     */
    public function canonicalize(string $fieldName): bool
    {
        $field = $this->getCanonicalFields()->getFields()[$fieldName] ?? false;
        if ($field) {
            $this->addCanonical($field);

            return true;
        }

        return false;
    }

    /**
     * Bootstrap trait.
     *
     * @return void
     */
    protected static function bootHasCanonical(): void
    {
        static::creating(static function (Model $model) {
            $model->generateOnCreate();
        });

        static::updating(static function (Model $model) {
            $model->generateOnUpdate();
        });
    }

    /**
     * Generate canonical fields on creating event.
     *
     * @return void
     */
    protected function generateOnCreate(): void
    {
        $this->getCanonicalFields()->getFields()->each(function ($field) {
            if ($field->generateOnCreate) {
                $this->addCanonical($field);
            }
        });
    }

    /**
     * Generate canonical fields on updating event.
     *
     * @return void
     */
    protected function generateOnUpdate(): void
    {
        $this->getCanonicalFields()->getFields()->each(function ($field) {
            if ($field->generateOnUpdate) {
                $this->addCanonical($field);
            }
        });
    }

    /**
     * Write canonicalized value to a field.
     *
     * @param CanonicalField $field
     */
    protected function addCanonical(CanonicalField $field): void
    {
        if ($this->hasCustomCanonicalBeenUsed($field->to)) {
            return;
        }
        $canonical = $this->generateCanonical($field);
        $to = $field->to;
        if (null !== $separator = $field->uniqueSeparator) {
            $canonical = $this->makeCanonicalUnique($canonical, $to, $separator);
        }
        $this->$to = $canonical;
    }

    /**
     * Determine if the custom canonical value was used.
     *
     * @param string $to
     * @return bool
     */
    protected function hasCustomCanonicalBeenUsed(string $to): bool
    {
        return $this->getOriginal($to) !== $this->$to && null !== $this->$to;
    }

    /**
     * Generate canonical field value.
     *
     * @param CanonicalField $field
     * @return string|null
     */
    protected function generateCanonical(CanonicalField $field): ?string
    {
        $data = data_get($this, $field->from, '');

        if (null !== $callback = $field->callback) {
            return $callback($data);
        }

        if ('default' !== $field->type) {
            $method = 'canonicalize'.Str::studly($field->type);
            $class = $this->determineClassForMethod($method);
        }

        if (empty($method) || empty($class)) {
            $class = Canonicalizer::class;
            $method = 'canonicalize';
        }

        return call_user_func([$class, $method], $data);
    }

    /**
     * Make canonical value unique.
     *
     * @param string|null $canonical
     * @param string $field
     * @param string $separator
     * @return string
     */
    protected function makeCanonicalUnique(?string $canonical, string $field, string $separator): string
    {
        $originalCanonical = $canonical;
        $i = 1;
        while (empty($canonical) || $this->otherRecordExistsWithCanonical($canonical, $field)) {
            $canonical = $originalCanonical.$separator.$i++;
        }

        return $canonical;
    }

    /**
     * Check another record with current canonical value existence.
     *
     * @param string $canonical
     * @param string $field
     * @return bool
     */
    protected function otherRecordExistsWithCanonical(string $canonical, string $field): bool
    {
        $key = $this->getKey();
        if ($this->incrementing) {
            $key ??= '0';
        }
        $query = static::where($field, $canonical)
            ->where($this->getKeyName(), '!=', $key)
            ->withoutGlobalScopes();
        if ($this->usesSoftDeletes()) {
            $query->withTrashed();
        }

        return $query->exists();
    }

    /**
     * Determine if the model uses SoftDeletes
     *
     * @return bool
     */
    protected function usesSoftDeletes(): bool
    {
        return (bool)in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($this));
    }

    /**
     * Determine class for specified method.
     *
     * @param string $method
     * @return $this|string|null
     */
    protected function determineClassForMethod(string $method)
    {
        if (method_exists($this, $method)) {
            return $this;
        }
        if (Canonicalizer::hasMacro($method) || method_exists(Canonicalizer::getFacadeRoot(), $method)) {
            return Canonicalizer::class;
        }
    }
}
