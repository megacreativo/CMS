<?php

namespace Adapik\CMS;

use FG\ASN1;
use FG\ASN1\Universal\Sequence;

/**
 * Certificate
 */
class Certificate
{
    const OID_EXTENSION_SUBJECT_KEY_ID        = '2.5.29.14';
    const OID_EXTENSION_BASIC_CONSTRAINTS     = '2.5.29.19';
    const OID_EXTENSION_AUTHORITY_KEY_ID      = '2.5.29.35';
    const OID_EXTENSION_AUTHORITY_INFO_ACCESS = '1.3.6.1.5.5.7.1.1';
    const OID_OCSP_URI                        = '1.3.6.1.5.5.7.48.1';

    /**
     * @var Sequence
     */
    private $sequence;

    /**
     * Certificate constructor.
     *
     * @param Sequence $object
     */
    public function __construct(Sequence $object)
    {
        $this->sequence = $object;
    }
    /**
     * @return \FG\ASN1\Universal\Sequence
     */
    private function getTBSCertificate()
    {
        return $this->sequence->findChildrenByType(\FG\ASN1\Universal\Sequence::class)[0];
    }
    /**
     * @return string
     */
    public function getSerial(): string
    {
        return (string) $this->getTBSCertificate()->findChildrenByType(\FG\ASN1\Universal\Integer::class)[0];
    }

    /**
     * @return Sequence
     */
    private function getExtensions()
    {
        $exTaggedObjects = $this->getTBSCertificate()->findChildrenByType(\FG\ASN1\ExplicitlyTaggedObject::class);
        $extensions      = array_filter($exTaggedObjects, function (ASN1\ASN1Object $value) {
            return $value->getIdentifier()->getTagNumber() === 3;
        });

        return array_pop($extensions);
    }

    /**
     * @param string $oidString
     *
     * @return ASN1\ASN1Object|null
     */
    private function getExtension(string $oidString)
    {
        $oid = $this->getExtensions()->findByOid($oidString);

        if ($oid) {
            $value = $oid[0]->getParent()->findChildrenByType(\FG\ASN1\Universal\OctetString::class)[0]->getBinaryContent();
            return ASN1\ASN1Object::fromBinary($value);
        }

        return null;
    }

    /**
     * @return string
     */
    public function getSubjectKeyIdentifier(): string
    {
        $content = $this->getExtension(self::OID_EXTENSION_SUBJECT_KEY_ID);

        return bin2hex($content->getBinaryContent());
    }

    /**
     * @return string|null
     */
    public function getAuthorityKeyIdentifier()
    {
        $content = $this->getExtension(self::OID_EXTENSION_AUTHORITY_KEY_ID);

        if(null === $content) {
            return null;
        }

        $children = $content->findChildrenByType(\FG\ASN1\ImplicitlyTaggedObject::class);
        $children = array_filter($children, function (ASN1\ASN1Object $value) {
            return $value->getIdentifier()->getTagNumber() === 0;
        });

        return bin2hex($children[0]->getBinaryContent());
    }

    /**
     * @return string[]
     */
    public function getOcspUris(): array
    {
        $content = $this->getExtension(self::OID_EXTENSION_AUTHORITY_INFO_ACCESS);

        if (null === $content) {
           return [];
        }

        $oids = $content->findByOid(self::OID_OCSP_URI);

        $uris = [];

        if ($oids) {
            $uris = [];
            foreach ($oids as $oid) {
                $siblings = $oid->getSiblings();
                $uri      = array_filter($siblings, function (ASN1\ASN1Object $value) {
                    return $value->getIdentifier()->getTagNumber() === 6;
                });
                $uri    = array_pop($uri);
                $uris[] = $uri->getBinaryContent();
            }
        }

        return $uris;
    }

    /**
     * @return Name
     */
    public function getIssuer()
    {
        return new Name($this->getTBSCertificate()->getChildren()[3]);
    }


    /**
     * @return string
     */
    public function getValidNotBefore(): string
    {
        return (string) $this->getTBSCertificate()->getChildren()[4]->getChildren()[0];
    }

    /**
     * @return string
     */
    public function getValidNotAfter(): string
    {
        return (string) $this->getTBSCertificate()->getChildren()[4]->getChildren()[1];
    }

    /**
     * @return Name
     */
    public function getSubject()
    {
        return new Name($this->getTBSCertificate()->getChildren()[5]);
    }

    /**
     * @return bool
     */
    public function isCa(): bool
    {
        $basicConstraints = $this->getExtension(self::OID_EXTENSION_BASIC_CONSTRAINTS);

        $isCa = 'false';

        if ($basicConstraints) {
            $isCa = $basicConstraints->getChildren()[0] ?? 'false';
        }

        return ((string) $isCa) === 'true';
    }

    /**
     * @return string
     */
    public function getBinary(): string
    {
        return $this->sequence->getBinary();
    }

    /**
     * Конструктор из бинарных данных
     *
     * @param $content
     *
     * @return Certificate
     */
    public static function createFromContent($content)
    {
        /** @var \FG\ASN1\Universal\Sequence $sequence */
        $sequence = ASN1\ASN1Object::fromFile($content);
        return new self($sequence);
    }
}
