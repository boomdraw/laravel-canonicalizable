<?php

namespace Boomdraw\Canonicalizable\Tests\Integration;

use Boomdraw\Canonicalizable\CanonicalField;
use Boomdraw\Canonicalizable\CanonicalFieldsCollection;
use Illuminate\Database\Eloquent\SoftDeletes;

class TestModelSoftDeletes extends TestModel
{
    use SoftDeletes;

    /** @var string */
    protected $table = 'test_model_soft_deletes';

    /**
     * Get the default fields used in the tests.
     *
     * @return CanonicalFieldsCollection
     */
    public function getCanonicalFields(): CanonicalFieldsCollection
    {
        return CanonicalFieldsCollection::create()
            ->addField(
                CanonicalField::create()
                    ->from('email')
                    ->disallowDuplicate()
            );
    }
}
