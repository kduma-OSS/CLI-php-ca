<?php

declare(strict_types=1);

use KDuma\PhpCA\Entity\CACertificateEntity;
use KDuma\PhpCA\Entity\KeyEntity;
use KDuma\PhpCA\Record\CertificateSubject\CertificateSubject;
use KDuma\PhpCA\Record\CertificateSubject\DN\CommonName;
use KDuma\PhpCA\Record\CertificateSubject\DN\Organization;
use KDuma\PhpCA\Record\CertificateValidity;
use KDuma\PhpCA\Record\Extension\Resolver\CaKeyFingerprintResolver;
use KDuma\PhpCA\Record\Extension\Resolver\ExtensionValueResolverFactory;
use KDuma\PhpCA\Record\Extension\Resolver\InputMultipleResolver;
use KDuma\PhpCA\Record\Extension\Resolver\InputResolver;
use KDuma\PhpCA\Record\Extension\Resolver\IssuanceContext;
use KDuma\PhpCA\Record\Extension\Resolver\LiteralResolver;
use KDuma\PhpCA\Record\Extension\Resolver\PresetInputProvider;
use KDuma\PhpCA\Record\Extension\Resolver\RelativeDateResolver;
use KDuma\PhpCA\Record\Extension\Resolver\SubjectFieldResolver;
use KDuma\PhpCA\Record\Extension\Resolver\SubjectKeyFingerprintResolver;
use KDuma\PhpCA\Record\Extension\Resolver\TemplateStringResolver;

// --- LiteralResolver ---

test('LiteralResolver: resolves to its literal value', function () {
    $resolver = new LiteralResolver('hello world');
    $context = createMockIssuanceContext();

    expect($resolver->resolve($context))->toBe('hello world');
});

test('LiteralResolver: type returns literal', function () {
    expect(LiteralResolver::type())->toBe('literal');
});

test('LiteralResolver: toArray with string returns the string directly', function () {
    $resolver = new LiteralResolver('simple string');
    expect($resolver->toArray())->toBe('simple string');
});

test('LiteralResolver: toArray with non-string returns array format', function () {
    $resolver = new LiteralResolver(42);
    expect($resolver->toArray())->toBe(['type' => 'literal', 'value' => 42]);
});

test('LiteralResolver: fromArray round-trip', function () {
    $resolver = new LiteralResolver('test');
    $restored = LiteralResolver::fromArray(['value' => 'test']);

    expect($restored->value)->toBe('test');
});

// --- SubjectKeyFingerprintResolver ---

test('SubjectKeyFingerprintResolver: type returns correct type', function () {
    expect(SubjectKeyFingerprintResolver::type())->toBe('subject-key-fingerprint');
});

test('SubjectKeyFingerprintResolver: toArray returns type only', function () {
    $resolver = new SubjectKeyFingerprintResolver;
    expect($resolver->toArray())->toBe(['type' => 'subject-key-fingerprint']);
});

test('SubjectKeyFingerprintResolver: fromArray creates instance', function () {
    $resolver = SubjectKeyFingerprintResolver::fromArray([]);
    expect($resolver)->toBeInstanceOf(SubjectKeyFingerprintResolver::class);
});

test('SubjectKeyFingerprintResolver: resolves to subject key fingerprint', function () {
    $context = createMockIssuanceContext(subjectKeyFingerprint: 'subject-fp-abc');

    $resolver = new SubjectKeyFingerprintResolver;
    expect($resolver->resolve($context))->toBe('subject-fp-abc');
});

// --- CaKeyFingerprintResolver ---

test('CaKeyFingerprintResolver: type returns correct type', function () {
    expect(CaKeyFingerprintResolver::type())->toBe('ca-key-fingerprint');
});

test('CaKeyFingerprintResolver: toArray returns type only', function () {
    $resolver = new CaKeyFingerprintResolver;
    expect($resolver->toArray())->toBe(['type' => 'ca-key-fingerprint']);
});

test('CaKeyFingerprintResolver: fromArray creates instance', function () {
    $resolver = CaKeyFingerprintResolver::fromArray([]);
    expect($resolver)->toBeInstanceOf(CaKeyFingerprintResolver::class);
});

test('CaKeyFingerprintResolver: resolves to CA key fingerprint', function () {
    $context = createMockIssuanceContext(caKeyFingerprint: 'ca-key-fp-xyz');

    $resolver = new CaKeyFingerprintResolver;
    expect($resolver->resolve($context))->toBe('ca-key-fp-xyz');
});

