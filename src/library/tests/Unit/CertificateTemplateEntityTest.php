<?php

declare(strict_types=1);

use KDuma\PhpCA\CertificationAuthority;
use KDuma\PhpCA\Entity\CertificateTemplateBuilder;
use KDuma\PhpCA\Entity\CertificateTemplateEntity;
use KDuma\PhpCA\Record\Extension\ExtensionRegistry;
use KDuma\PhpCA\Record\Extension\Template\Templates\BasicConstraintsExtensionTemplate;
use KDuma\PhpCA\Record\Extension\Template\Templates\ExtKeyUsageExtensionTemplate;
use KDuma\PhpCA\Record\Extension\Template\Templates\KeyUsageExtensionTemplate;
use KDuma\SimpleDAL\Adapter\Flysystem\FlysystemAdapter;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;

// --- CertificateTemplateEntity property tests ---

test('CertificateTemplateEntity: displayName can be set', function () {
    $entity = new CertificateTemplateEntity;
    $entity->displayName = 'My Template';

    expect($entity->displayName)->toBe('My Template');
});

test('CertificateTemplateEntity: parentId defaults to null', function () {
    $entity = new CertificateTemplateEntity;

    expect($entity->parentId)->toBeNull();
});

test('CertificateTemplateEntity: validity defaults to null', function () {
    $entity = new CertificateTemplateEntity;

    expect($entity->validity)->toBeNull();
});

test('CertificateTemplateEntity: extensions defaults to empty array', function () {
    $entity = new CertificateTemplateEntity;

    expect($entity->extensions)->toBe([]);
});

test('CertificateTemplateEntity: persisted is false for new entity', function () {
    $entity = new CertificateTemplateEntity;

    expect($entity->persisted)->toBeFalse();
});

test('CertificateTemplateEntity: id can be set before persistence', function () {
    $entity = new CertificateTemplateEntity;
    $entity->id = 'my-template';

    expect($entity->id)->toBe('my-template');
});

// --- getEffectiveExtensions ---

test('CertificateTemplateEntity: getEffectiveExtensions returns own extensions with no parent', function () {
    $entity = new CertificateTemplateEntity;
    $entity->extensions = [
        new BasicConstraintsExtensionTemplate(ca: false),
        new KeyUsageExtensionTemplate(digitalSignature: true),
    ];

    $effective = $entity->getEffectiveExtensions();

    expect($effective)->toHaveCount(2);
});

test('CertificateTemplateEntity: getEffectiveExtensions returns own extensions with no collection', function () {
    $entity = new CertificateTemplateEntity;
    $entity->parentId = 'some-parent';
    $entity->extensions = [
        new BasicConstraintsExtensionTemplate(ca: false),
    ];

    // Without a collection, parent cannot be resolved
    $effective = $entity->getEffectiveExtensions();

    expect($effective)->toHaveCount(1);
});

test('CertificateTemplateEntity: getEffectiveExtensions returns empty for entity with no extensions', function () {
    $entity = new CertificateTemplateEntity;

    expect($entity->getEffectiveExtensions())->toBeEmpty();
});

// --- getEffectiveValidity ---

test('CertificateTemplateEntity: getEffectiveValidity returns own validity', function () {
    $entity = new CertificateTemplateEntity;
    $entity->validity = new DateInterval('P1Y');

    $effective = $entity->getEffectiveValidity();

    expect($effective)->toBeInstanceOf(DateInterval::class)
        ->and($effective->y)->toBe(1);
});

test('CertificateTemplateEntity: getEffectiveValidity returns null when no validity and no parent', function () {
    $entity = new CertificateTemplateEntity;

    expect($entity->getEffectiveValidity())->toBeNull();
});

test('CertificateTemplateEntity: getEffectiveValidity returns null when parent not in collection', function () {
    $entity = new CertificateTemplateEntity;
    $entity->parentId = 'non-existent-parent';

    expect($entity->getEffectiveValidity())->toBeNull();
});

