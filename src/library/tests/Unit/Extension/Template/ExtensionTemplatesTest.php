<?php

declare(strict_types=1);

use KDuma\PhpCA\Record\Extension\Template\Templates\BasicConstraintsExtensionTemplate;
use KDuma\PhpCA\Record\Extension\Template\Templates\KeyUsageExtensionTemplate;
use KDuma\PhpCA\Record\Extension\Template\Templates\ExtKeyUsageExtensionTemplate;
use KDuma\PhpCA\Record\Extension\Template\Templates\SubjectAltNameExtensionTemplate;
use KDuma\PhpCA\Record\Extension\Template\Templates\CrlDistributionPointsExtensionTemplate;
use KDuma\PhpCA\Record\Extension\Template\Templates\AuthorityInfoAccessExtensionTemplate;
use KDuma\PhpCA\Record\Extension\Template\Templates\SubjectKeyIdentifierExtensionTemplate;
use KDuma\PhpCA\Record\Extension\Template\Templates\AuthorityKeyIdentifierExtensionTemplate;
use KDuma\PhpCA\Record\Extension\Template\Templates\PrivateKeyUsagePeriodExtensionTemplate;
use KDuma\PhpCA\Record\Extension\Template\Templates\NetscapeCommentExtensionTemplate;
use KDuma\PhpCA\Record\Extension\Resolver\LiteralResolver;
use KDuma\PhpCA\Record\Extension\Resolver\SubjectKeyFingerprintResolver;
use KDuma\PhpCA\Record\Extension\Resolver\CaKeyFingerprintResolver;
use KDuma\PhpCA\Record\Extension\Resolver\RelativeDateResolver;

// --- BasicConstraintsExtensionTemplate ---

test('BasicConstraintsExtensionTemplate: name returns correct name', function () {
    expect(BasicConstraintsExtensionTemplate::name())->toBe('basic-constraints');
});

test('BasicConstraintsExtensionTemplate: default values', function () {
    $template = new BasicConstraintsExtensionTemplate();

    expect($template->ca)->toBeFalse()
        ->and($template->pathLenConstraint)->toBeNull()
        ->and($template->critical)->toBeTrue()
        ->and($template->isCritical())->toBeTrue();
});

test('BasicConstraintsExtensionTemplate: toArray/fromArray round-trip with CA=true', function () {
    $template = new BasicConstraintsExtensionTemplate(ca: true, pathLenConstraint: 2, critical: true);
    $array = $template->toArray();

    expect($array)->toHaveKey('name', 'basic-constraints')
        ->and($array)->toHaveKey('ca', true)
        ->and($array)->toHaveKey('path_len_constraint', 2)
        ->and($array)->toHaveKey('critical', true);

    $restored = BasicConstraintsExtensionTemplate::fromArray($array);

    expect($restored->ca)->toBe($template->ca)
        ->and($restored->pathLenConstraint)->toBe($template->pathLenConstraint)
        ->and($restored->isCritical())->toBe($template->isCritical());
});

test('BasicConstraintsExtensionTemplate: toArray/fromArray round-trip with CA=false', function () {
    $template = new BasicConstraintsExtensionTemplate(ca: false, critical: false);
    $array = $template->toArray();
    $restored = BasicConstraintsExtensionTemplate::fromArray($array);

    expect($restored->ca)->toBeFalse()
        ->and($restored->pathLenConstraint)->toBeNull()
        ->and($restored->isCritical())->toBeFalse();
});

test('BasicConstraintsExtensionTemplate: fromArray with defaults', function () {
    $template = BasicConstraintsExtensionTemplate::fromArray([]);

    expect($template->ca)->toBeFalse()
        ->and($template->pathLenConstraint)->toBeNull()
        ->and($template->isCritical())->toBeTrue();
});

test('BasicConstraintsExtensionTemplate: fromArray validates ca is boolean', function () {
    BasicConstraintsExtensionTemplate::fromArray(['ca' => 'yes']);
})->throws(InvalidArgumentException::class, '"ca" must be a boolean');

test('BasicConstraintsExtensionTemplate: fromArray validates path_len_constraint is integer', function () {
    BasicConstraintsExtensionTemplate::fromArray(['ca' => true, 'path_len_constraint' => 'abc']);
})->throws(InvalidArgumentException::class, '"path_len_constraint" must be an integer');