// --- SubjectFieldResolver ---

test('SubjectFieldResolver: type returns correct type', function () {
    expect(SubjectFieldResolver::type())->toBe('subject-field');
});

test('SubjectFieldResolver: toArray includes field', function () {
    $resolver = new SubjectFieldResolver('CN');
    expect($resolver->toArray())->toBe([
        'type' => 'subject-field',
        'field' => 'CN',
    ]);
});

test('SubjectFieldResolver: fromArray round-trip', function () {
    $resolver = SubjectFieldResolver::fromArray(['field' => 'O']);
    expect($resolver->field)->toBe('O');
});

test('SubjectFieldResolver: fromArray requires field', function () {
    SubjectFieldResolver::fromArray([]);
})->throws(InvalidArgumentException::class, 'requires "field"');

test('SubjectFieldResolver: resolves to subject field value', function () {
    $context = createMockIssuanceContext();

    $resolver = new SubjectFieldResolver('CN');
    expect($resolver->resolve($context))->toBe('Test Subject');
});

test('SubjectFieldResolver: resolves to empty string when field not found', function () {
    $context = createMockIssuanceContext();

    $resolver = new SubjectFieldResolver('L');
    expect($resolver->resolve($context))->toBe('');
});

// --- TemplateStringResolver ---

test('TemplateStringResolver: type returns correct type', function () {
    expect(TemplateStringResolver::type())->toBe('template');
});

test('TemplateStringResolver: toArray includes template', function () {
    $resolver = new TemplateStringResolver('Hello {subject-cn}');
    expect($resolver->toArray())->toBe([
        'type' => 'template',
        'template' => 'Hello {subject-cn}',
    ]);
});

test('TemplateStringResolver: fromArray round-trip', function () {
    $resolver = TemplateStringResolver::fromArray(['template' => 'tpl-{serial}']);
    expect($resolver->template)->toBe('tpl-{serial}');
});

test('TemplateStringResolver: fromArray requires template', function () {
    TemplateStringResolver::fromArray([]);
})->throws(InvalidArgumentException::class, 'requires "template"');

test('TemplateStringResolver: resolves template variables', function () {
    $context = createMockIssuanceContext();

    $resolver = new TemplateStringResolver('CN={subject-cn}, Serial={serial}');
    $result = $resolver->resolve($context);

    expect($result)->toContain('CN=Test Subject')
        ->and($result)->toContain('Serial=');
});

// --- RelativeDateResolver ---

test('RelativeDateResolver: type returns correct type', function () {
    expect(RelativeDateResolver::type())->toBe('relative-date');
});

test('RelativeDateResolver: toArray includes base and optionally offset', function () {
    $resolver = new RelativeDateResolver(base: 'not-before');
    $array = $resolver->toArray();

    expect($array['type'])->toBe('relative-date')
        ->and($array['base'])->toBe('not-before')
        ->and($array)->not->toHaveKey('offset');
});

test('RelativeDateResolver: toArray with offset', function () {
    $resolver = new RelativeDateResolver(base: 'not-after', offset: '-P30D');
    $array = $resolver->toArray();

    expect($array['offset'])->toBe('-P30D');
});

test('RelativeDateResolver: fromArray round-trip', function () {
    $resolver = RelativeDateResolver::fromArray(['base' => 'not-after', 'offset' => 'P1Y']);

    expect($resolver->base)->toBe('not-after')
        ->and($resolver->offset)->toBe('P1Y');
});

test('RelativeDateResolver: resolves not-before base', function () {
    $notBefore = new DateTimeImmutable('2024-01-01');
    $notAfter = new DateTimeImmutable('2025-01-01');
    $context = createMockIssuanceContext(notBefore: $notBefore, notAfter: $notAfter);

    $resolver = new RelativeDateResolver(base: 'not-before');
    $result = $resolver->resolve($context);

    expect($result->format('Y-m-d'))->toBe('2024-01-01');
});

test('RelativeDateResolver: resolves not-after base', function () {
    $notBefore = new DateTimeImmutable('2024-01-01');
    $notAfter = new DateTimeImmutable('2025-01-01');
    $context = createMockIssuanceContext(notBefore: $notBefore, notAfter: $notAfter);

    $resolver = new RelativeDateResolver(base: 'not-after');
    $result = $resolver->resolve($context);

    expect($result->format('Y-m-d'))->toBe('2025-01-01');
});

