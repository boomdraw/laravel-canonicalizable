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
