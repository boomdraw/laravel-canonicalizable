<?php

namespace Boomdraw\Canonicalizable;

use Closure;

class CanonicalField
{
    /** @var string */
    public string $from;

    /** @var string */
    public string $to;

    /** @var string */
    public string $type = 'default';

    /** @var Closure|null */
    public ?Closure $callback = null;

    /** @var string|null */
    public ?string $uniqueSeparator = null;

    /** @var bool */
    public bool $generateOnCreate = true;

    /** @var bool */
    public bool $generateOnUpdate = true;

    /**
     * Create new CanonicalField instance.
     *
     * @return CanonicalField
     */
    public static function create(): self
    {
        return new static;
    }

    /**
     * Set field to generate canonical from.
     *
     * @param string $name
     * @return CanonicalField
     */
    public function from(string $name): self
    {
        $this->from = $name;
        if (empty($this->to)) {
            $this->to = "{$name}_canonical";
        }

        return $this;
    }

    /**
     * Set field to store canonicalized value.
     *
     * @param string $name
     * @return CanonicalField
     */
    public function to(string $name): self
    {
        $this->to = $name;

        return $this;
    }

    /**
     * Set field type to determine generator method.
     *
     * @param string $type
     * @return CanonicalField
     */
    public function type(string $type = 'default'): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Set a custom canonicalization method to call.
     *
     * @param Closure $callback
     * @return CanonicalField
     */
    public function callback(Closure $callback): self
    {
        $this->callback = $callback;

        return $this;
    }

    /**
     * Disable canonicalization on create.
     *
     * @return CanonicalField
     */
    public function doNotGenerateOnCreate(): self
    {
        $this->generateOnCreate = false;

        return $this;
    }

    /**
     * Disable canonicalization on update.
     *
     * @return CanonicalField
     */
    public function doNotGenerateOnUpdate(): self
    {
        $this->generateOnUpdate = false;

        return $this;
    }

    /**
     * Enable unique canonicalization field processing.
     *
     * @param string $separator
     * @return $this
     */
    public function disallowDuplicate(string $separator = '-'): self
    {
        $this->uniqueSeparator = $separator;

        return $this;
    }
}