test('RelativeDateResolver: resolves with positive offset', function () {
    $notBefore = new DateTimeImmutable('2024-01-01');
    $notAfter = new DateTimeImmutable('2025-01-01');
    $context = createMockIssuanceContext(notBefore: $notBefore, notAfter: $notAfter);

    $resolver = new RelativeDateResolver(base: 'not-before', offset: 'P30D');
    $result = $resolver->resolve($context);

    expect($result->format('Y-m-d'))->toBe('2024-01-31');
});

test('RelativeDateResolver: resolves with negative offset', function () {
    $notBefore = new DateTimeImmutable('2024-01-01');
    $notAfter = new DateTimeImmutable('2025-01-01');
    $context = createMockIssuanceContext(notBefore: $notBefore, notAfter: $notAfter);

    $resolver = new RelativeDateResolver(base: 'not-after', offset: '-P30D');
    $result = $resolver->resolve($context);

    expect($result->format('Y-m-d'))->toBe('2024-12-02');
});

test('RelativeDateResolver: resolves now base', function () {
    $context = createMockIssuanceContext();

    $resolver = new RelativeDateResolver(base: 'now');
    $result = $resolver->resolve($context);

    expect($result->format('Y-m-d'))->toBe((new DateTimeImmutable)->format('Y-m-d'));
});

test('RelativeDateResolver: throws for unknown base', function () {
    $context = createMockIssuanceContext();

    $resolver = new RelativeDateResolver(base: 'unknown');
    $resolver->resolve($context);
})->throws(InvalidArgumentException::class, 'Unknown date base');

// --- InputResolver ---

test('InputResolver: type returns correct type', function () {
    expect(InputResolver::type())->toBe('input');
});

test('InputResolver: toArray includes alias, label, and optional default', function () {
    $resolver = new InputResolver(alias: 'my-input', label: 'My Input', default: 'default-val');
    $array = $resolver->toArray();

    expect($array)->toBe([
        'type' => 'input',
        'alias' => 'my-input',
        'label' => 'My Input',
        'default' => 'default-val',
    ]);
});

test('InputResolver: toArray omits null default', function () {
    $resolver = new InputResolver(alias: 'my-input', label: 'My Input');
    $array = $resolver->toArray();

    expect($array)->not->toHaveKey('default');
});

test('InputResolver: fromArray round-trip', function () {
    $resolver = InputResolver::fromArray(['alias' => 'test-alias', 'label' => 'Test Label', 'default' => 'def']);

    expect($resolver->alias)->toBe('test-alias')
        ->and($resolver->label)->toBe('Test Label')
        ->and($resolver->default)->toBe('def');
});

test('InputResolver: fromArray uses alias as label fallback', function () {
    $resolver = InputResolver::fromArray(['alias' => 'my-alias']);

    expect($resolver->label)->toBe('my-alias');
});

test('InputResolver: fromArray uses label as alias fallback', function () {
    $resolver = InputResolver::fromArray(['label' => 'my-label']);

    expect($resolver->alias)->toBe('my-label');
});

test('InputResolver: fromArray requires alias or label', function () {
    InputResolver::fromArray([]);
})->throws(InvalidArgumentException::class, '"alias" or "label" is required');

test('InputResolver: fromArray validates alias format', function () {
    InputResolver::fromArray(['alias' => 'INVALID ALIAS!']);
})->throws(InvalidArgumentException::class, 'must contain only lowercase');

test('InputResolver: resolves from input provider', function () {
    $context = createMockIssuanceContext(inputAnswers: ['my-input' => 'provided-value']);

    $resolver = new InputResolver(alias: 'my-input', label: 'My Input');
    expect($resolver->resolve($context))->toBe('provided-value');
});

test('InputResolver: uses default when not provided', function () {
    $context = createMockIssuanceContext();

    $resolver = new InputResolver(alias: 'missing-input', label: 'Missing', default: 'fallback');
    expect($resolver->resolve($context))->toBe('fallback');
});

// --- InputMultipleResolver ---

test('InputMultipleResolver: type returns correct type', function () {
    expect(InputMultipleResolver::type())->toBe('input-multiple');
});

test('InputMultipleResolver: toArray includes alias and label', function () {
    $resolver = new InputMultipleResolver(alias: 'dns-names', label: 'DNS Names');

    expect($resolver->toArray())->toBe([
        'type' => 'input-multiple',
        'alias' => 'dns-names',
        'label' => 'DNS Names',
    ]);
});

