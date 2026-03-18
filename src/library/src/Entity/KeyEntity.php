<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Entity;

use KDuma\PhpCA\Record\Enum\KeyAttachment;
use KDuma\PhpCA\Record\KeyRecord;
use KDuma\PhpCA\Record\KeyType\BaseKeyType;
use KDuma\SimpleDAL\Contracts\Exception\AttachmentNotFoundException;
use KDuma\SimpleDAL\Typed\Contracts\TypedRecord;
use phpseclib3\Crypt\Common\AsymmetricKey;
use phpseclib3\Crypt\Common\PrivateKey;
use phpseclib3\Crypt\Common\PublicKey;
use phpseclib3\Crypt\PublicKeyLoader;

/**
 * @extends BaseEntity<KeyRecord>
 */
class KeyEntity extends BaseEntity
{
    public BaseKeyType $type {
        get {
            return $this->type;
        }
        set {
            if(isset($this->type)) {
                throw new \LogicException('Cannot set type on an existing entity.');
            }

            $this->type = $value;
        }
    }

    public string $fingerprint{
        get {
            return $this->fingerprint;
        }
        set {
            if(isset($this->fingerprint)) {
                throw new \LogicException('Cannot set fingerprint on an existing entity.');
            }

            $this->fingerprint = $value;

            if (!$this->persisted && !isset($this->id)) {
                $this->id = $value;
            }
        }
    }

    public bool $hasPrivateKey {
        get {
            return $this->hasPrivateKey;
        }
        set {
            if(isset($this->hasPrivateKey) && $this->hasPrivateKey === false && $value === true) {
                throw new \LogicException('Cannot set private key on an existing entity.');
            }

            $this->hasPrivateKey = $value;

            if($this->hasPrivateKey === false) {
                $this->_pendingChanges['privateKey'] = null;
            }
        }
    }

    public string $publicKey {
        get {
            if (isset($this->_pendingChanges['publicKey'])) {
                return $this->_pendingChanges['publicKey'];
            }
            return $this->attachments->get(KeyAttachment::PublicKey)->contents();
        }
        set {
            $this->_pendingChanges['publicKey'] = $value;
        }
    }

    public ?string $privateKey {
        get {
            if (array_key_exists('privateKey', $this->_pendingChanges)) {
                return $this->_pendingChanges['privateKey'];
            }
            try {
                return $this->attachments->get(KeyAttachment::PrivateKey)->contents();
            } catch (AttachmentNotFoundException) {
                return null;
            }
        }
        set {
            $this->_pendingChanges['privateKey'] = $value;
        }
    }

    public function getPublicKey(): PublicKey
    {
        return PublicKeyLoader::loadPublicKey($this->publicKey);
    }

    public function getPrivateKey(): ?PrivateKey
    {
        if ($this->privateKey === null) {
            return null;
        }
        return PublicKeyLoader::loadPrivateKey($this->privateKey);
    }

    public function getKey(): PrivateKey|PublicKey {
        return $this->getPrivateKey() ?? $this->getPublicKey();
    }



    private array $_pendingChanges = [];

    public function _afterPersisted(): void
    {
        parent::_afterPersisted();

        if (array_key_exists('publicKey', $this->_pendingChanges)) {
            $this->attachments->put(KeyAttachment::PublicKey, $this->_pendingChanges['publicKey']);
        }
        if (array_key_exists('privateKey', $this->_pendingChanges)) {
            if ($this->_pendingChanges['privateKey'] !== null) {
                $this->attachments->put(KeyAttachment::PrivateKey, $this->_pendingChanges['privateKey']);
            } else if ($this->attachments->has(KeyAttachment::PrivateKey)) {
                $this->attachments->delete(KeyAttachment::PrivateKey);
            }
        }

        $this->_pendingChanges = [];
    }

    /**
     * @param KeyEntity $entity
     * @param KeyRecord $record
     */
    protected static function _populateFromRecord(BaseEntity $entity, TypedRecord $record): void
    {
        assert($record instanceof KeyRecord);
        assert($entity instanceof KeyEntity);

        $entity->type = $record->type;
        $entity->fingerprint = $record->fingerprint;
        $entity->hasPrivateKey = $record->hasPrivateKey;
    }

    /**
     * @param KeyEntity $entity
     * @param KeyRecord $record
     */
    protected static function _populateToRecord(BaseEntity $entity, TypedRecord $record): void
    {
        assert($record instanceof KeyRecord);
        assert($entity instanceof KeyEntity);

        $record->type = $entity->type;
        $record->fingerprint = $entity->fingerprint;
        $record->hasPrivateKey = $entity->hasPrivateKey;
    }
}
