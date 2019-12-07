<?php

namespace Boomdraw\Canonicalizable;

use Illuminate\Support\Collection;

class CanonicalFieldsCollection
{
    /** @var Collection */
    private Collection $fields;

    /**
     * CanonicalFieldsCollection constructor.
     */
    public function __construct()
    {
        $this->fields = new Collection();
    }

    /**
     * Create new CanonicalFieldsCollection instance.
     *
     * @return CanonicalFieldsCollection
     */
    public static function create(): self
    {
        return new static;
    }

    /**
     * Add field to collection.
     *
     * @param CanonicalField $field
     * @return CanonicalFieldsCollection
     */
    public function addField(CanonicalField $field): self
    {
        $this->fields[$field->from] = $field;

        return $this;
    }

    /**
     * Get collection fields
     *
     * @return Collection
     */
    public function getFields(): Collection
    {
        return $this->fields;
    }

}