test('InputMultipleResolver: fromArray round-trip', function () {
    $resolver = InputMultipleResolver::fromArray(['alias' => 'ips', 'label' => 'IP Addresses']);

    expect($resolver->alias)->toBe('ips')
        ->and($resolver->label)->toBe('IP Addresses');
});

test('InputMultipleResolver: fromArray requires alias or label', function () {
    InputMultipleResolver::fromArray([]);
})->throws(InvalidArgumentException::class, '"alias" or "label" is required');

test('InputMultipleResolver: fromArray validates alias format', function () {
    InputMultipleResolver::fromArray(['alias' => 'BAD FORMAT']);
})->throws(InvalidArgumentException::class, 'must contain only lowercase');

test('InputMultipleResolver: resolves from input provider', function () {
    $context = createMockIssuanceContext(inputAnswers: ['dns-names' => ['example.com', 'www.example.com']]);

    $resolver = new InputMultipleResolver(alias: 'dns-names', label: 'DNS Names');
    expect($resolver->resolve($context))->toBe(['example.com', 'www.example.com']);
});

// --- PresetInputProvider ---

test('PresetInputProvider: ask returns preset answer', function () {
    $provider = new PresetInputProvider(['key' => 'value']);

    expect($provider->ask('key', 'Key'))->toBe('value');
});

test('PresetInputProvider: ask returns default when no preset', function () {
    $provider = new PresetInputProvider([]);

    expect($provider->ask('missing', 'Missing', 'default'))->toBe('default');
});

test('PresetInputProvider: ask throws when no preset and no default', function () {
    $provider = new PresetInputProvider([]);
    $provider->ask('missing', 'Missing');
})->throws(RuntimeException::class, 'No preset answer');

test('PresetInputProvider: ask converts array answer to comma-separated string', function () {
    $provider = new PresetInputProvider(['key' => ['a', 'b', 'c']]);

    expect($provider->ask('key', 'Key'))->toBe('a,b,c');
});

test('PresetInputProvider: askMultiple returns array answer', function () {
    $provider = new PresetInputProvider(['names' => ['Alice', 'Bob']]);

    expect($provider->askMultiple('names', 'Names'))->toBe(['Alice', 'Bob']);
});

test('PresetInputProvider: askMultiple splits string answer', function () {
    $provider = new PresetInputProvider(['names' => 'Alice,Bob']);

    expect($provider->askMultiple('names', 'Names'))->toBe(['Alice', 'Bob']);
});

test('PresetInputProvider: askMultiple throws when no preset', function () {
    $provider = new PresetInputProvider([]);
    $provider->askMultiple('missing', 'Missing');
})->throws(RuntimeException::class, 'No preset answer');

// --- ExtensionValueResolverFactory ---

test('ExtensionValueResolverFactory: fromMixed with string creates LiteralResolver', function () {
    $result = ExtensionValueResolverFactory::fromMixed('hello');

    expect($result)->toBeInstanceOf(LiteralResolver::class)
        ->and($result->value)->toBe('hello');
});

test('ExtensionValueResolverFactory: fromMixed with typed array creates resolver', function () {
    $result = ExtensionValueResolverFactory::fromMixed(['type' => 'literal', 'value' => 42]);

    expect($result)->toBeInstanceOf(LiteralResolver::class);
});

test('ExtensionValueResolverFactory: fromMixed with plain array maps elements', function () {
    $result = ExtensionValueResolverFactory::fromMixed(['a', 'b']);

    expect($result)->toBeArray()->toHaveCount(2);
});

test('ExtensionValueResolverFactory: fromArray throws for unknown type', function () {
    ExtensionValueResolverFactory::fromArray(['type' => 'nonexistent']);
})->throws(InvalidArgumentException::class, 'Unknown resolver type');

test('ExtensionValueResolverFactory: fromArray throws when type is missing', function () {
    ExtensionValueResolverFactory::fromArray([]);
})->throws(InvalidArgumentException::class, 'must have a "type" field');

test('ExtensionValueResolverFactory: toMixed with resolver returns toArray', function () {
    $resolver = new LiteralResolver('test');
    $result = ExtensionValueResolverFactory::toMixed($resolver);

    expect($result)->toBe('test');
});

test('ExtensionValueResolverFactory: toMixed with array maps elements', function () {
    $result = ExtensionValueResolverFactory::toMixed([new LiteralResolver('a'), 'plain']);

    expect($result)->toBe(['a', 'plain']);
});

test('ExtensionValueResolverFactory: toMixed with plain value returns as-is', function () {
    expect(ExtensionValueResolverFactory::toMixed(42))->toBe(42);
});

