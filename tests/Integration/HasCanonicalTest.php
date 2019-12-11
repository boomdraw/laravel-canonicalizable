<?php

namespace Boomdraw\Canonicalizable\Tests\Integration;

use Boomdraw\Canonicalizable\CanonicalField;
use Boomdraw\Canonicalizable\CanonicalFieldsCollection;
use Boomdraw\Canonicalizable\Tests\TestCase;
use Boomdraw\Canonicalizer\Facades\Canonicalizer;
use Illuminate\Support\Str;

class HasCanonicalTest extends TestCase
{
    protected $email = 'HelLo.World@HellO.cOM.Nl';
    protected $other_email = 'BlAblABla@GmaIl.cOm';

    /** @test */
    public function it_will_save_a_canonical_when_saving_a_model(): void
    {
        $model = TestModel::create(['email' => $this->email]);

        $this->assertSame($model->email_canonical, Canonicalizer::canonicalize($this->email));
    }

    /** @test */
    public function it_can_handle_null_values_when_creating_canonical(): void
    {
        $model = TestModel::create(['email' => null]);

        $this->assertNull($model->email_canonical);
    }

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
    public function it_will_not_change_the_canonical_when_the_source_field_is_not_changed(): void
    {
        $model = TestModel::create(['email' => $this->email]);
        $model->other_field = 'otherValue';
        $model->save();

        $this->assertSame($model->email_canonical, Canonicalizer::canonicalize($this->email));
    }

    /** @test */
    public function it_will_use_the_source_field_if_the_canonical_field_is_empty(): void
    {
        $model = TestModel::create(['email' => $this->email]);
        $model->email_canonical = null;
        $model->save();

        $this->assertSame($model->email_canonical, Canonicalizer::canonicalize($this->email));
    }

    /** @test */
    public function it_will_update_the_canonical_when_the_source_field_is_changed(): void
    {
        $model = TestModel::create(['email' => $this->email]);
        $model->email = $this->other_email;
        $model->save();

        $this->assertSame($model->email_canonical, Canonicalizer::canonicalize($this->other_email));
    }

    /** @test */
    public function it_nulls_empty_source_fields(): void
    {
        $model = TestModel::create(['email' => '']);

        $this->assertNull($model->email_canonical);
    }

    /** @test */
    public function it_can_handle_overwrites_when_creating_a_model(): void
    {
        $model = TestModel::create(['email' => $this->email, 'email_canonical' => $this->other_email]);

        $this->assertSame($model->email_canonical, $this->other_email);
    }

    /** @test */
    public function it_can_handle_overwrites_when_updating_a_model(): void
    {
        $model = TestModel::create(['email' => $this->email]);
        $model->email_canonical = $this->other_email;
        $model->save();

        $this->assertSame($model->email_canonical, $this->other_email);
    }

    /** @test */
    public function it_has_a_custom_anonymous_callback_as_a_canonicalize_method(): void
    {
        $model = new class extends TestModel {
            public function getCanonicalFields(): CanonicalFieldsCollection
            {
                return CanonicalFieldsCollection::create()
                    ->addField(
                        CanonicalField::create()
                            ->from('email')
                            ->callback(static function ($string) {
                                return mb_strtoupper($string);
                            })
                    );
            }
        };
        $model->email = $this->email;
        $model->save();

        $this->assertSame($model->email_canonical, mb_strtoupper($this->email));
    }

    /** @test */
    public function it_passes_custom_arguments_to_an_anonymous_callback_when_specific_type_provided(): void
    {
        $model = new class extends TestModel {
            public function getCanonicalFields(): CanonicalFieldsCollection
            {
                return CanonicalFieldsCollection::create()->addField(
                    CanonicalField::create()
                        ->from('email')
                        ->type('custom', ['hello', 'world'])
                        ->callback(static function ($string, $arg1, $arg2) {
                            return mb_strtoupper($string.$arg1.$arg2);
                        })
                );
            }
        };
        $model->email = $this->email;
        $model->save();

        $this->assertSame($model->email_canonical, mb_strtoupper($this->email.'HELLOWORLD'));
    }

    /** @test */
    public function it_has_a_custom_callback_as_a_canonicalize_method(): void
    {
        $model = new class extends TestModel {
            public function getCanonicalFields(): CanonicalFieldsCollection
            {
                return CanonicalFieldsCollection::create()
                    ->addField(
                        CanonicalField::create()
                            ->from('email')
                            ->callback([Str::class, 'slug'])
                    );
            }
        };
        $model->email = $this->email;
        $model->save();

        $this->assertSame($model->email_canonical, Str::slug($this->email));
    }

    /** @test */
    public function it_passes_custom_arguments_to_a_custom_callback_as_a_canonicalize_method(): void
    {
        $model = new class extends TestModel {
            public function getCanonicalFields(): CanonicalFieldsCollection
            {
                return CanonicalFieldsCollection::create()
                    ->addField(
                        CanonicalField::create()
                            ->from('email')
                            ->type('custom', ['_'])
                            ->callback([Str::class, 'slug'])
                    );
            }
        };
        $model->email = $this->email;
        $model->save();

        $this->assertSame($model->email_canonical, Str::slug($this->email, '_'));
    }