test('BasicConstraintsExtensionTemplate: fromArray validates path_len_constraint requires ca=true', function () {
    BasicConstraintsExtensionTemplate::fromArray(['path_len_constraint' => 3]);
})->throws(InvalidArgumentException::class, '"path_len_constraint" requires "ca" to be true');

test('BasicConstraintsExtensionTemplate: fromArray validates critical is boolean', function () {
    BasicConstraintsExtensionTemplate::fromArray(['critical' => 'true']);
})->throws(InvalidArgumentException::class, '"critical" must be a boolean');

// --- KeyUsageExtensionTemplate ---

test('KeyUsageExtensionTemplate: name returns correct name', function () {
    expect(KeyUsageExtensionTemplate::name())->toBe('key-usage');
});

test('KeyUsageExtensionTemplate: default values', function () {
    $template = new KeyUsageExtensionTemplate();

    expect($template->digitalSignature)->toBeFalse()
        ->and($template->nonRepudiation)->toBeFalse()
        ->and($template->keyEncipherment)->toBeFalse()
        ->and($template->dataEncipherment)->toBeFalse()
        ->and($template->keyAgreement)->toBeFalse()
        ->and($template->keyCertSign)->toBeFalse()
        ->and($template->cRLSign)->toBeFalse()
        ->and($template->encipherOnly)->toBeFalse()
        ->and($template->decipherOnly)->toBeFalse()
        ->and($template->isCritical())->toBeTrue();
});

test('KeyUsageExtensionTemplate: toArray/fromArray round-trip', function () {
    $template = new KeyUsageExtensionTemplate(
        digitalSignature: true,
        keyCertSign: true,
        cRLSign: true,
        critical: true,
    );

    $array = $template->toArray();

    expect($array['digital_signature'])->toBeTrue()
        ->and($array['key_cert_sign'])->toBeTrue()
        ->and($array['crl_sign'])->toBeTrue()
        ->and($array['name'])->toBe('key-usage');

    $restored = KeyUsageExtensionTemplate::fromArray($array);

    expect($restored->digitalSignature)->toBeTrue()
        ->and($restored->keyCertSign)->toBeTrue()
        ->and($restored->cRLSign)->toBeTrue()
        ->and($restored->nonRepudiation)->toBeFalse()
        ->and($restored->isCritical())->toBeTrue();
});

test('KeyUsageExtensionTemplate: fromArray validates boolean fields', function () {
    KeyUsageExtensionTemplate::fromArray(['digital_signature' => 'yes']);
})->throws(InvalidArgumentException::class, '"digital_signature" must be a boolean');

// --- ExtKeyUsageExtensionTemplate ---

test('ExtKeyUsageExtensionTemplate: name returns correct name', function () {
    expect(ExtKeyUsageExtensionTemplate::name())->toBe('ext-key-usage');
});

test('ExtKeyUsageExtensionTemplate: toArray/fromArray round-trip', function () {
    $template = new ExtKeyUsageExtensionTemplate(
        usages: ['serverAuth', 'clientAuth'],
        critical: false,
    );

    $array = $template->toArray();

    expect($array['usages'])->toBe(['serverAuth', 'clientAuth'])
        ->and($array['name'])->toBe('ext-key-usage');

    $restored = ExtKeyUsageExtensionTemplate::fromArray($array);

    expect($restored->usages)->toBe(['serverAuth', 'clientAuth'])
        ->and($restored->isCritical())->toBeFalse();
});

test('ExtKeyUsageExtensionTemplate: fromArray validates unknown usage', function () {
    ExtKeyUsageExtensionTemplate::fromArray(['usages' => ['unknownUsage']]);
})->throws(InvalidArgumentException::class, 'unknown usage');

test('ExtKeyUsageExtensionTemplate: fromArray validates usages is array', function () {
    ExtKeyUsageExtensionTemplate::fromArray(['usages' => 'serverAuth']);
})->throws(InvalidArgumentException::class, '"usages" must be an array');

test('ExtKeyUsageExtensionTemplate: fromArray validates each usage is string', function () {
    ExtKeyUsageExtensionTemplate::fromArray(['usages' => [123]]);
})->throws(InvalidArgumentException::class, 'each usage must be a string');

