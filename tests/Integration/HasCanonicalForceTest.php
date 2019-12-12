<?php

namespace Boomdraw\Canonicalizable\Tests\Integration;

use Boomdraw\Canonicalizable\CanonicalField;
use Boomdraw\Canonicalizable\CanonicalFieldsCollection;
use Boomdraw\Canonicalizable\Tests\TestCase;
use Boomdraw\Canonicalizer\Facades\Canonicalizer;
use Illuminate\Support\Str;

class HasCanonicalForceTest extends TestCase
{
    /** @test */
    public function it_forces_canonicalization_if_custom_canonicalized_setted(): void
    {
        $model = new class extends TestModel {
            public function getCanonicalFields(): CanonicalFieldsCollection
            {
                return CanonicalFieldsCollection::create()
                    ->addField(
                        CanonicalField::create()
                            ->from('email')
                            ->forceCanonicalization()
                    );
            }
        };
        $model->email = $this->other_email;
        $model->email_canonical = $this->email;
        $model->save();

        $this->assertSame($model->email_canonical, Canonicalizer::canonicalize($this->email));
    }

    /** @test */
    public function it_forces_specified_canonicalization_if_custom_canonicalized_setted(): void
    {
        $model = new class extends TestModel {
            public function getCanonicalFields(): CanonicalFieldsCollection
            {
                return CanonicalFieldsCollection::create()
                    ->addField(
                        CanonicalField::create()
                            ->from('email')
                            ->type('email')
                            ->forceCanonicalization()
                    );
            }
        };
        $model->email = $this->other_email;
        $model->email_canonical = $this->email;
        $model->save();

        $this->assertSame($model->email_canonical, Canonicalizer::email($this->email));
    }

    /** @test */
    public function it_forces_custom_canonicalization_if_custom_canonicalized_setted(): void
    {
        $model = new class extends TestModel {
            public function getCanonicalFields(): CanonicalFieldsCollection
            {
                return CanonicalFieldsCollection::create()
                    ->addField(
                        CanonicalField::create()
                            ->from('email')
                            ->callback([Str::class, 'slug'])
                            ->forceCanonicalization()
                    );
            }
        };
        $model->email = $this->other_email;
        $model->email_canonical = $this->email;
        $model->save();

        $this->assertSame($model->email_canonical, Str::slug($this->email));
    }

    /** @test */
    public function it_forces_anonymous_if_custom_canonicalized_setted(): void
    {
        $model = new class extends TestModel {
            public function getCanonicalFields(): CanonicalFieldsCollection
            {
                return CanonicalFieldsCollection::create()
                    ->addField(
                        CanonicalField::create()
                            ->from('email')
                            ->forceCanonicalization()
                            ->callback(static function ($string) {
                                return mb_strtoupper($string);
                            })
                    );
            }
        };
        $model->email = $this->other_email;
        $model->email_canonical = $this->email;
        $model->save();

        $this->assertSame($model->email_canonical, mb_strtoupper($this->email));
    }

    /** @test */
    public function it_forces_canonicalization_when_from_and_to_are_same(): void
    {
        $model = new class extends TestModel {
            public function getCanonicalFields(): CanonicalFieldsCollection
            {
                return CanonicalFieldsCollection::create()
                    ->addField(
                        CanonicalField::create()
                            ->from('email')
                            ->to('email')
                            ->forceCanonicalization()
                    );
            }
        };
        $model->email = $this->email;
        $model->save();

        $this->assertSame($model->email, Canonicalizer::canonicalize($this->email));
    }

    /** @test */
    public function it_forces_canonicalization_even_when_it_is_prevented_on_creation_or_update(): void
    {
        $model = new class extends TestModel {
            public function getCanonicalFields(): CanonicalFieldsCollection
            {
                return CanonicalFieldsCollection::create()
                    ->addField(
                        CanonicalField::create()
                            ->from('email')
                            ->forceCanonicalization()
                            ->doNotGenerateOnCreate()
                            ->doNotGenerateOnUpdate()
                    );
            }
        };
        $model->email_canonical = $this->email;
        $model->save();

        $this->assertSame($model->email_canonical, Canonicalizer::canonicalize($this->email));
    }
}
