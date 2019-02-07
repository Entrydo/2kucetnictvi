<?php declare (strict_types=1);

namespace Entrydo\Infrastructure\Invoice\ExpresDoklad;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NoResultException;

class ExpresDokladTokenRepository
{
    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getValidToken(): ?ExpresDokladToken
    {
        try {
            return $this->entityManager->createQueryBuilder()
                ->select('t')
                ->from(ExpresDokladToken::class, 't')
                ->where('t.invalid = :invalid')
                ->orderBy('t.expiresAt', 'DESC')
                ->setMaxResults(1)
                ->setParameter('invalid', FALSE)
                ->getQuery()
                ->getSingleResult();
        }
        catch (NoResultException $e) {
            return null;
        }
    }

    public function save(ExpresDokladToken $token): void
    {
        $this->entityManager->persist($token);
    }
}