// --- SubjectAltNameExtensionTemplate ---

test('SubjectAltNameExtensionTemplate: name returns correct name', function () {
    expect(SubjectAltNameExtensionTemplate::name())->toBe('subject-alt-name');
});

test('SubjectAltNameExtensionTemplate: toArray/fromArray round-trip with literal arrays', function () {
    $template = new SubjectAltNameExtensionTemplate(
        dnsNames: ['example.com', 'www.example.com'],
        ipAddresses: ['192.168.1.1'],
        emails: ['admin@example.com'],
        uris: ['https://example.com'],
        critical: false,
    );

    $array = $template->toArray();

    expect($array['name'])->toBe('subject-alt-name')
        ->and($array['critical'])->toBeFalse();

    $restored = SubjectAltNameExtensionTemplate::fromArray($array);

    expect($restored->isCritical())->toBeFalse();
});

test('SubjectAltNameExtensionTemplate: fromArray with empty data', function () {
    $template = SubjectAltNameExtensionTemplate::fromArray([]);

    expect($template->isCritical())->toBeFalse();
});

// --- CrlDistributionPointsExtensionTemplate ---

test('CrlDistributionPointsExtensionTemplate: name returns correct name', function () {
    expect(CrlDistributionPointsExtensionTemplate::name())->toBe('crl-distribution-points');
});

test('CrlDistributionPointsExtensionTemplate: toArray/fromArray round-trip', function () {
    $template = new CrlDistributionPointsExtensionTemplate(
        uris: ['http://crl.example.com/ca.crl'],
        critical: false,
    );

    $array = $template->toArray();

    expect($array['name'])->toBe('crl-distribution-points');

    $restored = CrlDistributionPointsExtensionTemplate::fromArray($array);

    expect($restored->isCritical())->toBeFalse();
});

test('CrlDistributionPointsExtensionTemplate: fromArray validates uris is array', function () {
    CrlDistributionPointsExtensionTemplate::fromArray(['uris' => 'not-an-array']);
})->throws(InvalidArgumentException::class, '"uris" must be an array');

// --- AuthorityInfoAccessExtensionTemplate ---

test('AuthorityInfoAccessExtensionTemplate: name returns correct name', function () {
    expect(AuthorityInfoAccessExtensionTemplate::name())->toBe('authority-info-access');
});

test('AuthorityInfoAccessExtensionTemplate: toArray/fromArray round-trip', function () {
    $template = new AuthorityInfoAccessExtensionTemplate(
        ocspUris: ['http://ocsp.example.com'],
        caIssuersUris: ['http://ca.example.com/ca.crt'],
        critical: false,
    );

    $array = $template->toArray();

    expect($array['name'])->toBe('authority-info-access');

    $restored = AuthorityInfoAccessExtensionTemplate::fromArray($array);

    expect($restored->isCritical())->toBeFalse();
});

test('AuthorityInfoAccessExtensionTemplate: fromArray validates array fields', function () {
    AuthorityInfoAccessExtensionTemplate::fromArray(['ocsp_uris' => 'not-array']);
})->throws(InvalidArgumentException::class, '"ocsp_uris" must be an array');

// --- SubjectKeyIdentifierExtensionTemplate ---

test('SubjectKeyIdentifierExtensionTemplate: name returns correct name', function () {
    expect(SubjectKeyIdentifierExtensionTemplate::name())->toBe('subject-key-identifier');
});

test('SubjectKeyIdentifierExtensionTemplate: toArray/fromArray round-trip with default resolver', function () {
    $template = new SubjectKeyIdentifierExtensionTemplate(
        keyIdentifier: new SubjectKeyFingerprintResolver(),
    );

    $array = $template->toArray();

    expect($array['name'])->toBe('subject-key-identifier')
        ->and($array['key_identifier'])->toBe(['type' => 'subject-key-fingerprint']);

    $restored = SubjectKeyIdentifierExtensionTemplate::fromArray($array);

    expect($restored->isCritical())->toBeFalse();
});