    /** @test */
    public function it_has_a_method_that_prevents_a_canonical_being_generated_on_creation(): void
    {
        $model = new class extends TestModel {
            public function getCanonicalFields(): CanonicalFieldsCollection
            {
                return CanonicalFieldsCollection::create()->addField(CanonicalField::create()->from('email')->doNotGenerateOnCreate());
            }
        };
        $model->email = $this->email;
        $model->save();

        $this->assertNull($model->email_canonical);
    }

    /** @test */
    public function it_has_a_method_that_prevents_a_canonical_being_generated_on_update(): void
    {
        $model = new class extends TestModel {
            public function getCanonicalFields(): CanonicalFieldsCollection
            {
                return CanonicalFieldsCollection::create()->addField(CanonicalField::create()->from('email')->doNotGenerateOnUpdate());
            }
        };
        $model->email = $this->email;
        $model->save();

        $model->email = $this->other_email;
        $model->save();

        $this->assertSame($model->email_canonical, Canonicalizer::canonicalize($this->email));
    }

    /** @test */
    public function it_has_a_method_to_force_canonical_generation(): void
    {
        $model = new class extends TestModel {
            public function getCanonicalFields(): CanonicalFieldsCollection
            {
                return CanonicalFieldsCollection::create()->addField(CanonicalField::create()->from('email')->doNotGenerateOnUpdate());
            }
        };
        $model->email = $this->email;
        $model->save();

        $model->email = $this->other_email;
        $this->assertTrue($model->canonicalize('email'));
        $this->assertFalse($model->canonicalize('mail'));
        $model->save();

        $this->assertSame($model->email_canonical, Canonicalizer::canonicalize($this->other_email));
    }

    /** @test */
    public function it_calls_custom_model_method_when_specific_type_provided(): void
    {
        $model = new class extends TestModel {
            public function getCanonicalFields(): CanonicalFieldsCollection
            {
                return CanonicalFieldsCollection::create()->addField(CanonicalField::create()->from('email')->type('custom'));
            }

            public function canonicalizeCustom(string $string)
            {
                return $string.'ok!';
            }
        };
        $model->email = $this->email;
        $model->save();

        $this->assertSame($model->email_canonical, $this->email.'ok!');
    }

    /** @test */
    public function it_calls_other_canonicalizer_method_when_specific_type_provided(): void
    {
        $model = new class extends TestModel {
            public function getCanonicalFields(): CanonicalFieldsCollection
            {
                return CanonicalFieldsCollection::create()->addField(CanonicalField::create()->from('email')->type('email'));
            }
        };
        $model->email = $this->email;
        $model->save();

        $this->assertSame($model->email_canonical, Canonicalizer::email($this->email));
    }

    /** @test */
    public function it_passes_custom_arguments_to_other_canonicalizer_method_when_specific_type_provided(): void
    {
        $model = new class extends TestModel {
            public function getCanonicalFields(): CanonicalFieldsCollection
            {
                return CanonicalFieldsCollection::create()->addField(CanonicalField::create()->from('email')->type('slug', ['_']));
            }
        };
        $model->email = $this->email;
        $model->save();

        $this->assertSame($model->email_canonical, Canonicalizer::slug($this->email, '_'));
    }

    /** @test */
    public function it_calls_canonicalizer_macro_method_when_specific_type_provided(): void
    {
        $model = new class extends TestModel {
            public function getCanonicalFields(): CanonicalFieldsCollection
            {
                return CanonicalFieldsCollection::create()->addField(CanonicalField::create()->from('email')->type('custom'));
            }
        };
        Canonicalizer::macro('custom', function (string $string) {
            return $string.'ok!';
        });
        $model->email = $this->email;
        $model->save();

        $this->assertSame($model->email_canonical, $this->email.'ok!');
    }

    /** @test */
    public function it_will_call_default_method_if_unkonwn_type(): void
    {
        $model = new class extends TestModel {
            public function getCanonicalFields(): CanonicalFieldsCollection
            {
                return CanonicalFieldsCollection::create()->addField(CanonicalField::create()->from('email')->type('blabla'));
            }
        };
        $model->email = $this->email;
        $model->save();

        $this->assertSame($model->email_canonical, Canonicalizer::canonicalize($this->email));
    }

    /** @test */
    public function it_will_write_canonicalized_to_custom_field(): void
    {
        $model = new class extends TestModel {
            public function getCanonicalFields(): CanonicalFieldsCollection
            {
                return CanonicalFieldsCollection::create()->addField(CanonicalField::create()->from('email')->to('other_field'));
            }
        };
        $model->email = $this->email;
        $model->save();

        $this->assertSame($model->other_field, Canonicalizer::canonicalize($this->email));
        $this->assertNull($model->email_canonical);
    }
}
