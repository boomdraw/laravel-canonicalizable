<?php

namespace Boomdraw\Canonicalizable\Tests\Integration;

use Boomdraw\Canonicalizable\CanonicalField;
use Boomdraw\Canonicalizable\CanonicalFieldsCollection;
use Boomdraw\Canonicalizable\Tests\TestCase;
use Boomdraw\Canonicalizer\Facades\Canonicalizer;

class HasCanonicalUniqueTest extends TestCase
{
    /** @test */
    public function it_can_handle_null_values_when_creating_unique_canonical()
    {
        $model = new class extends TestModel {
            public function getCanonicalFields(): CanonicalFieldsCollection
            {
                return CanonicalFieldsCollection::create()
                    ->addField(
                        CanonicalField::create()
                            ->from('email')
                            ->disallowDuplicate()
                    );
            }
        };
        $model->save();
        $this->assertSame('-1', $model->email_canonical);
    }

    /** @test */
    public function it_will_save_an_unique_slug()
    {
        $model = new class extends TestModel {
            public function getCanonicalFields(): CanonicalFieldsCollection
            {
                return CanonicalFieldsCollection::create()
                    ->addField(
                        CanonicalField::create()
                            ->from('email')
                            ->disallowDuplicate()
                    );
            }
        };
        $model->email = $this->email;
        $testModel = $model->replicate();
        $testModel->save();
        foreach (range(1, 10) as $i) {
            $testModel = $model->replicate();
            $testModel->save();
            $this->assertSame(Canonicalizer::canonicalize($model->email)."-{$i}", $testModel->email_canonical);
        }
    }

    /** @test */
    public function it_will_save_an_unique_slug_with_custom_separator()
    {
        $model = new class extends TestModel {
            public function getCanonicalFields(): CanonicalFieldsCollection
            {
                return CanonicalFieldsCollection::create()
                    ->addField(
                        CanonicalField::create()
                            ->from('email')
                            ->disallowDuplicate('#')
                    );
            }
        };
        $model->email = $this->email;
        $testModel = $model->replicate();
        $testModel->save();

        foreach (range(1, 10) as $i) {
            $testModel = $model->replicate();
            $testModel->save();
            $this->assertSame(Canonicalizer::canonicalize($model->email)."#{$i}", $testModel->email_canonical);
        }
    }

    /** @test */
    public function it_will_save_an_unique_slug_even_when_soft_deletes_are_on()
    {
        TestModelSoftDeletes::create(['email' => $this->email, 'deleted_at' => date('Y-m-d h:i:s')]);

        foreach (range(1, 10) as $i) {
            $model = TestModelSoftDeletes::create(['email' => $this->email, 'deleted_at' => date('Y-m-d h:i:s')]);
            $this->assertSame(Canonicalizer::canonicalize($model->email)."-{$i}", $model->email_canonical);
        }
    }

    /** @test */
    public function it_makes_custom_canonicalized_unique()
    {
        $model = new class extends TestModel {
            public function getCanonicalFields(): CanonicalFieldsCollection
            {
                return CanonicalFieldsCollection::create()
                    ->addField(
                        CanonicalField::create()
                            ->from('email')
                            ->disallowDuplicate()
                    );
            }
        };
        $model->email_canonical = $this->email;
        $testModel = $model->replicate();
        $testModel->save();
        foreach (range(1, 10) as $i) {
            $testModel = $model->replicate();
            $testModel->save();
            $this->assertSame($this->email."-{$i}", $testModel->email_canonical);
        }
    }

    /** @test */
    public function it_forces_unique_check_even_when_it_is_prevented_on_creation_or_update(): void
    {
        $model = new class extends TestModel {
            public function getCanonicalFields(): CanonicalFieldsCollection
            {
                return CanonicalFieldsCollection::create()
                    ->addField(
                        CanonicalField::create()
                            ->from('email')
                            ->disallowDuplicate()
                            ->doNotGenerateOnCreate()
                            ->doNotGenerateOnUpdate()
                    );
            }
        };
        $model->email_canonical = $this->email;
        $testModel = $model->replicate();
        $testModel->save();
        foreach (range(1, 10) as $i) {
            $testModel = $model->replicate();
            $testModel->save();
            $this->assertSame($this->email."-{$i}", $testModel->email_canonical);
        }
    }
}