// --- Template inheritance via integration test ---

test('template inheritance: child overrides parent extensions by name', function () {
    $ca = createTempCaForTemplateTests();

    // Create parent template
    $parent = $ca->templates->getBuilder('parent-tpl')
        ->displayName('Parent Template')
        ->validity(new DateInterval('P2Y'))
        ->addExtension(new BasicConstraintsExtensionTemplate(ca: false, critical: true))
        ->addExtension(new KeyUsageExtensionTemplate(digitalSignature: true))
        ->save();

    // Create child template overriding basic-constraints
    $child = $ca->templates->getBuilder('child-tpl')
        ->displayName('Child Template')
        ->parent($parent)
        ->addExtension(new BasicConstraintsExtensionTemplate(ca: true, pathLenConstraint: 1, critical: true))
        ->save();

    $effective = $child->getEffectiveExtensions($ca->templates);

    expect($effective)->toHaveCount(2);

    // Find the basic-constraints extension - it should be the child's override
    $bcTemplate = null;
    $kuTemplate = null;
    foreach ($effective as $ext) {
        if ($ext::name() === 'basic-constraints') {
            $bcTemplate = $ext;
        }
        if ($ext::name() === 'key-usage') {
            $kuTemplate = $ext;
        }
    }

    expect($bcTemplate)->not->toBeNull()
        ->and($bcTemplate->ca)->toBeTrue()
        ->and($bcTemplate->pathLenConstraint)->toBe(1);

    // key-usage should be inherited from parent
    expect($kuTemplate)->not->toBeNull()
        ->and($kuTemplate->digitalSignature)->toBeTrue();
});

test('template inheritance: child inherits validity from parent', function () {
    $ca = createTempCaForTemplateTests();

    $parent = $ca->templates->getBuilder('parent-validity')
        ->displayName('Parent')
        ->validity(new DateInterval('P5Y'))
        ->save();

    $child = $ca->templates->getBuilder('child-validity')
        ->displayName('Child')
        ->parent($parent)
        ->save();

    $effective = $child->getEffectiveValidity($ca->templates);

    expect($effective)->toBeInstanceOf(DateInterval::class)
        ->and($effective->y)->toBe(5);
});

test('template inheritance: child validity overrides parent', function () {
    $ca = createTempCaForTemplateTests();

    $parent = $ca->templates->getBuilder('parent-override')
        ->displayName('Parent')
        ->validity(new DateInterval('P5Y'))
        ->save();

    $child = $ca->templates->getBuilder('child-override')
        ->displayName('Child')
        ->parent($parent)
        ->validity(new DateInterval('P1Y'))
        ->save();

    $effective = $child->getEffectiveValidity($ca->templates);

    expect($effective)->toBeInstanceOf(DateInterval::class)
        ->and($effective->y)->toBe(1);
});

test('template inheritance: child adds new extensions alongside parent', function () {
    $ca = createTempCaForTemplateTests();

    $parent = $ca->templates->getBuilder('parent-add')
        ->displayName('Parent')
        ->validity(new DateInterval('P2Y'))
        ->addExtension(new BasicConstraintsExtensionTemplate(ca: false))
        ->save();

    $child = $ca->templates->getBuilder('child-add')
        ->displayName('Child')
        ->parent($parent)
        ->addExtension(new ExtKeyUsageExtensionTemplate(usages: ['serverAuth']))
        ->save();

    $effective = $child->getEffectiveExtensions($ca->templates);

    expect($effective)->toHaveCount(2);

    $names = array_map(fn ($ext) => $ext::name(), $effective);
    expect($names)->toContain('basic-constraints')
        ->and($names)->toContain('ext-key-usage');
});

// --- CertificateTemplateBuilder tests ---

