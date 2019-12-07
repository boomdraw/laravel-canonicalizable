<?php

namespace Boomdraw\Canonicalizable\Tests\Integration;

use Boomdraw\Canonicalizable\CanonicalFieldsCollection;
use Boomdraw\Canonicalizable\CanonicalField;
use Boomdraw\Canonicalizable\HasCanonical;
use Illuminate\Database\Eloquent\Model;

class TestModel extends Model
{
    use HasCanonical;

    /** @var string */
    protected $table = 'test_models';

    /** @var array $guarded */
    protected $guarded = [];

    /** @var bool $timestamps */
    public $timestamps = false;

    /** @var CanonicalFieldsCollection */
    protected CanonicalFieldsCollection $canonicalFieldsCollection;

    /**
     * Get fields to generate canonical for.
     *
     * @return CanonicalFieldsCollection
     */
    public function getCanonicalFields(): CanonicalFieldsCollection
    {
        return $this->canonicalFieldsCollection ?? $this->getDefaultCanonicalFieldsCollection();
    }

    /**
     * Set fields to generate canonical for.
     *
     * @param CanonicalFieldsCollection $canonicalFieldsCollection
     * @return $this
     */
    public function setCanonicalFields(CanonicalFieldsCollection $canonicalFieldsCollection): self
    {
        $this->canonicalFieldsCollection = $canonicalFieldsCollection;

        return $this;
    }

    /**
     * Get the default fields used in the tests.
     *
     * @return CanonicalFieldsCollection
     */
    public function getDefaultCanonicalFieldsCollection(): CanonicalFieldsCollection
    {
        return CanonicalFieldsCollection::create()
            ->addField(
                CanonicalField::create()
                    ->from('email')
            );
    }
}
