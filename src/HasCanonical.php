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
     * Get the value of the model's primary key.
     *
     * @return mixed
     */
    abstract public function getKey();

    /**
     * Get the primary key for the model.
     *
     * @return string
     */
    abstract public function getKeyName();

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
            if ($field->generateOnCreate || $this->forceCanonicalization($field)) {
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
            if ($field->generateOnUpdate || $this->forceCanonicalization($field)) {
                $this->addCanonical($field);
            }
        });
    }

    /**
     * Determine should canonicalization be forced
     *
     * @param CanonicalField $field
     * @return bool
     */
    protected function forceCanonicalization(CanonicalField $field): bool
    {
        $force = ($field->shouldBeUnique() || $field->forceCanonicalization);
        return $force && $this->hasCustomCanonicalBeenUsed($field->to);
    }

    /**
     * Write canonicalized value to a field.
     *
     * @param CanonicalField $field
     */
    protected function addCanonical(CanonicalField $field): void
    {
        $to = $field->to;
        if ($this->hasCustomCanonicalBeenUsed($to)) {
            $canonical = data_get($this, $to, '');
            if ($field->forceCanonicalization) {
                $canonical = $this->generateCanonical($field, $canonical);
            }
        } else {
            $canonical = data_get($this, $field->from, '');
            $canonical = $this->generateCanonical($field, $canonical);
        }
        if ($field->shouldBeUnique()) {
            $canonical = $this->makeCanonicalUnique($canonical, $to, $field->uniqueSeparator);
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
     * @param string|null $string
     * @return string|null
     */
    protected function generateCanonical(CanonicalField $field, string $string): ?string
    {
        $args = $field->args;
        array_unshift($args, $string);
        if (null !== $callback = $field->callback) {
            return call_user_func($callback, ...$args);
        }
        $callable = $this->determineCallable($field->type);

        return call_user_func($callable, ...$args);
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
            $canonical = $originalCanonical . $separator . $i++;
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
     * Determine callable for specific type.
     *
     * @param string $type
     * @return array
     */
    protected function determineCallable(string $type): array
    {
        if ('default' !== $type) {
            $method = 'canonicalize' . Str::studly($type);
            if (method_exists($this, $method)) {
                return [$this, $method];
            }
            $method = Str::camel($type);
            if (Canonicalizer::hasMacro($method) || method_exists(Canonicalizer::getFacadeRoot(), $method)) {
                return [Canonicalizer::class, $method];
            }
        }

        return [Canonicalizer::class, 'canonicalize'];
    }
}
