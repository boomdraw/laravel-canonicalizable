<?php

namespace Boomdraw\Canonicalizable\Tests\Integration;

use Boomdraw\Canonicalizable\CanonicalField;
use Boomdraw\Canonicalizable\CanonicalFieldsCollection;
use Boomdraw\Canonicalizable\Tests\TestCase;
use Boomdraw\Canonicalizer\Facades\Canonicalizer;
use Illuminate\Support\Str;

class HasCanonicalCustomCallbackTest extends TestCase
{
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
}