test('ExtensionValueResolverFactory: resolveField with resolver', function () {
    $context = createMockIssuanceContext();
    $resolver = new LiteralResolver('resolved-value');

    expect(ExtensionValueResolverFactory::resolveField($resolver, $context))->toBe('resolved-value');
});

test('ExtensionValueResolverFactory: resolveField with array of resolvers', function () {
    $context = createMockIssuanceContext();
    $resolvers = [new LiteralResolver('a'), new LiteralResolver('b')];

    $result = ExtensionValueResolverFactory::resolveField($resolvers, $context);

    expect($result)->toBe(['a', 'b']);
});

test('ExtensionValueResolverFactory: resolveField with plain value', function () {
    $context = createMockIssuanceContext();

    expect(ExtensionValueResolverFactory::resolveField('plain', $context))->toBe('plain');
});

test('ExtensionValueResolverFactory: collectInputResolvers finds InputResolver', function () {
    $input = new InputResolver(alias: 'test', label: 'Test');
    $result = ExtensionValueResolverFactory::collectInputResolvers($input);

    expect($result)->toHaveKey('test')
        ->and($result['test'])->toBe($input);
});

test('ExtensionValueResolverFactory: collectInputResolvers finds nested InputMultipleResolver', function () {
    $input = new InputMultipleResolver(alias: 'dns', label: 'DNS');
    $result = ExtensionValueResolverFactory::collectInputResolvers([new LiteralResolver('x'), $input]);

    expect($result)->toHaveKey('dns');
});

test('ExtensionValueResolverFactory: collectInputResolvers returns empty for non-input values', function () {
    $result = ExtensionValueResolverFactory::collectInputResolvers(new LiteralResolver('x'));

    expect($result)->toBeEmpty();
});

// --- IssuanceContext::getVariable ---

test('IssuanceContext: getVariable returns subject-cn', function () {
    $context = createMockIssuanceContext();

    expect($context->getVariable('subject-cn'))->toBe('Test Subject');
});

test('IssuanceContext: getVariable returns serial', function () {
    $context = createMockIssuanceContext();

    expect($context->getVariable('serial'))->toBe($context->serialNumber);
});

test('IssuanceContext: getVariable returns sequence', function () {
    $context = createMockIssuanceContext();

    expect($context->getVariable('sequence'))->toBe('1');
});

test('IssuanceContext: getVariable throws for unknown variable', function () {
    $context = createMockIssuanceContext();

    $context->getVariable('unknown-var');
})->throws(InvalidArgumentException::class, 'Unknown template variable');

// --- Helper function to create a mock IssuanceContext ---

function createMockIssuanceContext(
    string $subjectKeyFingerprint = 'subject-fp-default',
    string $caKeyFingerprint = 'ca-key-fp-default',
    string $caCertFingerprint = 'ca-cert-fp-default',
    ?DateTimeImmutable $notBefore = null,
    ?DateTimeImmutable $notAfter = null,
    array $inputAnswers = [],
): IssuanceContext {
    $notBefore ??= new DateTimeImmutable('2024-01-01');
    $notAfter ??= new DateTimeImmutable('2025-01-01');

    // Create minimal mock objects using real classes with reflection to bypass immutability
    $subjectKey = new KeyEntity;
    $ref = new ReflectionProperty(KeyEntity::class, 'fingerprint');
    $ref->setValue($subjectKey, $subjectKeyFingerprint);

    $caKey = new KeyEntity;
    $ref->setValue($caKey, $caKeyFingerprint);

    $caCert = new CACertificateEntity;
    $caCertRef = new ReflectionProperty(CACertificateEntity::class, 'fingerprint');
    $caCertRef->setValue($caCert, $caCertFingerprint);

    $caCertSubjectRef = new ReflectionProperty(CACertificateEntity::class, 'subject');
    $caCertSubjectRef->setValue($caCert, new CertificateSubject([
        new CommonName('Test CA'),
    ]));

    $subject = new CertificateSubject([
        new CommonName('Test Subject'),
        new Organization('Test Org'),
    ]);

    $validity = new CertificateValidity($notBefore, $notAfter);

    $inputProvider = new PresetInputProvider($inputAnswers);

    return new IssuanceContext(
        subjectKey: $subjectKey,
        caCertificate: $caCert,
        caKey: $caKey,
        subject: $subject,
        validity: $validity,
        sequence: 1,
        serialNumber: 'serial-001',
        inputProvider: $inputProvider,
    );
}
