<?php

namespace App\Repository;

use App\Entity\OrderLines;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OrderLines>
 *
 * @method OrderLines|null find($id, $lockMode = null, $lockVersion = null)
 * @method OrderLines|null findOneBy(array $criteria, array $orderBy = null)
 * @method OrderLines[]    findAll()
 * @method OrderLines[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OrderLinesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrderLines::class);
    }

    public function save(OrderLines $orderLine, bool $flush): void
    {
        $this->getEntityManager()->persist($orderLine);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(OrderLines $orderLine, bool $flush): void
    {
        $this->getEntityManager()->remove($orderLine);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return OrderLines[] Returns an array of OrderLines objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('o')
//            ->andWhere('o.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('o.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?OrderLines
//    {
//        return $this->createQueryBuilder('o')
//            ->andWhere('o.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