test('CertificateTemplateBuilder: creates and persists template', function () {
    $ca = createTempCaForTemplateTests();

    $template = $ca->templates->getBuilder('test-builder-tpl')
        ->displayName('Builder Test')
        ->validity(new DateInterval('P1Y'))
        ->addExtension(new BasicConstraintsExtensionTemplate(ca: false))
        ->save();

    expect($template)->toBeInstanceOf(CertificateTemplateEntity::class)
        ->and($template->persisted)->toBeTrue()
        ->and($template->id)->toBe('test-builder-tpl')
        ->and($template->displayName)->toBe('Builder Test')
        ->and($template->extensions)->toHaveCount(1);
});

test('CertificateTemplateBuilder: requires validity or parent', function () {
    $ca = createTempCaForTemplateTests();

    $ca->templates->getBuilder('no-validity-tpl')
        ->displayName('No Validity')
        ->save();
})->throws(LogicException::class, 'Template validity is required');

test('CertificateTemplateBuilder: allows no validity when parent is set', function () {
    $ca = createTempCaForTemplateTests();

    $parent = $ca->templates->getBuilder('parent-for-no-validity')
        ->displayName('Parent')
        ->validity(new DateInterval('P3Y'))
        ->save();

    $child = $ca->templates->getBuilder('child-no-validity')
        ->displayName('Child')
        ->parent($parent)
        ->save();

    expect($child->persisted)->toBeTrue()
        ->and($child->parentId)->toBe('parent-for-no-validity')
        ->and($child->validity)->toBeNull();
});

test('CertificateTemplateBuilder: parent accepts entity or string', function () {
    $ca = createTempCaForTemplateTests();

    $parent = $ca->templates->getBuilder('parent-entity-or-string')
        ->displayName('Parent')
        ->validity(new DateInterval('P2Y'))
        ->save();

    // Pass entity directly
    $child1 = $ca->templates->getBuilder('child-with-entity')
        ->displayName('Child 1')
        ->parent($parent)
        ->save();

    // Pass string ID
    $child2 = $ca->templates->getBuilder('child-with-string')
        ->displayName('Child 2')
        ->parent('parent-entity-or-string')
        ->save();

    expect($child1->parentId)->toBe('parent-entity-or-string')
        ->and($child2->parentId)->toBe('parent-entity-or-string');
});

test('CertificateTemplateBuilder: methods return self for chaining', function () {
    $ca = createTempCaForTemplateTests();
    $builder = $ca->templates->getBuilder('chaining-test');

    $r1 = $builder->displayName('Test');
    $r2 = $builder->parent('parent-id');
    $r3 = $builder->validity(new DateInterval('P1Y'));
    $r4 = $builder->addExtension(new BasicConstraintsExtensionTemplate);

    expect($r1)->toBe($builder)
        ->and($r2)->toBe($builder)
        ->and($r3)->toBe($builder)
        ->and($r4)->toBe($builder);
});

test('template can be found after creation', function () {
    $ca = createTempCaForTemplateTests();

    $template = $ca->templates->getBuilder('findable-tpl')
        ->displayName('Findable')
        ->validity(new DateInterval('P1Y'))
        ->save();

    $found = $ca->templates->find('findable-tpl');

    expect($found->id)->toBe('findable-tpl')
        ->and($found->displayName)->toBe('Findable')
        ->and($found->validity->y)->toBe(1);
});

test('CertificateTemplateEntityCollection returns builder', function () {
    $ca = createTempCaForTemplateTests();
    $builder = $ca->templates->getBuilder('new-tpl');

    expect($builder)->toBeInstanceOf(CertificateTemplateBuilder::class);
});

function createTempCaForTemplateTests(): CertificationAuthority
{
    $tempDir = sys_get_temp_dir().'/php-ca-test-'.uniqid();
    mkdir($tempDir, 0777, true);
    $filesystem = new Filesystem(new LocalFilesystemAdapter($tempDir));
    $adapter = new FlysystemAdapter($filesystem);
    ExtensionRegistry::registerDefaults();

    return new CertificationAuthority($adapter);
}