test('SubjectKeyIdentifierExtensionTemplate: fromArray uses SubjectKeyFingerprintResolver as default', function () {
    $template = SubjectKeyIdentifierExtensionTemplate::fromArray([]);

    $array = $template->toArray();
    expect($array['key_identifier'])->toBe(['type' => 'subject-key-fingerprint']);
});

// --- AuthorityKeyIdentifierExtensionTemplate ---

test('AuthorityKeyIdentifierExtensionTemplate: name returns correct name', function () {
    expect(AuthorityKeyIdentifierExtensionTemplate::name())->toBe('authority-key-identifier');
});

test('AuthorityKeyIdentifierExtensionTemplate: toArray/fromArray round-trip with default resolver', function () {
    $template = new AuthorityKeyIdentifierExtensionTemplate(
        keyIdentifier: new CaKeyFingerprintResolver(),
    );

    $array = $template->toArray();

    expect($array['name'])->toBe('authority-key-identifier')
        ->and($array['key_identifier'])->toBe(['type' => 'ca-key-fingerprint']);

    $restored = AuthorityKeyIdentifierExtensionTemplate::fromArray($array);

    expect($restored->isCritical())->toBeFalse();
});

test('AuthorityKeyIdentifierExtensionTemplate: fromArray uses CaKeyFingerprintResolver as default', function () {
    $template = AuthorityKeyIdentifierExtensionTemplate::fromArray([]);

    $array = $template->toArray();
    expect($array['key_identifier'])->toBe(['type' => 'ca-key-fingerprint']);
});

// --- NetscapeCommentExtensionTemplate ---

test('NetscapeCommentExtensionTemplate: name returns correct name', function () {
    expect(NetscapeCommentExtensionTemplate::name())->toBe('netscape-comment');
});

test('NetscapeCommentExtensionTemplate: toArray/fromArray round-trip with literal string', function () {
    $template = new NetscapeCommentExtensionTemplate(
        comment: new LiteralResolver('Generated by Test CA'),
    );

    $array = $template->toArray();

    expect($array['name'])->toBe('netscape-comment')
        ->and($array['comment'])->toBe('Generated by Test CA');

    $restored = NetscapeCommentExtensionTemplate::fromArray($array);

    expect($restored->isCritical())->toBeFalse();
});

test('NetscapeCommentExtensionTemplate: fromArray uses empty LiteralResolver as default', function () {
    $template = NetscapeCommentExtensionTemplate::fromArray([]);

    $array = $template->toArray();
    expect($array['comment'])->toBe('');
});

// --- PrivateKeyUsagePeriodExtensionTemplate ---

test('PrivateKeyUsagePeriodExtensionTemplate: name returns correct name', function () {
    expect(PrivateKeyUsagePeriodExtensionTemplate::name())->toBe('private-key-usage-period');
});

test('PrivateKeyUsagePeriodExtensionTemplate: toArray/fromArray round-trip', function () {
    $notBefore = new RelativeDateResolver(base: 'not-before');
    $notAfter = new RelativeDateResolver(base: 'not-after');

    $template = new PrivateKeyUsagePeriodExtensionTemplate(
        notBefore: $notBefore,
        notAfter: $notAfter,
    );

    $array = $template->toArray();

    expect($array['name'])->toBe('private-key-usage-period')
        ->and($array)->toHaveKey('not_before')
        ->and($array)->toHaveKey('not_after');

    $restored = PrivateKeyUsagePeriodExtensionTemplate::fromArray($array);

    expect($restored->isCritical())->toBeFalse();
});

test('PrivateKeyUsagePeriodExtensionTemplate: fromArray requires at least one date', function () {
    PrivateKeyUsagePeriodExtensionTemplate::fromArray([]);
})->throws(InvalidArgumentException::class, 'at least one of "not_before" or "not_after" is required');

test('PrivateKeyUsagePeriodExtensionTemplate: toArray filters null values', function () {
    $template = new PrivateKeyUsagePeriodExtensionTemplate(
        notBefore: new RelativeDateResolver(base: 'not-before'),
    );

    $array = $template->toArray();

    expect($array)->not->toHaveKey('not_after');
});

// --- getRequiredInputs ---

test('getRequiredInputs returns empty array for templates without input resolvers', function () {
    $template = new BasicConstraintsExtensionTemplate(ca: true);

    expect($template->getRequiredInputs())->toBeEmpty();
});
